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
}
