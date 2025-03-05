<?php

namespace App\Service;

use Symfony\Component\HttpKernel\KernelInterface;

class MapService
{
    private string $tempDir;

    public function __construct(KernelInterface $kernel)
    {
        $this->tempDir = $kernel->getCacheDir() . '/temp_maps';
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }
    }

    public function generateStaticMap(float $centerLat, float $centerLon, array $markers): string
    {
        // Erstelle einen einzigartigen Dateinamen
        $filename = sprintf('map_%s.png', uniqid());
        $filepath = $this->tempDir . '/' . $filename;

        // Konvertiere Koordinaten zu Tile-Koordinaten (Zoom Level 14)
        $zoom = 14;
        list($centerTileX, $centerTileY) = $this->latLonToTile($centerLat, $centerLon, $zoom);

        // Erstelle ein leeres Bild
        $width = 800;
        $height = 400;
        $image = imagecreatetruecolor($width, $height);

        // Berechne die benötigten Tiles
        $tilesX = ceil($width / 256) + 1;
        $tilesY = ceil($height / 256) + 1;
        $offsetX = ($width - 256) / 2;
        $offsetY = ($height - 256) / 2;

        // Lade und füge Tiles hinzu
        for ($x = -floor($tilesX/2); $x <= floor($tilesX/2); $x++) {
            for ($y = -floor($tilesY/2); $y <= floor($tilesY/2); $y++) {
                $tileX = $centerTileX + $x;
                $tileY = $centerTileY + $y;
                
                $tileUrl = sprintf(
                    'https://tile.openstreetmap.org/%d/%d/%d.png',
                    $zoom, $tileX, $tileY
                );

                // Hole Tile mit Retry-Mechanismus
                $tileData = $this->fetchTileWithRetry($tileUrl);
                if ($tileData) {
                    $tile = imagecreatefromstring($tileData);
                    if ($tile) {
                        imagecopy(
                            $image, 
                            $tile, 
                            $offsetX + ($x * 256), 
                            $offsetY + ($y * 256), 
                            0, 0, 256, 256
                        );
                        imagedestroy($tile);
                    }
                }
                
                // Füge kleine Verzögerung hinzu um den Server nicht zu überlasten
                usleep(100000); // 100ms
            }
        }

        // Zeichne Marker
        foreach ($markers as $marker) {
            if (is_array($marker) && count($marker) === 2) {
                list($markerTileX, $markerTileY) = $this->latLonToTile($marker[0], $marker[1], $zoom);
                $pixelX = $offsetX + (($markerTileX - $centerTileX) * 256);
                $pixelY = $offsetY + (($markerTileY - $centerTileY) * 256);
                
                // Zeichne roten Marker
                $red = imagecolorallocate($image, 255, 0, 0);
                imagefilledellipse($image, $pixelX, $pixelY, 10, 10, $red);
            }
        }

        // Speichere das Bild
        imagepng($image, $filepath);
        imagedestroy($image);

        return $filename;
    }

    private function latLonToTile(float $lat, float $lon, int $zoom): array
    {
        $x = floor((($lon + 180) / 360) * pow(2, $zoom));
        $y = floor((1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / pi()) / 2 * pow(2, $zoom));
        return [$x, $y];
    }

    private function fetchTileWithRetry(string $url, int $maxRetries = 3): string|false
    {
        $attempt = 0;
        while ($attempt < $maxRetries) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; YourApp/1.0)');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $data = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($data !== false && $httpCode === 200) {
                return $data;
            }

            $attempt++;
            if ($attempt < $maxRetries) {
                sleep(1); // Warte 1 Sekunde vor dem nächsten Versuch
            }
        }
        return false;
    }

    public function cleanupTempMaps(): void
    {
        // Lösche alle temporären Karten älter als 1 Stunde
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && time() - filemtime($file) > 3600) {
                unlink($file);
            }
        }
    }

    public function getTempMapPath(string $filename): string
    {
        return $this->tempDir . '/' . $filename;
    }
} 