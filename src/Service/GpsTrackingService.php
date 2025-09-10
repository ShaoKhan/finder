<?php

namespace App\Service;

use App\Entity\Begehung;
use App\Entity\User;
use App\Repository\BegehungRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class GpsTrackingService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BegehungRepository $begehungRepository
    ) {
    }

    /**
     * Startet eine neue Begehung für einen Benutzer
     */
    public function startBegehung(User $user, float $latitude, float $longitude): Begehung
    {
        // Prüfe ob bereits eine aktive Begehung existiert
        $activeBegehung = $this->begehungRepository->findActiveByUser($user);
        if ($activeBegehung) {
            throw new \Exception('Es läuft bereits eine aktive Begehung. Beende diese zuerst.');
        }

        $begehung = new Begehung();
        $begehung->setUser($user);
        $begehung->setStartLatitude($latitude);
        $begehung->setStartLongitude($longitude);
        $begehung->setStartTime(new \DateTime());
        $begehung->setIsActive(true);

        $this->entityManager->persist($begehung);
        $this->entityManager->flush();

        return $begehung;
    }

    /**
     * Beendet eine aktive Begehung
     */
    public function stopBegehung(User $user, float $latitude, float $longitude): ?Begehung
    {
        $activeBegehung = $this->begehungRepository->findActiveByUser($user);
        if (!$activeBegehung) {
            return null;
        }

        $activeBegehung->setEndLatitude($latitude);
        $activeBegehung->setEndLongitude($longitude);
        $activeBegehung->setEndTime(new \DateTime());
        $activeBegehung->setIsActive(false);
        $activeBegehung->calculateDuration();

        $this->entityManager->flush();

        return $activeBegehung;
    }

    /**
     * Fügt einen GPS-Punkt zur aktiven Begehung hinzu
     */
    public function addTrackPoint(User $user, float $latitude, float $longitude): bool
    {
        $activeBegehung = $this->begehungRepository->findActiveByUser($user);
        if (!$activeBegehung) {
            return false;
        }

        $activeBegehung->addTrackPoint($latitude, $longitude);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Gibt die aktive Begehung für einen Benutzer zurück
     */
    public function getActiveBegehung(User $user): ?Begehung
    {
        return $this->begehungRepository->findActiveByUser($user);
    }

    /**
     * Gibt alle Begehungen für einen Benutzer zurück
     */
    public function getBegehungenByUser(User $user): array
    {
        return $this->begehungRepository->findByUser($user);
    }

    /**
     * Berechnet die Fläche eines GPS-Tracks mit Convex Hull
     */
    public function calculateTrackArea(array $trackPoints): float
    {
        if (count($trackPoints) < 2) {
            return 0.0;
        }

        // Bei nur 2 Punkten: Buffer-Zone um die Linie (2m Padding)
        if (count($trackPoints) === 2) {
            return $this->calculateLineBufferArea(
                $trackPoints[0],
                $trackPoints[1],
                2.0
            );
        }

        // Bei 3+ Punkten: Convex Hull berechnen
        $convexHull = $this->calculateConvexHull($trackPoints);
        return $this->calculatePolygonArea($convexHull);
    }

    /**
     * Berechnet die Fläche einer Buffer-Zone um eine Linie
     */
    private function calculateLineBufferArea(array $point1, array $point2, float $bufferDistance): float
    {
        // Berechne die Distanz zwischen den beiden Punkten
        $distance = $this->calculateDistance(
            $point1['latitude'],
            $point1['longitude'],
            $point2['latitude'],
            $point2['longitude']
        );

        // Berechne die Fläche der Buffer-Zone
        // Die Fläche besteht aus:
        // 1. Einem Rechteck mit der Länge der Linie und der Breite 2 * bufferDistance
        // 2. Zwei Halbkreisen an den Enden mit dem Radius bufferDistance
        
        $rectangleArea = $distance * (2 * $bufferDistance);
        $circleArea = M_PI * $bufferDistance * $bufferDistance; // Zwei Halbkreise = ein ganzer Kreis
        
        return $rectangleArea + $circleArea;
    }

    /**
     * Berechnet das Convex Hull mit Graham Scan Algorithmus
     */
    public function calculateConvexHull(array $points): array
    {
        if (count($points) < 3) {
            return $points;
        }

        // GPS-Rauschen filtern (Punkte die zu nah beieinander liegen)
        $filteredPoints = $this->filterGpsNoise($points);
        
        // Wenn nach dem Filtern zu wenige Punkte übrig sind, verwende alle Punkte
        if (count($filteredPoints) < 3) {
            return $points;
        }

        // Sortiere Punkte nach Y-Koordinate, dann nach X-Koordinate
        usort($filteredPoints, function($a, $b) {
            if ($a['latitude'] == $b['latitude']) {
                return $a['longitude'] <=> $b['longitude'];
            }
            return $a['latitude'] <=> $b['latitude'];
        });

        $hull = [];
        $hull[] = $filteredPoints[0];
        $hull[] = $filteredPoints[1];

        for ($i = 2; $i < count($filteredPoints); $i++) {
            while (count($hull) > 1 && $this->crossProduct($hull[count($hull)-2], $hull[count($hull)-1], $filteredPoints[$i]) <= 0) {
                array_pop($hull);
            }
            $hull[] = $filteredPoints[$i];
        }

        return $hull;
    }

    /**
     * Filtert GPS-Rauschen (Punkte die zu nah beieinander liegen)
     */
    private function filterGpsNoise(array $points, float $minDistance = 1.0): array
    {
        if (count($points) < 2) {
            return $points;
        }

        $filtered = [$points[0]]; // Ersten Punkt immer behalten
        
        for ($i = 1; $i < count($points); $i++) {
            $lastPoint = end($filtered);
            $currentPoint = $points[$i];
            
            $distance = $this->calculateDistance(
                $lastPoint['latitude'],
                $lastPoint['longitude'],
                $currentPoint['latitude'],
                $currentPoint['longitude']
            );
            
            // Nur Punkte behalten die mindestens 1m entfernt sind
            if ($distance >= $minDistance) {
                $filtered[] = $currentPoint;
            }
        }
        
        return $filtered;
    }

    /**
     * Berechnet die Fläche eines Polygons mit der Shoelace-Formel für GPS-Koordinaten
     */
    private function calculatePolygonArea(array $polygonPoints): float
    {
        if (count($polygonPoints) < 3) {
            return 0.0;
        }

        $area = 0.0;
        $n = count($polygonPoints);

        // Shoelace-Formel für GPS-Koordinaten (in Quadratgrad)
        for ($i = 0; $i < $n; $i++) {
            $j = ($i + 1) % $n;
            $area += $polygonPoints[$i]['longitude'] * $polygonPoints[$j]['latitude'];
            $area -= $polygonPoints[$j]['longitude'] * $polygonPoints[$i]['latitude'];
        }

        $area = abs($area) / 2.0;
        
        // Umrechnung von Quadratgrad zu Quadratmetern
        // 1 Grad ≈ 111.32 km, also 1 Grad² ≈ 12.393.742.400 m²
        $areaInSquareMeters = $area * 12393742400;
        
        return $areaInSquareMeters;
    }

    /**
     * Berechnet die Distanz zwischen zwei GPS-Punkten (Haversine-Formel)
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Erdradius in Metern

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Berechnet die Gesamtdistanz eines Tracks
     */
    public function calculateTrackDistance(array $trackPoints): float
    {
        if (count($trackPoints) < 2) {
            return 0.0;
        }

        $totalDistance = 0.0;
        for ($i = 0; $i < count($trackPoints) - 1; $i++) {
            $point1 = $trackPoints[$i];
            $point2 = $trackPoints[$i + 1];
            
            $totalDistance += $this->calculateDistance(
                $point1['latitude'],
                $point1['longitude'],
                $point2['latitude'],
                $point2['longitude']
            );
        }

        return $totalDistance;
    }

    /**
     * Generiert GeoJSON für einen Track
     */
    public function generateTrackGeoJson(array $trackPoints): array
    {
        $coordinates = [];
        foreach ($trackPoints as $point) {
            $coordinates[] = [$point['longitude'], $point['latitude']];
        }

        return [
            'type' => 'Feature',
            'properties' => [
                'name' => 'GPS Track',
                'distance' => $this->calculateTrackDistance($trackPoints),
                'area' => $this->calculateTrackArea($trackPoints)
            ],
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => $coordinates
            ]
        ];
    }

    /**
     * Generiert GeoJSON für ein Polygon (vereinfachte Convex Hull)
     */
    public function generatePolygonGeoJson(array $trackPoints): array
    {
        if (count($trackPoints) < 2) {
            return [];
        }

        // Convex Hull berechnen
        $hull = $this->calculateConvexHull($trackPoints);
        
        if (count($hull) < 3) {
            // Bei weniger als 3 Punkten: Buffer-Zone um Linie
            return $this->generateBufferZoneGeoJson($trackPoints);
        }
        
        $coordinates = [];
        foreach ($hull as $point) {
            $coordinates[] = [$point['longitude'], $point['latitude']];
        }
        // Polygon muss geschlossen sein
        $coordinates[] = $coordinates[0];

        return [
            'type' => 'Feature',
            'properties' => [
                'name' => 'Begehungsfläche (Convex Hull)',
                'area' => $this->calculateTrackArea($trackPoints),
                'points' => count($hull)
            ],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [$coordinates]
            ]
        ];
    }

    /**
     * Generiert GeoJSON für Buffer-Zone um Linie
     */
    private function generateBufferZoneGeoJson(array $trackPoints): array
    {
        if (count($trackPoints) < 2) {
            return [];
        }

        $point1 = $trackPoints[0];
        $point2 = $trackPoints[1];
        
        // Berechne Buffer-Zone (2m Padding)
        $buffer = 0.00002; // ~2m in Grad (ungefähr)
        
        // Berechne die Richtung der Linie
        $dx = $point2['longitude'] - $point1['longitude'];
        $dy = $point2['latitude'] - $point1['latitude'];
        $length = sqrt($dx * $dx + $dy * $dy);
        
        // Normalisierte Richtungsvektoren
        $dxNorm = $dx / $length;
        $dyNorm = $dy / $length;
        
        // Senkrechte Vektoren für Buffer
        $perpX = -$dyNorm * $buffer;
        $perpY = $dxNorm * $buffer;
        
        // Erstelle Rechteck um die Linie
        $coordinates = [
            [$point1['longitude'] + $perpX, $point1['latitude'] + $perpY],
            [$point1['longitude'] - $perpX, $point1['latitude'] - $perpY],
            [$point2['longitude'] - $perpX, $point2['latitude'] - $perpY],
            [$point2['longitude'] + $perpX, $point2['latitude'] + $perpY],
            [$point1['longitude'] + $perpX, $point1['latitude'] + $perpY] // Geschlossen
        ];

        return [
            'type' => 'Feature',
            'properties' => [
                'name' => 'Begehungsfläche (2m Buffer-Zone)',
                'area' => $this->calculateTrackArea($trackPoints),
                'points' => 2
            ],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [$coordinates]
            ]
        ];
    }


    /**
     * Berechnet das Kreuzprodukt für Convex Hull
     */
    private function crossProduct(array $O, array $A, array $B): float
    {
        return ($A['longitude'] - $O['longitude']) * ($B['latitude'] - $O['latitude']) - 
               ($A['latitude'] - $O['latitude']) * ($B['longitude'] - $O['longitude']);
    }

    /**
     * Validiert GPS-Koordinaten
     */
    public function validateCoordinates(float $latitude, float $longitude): bool
    {
        return $latitude >= -90 && $latitude <= 90 && 
               $longitude >= -180 && $longitude <= 180;
    }

    /**
     * Löscht eine Begehung (ohne zugeordnete Funde zu löschen)
     */
    public function deleteBegehung(Begehung $begehung): void
    {
        // Zugeordnete Funde von der Begehung trennen (nicht löschen)
        foreach ($begehung->getFoundsImages() as $foundsImage) {
            $foundsImage->setBegehung(null);
            $foundsImage->setTrackIndex(null);
        }

        // Begehung aus der Datenbank entfernen
        $this->entityManager->remove($begehung);
        $this->entityManager->flush();
    }

    /**
     * Prüft ob GPS-Signal verfügbar ist (vereinfacht)
     */
    public function isGpsAvailable(): bool
    {
        // In einer echten Implementierung würde hier die Browser-Geolocation API geprüft
        return true;
    }
}
