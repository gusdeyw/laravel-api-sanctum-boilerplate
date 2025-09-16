<?php

namespace App\Console\Commands;

use App\Services\WeatherService;
use Illuminate\Console\Command;

class TestWeatherAPI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:weather {location?} {--clear-cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Weather API integration';

    protected WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        parent::__construct();
        $this->weatherService = $weatherService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $location = $this->argument('location') ?? 'Perth, Australia';

        $this->info("🌤️  Testing Weather API for: {$location}");
        $this->line('');

        // Clear cache if requested
        if ($this->option('clear-cache')) {
            $this->info('🗑️  Clearing cache...');
            $this->weatherService->clearCache($location);
            $this->line('');
        }

        // Test the weather service
        $result = $this->weatherService->getCurrentWeather($location);

        if ($result['status'] === 'success') {
            $data = $result['data'];

            $this->info('✅ Weather data retrieved successfully!');
            $this->line('');

            $this->table([
                'Property',
                'Value'
            ], [
                ['Location', $data['location']],
                ['Region', $data['region']],
                ['Country', $data['country']],
                ['Temperature (°C)', $data['temperature_celsius']],
                ['Temperature (°F)', $data['temperature_fahrenheit']],
                ['Condition', $data['condition']],
                ['Humidity', $data['humidity'] . '%'],
                ['Wind Speed (kph)', $data['wind_speed_kph']],
                ['Wind Direction', $data['wind_direction']],
                ['Feels Like (°C)', $data['feels_like_celsius']],
                ['Pressure (mb)', $data['pressure_mb']],
                ['Visibility (km)', $data['visibility_km']],
                ['UV Index', $data['uv_index']],
                ['Last Updated', $data['last_updated']],
                ['Cached', $data['cached'] ? 'Yes' : 'No'],
                ['Cache Expires', $data['cache_expires_at'] ?? 'N/A'],
            ]);

            if ($data['cached']) {
                $this->warn('⚡ This data was served from cache');
            } else {
                $this->info('🌐 This data was fetched fresh from API');
            }
        } else {
            $this->error('❌ Failed to retrieve weather data');
            $this->error('Error: ' . $result['message']);
        }

        $this->line('');
        $this->info('💡 Usage examples:');
        $this->line('  php artisan test:weather');
        $this->line('  php artisan test:weather "London, UK"');
        $this->line('  php artisan test:weather "Tokyo, Japan" --clear-cache');

        return 0;
    }
}
