<?php
declare(strict_types = 1);

namespace App\Service;

use Exception;
use Geometry;
use geoPHP;
use proj4php\Point;
use proj4php\Proj;
use proj4php\Proj4php;

class GeoService
{
    private array $wfsServices;

    public function __construct(array $wfsServices)
    {
        $this->wfsServices = $wfsServices;
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
     * @throws Exception
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
            throw new Exception("Failed to fetch data from Nominatim API.");
        }

        return json_decode($response, TRUE);
    }

    /**
     * find nearest church with overpass api
     *
     * @param float $latitude
     * @param float $longitude
     *
     * @return array|null
     */
    public function findNearestChurch(float $latitude, float $longitude): ?array
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

            if($response === FALSE) {
                throw new Exception("Failed to fetch data from Overpass API.");
            }
            $data = json_decode($response, TRUE);

            if(!empty($data['elements'])) {
                $nearest   = $data['elements'][0];
                $name      = $nearest['tags']['name'] ?? 'Unknown Church';
                $latitude  = $nearest['lat'] ?? NULL;
                $longitude = $nearest['lon'] ?? NULL;

                return [
                    'name'      => $name,
                    'latitude'  => $latitude,
                    'longitude' => $longitude,
                ];
            }

            return NULL;
        }
        catch(Exception $e) {
            return NULL;
        }
    }

    public function getNearestTown(float $latitude, float $longitude): ?array
    {
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$latitude&lon=$longitude";

        // Set the User-Agent header
        $options = [
            "http" => [
                "header" => "User-Agent: MySymfonyApp/1.0 (myemail@example.com)\r\n",
            ],
        ];

        // Create a stream context
        $context = stream_context_create($options);

        try {
            $response = @file_get_contents($url, FALSE, $context);
            if($response === FALSE) {
                throw new Exception("Failed to fetch data from Nominatim API.");
            }

            $data = json_decode($response, TRUE);

            // Check for 'address' and extract the nearest town, city, or village
            $nearestTown = $data['address']['town']
                           ?? $data['address']['city']
                              ?? $data['address']['village']
                                 ?? NULL;

            if(!$nearestTown) {
                return NULL;
            }

            #dd($data, $nearestTown);

            // Extract latitude and longitude of the place
            $townLatitude  = $data['lat'] ?? NULL;
            $townLongitude = $data['lon'] ?? NULL;

            return [
                'name'      => $nearestTown,
                'latitude'  => (float)$townLatitude,
                'longitude' => (float)$townLongitude,
            ];

        }
        catch(Exception $e) {
            return NULL;
        }
    }


    /**
     * calculate distancces with haversine formula
     *
     * @param float $lat1 //my location
     * @param float $lon1 //my location
     * @param float $lat2
     * @param float $lon2
     *
     * @return float
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat        = deg2rad($lat2 - $lat1);
        $dLon        = deg2rad($lon2 - $lon1);
        $a           = sin($dLat / 2) * sin($dLat / 2) +
                       cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
                       sin($dLon / 2) * sin($dLon / 2);
        $c           = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    public function getGemarkungByUTM(float $utmX, float $utmY): ?array
    {
        // Basis-URL der API
        $baseUrl = "https://ogc-api.geobasis-bb.de/alkis-vereinfacht/v1/collections/katasterbezirk";

        // API-Parameter (mit Filter für Punktabfrage)
        $params = [
            'f'      => 'json', // Rückgabeformat JSON
            'filter' => "INTERSECTS(geometry,SRID=25833;POINT($utmX $utmY))", // Geometrie-Filter
        ];

        // Erzeuge die URL mit den Parametern
        $queryString = http_build_query($params);
        $requestUrl  = "$baseUrl?$queryString";

        echo $requestUrl;
        try {
            // API-Aufruf
            $response = @file_get_contents($requestUrl);

            if($response === FALSE) {
                throw new Exception("Failed to fetch data from the Gemarkung API.");
            }

            // JSON-Daten dekodieren
            $data = json_decode($response, TRUE);

            // Prüfen, ob Features vorhanden sind
            if(!empty($data['features'])) {
                $feature    = $data['features'][0]; // Nimm das erste Feature
                $properties = $feature['properties'];

                // Extrahiere den Namen und die Nummer der Gemarkung
                $gemarkungName   = $properties['gemarkungsname'] ?? NULL;
                $gemarkungNummer = $properties['gemarkungsnummer'] ?? NULL;

                return [
                    'name'   => $gemarkungName,
                    'number' => $gemarkungNummer,
                ];
            }

            // Keine Features gefunden
            return NULL;
        }
        catch(Exception $e) {
            // Fehlerprotokollierung (optional)
            // error_log($e->getMessage());
            return NULL;
        }
    }

    public function downloadGemarkungen(string $localFile): void
    {
        $url = "https://ogc-api.geobasis-bb.de/alkis-vereinfacht/v1/collections/katasterbezirk/items?f=json";
        try {
            $response = file_get_contents($url);
            if($response === FALSE) {
                throw new Exception("Failed to fetch data from the API.");
            }

            file_put_contents($localFile, $response);
        }
        catch(Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * @throws exception
     */
    public function findGemarkung(float $latitude, float $longitude, string $localFile): ?array
    {
        $data = json_decode(file_get_contents($localFile), true);
        foreach ($data['features'] as $feature) {
            $geometryData = $feature['geometry'];

            // Lade MultiPolygon
            $geometry = geoPHP::load(json_encode($geometryData), 'json');
            if (!$geometry instanceof Geometry) {
                throw new \Exception("Invalid MultiPolygon geometry.");
                continue;
            }

            // Lade Punkt
            $pointWKT = "POINT($longitude $latitude)";
            $point = geoPHP::load($pointWKT, 'wkt');
            if (!$point instanceof Geometry) {
                throw new \Exception("Failed to create Point geometry.");
            }

            // Prüfe Bounding Box
            $boundingBox = $geometry->getBBox();
            if ($longitude < $boundingBox['minx'] || $longitude > $boundingBox['maxx'] ||
                $latitude < $boundingBox['miny'] || $latitude > $boundingBox['maxy']) {
                echo "Point ($longitude, $latitude) is outside the bounding box:<br />";
                echo "Bounding Box: MinX={$boundingBox['minx']}, MinY={$boundingBox['miny']}, MaxX={$boundingBox['maxx']}, MaxY={$boundingBox['maxy']}<br />";
                continue;
            }



            // Prüfe, ob der Punkt im MultiPolygon liegt
            if ($geometry->contains($point)) {
                echo 'got points<br />';
                $properties = $feature['properties'];

                return [
                    'name'   => $properties['gemarkungsname'] ?? null,
                    'number' => $properties['gemarkungsnummer'] ?? null,
                ];
            }
        }

        return null;
    }



}
