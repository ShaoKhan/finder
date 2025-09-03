<?php

declare(strict_types = 1);

namespace App\Controller;


use App\Entity\FoundsImage;
use App\Entity\Project;
use App\Entity\User;
use App\Form\FoundsImageUploadType;
use App\Repository\FoundsImageRepository;
use App\Repository\ProjectRepository;
use App\Service\GeoService;
use App\Service\ImageService;
use App\Service\PdfService;
use App\Service\MapService;
use App\Service\LocalGemarkungService;
use DateTime;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

class FoundsController extends FinderAbstractController
{

    public function __construct(
        private readonly GeoService                $geoService,
        private readonly ImageService              $imageService,
        private readonly FoundsImageRepository     $foundsImageRepository,
        private readonly ProjectRepository         $projectRepository,
        private readonly TranslatorInterface       $translator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly LoggerInterface           $logger,
        private readonly LocalGemarkungService     $localGemarkungService,
    ) {
        parent::__construct();
    }

    #[Route('/founds/index', name: 'founds_index')]
    public function index(): Response
    {
        return $this->render('founds/index.html.twig');
    }

    #[Route('/photo/upload', name: 'photo_upload')]
    public function upload(
        Request    $request,
        GeoService $geoService,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $form = $this->createForm(FoundsImageUploadType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $uploadedFiles = $form->get('files')->getData();
            $isPublic = $form->get('isPublic')->getData();

            if(empty($uploadedFiles)) {
                $this->addFlash('error', $this->translator->trans('form.noFilesUploaded', [], 'founds'));
                return $this->redirectToRoute('photo_upload');
            }

            $errors = [];
            $successCount = 0;
            $maxFileSize = 10 * 1024 * 1024; // 10MB
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/heic'];

            foreach($uploadedFiles as $uploadedFile) {
                $fileName = $uploadedFile->getClientOriginalName();
                $tempFilePath = null;
                
                // Validiere Dateityp und Größe
                if (!in_array($uploadedFile->getMimeType(), $allowedTypes)) {
                    $errors[$fileName][] = $this->translator->trans('invalidFileType', [
                        '%filename%' => $fileName,
                        '%type%' => $uploadedFile->getMimeType()
                    ], 'founds');
                    continue;
                }

                if ($uploadedFile->getSize() > $maxFileSize) {
                    $errors[$fileName][] = $this->translator->trans('fileTooLarge', [
                        '%filename%' => $fileName,
                        '%size%' => number_format($uploadedFile->getSize() / 1024 / 1024, 2),
                        '%maxsize%' => number_format($maxFileSize / 1024 / 1024, 2)
                    ], 'founds');
                    continue;
                }

                try {
                    // Temporärer Upload für EXIF-Prüfung
                    $tempFilePath = $this->handleFileUpload($uploadedFile);

                    // Prüfe, ob es sich wirklich um ein Bild handelt
                    if (false === @getimagesize($tempFilePath)) {
                        $errors[$fileName][] = $this->translator->trans('notAnImage', [
                            '%filename%' => $fileName
                        ], 'founds');
                        $this->safeUnlink($tempFilePath);
                        continue;
                    }
                    
                    // Extrahiere und validiere EXIF-Daten
                    $exifData = $this->imageService->extractExifData($tempFilePath);
                    $validationErrors = $this->validateExifData($exifData, $fileName);
                    
                    if (!empty($validationErrors)) {
                        $errors[$fileName] = array_merge($errors[$fileName] ?? [], $validationErrors);
                        $this->safeUnlink($tempFilePath);
                        continue;
                    }

                    $latitude = $exifData['latitude'];
                    $longitude = $exifData['longitude'];

                    // Hole und validiere Standortdaten
                    try {
                        $locationData = $this->getLocationData($geoService, $latitude, $longitude);
                        $utmCoordinates = $geoService->convertToUTM33($latitude, $longitude);
                        
                        if (empty($locationData)) {
                            throw new Exception($this->translator->trans('noLocationData', [
                                '%filename%' => $fileName,
                                '%latitude%' => $latitude,
                                '%longitude%' => $longitude
                            ], 'founds'));
                        }

                        if (empty($utmCoordinates)) {
                            throw new Exception($this->translator->trans('noUTMConversion', [
                                '%filename%' => $fileName,
                                '%latitude%' => $latitude,
                                '%longitude%' => $longitude
                            ], 'founds'));
                        }

                        // Erstelle und speichere das Foto-Entity
                        $photo = new FoundsImage();
                        $this->populatePhotoEntity($photo, $exifData, $locationData, $utmCoordinates, $tempFilePath, $isPublic);
                        
                        $this->foundsImageRepository->save($photo, true);
                        $successCount++;
                        
                    } catch (Exception $e) {
                        $errors[$fileName][] = $e->getMessage();
                        $this->safeUnlink($tempFilePath);
                        // Logging für kritische Fehler
                        $this->logger->error('Fehler beim Verarbeiten des Bildes: ' . $e->getMessage());
                    }

                } catch (Exception $e) {
                    $errors[$fileName][] = $e->getMessage();
                    $this->safeUnlink($tempFilePath);
                    // Logging für kritische Fehler
                    $this->logger->error('Fehler beim Upload: ' . $e->getMessage());
                }
            }

            // Zeige Erfolgs- und Fehlermeldungen
            if($successCount > 0) {
                // Erfolgsmeldung nur mit Anzahl der hochgeladenen Bilder
                $this->addFlash(
                    'success', 
                    $this->translator->trans('photosUploadSuccess', 
                    ['%count%' => $successCount], 
                    'founds')
                );
                
                // Bei erfolgreichem Upload zur Bildliste weiterleiten
                return $this->redirectToRoute('image_list');
            }

            if (!empty($errors)) {
                // Zeige eine allgemeine Fehlermeldung, wenn alle Uploads fehlschlagen
                if ($successCount === 0) {
                    $this->addFlash('error', $this->translator->trans('uploadAllFailed', [], 'founds'));
                } else {
                    // Teilweise erfolgreich: Zeige Erfolgsmeldung und Fehlermeldungen
                    $this->addFlash(
                        'success', 
                        $this->translator->trans('uploadPartialSuccess', 
                        ['%count%' => $successCount], 
                        'founds')
                    );
                    
                    // Zeige Fehlermeldungen für fehlgeschlagene Uploads
                    foreach($errors as $fileName => $fileErrors) {
                        $errorMessage = "<strong>$fileName</strong><ul class='error-list'>";
                        foreach($fileErrors as $error) {
                            $errorMessage .= "<li>$error</li>";
                        }
                        $errorMessage .= "</ul>";
                        $this->addFlash('error', $errorMessage);
                    }
                    
                    // Weiterleitung zur Bildliste bei teilweisem Erfolg
                    return $this->redirectToRoute('image_list');
                }
                
                // Nur Fehler, keine Erfolge
                foreach($errors as $fileName => $fileErrors) {
                    $errorMessage = "<strong>$fileName</strong><ul class='error-list'>";
                    foreach($fileErrors as $error) {
                        $errorMessage .= "<li>$error</li>";
                    }
                    $errorMessage .= "</ul>";
                    $this->addFlash('error', $errorMessage);
                }
                
                // Bei Fehlern auf der Upload-Seite bleiben
                return $this->redirectToRoute('photo_upload');
            }

            // Falls weder Erfolg noch Fehler (sollte nicht vorkommen)
            return $this->redirectToRoute('photo_upload');
        }

        return $this->render('founds/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Validiert die EXIF-Daten eines Bildes und ergänzt fehlende Daten
     * @return array Fehlermeldungen
     */
    private function validateExifData(array &$exifData, string $fileName): array
    {
        $errors = [];
        
        if(empty($exifData)) {
            $errors[] = $this->translator->trans('noExifData', [
                '%filename%' => $fileName
            ], 'founds');
            return $errors;
        }

        // Strikte Überprüfung der Koordinaten
        $latitude = $exifData['latitude'] ?? null;
        $longitude = $exifData['longitude'] ?? null;

        // Prüfe ob Koordinaten überhaupt vorhanden sind
        if ($latitude === null || $longitude === null) {
            $errors[] = $this->translator->trans('noCoordinates', [
                '%filename%' => $fileName
            ], 'founds');
            return $errors;
        }

        // Prüfe ob Koordinaten numerisch sind
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            $errors[] = $this->translator->trans('invalidCoordinateFormat', [
                '%filename%' => $fileName
            ], 'founds');
            return $errors;
        }

        // Konvertiere zu Float für die Bereichsprüfung
        $latitude = (float)$latitude;
        $longitude = (float)$longitude;

        // Prüfe Koordinatenbereiche
        if ($latitude < -90 || $latitude > 90) {
            $errors[] = $this->translator->trans('invalidLatitude', [
                '%filename%' => $fileName,
                '%value%' => $latitude
            ], 'founds');
            return $errors;
        }

        if ($longitude < -180 || $longitude > 180) {
            $errors[] = $this->translator->trans('invalidLongitude', [
                '%filename%' => $fileName,
                '%value%' => $longitude
            ], 'founds');
            return $errors;
        }

        // Prüfe auf 0,0 Koordinaten (ungültiger Standort)
        if ($latitude === 0.0 && $longitude === 0.0) {
            $errors[] = $this->translator->trans('nullIslandCoordinates', [
                '%filename%' => $fileName
            ], 'founds');
            return $errors;
        }

        // Wenn kein Datum vorhanden ist, verwende das aktuelle Datum
        if(!isset($exifData['DateTime'])) {
            $exifData['DateTime'] = (new DateTime())->format('Y:m:d H:i:s');
            $this->addFlash('info', $this->translator->trans('usingCurrentDate', [
                '%filename%' => $fileName
            ], 'founds'));
        }

        return $errors;
    }

    private function handleFileUpload($uploadedFile): string
    {
        $newFilename = uniqid() . '.' . $uploadedFile->guessExtension();
        $uploadDir   = $this->getParameter('uploads_directory');
        $filePath    = $uploadDir . '/' . $newFilename;

        $uploadedFile->move($uploadDir, $newFilename);

        return $filePath;
    }

    /**
     * Löscht eine Datei sicher, falls sie existiert
     */
    private function safeUnlink(?string $filePath): void
    {
        if ($filePath && file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * @throws Exception
     */
    private function getLocationData(GeoService $geoService, float $latitude, float $longitude): array
    {
        try {
            return $geoService->getLocationData($latitude, $longitude) ?? [];
        } catch(Exception $e) {
            $this->logger->error('Fehler bei der Standortbestimmung: ' . $e->getMessage(), [
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
            $this->addFlash('error', $this->translator->trans('noLocation', [], 'founds'));
            throw new Exception($this->translator->trans('noLocation', [], 'founds'));
        }
    }

    private function populatePhotoEntity(
        FoundsImage $photo,
        array       $exifData,
        array       $locationData,
        array       $utmCoordinates,
        string      $filePath,
        bool        $isPublic,
    ): void {

        $latitude  = $exifData['latitude'] ?? 0.0;
        $longitude = $exifData['longitude'] ?? 0.0;
        $distanceChurch = null;
        $distanceTown = null;
        $church = null;
        $town = null;
        $churchOrCenterName = 'unbekannt';
        $distance = null;

        $nearestChurch = $this->geoService->findNearestChurch($latitude, $longitude);
        $nearestTown   = $this->geoService->getNearestTown($latitude, $longitude);

        // Distanzen nur berechnen, wenn Ziel existiert
        if ($nearestChurch && isset($nearestChurch['latitude'], $nearestChurch['longitude'])) {
            try {
                $distanceChurch = $this->geoService->calculateDistance($latitude, $longitude, $nearestChurch['latitude'], $nearestChurch['longitude']);
                $church = 'Kirche: ' . $nearestChurch['name'];
            } catch (\Throwable $e) {
                $distanceChurch = null;
                $this->logger->warning('Fehler bei Distanzberechnung zur Kirche: ' . $e->getMessage());
            }
        }
        if ($nearestTown && isset($nearestTown['latitude'], $nearestTown['longitude'])) {
            try {
                $distanceTown = $this->geoService->calculateDistance($latitude, $longitude, $nearestTown['latitude'], $nearestTown['longitude']);
                $town = 'Ort: ' . $nearestTown['name'];
            } catch (\Throwable $e) {
                $distanceTown = null;
                $this->logger->warning('Fehler bei Distanzberechnung zum Ort: ' . $e->getMessage());
            }
        }

        // Entscheide, was näher ist
        if ($distanceChurch !== null && ($distanceTown === null || $distanceChurch < $distanceTown)) {
            $churchOrCenterName = $church;
            $distance = $distanceChurch;
        } elseif ($distanceTown !== null && ($distanceChurch === null || $distanceTown < $distanceChurch)) {
            $churchOrCenterName = $town;
            $distance = $distanceTown;
        } else {
            $this->addFlash('error', $this->translator->trans('noChurchOrTownFound', [], 'founds'));
        }

        if ($distance === null) {
            $this->addFlash('error', $this->translator->trans('noValidDistance', [], 'founds'));
        }

        // Robustere EXIF-Datumskonvertierung
        $dateTime = new \DateTime();
        if (isset($exifData['DateTime'])) {
            $dt = \DateTime::createFromFormat('Y:m:d H:i:s', $exifData['DateTime']);
            if ($dt !== false) {
                $dateTime = $dt;
            } else {
                $this->logger->warning('Ungültiges EXIF-Datum: ' . $exifData['DateTime']);
            }
        }

        // Adressdaten übersichtlich zuweisen
        $address = $locationData['address'] ?? [];
        $photo->latitude                 = $latitude;
        $photo->longitude                = $longitude;
        $photo->cameraModel              = $exifData['camera_model'] ?? null;
        $photo->exposureTime             = $exifData['exposure_time'] ?? null;
        $photo->fNumber                  = $exifData['f_number'] ?? null;
        $photo->iso                      = $exifData['iso'] ?? null;
        $photo->dateTime                 = $dateTime;
        $photo->filePath                 = basename($filePath);
        $photo->username                 = $this->getUserFullName();
        $photo->createdAt                = new \DateTime();
        $photo->utmX                     = $utmCoordinates['utmX'];
        $photo->utmY                     = $utmCoordinates['utmY'];
        $photo->parcel                   = $locationData['parcel'] ?? 'unbekannt';
        $photo->district                 = $address['city'] ?? null;
        $photo->county                   = $address['county'] ?? null;
        $photo->state                    = $address['state'] ?? null;
        $photo->nearestStreet            = $address['road'] ?? null;
        $photo->nearestTown              = $town;
        $photo->distanceToChurchOrCenter = $distance;
        $photo->churchOrCenterName       = $churchOrCenterName;
        $photo->setUser($this->getUser());
        /** @var User $user */
        $user = $this->getUser();
        $photo->user_uuid = $user?->getUuid();
        $photo->username = $user?->getEmail();
        $photo->isPublic  = $isPublic;

        // Gemarkung und Flurstück ermitteln: erst lokal, dann API als Fallback
        $gemarkung = null;
        $flurstueck = null;
        
        if (!empty($utmCoordinates['utmX']) && !empty($utmCoordinates['utmY'])) {
            $result = $this->localGemarkungService->findGemarkungAndFlurstueckByUTM($utmCoordinates['utmX'], $utmCoordinates['utmY']);
            $gemarkung = $result['gemarkung'];
            $flurstueck = $result['flurstueck'];
            
            $this->logger->info('Lokale Gemarkung- und Flurstück-Suche', [
                'utmX' => $utmCoordinates['utmX'],
                'utmY' => $utmCoordinates['utmY'],
                'gemarkung' => $gemarkung,
                'flurstueck' => $flurstueck
            ]);
            
            if (!$gemarkung) {
                // API als Fallback nur für Gemarkung
                $gemarkung = $this->geoService->getGemarkungByUTM($utmCoordinates['utmX'], $utmCoordinates['utmY']);
                $this->logger->info('Gemarkung-API Rückgabe', [
                    'utmX' => $utmCoordinates['utmX'],
                    'utmY' => $utmCoordinates['utmY'],
                    'gemarkung' => $gemarkung
                ]);
            }
        } else {
            $this->addFlash('warning', $this->translator->trans('noUtmCoordinates', [], 'founds'));
        }
        
        $photo->gemarkungName = $gemarkung['name'] ?? null;
        $photo->gemarkungNummer = $gemarkung['number'] ?? null;
        $photo->flurstueckName = $flurstueck['name'] ?? null;
        $photo->flurstueckNummer = $flurstueck['number'] ?? null;
    }


    #[Route('/images', name: 'image_list')]
    public function listImages(
        Request               $request,
        PaginatorInterface    $paginator,
        FoundsImageRepository $foundsImageRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $sortField = $request->query->get('sort', 'createdAt');
        $sortOrder = $request->query->get('order', 'desc');
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 50);
        $search = $request->query->get('search', '');

        // Hole alle Bilder
        $query = $foundsImageRepository->findAllFiltered($sortField, $sortOrder, $search, $this->getUser());
        $pagination = $paginator->paginate($query, $page, $limit);

        // Gruppiere Bilder nach Datum
        $groupedImages = [];
        foreach ($pagination->getItems() as $image) {
            $dateKey = $image->createdAt->format('Y-m-d');
            if (!isset($groupedImages[$dateKey])) {
                $groupedImages[$dateKey] = [
                    'date' => $image->createdAt,
                    'images' => []
                ];
            }

            $groupedImages[$dateKey]['images'][] = [
                'id' => $image->getId(),
                'name' => $image->getName(),
                'latitude' => $image->latitude,
                'longitude' => $image->longitude,
                'church_or_center_name' => $image->churchOrCenterName,
                'distanceToChurchOrCenter' => $image->distanceToChurchOrCenter,
                'nearestTown' => $image->nearestTown,
                'state' => $image->state,
                'county' => $image->county,
                'district' => $image->district,
                'parcel' => $image->parcel,
                'filePath' => $image->filePath,
                'hasCoordinates' => $image->latitude !== 0.0 && $image->longitude !== 0.0,
                'createdAt' => $image->createdAt,
                'utm' => ($image->utmY > 0.0 && $image->utmX > 0.0)
                    ? number_format($image->utmX, 2, '.', '') . ', ' . number_format($image->utmY, 2, '.', '')
                    : null,
                'csrf' => $this->csrfTokenManager->getToken('delete' . $image->getId()),
                'gemarkungName' => $image->gemarkungName,
                'gemarkungNummer' => $image->gemarkungNummer,
                'flurstueckName' => $image->flurstueckName,
                'flurstueckNummer' => $image->flurstueckNummer,
            ];
        }

        // Sortiere die Gruppen nach Datum (neueste zuerst)
        krsort($groupedImages);

        // Hole alle Projekte des Benutzers für das Dropdown
        $projects = $this->projectRepository->findByUser($this->getUser());

        return $this->render('founds/list.html.twig', [
            'pagination' => $pagination,
            'groupedImages' => $groupedImages,
            'sort' => $sortField,
            'order' => $sortOrder,
            'limit' => $limit,
            'projects' => $projects,
        ]);
    }

    #[Route('founds/gallery', name: 'found_gallery')]
    public function galeryAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $photos = $this->foundsImageRepository->findBy(['isPublic' => TRUE]);

        return $this->render(
            'founds/galery.html.twig',
            [
                'images' => $photos,
            ],
        );
    }

    #[Route('/generate-pdf/{date}', name: 'generate_pdf', methods: ['GET'])]
    public function generatePdf(
        string                $date,
        FoundsImageRepository $foundsImageRepository,
        PdfService           $pdfService,
        MapService           $mapService,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Konvertiere das Datum in DateTime Objekte für den Start und End des Tages
        $startDate = DateTime::createFromFormat('Y-m-d', $date)->setTime(0, 0, 0);
        $endDate = DateTime::createFromFormat('Y-m-d', $date)->setTime(23, 59, 59);
        
        // Hole alle Bilder des Tages
        $images = $foundsImageRepository->findByDateRange($startDate, $endDate);

        if(empty($images)) {
            throw $this->createNotFoundException('Keine Bilder für das angegebene Datum gefunden.');
        }

        // Berechne min/max UTM Koordinaten und generiere Karte
        $utmCoordinates = [
            'min_utmX' => null,
            'max_utmX' => null,
            'min_utmY' => null,
            'max_utmY' => null
        ];

        $markers = [];
        $latitudes = [];
        $longitudes = [];
        
        foreach ($images as $image) {
            if ($image->latitude && $image->longitude) {
                $markers[] = [$image->latitude, $image->longitude];
                $latitudes[] = $image->latitude;
                $longitudes[] = $image->longitude;
            }
            
            if ($image->utmX !== null) {
                if ($utmCoordinates['min_utmX'] === null || $image->utmX < $utmCoordinates['min_utmX']) {
                    $utmCoordinates['min_utmX'] = $image->utmX;
                }
                if ($utmCoordinates['max_utmX'] === null || $image->utmX > $utmCoordinates['max_utmX']) {
                    $utmCoordinates['max_utmX'] = $image->utmX;
                }
            }
            if ($image->utmY !== null) {
                if ($utmCoordinates['min_utmY'] === null || $image->utmY < $utmCoordinates['min_utmY']) {
                    $utmCoordinates['min_utmY'] = $image->utmY;
                }
                if ($utmCoordinates['max_utmY'] === null || $image->utmY > $utmCoordinates['max_utmY']) {
                    $utmCoordinates['max_utmY'] = $image->utmY;
                }
            }
        }

        // Generiere die Karte wenn Koordinaten vorhanden sind
        $mapFilename = null;
        if (!empty($markers)) {
            $centerLat = (min($latitudes) + max($latitudes)) / 2;
            $centerLon = (min($longitudes) + max($longitudes)) / 2;
            $mapFilename = $mapService->generateStaticMap($centerLat, $centerLon, $markers);
        }

        // Setze Standardwerte falls keine Koordinaten gefunden wurden
        $utmCoordinates['min_utmX'] = $utmCoordinates['min_utmX'] ?? 0;
        $utmCoordinates['max_utmX'] = $utmCoordinates['max_utmX'] ?? 0;
        $utmCoordinates['min_utmY'] = $utmCoordinates['min_utmY'] ?? 0;
        $utmCoordinates['max_utmY'] = $utmCoordinates['max_utmY'] ?? 0;

        // Generiere PDF
        $response = $pdfService->generatePdf(
            'pdf/upload_report.html.twig',
            [
                'images' => $images,
                'date' => $startDate,
                'min_utmX' => $utmCoordinates['min_utmX'],
                'max_utmX' => $utmCoordinates['max_utmX'],
                'min_utmY' => $utmCoordinates['min_utmY'],
                'max_utmY' => $utmCoordinates['max_utmY'],
                'mapFilename' => $mapFilename,
                'tempMapDir' => $mapService->getTempMapPath('')
            ],
            sprintf('Fundmeldungen-%s.pdf', $date)
        );

        // Cleanup
        $mapService->cleanupTempMaps();

        return $response;
    }

    #[Route('/generate-word/{date}', name: 'generate_word', methods: ['GET'])]
    public function generateWord(
        string                $date,
        FoundsImageRepository $foundsImageRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Konvertiere das Datum in DateTime Objekte für den Start und End des Tages
        $startDate = DateTime::createFromFormat('Y-m-d', $date)->setTime(0, 0, 0);
        $endDate = DateTime::createFromFormat('Y-m-d', $date)->setTime(23, 59, 59);

        // Hole alle Bilder des Tages
        $images = $foundsImageRepository->findByDateRange($startDate, $endDate);

        if(empty($images)) {
            throw $this->createNotFoundException('Keine Bilder für das angegebene Datum gefunden.');
        }

        // Berechne min/max UTM Koordinaten für Word-Template
        $utmCoordinates = [
            'min_utmX' => null,
            'max_utmX' => null,
            'min_utmY' => null,
            'max_utmY' => null
        ];

        foreach ($images as $image) {
            if ($image->utmX !== null) {
                if ($utmCoordinates['min_utmX'] === null || $image->utmX < $utmCoordinates['min_utmX']) {
                    $utmCoordinates['min_utmX'] = $image->utmX;
                }
                if ($utmCoordinates['max_utmX'] === null || $image->utmX > $utmCoordinates['max_utmX']) {
                    $utmCoordinates['max_utmX'] = $image->utmX;
                }
            }
            if ($image->utmY !== null) {
                if ($utmCoordinates['min_utmY'] === null || $image->utmY < $utmCoordinates['min_utmY']) {
                    $utmCoordinates['min_utmY'] = $image->utmY;
                }
                if ($utmCoordinates['max_utmY'] === null || $image->utmY > $utmCoordinates['max_utmY']) {
                    $utmCoordinates['max_utmY'] = $image->utmY;
                }
            }
        }

        // Setze Standardwerte falls keine Koordinaten gefunden wurden
        $utmCoordinates['min_utmX'] = $utmCoordinates['min_utmX'] ?? 0;
        $utmCoordinates['max_utmX'] = $utmCoordinates['max_utmX'] ?? 0;
        $utmCoordinates['min_utmY'] = $utmCoordinates['min_utmY'] ?? 0;
        $utmCoordinates['max_utmY'] = $utmCoordinates['max_utmY'] ?? 0;

        $html = $this->renderView('word/upload_report.html.twig', [
            'images' => $images,
            'date' => $startDate,
            'min_utmX' => $utmCoordinates['min_utmX'],
            'max_utmX' => $utmCoordinates['max_utmX'],
            'min_utmY' => $utmCoordinates['min_utmY'],
            'max_utmY' => $utmCoordinates['max_utmY']
        ]);

        $response = new Response($html);
        $response->headers->set('Content-Type', 'application/vnd.ms-word');
        $response->headers->set('Content-Disposition', 'attachment;filename="fundmeldung.doc"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    #[Route('/found/{id}/delete', name: 'found_delete', methods: ['POST'])]
    public function delete(
        Request                   $request,
        CsrfTokenManagerInterface $csrfTokenManager,
        int                       $id,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $entity = $this->foundsImageRepository->find($id);

        if(!$entity) {
            return $this->json([
                'success' => false,
                'message' => $this->translator->trans('delete.foundNotFound', [], 'founds')
            ], 404);
        }

        if(!$this->isCsrfTokenValid('delete' . $entity->getId(), $request->request->get('_token'))) {
            return $this->json([
                'success' => false,
                'message' => $this->translator->trans('delete.invalidCSRFToken', [], 'founds')
            ], 400);
        }

        $uploadDirectory = $this->getParameter('uploads_directory');
        $filePath = $uploadDirectory . '/' . $entity->filePath;
        if(file_exists($filePath)) {
            unlink($filePath);
        }

        $this->foundsImageRepository->remove($entity, TRUE);

        return $this->json([
            'success' => true,
            'message' => $this->translator->trans('delete.success', [], 'founds')
        ]);
    }

    #[Route('/found/bulk-delete', name: 'found_bulk_delete', methods: ['POST'])]
    public function bulkDelete(
        Request                   $request,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $ids = $request->request->all('ids');
        $token = $request->request->get('_token');

        if (empty($ids)) {
            return $this->json([
                'success' => false,
                'message' => $this->translator->trans('bulkDelete.noImagesSelected', [], 'founds')
            ], 400);
        }

        // CSRF-Token validieren
        if (!$this->isCsrfTokenValid('bulk_delete', $token)) {
            return $this->json([
                'success' => false,
                'message' => $this->translator->trans('delete.invalidCSRFToken', [], 'founds')
            ], 400);
        }

        $deletedCount = 0;
        $errors = [];
        $uploadDirectory = $this->getParameter('uploads_directory');

        foreach ($ids as $id) {
            $entity = $this->foundsImageRepository->find($id);
            
            if (!$entity) {
                $errors[] = "Bild mit ID $id nicht gefunden.";
                continue;
            }

            // Prüfe, ob der Benutzer das Recht hat, dieses Bild zu löschen
            /** @var User $user */
            $user = $this->getUser();
            if ($entity->user_uuid !== $user?->getUuid()) {
                $errors[] = "Keine Berechtigung zum Löschen von Bild $id.";
                continue;
            }

            // Lösche die Datei
            $filePath = $uploadDirectory . '/' . $entity->filePath;
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Lösche aus der Datenbank
            $this->foundsImageRepository->remove($entity, false);
            $deletedCount++;
        }

        // Flush alle Änderungen
        $this->foundsImageRepository->getEntityManager()->flush();

        $message = "$deletedCount Bilder wurden erfolgreich gelöscht.";
        if (!empty($errors)) {
            $message .= " Fehler: " . implode(', ', $errors);
        }

        return $this->json([
            'success' => true,
            'message' => $message,
            'deletedCount' => $deletedCount,
            'errors' => $errors
        ]);
    }

    #[Route('/found/bulk-assign-project', name: 'found_bulk_assign_project', methods: ['POST'])]
    public function bulkAssignProject(
        Request                   $request,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $ids = $request->request->all('ids');
        $projectId = $request->request->get('project_id');
        $token = $request->request->get('_token');

        if (empty($ids)) {
            return $this->json([
                'success' => false,
                'message' => $this->translator->trans('bulkAssign.noImagesSelected', [], 'founds')
            ], 400);
        }

        if (empty($projectId)) {
            return $this->json([
                'success' => false,
                'message' => $this->translator->trans('bulkAssign.noProjectSelected', [], 'founds')
            ], 400);
        }

        // CSRF-Token validieren
        if (!$this->isCsrfTokenValid('bulk_assign_project', $token)) {
            return $this->json([
                'success' => false,
                'message' => $this->translator->trans('delete.invalidCSRFToken', [], 'founds')
            ], 400);
        }

        // Projekt laden und Berechtigung prüfen
        $project = $this->projectRepository->find($projectId);
        if (!$project) {
            return $this->json([
                'success' => false,
                'message' => $this->translator->trans('bulkAssign.projectNotFound', [], 'founds')
            ], 404);
        }

        if (!$project->getUsers()->contains($this->getUser())) {
            return $this->json([
                'success' => false,
                'message' => $this->translator->trans('bulkAssign.noProjectAccess', [], 'founds')
            ], 403);
        }

        $assignedCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            $entity = $this->foundsImageRepository->find($id);
            
            if (!$entity) {
                $errors[] = "Bild mit ID $id nicht gefunden.";
                continue;
            }

            // Prüfe, ob der Benutzer das Recht hat, dieses Bild zu bearbeiten
            /** @var User $user */
            $user = $this->getUser();
            if ($entity->user_uuid !== $user?->getUuid()) {
                $errors[] = "Keine Berechtigung zum Bearbeiten von Bild $id.";
                continue;
            }

            // Weise das Bild dem Projekt zu
            $entity->setProject($project);
            $this->foundsImageRepository->save($entity, false);
            $assignedCount++;
        }

        // Flush alle Änderungen
        $this->foundsImageRepository->getEntityManager()->flush();

        $message = "$assignedCount Bilder wurden erfolgreich dem Projekt '{$project->getName()}' zugeordnet.";
        if (!empty($errors)) {
            $message .= " Fehler: " . implode(', ', $errors);
        }

        return $this->json([
            'success' => true,
            'message' => $message,
            'assignedCount' => $assignedCount,
            'errors' => $errors
        ]);
    }

    /**
     * convert datetime string to DateTime Object
     *
     * @param string $dateString
     *
     * @return DateTime
     */
    private function DateStringToDateTime(string $dateString): DateTime
    {
        $formattedDateString = str_replace(':', '-', substr($dateString, 0, 10)) . substr($dateString, 10);
        $dateTime            = DateTime::createFromFormat('Y-m-d H:i:s', $formattedDateString);

        if($dateTime === FALSE) {
            $dateTime = new DateTime();
        }

        return $dateTime;
    }
}
