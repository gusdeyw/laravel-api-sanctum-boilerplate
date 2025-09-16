<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WeatherController extends Controller
{
    protected WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * Get current weather data for a location
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCurrentWeather(Request $request): JsonResponse
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'location' => 'nullable|string|max:255|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'message' => 'Invalid location parameter.',
            ], 422);
        }

        // Use provided location or default to Perth, Australia
        $location = $request->get('location', config('weather.default_location'));

        // Get weather data from service
        $result = $this->weatherService->getCurrentWeather($location);

        // Return appropriate HTTP status based on result
        $statusCode = $result['status'] === 'success' ? 200 : 500;

        // Add timestamp to response
        $result['timestamp'] = now()->toISOString();

        return response()->json($result, $statusCode);
    }

    /**
     * Clear weather cache for a location
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clearCache(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'location' => 'nullable|string|max:255|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'message' => 'Invalid location parameter.',
            ], 422);
        }

        $location = $request->get('location', config('weather.default_location'));

        $cleared = $this->weatherService->clearCache($location);

        return response()->json([
            'status' => 'success',
            'data' => [
                'cache_cleared' => $cleared,
                'location' => $location
            ],
            'message' => $cleared ? 'Cache cleared successfully' : 'Cache was already empty',
            'timestamp' => now()->toISOString()
        ]);
    }
}
