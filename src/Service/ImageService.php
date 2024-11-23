<?php
declare(strict_types = 1);

namespace App\Service;


class ImageService{

    public function extractExifData(string $filePath): array
    {
        $exifData = @exif_read_data($filePath, 'ANY_TAG', true);

        if (!$exifData) {
            return [];
        }

        $gpsData = $exifData['GPS'] ?? null;
        $latitude = null;
        $longitude = null;

        if ($gpsData) {
            $latitude = $this->getGpsCoordinate($gpsData['GPSLatitude'] ?? null, $gpsData['GPSLatitudeRef'] ?? null);
            $longitude = $this->getGpsCoordinate($gpsData['GPSLongitude'] ?? null, $gpsData['GPSLongitudeRef'] ?? null);
        }

        return [
            'camera_model' => $exifData['IFD0']['Model'] ?? null,
            'exposure_time' => $exifData['EXIF']['ExposureTime'] ?? null,
            'f_number' => $exifData['EXIF']['FNumber'] ?? null,
            'iso' => $exifData['EXIF']['ISOSpeedRatings'] ?? null,
            'date_time' => $exifData['IFD0']['DateTime'] ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    private function getGpsCoordinate(?array $coordinate, ?string $hemisphere): ?float
    {
        if (!$coordinate || !$hemisphere) {
            return null;
        }

        // Convert GPS coordinates to decimal format
        $degrees = $this->fractionToFloat($coordinate[0]);
        $minutes = $this->fractionToFloat($coordinate[1]);
        $seconds = $this->fractionToFloat($coordinate[2]);

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        // Adjust for hemisphere
        return ($hemisphere == 'S' || $hemisphere == 'W') ? -$decimal : $decimal;
    }

    private function fractionToFloat(string $fraction): float
    {
        $parts = explode('/', $fraction);
        return count($parts) == 2 ? floatval($parts[0]) / floatval($parts[1]) : floatval($fraction);
    }

}