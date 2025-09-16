<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WeatherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WeatherApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Cache::flush();
    }

    /** @test */
    public function it_requires_authentication_to_access_weather_endpoint()
    {
        $response = $this->getJson('/api/weather');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /** @test */
    public function authenticated_user_can_get_weather_data()
    {
        Sanctum::actingAs($this->user);

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

        $response = $this->getJson('/api/weather');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'location',
                'region',
                'country',
                'temperature_celsius',
                'temperature_fahrenheit',
                'condition',
                'humidity',
                'wind_speed_kph',
                'cached',
                'cache_expires_at'
            ],
            'message',
            'timestamp'
        ]);

        $response->assertJson([
            'status' => 'success',
            'message' => null
        ]);

        $data = $response->json('data');
        $this->assertEquals('Perth, Australia', $data['location']);
        $this->assertEquals(22.5, $data['temperature_celsius']);
        $this->assertEquals('Sunny', $data['condition']);
    }

    /** @test */
    public function it_can_get_weather_for_custom_location()
    {
        Sanctum::actingAs($this->user);

        // Mock HTTP response for London
        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => [
                    'name' => 'London',
                    'region' => 'City of London, Greater London',
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

        $response = $this->getJson('/api/weather?location=London,UK');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success'
        ]);

        $data = $response->json('data');
        $this->assertEquals('London, United Kingdom', $data['location']);
        $this->assertEquals(15.0, $data['temperature_celsius']);
        $this->assertEquals('Cloudy', $data['condition']);
    }

    /** @test */
    public function it_validates_location_parameter()
    {
        Sanctum::actingAs($this->user);

        // Test with too short location
        $response = $this->getJson('/api/weather?location=A');

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'data' => null
        ]);
        $response->assertJsonFragment(['message' => 'Invalid location parameter.']);

        // Test with too long location
        $longLocation = str_repeat('A', 300);
        $response = $this->getJson('/api/weather?location=' . $longLocation);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'data' => null
        ]);
    }

    /** @test */
    public function it_handles_weather_api_errors()
    {
        Sanctum::actingAs($this->user);

        // Mock HTTP error response
        Http::fake([
            'api.weatherapi.com/*' => Http::response([], 401)
        ]);

        $response = $this->getJson('/api/weather');

        $response->assertStatus(500);
        $response->assertJson([
            'status' => 'error',
            'data' => null,
            'message' => 'Unable to fetch weather data. Please try again later.'
        ]);
    }

    /** @test */
    public function it_serves_cached_data_on_subsequent_requests()
    {
        Sanctum::actingAs($this->user);

        // Mock HTTP response
        Http::fake([
            'api.weatherapi.com/*' => Http::response([
                'location' => [
                    'name' => 'Sydney',
                    'region' => 'New South Wales',
                    'country' => 'Australia'
                ],
                'current' => [
                    'temp_c' => 25.0,
                    'temp_f' => 77.0,
                    'condition' => ['text' => 'Clear', 'icon' => '//cdn.weatherapi.com/weather/64x64/day/113.png'],
                    'humidity' => 55,
                    'wind_kph' => 12.0,
                    'wind_mph' => 7.5,
                    'wind_dir' => 'E',
                    'pressure_mb' => 1018.0,
                    'feelslike_c' => 26.0,
                    'feelslike_f' => 78.8,
                    'vis_km' => 15.0,
                    'uv' => 7.0,
                    'last_updated' => '2025-09-16 14:30'
                ]
            ], 200)
        ]);

        // First request - should fetch from API
        $response1 = $this->getJson('/api/weather?location=Sydney,Australia');
        $response1->assertStatus(200);
        $data1 = $response1->json('data');
        $this->assertFalse($data1['cached']);

        // Second request - should fetch from cache
        $response2 = $this->getJson('/api/weather?location=Sydney,Australia');
        $response2->assertStatus(200);
        $data2 = $response2->json('data');
        $this->assertTrue($data2['cached']);
        $this->assertNotNull($data2['cache_expires_at']);

        // Verify only one HTTP call was made
        Http::assertSentCount(1);
    }

    /** @test */
    public function it_can_clear_cache_for_location()
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson('/api/weather/cache?location=Perth,Australia');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'cache_cleared',
                'location'
            ],
            'message',
            'timestamp'
        ]);

        $response->assertJson([
            'status' => 'success',
            'data' => [
                'location' => 'Perth,Australia'
            ]
        ]);
    }

    /** @test */
    public function cache_clear_requires_authentication()
    {
        $response = $this->deleteJson('/api/weather/cache');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /** @test */
    public function cache_clear_validates_location_parameter()
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson('/api/weather/cache?location=A');

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'data' => null
        ]);
        $response->assertJsonFragment(['message' => 'Invalid location parameter.']);
    }

    /** @test */
    public function it_returns_proper_timestamps_in_responses()
    {
        Sanctum::actingAs($this->user);

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

        $response = $this->getJson('/api/weather');

        $response->assertStatus(200);

        $timestamp = $response->json('timestamp');
        $this->assertNotNull($timestamp);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $timestamp);
    }

    /** @test */
    public function it_handles_network_timeouts()
    {
        Sanctum::actingAs($this->user);

        // Mock timeout
        Http::fake([
            'api.weatherapi.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            }
        ]);

        $response = $this->getJson('/api/weather');

        $response->assertStatus(500);
        $response->assertJson([
            'status' => 'error',
            'data' => null,
            'message' => 'Unable to fetch weather data. Please try again later.'
        ]);
    }
}
