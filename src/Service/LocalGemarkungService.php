<?php

namespace App\Service;

use Exception;
use App\Service\GeoService;

class LocalGemarkungService
{
    private string $geojsonFile;
    private array $features;
    private GeoService $geoService;

    public function __construct(string $geojsonFile, GeoService $geoService)
    {
        $this->geojsonFile = $geojsonFile;
        $this->features = $this->loadFeatures();
        $this->geoService = $geoService;
    }

    private function loadFeatures(): array
    {
        if (!file_exists($this->geojsonFile)) {
            throw new Exception('GeoJSON-Datei nicht gefunden: ' . $this->geojsonFile);
        }
        $data = json_decode(file_get_contents($this->geojsonFile), true);
        return $data['features'] ?? [];
    }

    /**
     * Punkt-in-Polygon-Suche für UTM-Koordinaten (EPSG:25833)
     * Gibt ['name' => ..., 'number' => ...] oder null zurück
     */
    public function findGemarkungByUTM(float $utmX, float $utmY): ?array
    {
        $result = $this->findGemarkungAndFlurstueckByUTM($utmX, $utmY);
        return $result['gemarkung'] ?? null;
    }

    /**
     * Punkt-in-Polygon-Suche für UTM-Koordinaten (EPSG:25833)
     * Gibt ['gemarkung' => ..., 'flurstueck' => ...] zurück
     */
    public function findGemarkungAndFlurstueckByUTM(float $utmX, float $utmY): array
    {
        // Prüfe, ob die Koordinaten bereits in UTM33-Format sind
        // UTM33 X-Koordinaten sind typischerweise 6-stellig (300000-900000)
        // UTM33 Y-Koordinaten sind typischerweise 7-stellig (5000000-6000000)
        if ($utmX >= 300000 && $utmX <= 900000 && $utmY >= 5000000 && $utmY <= 6000000) {
            // Koordinaten sind bereits in UTM33-Format - keine Konvertierung nötig
        } elseif ($utmX > -180 && $utmX < 180 && $utmY > -90 && $utmY < 90) {
            // Werte im WGS84-Bereich: Konvertierung zu UTM33
            $utm = $this->geoService->convertToUTM33($utmY, $utmX); // beachte: Reihenfolge lat, lon
            $utmX = $utm['utmX'];
            $utmY = $utm['utmY'];
        } else {
            // Ungültige Koordinaten
            return ['gemarkung' => null, 'flurstueck' => null];
        }
        
        $gemarkung = null;
        $flurstueck = null;
        
        foreach ($this->features as $feature) {
            $geometry = $feature['geometry'] ?? null;
            if (!$geometry || $geometry['type'] !== 'Polygon' && $geometry['type'] !== 'MultiPolygon') {
                continue;
            }
            if ($this->pointInPolygon([$utmX, $utmY], $geometry)) {
                $props = $feature['properties'] ?? [];
                $art = $props['art'] ?? '';
                
                // Gemarkung: hat 'ueboname' und 'uebobjekt', aber KEINE 'art' oder 'art' ist nicht "Gemarkungsteil/Flur"
                // ODER hat 'art' = "Gemarkung" mit 'name' und 'schluessel'
                if (($props['ueboname'] && $props['uebobjekt'] && $art !== 'Gemarkungsteil/Flur' && $art !== 'Gemarkungsteil / Flur') ||
                    ($art === 'Gemarkung' && $props['name'] && $props['schluessel'])) {
                    
                    if ($art === 'Gemarkung') {
                        // Für art="Gemarkung" verwende name und schluessel
                        $gemarkung = [
                            'name' => $props['name'] ?? null,
                            'number' => $props['schluessel'] ?? null,
                        ];
                    } else {
                        // Für andere Fälle verwende ueboname und uebobjekt
                        $gemarkung = [
                            'name' => $props['ueboname'] ?? null,
                            'number' => $props['uebobjekt'] ?? null,
                        ];
                    }
                }
                
                // Flurstück: hat 'name' und 'schluessel', UND 'art' ist "Gemarkungsteil/Flur" oder "Gemarkungsteil / Flur"
                if ($props['name'] && $props['schluessel'] && ($art === 'Gemarkungsteil/Flur' || $art === 'Gemarkungsteil / Flur')) {
                    $flurstueck = [
                        'name' => $props['name'] ?? null,
                        'number' => $props['schluessel'] ?? null,
                    ];
                    
                    // Wenn das Flurstück-Feature auch Gemarkung-Informationen hat, verwende diese
                    if ($props['ueboname'] && $props['uebobjekt']) {
                        $gemarkung = [
                            'name' => $props['ueboname'] ?? null,
                            'number' => $props['uebobjekt'] ?? null,
                        ];
                    }
                }
            }
        }
        
        return [
            'gemarkung' => $gemarkung,
            'flurstueck' => $flurstueck,
        ];
    }

    /**
     * Prüft, ob ein Punkt in einem Polygon/MultiPolygon liegt (2D, [x, y])
     */
    private function pointInPolygon(array $point, array $geometry): bool
    {
        $x = $point[0];
        $y = $point[1];
        if ($geometry['type'] === 'Polygon') {
            foreach ($geometry['coordinates'] as $ring) {
                if ($this->isPointInRing($x, $y, $ring)) {
                    return true;
                }
            }
        } elseif ($geometry['type'] === 'MultiPolygon') {
            foreach ($geometry['coordinates'] as $polygon) {
                foreach ($polygon as $ring) {
                    if ($this->isPointInRing($x, $y, $ring)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Ray-Casting-Algorithmus für Punkt-in-Ring-Test
     */
    private function isPointInRing(float $x, float $y, array $ring): bool
    {
        $inside = false;
        $n = count($ring);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $ring[$i][0]; $yi = $ring[$i][1];
            $xj = $ring[$j][0]; $yj = $ring[$j][1];
            $intersect = (($yi > $y) !== ($yj > $y)) &&
                ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1e-10) + $xi);
            if ($intersect) {
                $inside = !$inside;
            }
        }
        return $inside;
    }
} 