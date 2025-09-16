<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Weather API Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains settings for the WeatherAPI.com
    | integration used to fetch current weather data for locations.
    |
    */

    'api_key' => env('WEATHER_API_KEY'),

    'api_url' => env('WEATHER_API_URL', 'http://api.weatherapi.com/v1/current.json'),

    'cache_ttl' => env('WEATHER_CACHE_TTL', 900), // 15 minutes in seconds

    'timeout' => env('WEATHER_API_TIMEOUT', 10), // API request timeout in seconds

    'default_location' => env('WEATHER_DEFAULT_LOCATION', 'Perth, Australia'),

];
