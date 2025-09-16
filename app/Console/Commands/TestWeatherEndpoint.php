<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

class TestWeatherEndpoint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:weather-endpoint {location?} {--user=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Weather API endpoint via HTTP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $location = $this->argument('location');
        $userId = $this->option('user');

        $this->info('🧪 Testing Weather API Endpoint');
        $this->line('');

        // Get or create a test user
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found");
                return 1;
            }
        } else {
            $user = User::first();
            if (!$user) {
                $this->error('No users found. Please create a user first or register via API.');
                return 1;
            }
        }

        // Create access token
        $token = $user->createToken('weather-test')->plainTextToken;

        $this->info("👤 Using user: {$user->name} (ID: {$user->id})");
        $this->line('');

        // Prepare request
        $url = 'http://127.0.0.1:8000/api/weather';
        $params = [];

        if ($location) {
            $params['location'] = $location;
            $this->info("📍 Testing location: {$location}");
        } else {
            $this->info("📍 Testing default location (Perth, Australia)");
        }

        // Make authenticated request
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get($url, $params);

            $this->line('');
            $this->info("📡 Request: GET {$url}" . ($params ? '?' . http_build_query($params) : ''));
            $this->info("🔑 Authorization: Bearer [token]");
            $this->line('');

            if ($response->successful()) {
                $data = $response->json();

                $this->info('✅ Response Status: ' . $response->status());
                $this->line('');

                if ($data['status'] === 'success' && $data['data']) {
                    $weatherData = $data['data'];

                    $this->table(['Property', 'Value'], [
                        ['Status', $data['status']],
                        ['Location', $weatherData['location']],
                        ['Temperature', $weatherData['temperature_celsius'] . '°C (' . $weatherData['temperature_fahrenheit'] . '°F)'],
                        ['Condition', $weatherData['condition']],
                        ['Humidity', $weatherData['humidity'] . '%'],
                        ['Wind Speed', $weatherData['wind_speed_kph'] . ' kph'],
                        ['Cached', $weatherData['cached'] ? 'Yes' : 'No'],
                        ['Timestamp', $data['timestamp']],
                    ]);

                    if ($weatherData['cached']) {
                        $this->warn('⚡ Data served from cache');
                        $this->line('Cache expires: ' . $weatherData['cache_expires_at']);
                    }
                } else {
                    $this->error('❌ Error in response:');
                    $this->line('Status: ' . $data['status']);
                    $this->line('Message: ' . $data['message']);
                }
            } else {
                $this->error('❌ Request failed with status: ' . $response->status());
                $this->line('Response: ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('❌ Request failed: ' . $e->getMessage());
        }

        $this->line('');
        $this->info('💡 Usage examples:');
        $this->line('  php artisan test:weather-endpoint');
        $this->line('  php artisan test:weather-endpoint "London, UK"');
        $this->line('  php artisan test:weather-endpoint "Tokyo" --user=1');

        return 0;
    }
}
