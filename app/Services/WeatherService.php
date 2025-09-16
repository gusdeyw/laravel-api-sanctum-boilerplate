<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    /**
     * Get current weather data for a location
     *
     * @param string $location
     * @return array
     */
    public function getCurrentWeather(string $location): array
    {
        try {
            // Create cache key based on location
            $cacheKey = 'weather_' . md5(strtolower(trim($location)));

            // Check if data exists in cache
            $wasCached = Cache::has($cacheKey);

            // Try to get from cache first
            $weatherData = Cache::remember($cacheKey, (int) config('weather.cache_ttl'), function () use ($location) {
                return $this->fetchWeatherFromAPI($location);
            });

            // Mark if data was served from cache
            $weatherData['cached'] = $wasCached;
            if ($wasCached) {
                $weatherData['cache_expires_at'] = now()->addSeconds((int) config('weather.cache_ttl'))->toISOString();
            } else {
                $weatherData['cache_expires_at'] = null;
            }

            return [
                'status' => 'success',
                'data' => $weatherData,
                'message' => null
            ];
        } catch (\Exception $e) {
            Log::error('Weather API Error: ' . $e->getMessage(), [
                'location' => $location,
                'exception' => $e
            ]);

            return [
                'status' => 'error',
                'data' => null,
                'message' => 'Unable to fetch weather data. Please try again later.'
            ];
        }
    }

    /**
     * Fetch weather data from WeatherAPI.com
     *
     * @param string $location
     * @return array
     * @throws \Exception
     */
    private function fetchWeatherFromAPI(string $location): array
    {
        $apiKey = config('weather.api_key');
        $apiUrl = config('weather.api_url');

        if (empty($apiKey)) {
            throw new \Exception('Weather API key not configured');
        }

        $response = Http::timeout(config('weather.timeout'))
            ->get($apiUrl, [
                'key' => $apiKey,
                'q' => $location,
                'aqi' => 'no' // We don't need air quality data
            ]);

        if (!$response->successful()) {
            if ($response->status() === 400) {
                throw new \Exception('Invalid location provided');
            } elseif ($response->status() === 401) {
                throw new \Exception('Invalid API key');
            } elseif ($response->status() === 403) {
                throw new \Exception('API quota exceeded');
            } else {
                throw new \Exception('Weather service temporarily unavailable');
            }
        }

        $data = $response->json();

        if (!isset($data['current']) || !isset($data['location'])) {
            throw new \Exception('Invalid response from weather service');
        }

        return [
            'location' => $data['location']['name'] . ', ' . $data['location']['country'],
            'region' => $data['location']['region'],
            'country' => $data['location']['country'],
            'temperature_celsius' => $data['current']['temp_c'],
            'temperature_fahrenheit' => $data['current']['temp_f'],
            'condition' => $data['current']['condition']['text'],
            'condition_icon' => $data['current']['condition']['icon'],
            'humidity' => $data['current']['humidity'],
            'wind_speed_kph' => $data['current']['wind_kph'],
            'wind_speed_mph' => $data['current']['wind_mph'],
            'wind_direction' => $data['current']['wind_dir'],
            'pressure_mb' => $data['current']['pressure_mb'],
            'feels_like_celsius' => $data['current']['feelslike_c'],
            'feels_like_fahrenheit' => $data['current']['feelslike_f'],
            'visibility_km' => $data['current']['vis_km'],
            'uv_index' => $data['current']['uv'],
            'last_updated' => $data['current']['last_updated'],
            'cached' => false,
            'cache_expires_at' => null
        ];
    }

    /**
     * Clear weather cache for a specific location
     *
     * @param string $location
     * @return bool
     */
    public function clearCache(string $location): bool
    {
        $cacheKey = 'weather_' . md5(strtolower(trim($location)));
        return Cache::forget($cacheKey);
    }

    /**
     * Clear all weather cache
     *
     * @return bool
     */
    public function clearAllCache(): bool
    {
        // This is a simple implementation - in production you might want
        // to use cache tags for more efficient clearing
        return Cache::flush();
    }
}
