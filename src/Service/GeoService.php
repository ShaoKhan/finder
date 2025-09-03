<?php
declare(strict_types = 1);

namespace App\Service;

use Exception;
use Geometry;
use \geoPHP;
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
     * Konvertiert UTM33-Koordinaten zu WGS84
     *
     * @param float $utmX
     * @param float $utmY
     *
     * @return array
     */
    public function convertToWGS84(float $utmX, float $utmY): array
    {
        $proj4 = new Proj4php();
        $wgs84 = new Proj('EPSG:4326', $proj4);  // WGS84
        $utm33 = new Proj('EPSG:32633', $proj4); // UTM33

        $point = new Point($utmX, $utmY, $utm33);
        $wgs84Point = $proj4->transform($wgs84, $point);

        return ['longitude' => $wgs84Point->x, 'latitude' => $wgs84Point->y];
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
        // Erst: Reverse-Geocoding um den Ortsnamen zu finden
        $reverseUrl = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$latitude&lon=$longitude";
        
        $options = [
            "http" => [
                "header" => "User-Agent: MySymfonyApp/1.0 (myemail@example.com)\r\n",
            ],
        ];
        
        $context = stream_context_create($options);
        
        try {
            $response = @file_get_contents($reverseUrl, FALSE, $context);
            if($response === FALSE) {
                throw new Exception("Failed to fetch data from Nominatim API.");
            }
            
            $data = json_decode($response, TRUE);
            
            // Extrahiere den Ortsnamen
            $nearestTown = $data['address']['town'] ?? $data['address']['city'] ?? $data['address']['village'] ?? NULL;
            
            if(!$nearestTown) {
                return NULL;
            }
            
            // Dann: Suche nach dem Ortskern
            $searchUrl = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode("$nearestTown, Brandenburg, Germany") . "&limit=1";
            
            $response = @file_get_contents($searchUrl, FALSE, $context);
            if($response === FALSE) {
                throw new Exception("Failed to fetch town center data from Nominatim API.");
            }
            
            $searchData = json_decode($response, TRUE);
            
            if(empty($searchData)) {
                return NULL;
            }
            
            $town = $searchData[0];
            $townLatitude = $town['lat'] ?? NULL;
            $townLongitude = $town['lon'] ?? NULL;
            
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

    /**
     * Berechnet die Himmelsrichtung von einem Punkt zu einem anderen
     * 
     * @param float $lat1 // Standort (von)
     * @param float $lon1 // Standort (von)
     * @param float $lat2 // Ziel (zu)
     * @param float $lon2 // Ziel (zu)
     * @return string Himmelsrichtung (N, NO, O, SO, S, SW, W, NW)
     */
    public function calculateBearing(float $lat1, float $lon1, float $lat2, float $lon2): string
    {
        // Konvertiere Grad zu Radiant
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $dLonRad = deg2rad($lon2 - $lon1);

        // Berechne den Bearing (Azimut)
        $y = sin($dLonRad) * cos($lat2Rad);
        $x = cos($lat1Rad) * sin($lat2Rad) - sin($lat1Rad) * cos($lat2Rad) * cos($dLonRad);
        
        $bearing = atan2($y, $x);
        
        // Konvertiere von Radiant zu Grad und normalisiere auf 0-360°
        $bearingDegrees = rad2deg($bearing);
        $bearingDegrees = fmod($bearingDegrees + 360, 360);

        // Konvertiere zu Himmelsrichtung
        return $this->degreesToCompass($bearingDegrees);
    }

    /**
     * Konvertiert Grad in Himmelsrichtung
     * 
     * @param float $degrees
     * @return string
     */
    private function degreesToCompass(float $degrees): string
    {
        $directions = [
            'N' => [348.75, 11.25],
            'NO' => [11.25, 33.75],
            'O' => [33.75, 56.25],
            'SO' => [56.25, 78.75],
            'S' => [78.75, 101.25],
            'SW' => [101.25, 123.75],
            'W' => [123.75, 146.25],
            'NW' => [146.25, 168.75],
            'N2' => [168.75, 191.25], // N (180-191.25)
            'NW2' => [191.25, 213.75], // NW (191.25-213.75)
            'W2' => [213.75, 236.25], // W (213.75-236.25)
            'SW2' => [236.25, 258.75], // SW (236.25-258.75)
            'S2' => [258.75, 281.25], // S (258.75-281.25)
            'SO2' => [281.25, 303.75], // SO (281.25-303.75)
            'O2' => [303.75, 326.25], // O (303.75-326.25)
            'NO2' => [326.25, 348.75], // NO (326.25-348.75)
        ];

        foreach ($directions as $direction => $range) {
            if ($degrees >= $range[0] && $degrees < $range[1]) {
                // Entferne die "2" Suffixe für die Rückgabe
                return str_replace('2', '', $direction);
            }
        }

        // Fallback für den Fall, dass nichts gefunden wird
        return 'N';
    }

    public function getGemarkungByUTM(float $utmX, float $utmY): ?array
    {
        // Basis-URL der API (neuer Endpunkt /items)
        $baseUrl = "https://ogc-api.geobasis-bb.de/alkis-vereinfacht/v1/collections/katasterbezirk/items";

        // Erzeuge eine kleine BBox um den Punkt (±0.5 Meter)
        $delta = 0.5;
        $minX = $utmX - $delta;
        $maxX = $utmX + $delta;
        $minY = $utmY - $delta;
        $maxY = $utmY + $delta;

        // API-Parameter (BBox für kleinen Bereich um den Punkt)
        $params = [
            'f'    => 'json',
            'bbox' => "$minX,$minY,$maxX,$maxY",
        ];

        // Erzeuge die URL mit den Parametern
        $queryString = http_build_query($params);
        $requestUrl  = "$baseUrl?$queryString";

        error_log('Gemarkung-API URL: ' . $requestUrl);
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
     * Findet die Gemarkung und das Flurstück basierend auf den gegebenen Koordinaten
     * 
     * @param float $latitude
     * @param float $longitude
     * @param string $localFile
     * @return array|null
     * @throws \Exception
     */
    public function findGemarkungAndFlurstueck(float $latitude, float $longitude, string $localFile): ?array
    {
        $data = json_decode(file_get_contents($localFile), true);
        
        if (!$data || !isset($data['features'])) {
            return null;
        }
        
        $gemarkung = null;
        $flurstueck = null;
        
        foreach ($data['features'] as $feature) {
            $geometryData = $feature['geometry'];

            // Lade MultiPolygon
            $geometry = geoPHP::load(json_encode($geometryData), 'json');
            if (!$geometry instanceof Geometry) {
                continue;
            }

            // Lade Punkt
            $pointWKT = "POINT($longitude $latitude)";
            $point = geoPHP::load($pointWKT, 'wkt');
            if (!$point instanceof Geometry) {
                continue;
            }

            // Prüfe Bounding Box für Performance-Optimierung
            $boundingBox = $geometry->getBBox();
            if ($longitude < $boundingBox['minx'] || $longitude > $boundingBox['maxx'] ||
                $latitude < $boundingBox['miny'] || $latitude > $boundingBox['maxy']) {
                continue;
            }

            // Prüfe, ob der Punkt im MultiPolygon liegt
            if ($geometry->contains($point)) {
                $properties = $feature['properties'];
                
                // Bestimme, ob es sich um eine Gemarkung oder ein Flurstück handelt
                $art = $properties['art'] ?? '';
                
                if ($art === 'Gemarkungsteil/Flur') {
                    // Es ist ein Flurstück
                    $flurstueck = [
                        'name' => $properties['name'] ?? null,
                        'number' => $properties['schluessel'] ?? null,
                    ];
                } else {
                    // Es ist eine Gemarkung
                    $gemarkung = [
                        'name' => $properties['ueboname'] ?? null,
                        'number' => $properties['uebobjekt'] ?? null,
                    ];
                }
            }
        }

        // Gib beide Ergebnisse zurück
        return [
            'gemarkung' => $gemarkung,
            'flurstueck' => $flurstueck,
        ];
    }

    /**
     * Findet die Gemarkung basierend auf den gegebenen Koordinaten
     * 
     * @param float $latitude
     * @param float $longitude
     * @param string $localFile
     * @return array|null
     * @throws \Exception
     */
    public function findGemarkung(float $latitude, float $longitude, string $localFile): ?array
    {
        $result = $this->findGemarkungAndFlurstueck($latitude, $longitude, $localFile);
        return $result['gemarkung'] ?? null;
    }



}
