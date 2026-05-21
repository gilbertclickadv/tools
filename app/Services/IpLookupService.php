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

        return Cache::remember('freecurrency_rates', now()->addMinutes(3), function () use ($apiKey) {
            try {
                $response = Http::timeout(3)->get("https://api.freecurrencyapi.com/v1/latest", [
                    'apikey' => $apiKey,
                ]);

                if ($response->successful()) {
                    return $response->json()['data'] ?? [];
                }
            } catch (\Exception $e) {
                Log::error("FreecurrencyAPI request failed: " . $e->getMessage());
            }
            return [];
        });
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
            ],
        ];
    }
}
