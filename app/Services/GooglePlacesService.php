<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesService
{
    protected $apiKey;
    protected $baseUrl = 'https://maps.googleapis.com/maps/api/place/';

    public function __construct()
    {
        $this->apiKey = env('GOOGLE_PLACES_API_KEY');
    }

    /**
     * Search for places (restaurants) using Text Search or Nearby Search.
     *
     * @param string|null $query
     * @param float|null $latitude
     * @param float|null $longitude
     * @return array
     */
    public function searchPlaces(?string $query = null, ?float $latitude = null, ?float $longitude = null): array
    {
        $params = [
            'key' => $this->apiKey,
            'type' => 'restaurant', // Focus on restaurants
        ];

        if ($query && $latitude && $longitude) {
            // Text Search with location bias
            $params['query'] = $query;
            $params['location'] = "$latitude,$longitude";
            $params['radius'] = 10000; // Search radius in meters (10km)
            $endpoint = 'textsearch/json';
        } elseif ($query) {
            // Text Search without location bias
            $params['query'] = $query;
            $endpoint = 'textsearch/json';
        } elseif ($latitude && $longitude) {
            // Nearby Search
            $params['location'] = "$latitude,$longitude";
            $params['radius'] = 10000; // Search radius in meters (10km)
            $endpoint = 'nearbysearch/json';
        } else {
            // No valid search criteria
            return [];
        }

        try {
            $response = Http::get($this->baseUrl . $endpoint, $params);
            $response->throw(); // Throws an exception if a client or server error occurred

            $data = $response->json();
            return $data['results'] ?? [];
        } catch (\Throwable $e) {
            Log::error('Google Places API Search Error: ' . $e->getMessage(), ['params' => $params]);
            return [];
        }
    }

    /**
     * Get details for a specific place (restaurant) by place_id.
     *
     * @param string $placeId
     * @return array
     */
    public function getPlaceDetails(string $placeId): array
    {
        $params = [
            'place_id' => $placeId,
            'key' => $this->apiKey,
            'fields' => 'name,formatted_address,formatted_phone_number,website,rating,user_ratings_total,opening_hours,reviews,url', // Request necessary fields
        ];

        try {
            $response = Http::get($this->baseUrl . 'details/json', $params);
            $response->throw();

            $data = $response->json();
            return $data['result'] ?? [];
        } catch (\Throwable $e) {
            Log::error('Google Places API Get Details Error: ' . $e->getMessage(), ['place_id' => $placeId]);
            return [];
        }
    }

    // You can add methods for photos, autocomplete, etc., if needed
}