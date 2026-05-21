<?php

namespace Tests\Unit;

use App\Services\IpLookupService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IpLookupServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::clear();
    }

    /**
     * Test that the API key from config is used, and rates are fetched and cached.
     */
    public function test_get_exchange_rates_fetches_and_caches_rates_for_three_minutes(): void
    {
        // 1. Arrange: Mock the Http request to FreecurrencyAPI
        Http::fake([
            'https://api.freecurrencyapi.com/v1/latest*' => Http::response([
                'data' => [
                    'EUR' => 0.92,
                    'GBP' => 0.78,
                    'JPY' => 155.45,
                ]
            ], 200)
        ]);

        // 2. Act: Instantiate the service and call the method
        $service = new IpLookupService();
        $rates = $service->getExchangeRates();

        // 3. Assert: Verify returned data is correct
        $this->assertArrayHasKey('EUR', $rates);
        $this->assertEquals(0.92, $rates['EUR']);
        $this->assertEquals(0.78, $rates['GBP']);

        // 4. Assert: Check that rates were cached in the Laravel Cache
        $this->assertTrue(Cache::has('freecurrency_rates'));
        $cachedRates = Cache::get('freecurrency_rates');
        $this->assertEquals(0.92, $cachedRates['EUR']);

        // 5. Assert: Verify the cache works by clearing Http Fakes and running again
        Http::fake([
            'https://api.freecurrencyapi.com/v1/latest*' => Http::response(null, 500)
        ]);

        $ratesFromCache = $service->getExchangeRates();
        $this->assertEquals(0.92, $ratesFromCache['EUR']);
    }

    /**
     * Test that configuration key is used dynamically.
     */
    public function test_get_exchange_rates_uses_configured_api_key(): void
    {
        $testApiKey = 'test_api_key_12345';
        config(['services.freecurrency.key' => $testApiKey]);

        Http::fake([
            'https://api.freecurrencyapi.com/v1/latest*' => Http::response(['data' => ['EUR' => 0.92]], 200)
        ]);

        $service = new IpLookupService();
        $rates = $service->getExchangeRates();

        $this->assertEquals(0.92, $rates['EUR']);

        Http::assertSent(function ($request) use ($testApiKey) {
            $parsedUrl = parse_url($request->url());
            parse_str($parsedUrl['query'] ?? '', $queryParams);
            return str_contains($request->url(), 'https://api.freecurrencyapi.com/v1/latest') &&
                   ($queryParams['apikey'] ?? '') === $testApiKey;
        });
    }

    /**
     * Test that we fall back to the long-term fallback cache if active cache is expired/cleared and API fails.
     */
    public function test_get_exchange_rates_falls_back_to_long_term_cache_on_api_failure(): void
    {
        // 1. Arrange: Ensure both active cache is empty but fallback cache is populated
        Cache::forget('freecurrency_rates');
        Cache::put('freecurrency_rates_fallback', ['EUR' => 0.95], now()->addDays(7));

        Http::fake([
            'https://api.freecurrencyapi.com/v1/latest*' => Http::response(null, 500)
        ]);

        // 2. Act: Call the service method
        $service = new IpLookupService();
        $rates = $service->getExchangeRates();

        // 3. Assert: Check it falls back to the 7-day fallback cache
        $this->assertEquals(0.95, $rates['EUR']);
    }

    /**
     * Test that we fall back to the ultimate hardcoded seed rates if all caches are empty and the API fails.
     */
    public function test_get_exchange_rates_falls_back_to_seed_rates_on_total_failure(): void
    {
        // 1. Arrange: Clear all caches
        Cache::forget('freecurrency_rates');
        Cache::forget('freecurrency_rates_fallback');

        Http::fake([
            'https://api.freecurrencyapi.com/v1/latest*' => Http::response(null, 500)
        ]);

        // 2. Act: Call the service method
        $service = new IpLookupService();
        $rates = $service->getExchangeRates();

        // 3. Assert: Verify the hardcoded EUR seed rate (0.92) is returned
        $this->assertArrayHasKey('EUR', $rates);
        $this->assertEquals(0.92, $rates['EUR']);
        $this->assertEquals(83.3, $rates['INR']);
    }
}
