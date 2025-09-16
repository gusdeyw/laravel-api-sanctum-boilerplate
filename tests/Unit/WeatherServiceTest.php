<?php

namespace Tests\Unit;

use App\Services\WeatherService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WeatherServiceTest extends TestCase
{
    protected WeatherService $weatherService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->weatherService = new WeatherService();

        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_can_fetch_weather_data_successfully()
    {
        // Mock HTTP response
        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => [
                    'name' => 'Perth',
                    'region' => 'Western Australia',
                    'country' => 'Australia'
                ],
                'current' => [
                    'temp_c' => 22.5,
                    'temp_f' => 72.5,
                    'condition' => ['text' => 'Sunny', 'icon' => '//cdn.weatherapi.com/weather/64x64/day/113.png'],
                    'humidity' => 65,
                    'wind_kph' => 15.2,
                    'wind_mph' => 9.4,
                    'wind_dir' => 'SW',
                    'pressure_mb' => 1013.0,
                    'feelslike_c' => 24.0,
                    'feelslike_f' => 75.2,
                    'vis_km' => 10.0,
                    'uv' => 6.0,
                    'last_updated' => '2025-09-16 15:30'
                ]
            ], 200)
        ]);

        $result = $this->weatherService->getCurrentWeather('Perth, Australia');

        $this->assertEquals('success', $result['status']);
        $this->assertNull($result['message']);
        $this->assertIsArray($result['data']);

        $data = $result['data'];
        $this->assertEquals('Perth, Australia', $data['location']);
        $this->assertEquals(22.5, $data['temperature_celsius']);
        $this->assertEquals(72.5, $data['temperature_fahrenheit']);
        $this->assertEquals('Sunny', $data['condition']);
        $this->assertEquals(65, $data['humidity']);
        $this->assertFalse($data['cached']);
    }

    /** @test */
    public function it_handles_api_errors_gracefully()
    {
        // Mock HTTP error response
        Http::fake([
            'api.weatherapi.com/*' => Http::response([], 401)
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/Weather API Error/'), \Mockery::any());

        $result = $this->weatherService->getCurrentWeather('Perth, Australia');

        $this->assertEquals('error', $result['status']);
        $this->assertNull($result['data']);
        $this->assertEquals('Unable to fetch weather data. Please try again later.', $result['message']);
    }

    /** @test */
    public function it_handles_invalid_location_error()
    {
        // Mock HTTP 400 response for invalid location
        Http::fake([
            'api.weatherapi.com/*' => Http::response(['error' => ['message' => 'No matching location found.']], 400)
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/Weather API Error/'), \Mockery::any());

        $result = $this->weatherService->getCurrentWeather('InvalidLocation123');

        $this->assertEquals('error', $result['status']);
        $this->assertNull($result['data']);
        $this->assertEquals('Unable to fetch weather data. Please try again later.', $result['message']);
    }

    /** @test */
    public function it_caches_weather_data_correctly()
    {
        // Mock HTTP response
        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => [
                    'name' => 'London',
                    'region' => 'City of London',
                    'country' => 'United Kingdom'
                ],
                'current' => [
                    'temp_c' => 15.0,
                    'temp_f' => 59.0,
                    'condition' => ['text' => 'Cloudy', 'icon' => '//cdn.weatherapi.com/weather/64x64/day/119.png'],
                    'humidity' => 80,
                    'wind_kph' => 10.0,
                    'wind_mph' => 6.2,
                    'wind_dir' => 'N',
                    'pressure_mb' => 1015.0,
                    'feelslike_c' => 13.0,
                    'feelslike_f' => 55.4,
                    'vis_km' => 8.0,
                    'uv' => 2.0,
                    'last_updated' => '2025-09-16 10:30'
                ]
            ], 200)
        ]);

        // First call - should fetch from API
        $result1 = $this->weatherService->getCurrentWeather('London, UK');
        $this->assertFalse($result1['data']['cached']);

        // Second call - should fetch from cache
        $result2 = $this->weatherService->getCurrentWeather('London, UK');
        $this->assertTrue($result2['data']['cached']);
        $this->assertNotNull($result2['data']['cache_expires_at']);

        // Verify only one HTTP call was made
        Http::assertSentCount(1);
    }

    /** @test */
    public function it_can_clear_cache_for_location()
    {
        $location = 'Sydney, Australia';

        // Add something to cache first
        Cache::put('weather_' . md5(strtolower(trim($location))), ['test' => 'data'], 900);

        $this->assertTrue(Cache::has('weather_' . md5(strtolower(trim($location)))));

        $result = $this->weatherService->clearCache($location);

        $this->assertTrue($result);
        $this->assertFalse(Cache::has('weather_' . md5(strtolower(trim($location)))));
    }

    /** @test */
    public function it_handles_missing_api_key()
    {
        // Temporarily set API key to null
        config(['weather.api_key' => null]);

        Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/Weather API Error/'), \Mockery::any());

        $result = $this->weatherService->getCurrentWeather('Perth, Australia');

        $this->assertEquals('error', $result['status']);
        $this->assertNull($result['data']);
        $this->assertEquals('Unable to fetch weather data. Please try again later.', $result['message']);
    }

    /** @test */
    public function it_handles_malformed_api_response()
    {
        // Mock HTTP response with missing required fields
        Http::fake([
            'api.weatherapi.com/*' => Http::response(['invalid' => 'response'], 200)
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/Weather API Error/'), \Mockery::any());

        $result = $this->weatherService->getCurrentWeather('Perth, Australia');

        $this->assertEquals('error', $result['status']);
        $this->assertNull($result['data']);
        $this->assertEquals('Unable to fetch weather data. Please try again later.', $result['message']);
    }

    /** @test */
    public function it_generates_correct_cache_keys()
    {
        // Mock HTTP response
        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => ['name' => 'Test', 'region' => 'Test', 'country' => 'Test'],
                'current' => [
                    'temp_c' => 20,
                    'temp_f' => 68,
                    'condition' => ['text' => 'Clear', 'icon' => ''],
                    'humidity' => 50,
                    'wind_kph' => 5,
                    'wind_mph' => 3,
                    'wind_dir' => 'N',
                    'pressure_mb' => 1010,
                    'feelslike_c' => 20,
                    'feelslike_f' => 68,
                    'vis_km' => 10,
                    'uv' => 3,
                    'last_updated' => '2025-09-16 12:00'
                ]
            ], 200)
        ]);

        // Test different location formats should generate same cache key
        $this->weatherService->getCurrentWeather('Perth, Australia');
        $this->weatherService->getCurrentWeather('  PERTH, AUSTRALIA  ');
        $this->weatherService->getCurrentWeather('perth, australia');

        // Should only make one HTTP call due to cache
        Http::assertSentCount(1);
    }
}
