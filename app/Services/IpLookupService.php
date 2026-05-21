<?php

namespace App\Services;

use GeoIp2\Database\Reader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpLookupService
{
    /**
     * Perform IP lookup (Local MaxMind -> External Fallback -> Mock Loopback).
     */
    public function lookup(string $ip): array
    {
        $ip = trim($ip);

        // Sanitize and handle local / empty IPs
        if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return $this->getMockLoopbackData($ip ?: '127.0.0.1');
        }

        $cacheKey = 'ip_lookup:' . $ip;

        return Cache::remember($cacheKey, now()->addHour(), function () use ($ip) {
            $data = null;
            $isFallback = false;

            $cityDb = storage_path('app/maxmind/GeoLite2-City.mmdb');
            $asnDb = storage_path('app/maxmind/GeoLite2-ASN.mmdb');

            // 1. Try Local MaxMind Databases
            if (file_exists($cityDb) && file_exists($asnDb)) {
                try {
                    $cityReader = new Reader($cityDb);
                    $asnReader = new Reader($asnDb);

                    $cityRecord = $cityReader->city($ip);
                    $asnRecord = $asnReader->asn($ip);

                    $data = [
                        'ip' => $ip,
                        'ip_version' => str_contains($ip, ':') ? 6 : 4,
                        'country_code' => $cityRecord->country->isoCode ?? null,
                        'country_name' => $cityRecord->country->name ?? null,
                        'city_name' => $cityRecord->city->name ?? null,
                        'region_name' => $cityRecord->mostSpecificSubdivision->name ?? null,
                        'zip_code' => $cityRecord->postal->code ?? null,
                        'latitude' => $cityRecord->location->latitude ?? null,
                        'longitude' => $cityRecord->location->longitude ?? null,
                        'timezone' => $cityRecord->location->timeZone ?? null,
                        'asn' => $asnRecord->autonomousSystemNumber ?? null,
                        'isp' => $asnRecord->autonomousSystemOrganization ?? null,
                        'is_fallback' => false,
                    ];
                } catch (\Exception $e) {
                    Log::info("MaxMind local lookup failed for IP {$ip}: " . $e->getMessage());
                }
            }

            // 2. Try External API Fallback if MaxMind fails or is not available
            if (!$data) {
                try {
                    $response = Http::timeout(3)
                        ->withHeaders(['User-Agent' => 'FluxMedia/1.0'])
                        ->get("https://freeipapi.com/api/json/{$ip}");

                    if ($response->successful()) {
                        $resData = $response->json();
                        $data = [
                            'ip' => $ip,
                            'ip_version' => $resData['ipVersion'] ?? (str_contains($ip, ':') ? 6 : 4),
                            'country_code' => $resData['countryCode'] ?? null,
                            'country_name' => $resData['countryName'] ?? null,
                            'city_name' => $resData['cityName'] ?? null,
                            'region_name' => $resData['regionName'] ?? null,
                            'zip_code' => $resData['zipCode'] ?? null,
                            'latitude' => $resData['latitude'] ?? null,
                            'longitude' => $resData['longitude'] ?? null,
                            'timezone' => !empty($resData['timeZones']) ? $resData['timeZones'][0] : null,
                            'asn' => $resData['asn'] ?? null,
                            'isp' => $resData['asnOrganization'] ?? null,
                            'is_fallback' => true,
                        ];
                        $isFallback = true;
                    }
                } catch (\Exception $e) {
                    Log::error("External IP Geolocation API fallback failed for IP {$ip}: " . $e->getMessage());
                }
            }

            // 3. Fallback to Mock Loopback Data if both local and external API failed
            if (!$data) {
                return $this->getMockLoopbackData($ip);
            }

            // 4. Inject Currency Metadata & Exchange Rates
            $countryCode = $data['country_code'] ?? 'US';
            $currencyMeta = CurrencyMapper::map($countryCode);

            $exchangeRates = $this->getExchangeRates();
            $currencyCode = $currencyMeta['code'];

            $rate = 1.0;
            $inverseRate = 1.0;

            if ($currencyCode !== 'USD' && !empty($exchangeRates)) {
                $targetRate = $exchangeRates[$currencyCode] ?? null;
                if ($targetRate) {
                    $rate = (float) $targetRate;
                    $inverseRate = $rate > 0 ? (float) (1 / $rate) : 1.0;
                }
            }

            $data['currency'] = [
                'code' => $currencyCode,
                'name' => $currencyMeta['name'],
                'symbol' => $currencyMeta['symbol'],
                'rate' => round($rate, 4),
                'inverse_rate' => round($inverseRate, 4),
                'has_rates' => !empty($exchangeRates),
                'all_currencies' => CurrencyMapper::allCurrencies(),
                'all_rates' => $exchangeRates ?: [],
            ];

            return $data;
        });
    }

    /**
     * Fetch Exchange Rates relative to USD using Freecurrencyapi.com
     */
    public function getExchangeRates(): array
    {
        $apiKey = config('services.freecurrency.key') ?: env('FREECURRENCY_API_KEY', 'fca_live_kNQ5JGI2Q4DbhAajUd9s9Hsti4u2qnebc8WubUhu');
        if (!$apiKey) {
            return [];
        }

        // 1. Try to get from active 3-minute cache
        $cached = Cache::get('freecurrency_rates');
        if (!empty($cached)) {
            return $cached;
        }

        // 2. Fetch fresh rates from API
        try {
            $response = Http::timeout(3)->get("https://api.freecurrencyapi.com/v1/latest", [
                'apikey' => $apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                if (!empty($data)) {
                    // Only cache successful, non-empty responses
                    Cache::put('freecurrency_rates', $data, now()->addMinutes(3));
                    Cache::put('freecurrency_rates_fallback', $data, now()->addDays(7)); // Long-term fallback cache
                    return $data;
                }
            }
        } catch (\Exception $e) {
            Log::error("FreecurrencyAPI request failed: " . $e->getMessage());
        }

        // 3. Fallback to long-term cached rates if API failed
        $fallback = Cache::get('freecurrency_rates_fallback');
        if (!empty($fallback)) {
            Log::info("Using long-term fallback currency rates due to API failure.");
            return $fallback;
        }

        // 4. Ultimate hardcoded seed rates when cache, long-term cache, AND live API query fail
        Log::warning("Using hardcoded seed currency rates as ultimate fallback.");
        return [
            'EUR' => 0.92,
            'GBP' => 0.78,
            'INR' => 83.3,
            'CAD' => 1.36,
            'AUD' => 1.50,
            'JPY' => 155.0,
            'CNY' => 7.24,
            'CHF' => 0.90,
            'NZD' => 1.63,
            'SEK' => 10.6,
            'NOK' => 10.6,
            'DKK' => 6.85,
            'RUB' => 90.0,
            'BRL' => 5.15,
            'ZAR' => 18.5,
            'KRW' => 1360.0,
            'SGD' => 1.35,
            'HKD' => 7.80,
            'MXN' => 16.7,
            'TRY' => 32.2,
            'IDR' => 16000.0,
            'MYR' => 4.70,
            'PHP' => 58.0,
            'THB' => 36.5,
            'VND' => 25400.0,
            'PKR' => 278.0,
            'AED' => 3.67,
            'SAR' => 3.75,
            'ILS' => 3.70,
            'PLN' => 3.95,
            'UAH' => 40.0,
            'RON' => 4.60,
            'HUF' => 360.0,
            'CZK' => 22.8,
            'COP' => 3850.0,
            'CLP' => 910.0,
            'PEN' => 3.72,
            'ARS' => 890.0,
            'EGP' => 47.0,
            'NGN' => 1500.0,
            'KES' => 130.0,
            'MAD' => 10.0,
            'TWD' => 32.2,
            'BDT' => 117.0,
            'USD' => 1.0,
        ];
    }

    /**
     * Generate mock data for private networks/loopbacks.
     */
    private function getMockLoopbackData(string $ip): array
    {
        $currencyMeta = CurrencyMapper::map('US');
        return [
            'ip' => $ip,
            'ip_version' => str_contains($ip, ':') ? 6 : 4,
            'country_code' => 'LOCAL',
            'country_name' => 'Internal / Private Network',
            'city_name' => 'Localhost',
            'region_name' => 'Intranet',
            'zip_code' => '00000',
            'latitude' => 0.0,
            'longitude' => 0.0,
            'timezone' => 'UTC',
            'asn' => '0',
            'isp' => 'Loopback / Private LAN Route',
            'is_fallback' => true,
            'currency' => [
                'code' => $currencyMeta['code'],
                'name' => $currencyMeta['name'],
                'symbol' => $currencyMeta['symbol'],
                'rate' => 1.0,
                'inverse_rate' => 1.0,
                'has_rates' => false,
                'all_currencies' => CurrencyMapper::allCurrencies(),
                'all_rates' => [],
            ],
        ];
    }
}
