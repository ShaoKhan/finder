<?php
declare(strict_types = 1);

namespace App\Service;

use proj4php\Point;
use proj4php\Proj;
use proj4php\Proj4php;

class GeoService
{

    public function __construct()
    {

    }

    /**
     * alternative calculate UTM33 from Proj4php
     *
     * @param float $latitude
     * @param float $longitude
     *
     * @return array
     */
    public function convertToUTM33(float $latitude, float $longitude): array
    {
        $proj4 = new Proj4php();
        $wgs84 = new Proj('EPSG:4326', $proj4);  // WGS84
        $utm33 = new Proj('EPSG:32633', $proj4); // UTM33

        $point    = new Point($longitude, $latitude, $wgs84);
        $utmPoint = $proj4->transform($utm33, $point);

        return ['utmX' => $utmPoint->x, 'utmY' => $utmPoint->y];
    }

    /**
     * get gemarkungen and flurstücke from nominating API
     *
     * @param float $latitude
     * @param float $longitude
     *
     * @return array|null
     * @throws \Exception
     */
    public function getLocationData(float $latitude, float $longitude): ?array
    {
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$latitude&lon=$longitude";

        $opts = [
            "http" => [
                "header" => "User-Agent: MySymfonyApp/1.0 (myemail@example.com)",
            ],
        ];

        $context  = stream_context_create($opts);
        $response = file_get_contents($url, FALSE, $context);

        if($response === FALSE) {
            throw new \Exception("Failed to fetch data from Nominatim API.");
        }

        return json_decode($response, TRUE);
    }

    /**
     * find nearest church with overpass api
     *
     * @param float $latitude
     * @param float $longitude
     *
     * @return array
     */
    public function findNearestChurch(float $latitude, float $longitude): array
    {
        $query = <<<EOT
[out:json];
(
  node(around:10000,$latitude,$longitude)["amenity"="place_of_worship"];
  node(around:10000,$latitude,$longitude)["amenity"="town_hall"];
);
out center 1;
EOT;

        $url = "https://overpass-api.de/api/interpreter?data=" . urlencode($query);

        try {
            $response = @file_get_contents($url);

            if ($response === false) {
                throw new \Exception("Failed to fetch data from Overpass API.");
            }

            $data = json_decode($response, true);

            // Ensure elements exist and return, otherwise return empty array
            return $data['elements'] ?? [];
        } catch (\Exception $e) {
            // Log the error for debugging
            // error_log($e->getMessage());
            return []; // Return an empty array if the request fails
        }
    }

    public function getNearestTown(float $latitude, float $longitude): ?string
    {
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$latitude&lon=$longitude";

        // Set the User-Agent header
        $options = [
            "http" => [
                "header" => "User-Agent: MySymfonyApp/1.0 (myemail@example.com)\r\n"
            ]
        ];

        // Create a stream context
        $context = stream_context_create($options);

        try {
            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                throw new \Exception("Failed to fetch data from Nominatim API.");
            }

            $data = json_decode($response, true);

            // Check for 'address' and extract the nearest town, city, or village
            $nearestTown = $data['address']['town']
                           ?? $data['address']['city']
                              ?? $data['address']['village']
                                 ?? null;

            return $nearestTown;

        } catch (\Exception $e) {
            // Log the error for debugging
            // error_log($e->getMessage());
            return null; // Return null if the request fails
        }
    }


    /**
     * calculate distancces with haversine formula
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     *
     * @return float
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // in km
        $dLat        = deg2rad($lat2 - $lat1);
        $dLon        = deg2rad($lon2 - $lon1);
        $a           = sin($dLat / 2) * sin($dLat / 2) +
                       cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
                       sin($dLon / 2) * sin($dLon / 2);
        $c           = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c; // in km
    }
}
