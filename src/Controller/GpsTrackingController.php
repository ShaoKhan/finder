<?php

namespace App\Controller;

use App\Entity\Begehung;
use App\Service\GpsTrackingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/gps-tracking')]
#[IsGranted('ROLE_USER')]
class GpsTrackingController extends AbstractController
{
    public function __construct(
        private GpsTrackingService $gpsTrackingService,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/start', name: 'gps_tracking_start', methods: ['POST'])]
    public function startTracking(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            return new JsonResponse(['error' => 'Latitude und Longitude sind erforderlich'], 400);
        }

        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];

        if (!$this->gpsTrackingService->validateCoordinates($latitude, $longitude)) {
            return new JsonResponse(['error' => 'Ungültige GPS-Koordinaten'], 400);
        }

        try {
            $begehung = $this->gpsTrackingService->startBegehung(
                $this->getUser(),
                $latitude,
                $longitude
            );

            return new JsonResponse([
                'success' => true,
                'begehung' => [
                    'id' => $begehung->getId(),
                    'uuid' => $begehung->getUuid(),
                    'startTime' => $begehung->getStartTime()->format('c'),
                    'startLatitude' => $begehung->getStartLatitude(),
                    'startLongitude' => $begehung->getStartLongitude(),
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/stop', name: 'gps_tracking_stop', methods: ['POST'])]
    public function stopTracking(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            return new JsonResponse(['error' => 'Latitude und Longitude sind erforderlich'], 400);
        }

        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];

        if (!$this->gpsTrackingService->validateCoordinates($latitude, $longitude)) {
            return new JsonResponse(['error' => 'Ungültige GPS-Koordinaten'], 400);
        }

        $begehung = $this->gpsTrackingService->stopBegehung(
            $this->getUser(),
            $latitude,
            $longitude
        );

        if (!$begehung) {
            return new JsonResponse(['error' => 'Keine aktive Begehung gefunden'], 404);
        }

        return new JsonResponse([
            'success' => true,
            'begehung' => [
                'id' => $begehung->getId(),
                'uuid' => $begehung->getUuid(),
                'startTime' => $begehung->getStartTime()->format('c'),
                'endTime' => $begehung->getEndTime()->format('c'),
                'duration' => $begehung->getDuration(),
                'formattedDuration' => $begehung->getFormattedDuration(),
                'startLatitude' => $begehung->getStartLatitude(),
                'startLongitude' => $begehung->getStartLongitude(),
                'endLatitude' => $begehung->getEndLatitude(),
                'endLongitude' => $begehung->getEndLongitude(),
                'trackData' => $begehung->getTrackAsArray(),
            ]
        ]);
    }

    #[Route('/track-point', name: 'gps_tracking_add_point', methods: ['POST'])]
    public function addTrackPoint(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            return new JsonResponse(['error' => 'Latitude und Longitude sind erforderlich'], 400);
        }

        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];

        if (!$this->gpsTrackingService->validateCoordinates($latitude, $longitude)) {
            return new JsonResponse(['error' => 'Ungültige GPS-Koordinaten'], 400);
        }

        $success = $this->gpsTrackingService->addTrackPoint(
            $this->getUser(),
            $latitude,
            $longitude
        );

        if (!$success) {
            return new JsonResponse(['error' => 'Keine aktive Begehung gefunden'], 404);
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route('/status', name: 'gps_tracking_status', methods: ['GET'])]
    public function getStatus(): JsonResponse
    {
        $activeBegehung = $this->gpsTrackingService->getActiveBegehung($this->getUser());

        if (!$activeBegehung) {
            return new JsonResponse(['active' => false]);
        }

        return new JsonResponse([
            'active' => true,
            'begehung' => [
                'id' => $activeBegehung->getId(),
                'uuid' => $activeBegehung->getUuid(),
                'startTime' => $activeBegehung->getStartTime()->format('c'),
                'startLatitude' => $activeBegehung->getStartLatitude(),
                'startLongitude' => $activeBegehung->getStartLongitude(),
                'trackData' => $activeBegehung->getTrackAsArray(),
            ]
        ]);
    }

    #[Route('/history', name: 'gps_tracking_history', methods: ['GET'])]
    public function getHistory(): Response
    {
        return $this->render('gps_tracking/history.html.twig');
    }

    #[Route('/history/api', name: 'gps_tracking_history_api', methods: ['GET'])]
    public function getHistoryApi(): JsonResponse
    {
        $begehungen = $this->gpsTrackingService->getBegehungenByUser($this->getUser());

        $history = [];
        foreach ($begehungen as $begehung) {
            $trackData = $begehung->getTrackAsArray();
            $distance = $this->gpsTrackingService->calculateTrackDistance($trackData);
            $area = $this->gpsTrackingService->calculateTrackArea($trackData);
            
            $history[] = [
                'id' => $begehung->getId(),
                'uuid' => $begehung->getUuid(),
                'startTime' => $begehung->getStartTime()->format('c'),
                'endTime' => $begehung->getEndTime() ? $begehung->getEndTime()->format('c') : null,
                'duration' => $begehung->getDuration(),
                'formattedDuration' => $begehung->getFormattedDuration(),
                'isActive' => $begehung->isActive(),
                'startLatitude' => $begehung->getStartLatitude(),
                'startLongitude' => $begehung->getStartLongitude(),
                'endLatitude' => $begehung->getEndLatitude(),
                'endLongitude' => $begehung->getEndLongitude(),
                'foundsCount' => $begehung->getFoundsImages()->count(),
                'distance' => $distance,
                'area' => $area,
                'trackData' => $trackData,
            ];
        }

        return new JsonResponse(['begehungen' => $history]);
    }

    #[Route('/map/{id}', name: 'gps_tracking_map', methods: ['GET'])]
    public function showMap(int $id): Response
    {
        $begehung = $this->entityManager->getRepository(Begehung::class)->find($id);

        if (!$begehung || $begehung->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Begehung nicht gefunden');
        }

        $trackData = $begehung->getTrackAsArray();
        $geoJson = $this->gpsTrackingService->generateTrackGeoJson($trackData);
        $polygonGeoJson = $this->gpsTrackingService->generatePolygonGeoJson($trackData);

        return $this->render('gps_tracking/map.html.twig', [
            'begehung' => $begehung,
            'trackGeoJson' => json_encode($geoJson),
            'polygonGeoJson' => json_encode($polygonGeoJson),
        ]);
    }

    #[Route('/geojson/{id}', name: 'gps_tracking_geojson', methods: ['GET'])]
    public function getGeoJson(int $id): JsonResponse
    {
        $begehung = $this->entityManager->getRepository(Begehung::class)->find($id);

        if (!$begehung || $begehung->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Begehung nicht gefunden');
        }

        $trackData = $begehung->getTrackAsArray();
        $geoJson = $this->gpsTrackingService->generateTrackGeoJson($trackData);
        $polygonGeoJson = $this->gpsTrackingService->generatePolygonGeoJson($trackData);

        return new JsonResponse([
            'track' => $geoJson,
            'polygon' => $polygonGeoJson,
            'stats' => [
                'distance' => $this->gpsTrackingService->calculateTrackDistance($trackData),
                'area' => $this->gpsTrackingService->calculateTrackArea($trackData),
                'duration' => $begehung->getDuration(),
                'formattedDuration' => $begehung->getFormattedDuration(),
            ]
        ]);
    }

    /**
     * Löscht eine Begehung (nur Admin oder Ersteller)
     */
    #[Route('/delete/{id}', name: 'gps_tracking_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $begehung = $this->entityManager->getRepository(Begehung::class)->find($id);
        
        if (!$begehung) {
            throw $this->createNotFoundException('Begehung nicht gefunden');
        }

        // Berechtigung prüfen: Admin oder Ersteller
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && $begehung->getUser() !== $user) {
            throw $this->createAccessDeniedException('Keine Berechtigung zum Löschen dieser Begehung');
        }

        // CSRF-Token prüfen
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_begehung', $submittedToken)) {
            throw $this->createAccessDeniedException('Ungültiger CSRF-Token');
        }

        try {
            $this->gpsTrackingService->deleteBegehung($begehung);
            $this->addFlash('success', 'Begehung wurde erfolgreich gelöscht.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Fehler beim Löschen der Begehung: ' . $e->getMessage());
        }

        return $this->redirectToRoute('gps_tracking_history');
    }
}
