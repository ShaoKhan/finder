<?php

declare(strict_types = 1);

namespace App\Controller;


use App\Entity\FoundsImage;
use App\Form\FoundsImageUploadType;
use App\Repository\FoundsImageRepository;
use App\Service\GeoService;
use App\Service\ImageService;
use App\Service\PdfService;
use DateTime;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FoundsController extends FinderAbstractController
{

    public function __construct(
        private readonly GeoService                $geoService,
        private readonly ImageService              $imageService,
        private readonly FoundsImageRepository     $foundsImageRepository,
        private readonly TranslatorInterface       $translator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
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
            $allowedTypes = ['image/jpeg', 'image/jpg'];

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
                    
                    // Extrahiere und validiere EXIF-Daten
                    $exifData = $this->imageService->extractExifData($tempFilePath);
                    $validationErrors = $this->validateExifData($exifData, $fileName);
                    
                    if (!empty($validationErrors)) {
                        $errors[$fileName] = array_merge($errors[$fileName] ?? [], $validationErrors);
                        // Lösche die temporäre Datei
                        if (file_exists($tempFilePath)) {
                            unlink($tempFilePath);
                        }
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
                        if (file_exists($tempFilePath)) {
                            unlink($tempFilePath);
                        }
                    }

                } catch (Exception $e) {
                    $errors[$fileName][] = $e->getMessage();
                    if ($tempFilePath && file_exists($tempFilePath)) {
                        unlink($tempFilePath);
                    }
                }
            }

            // Zeige Erfolgs- und Fehlermeldungen
            if($successCount > 0) {
                $this->addFlash(
                    'success', 
                    $this->translator->trans('photosUploadSuccess', 
                    ['%count%' => $successCount], 
                    'founds')
                );
            }

            if (!empty($errors)) {
                foreach($errors as $fileName => $fileErrors) {
                    $errorMessage = "<strong>$fileName</strong><ul class='error-list'>";
                    foreach($fileErrors as $error) {
                        $errorMessage .= "<li>$error</li>";
                    }
                    $errorMessage .= "</ul>";
                    $this->addFlash('error', $errorMessage);
                }
            }

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
     * @throws Exception
     */
    private function getLocationData(GeoService $geoService, float $latitude, float $longitude): array
    {
        try {
            return $geoService->getLocationData($latitude, $longitude) ?? [];
        }
        catch(Exception) {
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

        $latitude       = $exifData['latitude'] ?? 0.0;
        $longitude      = $exifData['longitude'] ?? 0.0;
        $distance       = NULL;
        $church         = NULL;
        $town           = NULL;
        $nearestChurch  = $this->geoService->findNearestChurch($latitude, $longitude);
        $nearestTown    = $this->geoService->getNearestTown($latitude, $longitude);

        $distanceChurch = $this->geoService->calculateDistance($latitude, $longitude, $nearestChurch['latitude'], $nearestChurch['longitude']);
        $distanceTown   = $this->geoService->calculateDistance($latitude, $longitude, $nearestTown['latitude'], $nearestTown['longitude']);

        if($nearestChurch !== NULL) {
            $distanceChurch = $this->geoService->calculateDistance($latitude, $longitude, $nearestChurch['latitude'], $nearestChurch['longitude']);
            $church         = 'Kirche: ' . $nearestChurch['name'];
        }
        if($nearestTown !== NULL) {
            $distanceTown = $this->geoService->calculateDistance($latitude, $longitude, $nearestTown['latitude'], $nearestTown['longitude']);
            $town         = 'Ort: ' . $nearestTown['name'];
        }


        if($distanceChurch < $distanceTown) {
            $churchOrCenterName = $church;
            $distance           = $distanceChurch;
        } elseif($distanceChurch > $distanceTown) {
            $churchOrCenterName = $town;
            $distance           = $distanceTown;
        } else {
            $churchOrCenterName = 'unbekannt';
            $this->addFlash('error', $this->translator->trans('noChurchOrTownFound', [], 'founds'));
        }

        if($distance === NULL) {
            $this->addFlash('error', $this->translator->trans('noValidDistance', [], 'founds'));
        }

        $photo->latitude                 = $latitude;
        $photo->longitude                = $longitude;
        $photo->cameraModel              = $exifData['camera_model'] ?? NULL;
        $photo->exposureTime             = $exifData['exposure_time'] ?? NULL;
        $photo->fNumber                  = $exifData['f_number'] ?? NULL;
        $photo->iso                      = $exifData['iso'] ?? NULL;
        $photo->dateTime                 = isset($exifData['DateTime'])
            ? DateTime::createFromFormat('Y:m:d H:i:s', $exifData['DateTime'])
            : new DateTime();
        $photo->filePath                 = basename($filePath);
        $photo->username                 = $this->getUserFullName();
        $photo->createdAt                = new DateTime();
        $photo->utmX                     = $utmCoordinates['utmX'];
        $photo->utmY                     = $utmCoordinates['utmY'];
        $photo->parcel                   = $locationData['parcel'] ?? 'unbekannt';
        $photo->district                 = $locationData['address']['city'] ?? NULL;
        $photo->county                   = $locationData['address']['county'] ?? NULL;
        $photo->state                    = $locationData['address']['state'] ?? NULL;
        $photo->nearestStreet            = $locationData['address']['road'] ?? NULL;
        $photo->nearestTown              = $town;
        $photo->distanceToChurchOrCenter = $distance;
        $photo->churchOrCenterName       = $churchOrCenterName;
        $photo->setUser($this->getUser());
        $photo->user_uuid = $this->getUser()->getUuid();
        $photo->isPublic  = $isPublic;

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
            ];
        }

        // Sortiere die Gruppen nach Datum (neueste zuerst)
        krsort($groupedImages);

        return $this->render('founds/list.html.twig', [
            'pagination' => $pagination,
            'groupedImages' => $groupedImages,
            'sort' => $sortField,
            'order' => $sortOrder,
            'limit' => $limit,
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

        // Berechne min/max UTM Koordinaten
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

        // Generiere PDF
        $html = $this->renderView('pdf/upload_report.html.twig', [
            'images' => $images,
            'date' => $startDate,
            'min_utmX' => $utmCoordinates['min_utmX'],
            'max_utmX' => $utmCoordinates['max_utmX'],
            'min_utmY' => $utmCoordinates['min_utmY'],
            'max_utmY' => $utmCoordinates['max_utmY']
        ]);

        return $pdfService->generatePdf(
            'pdf/upload_report.html.twig',
            [
                'images' => $images,
                'date' => $startDate,
                'min_utmX' => $utmCoordinates['min_utmX'],
                'max_utmX' => $utmCoordinates['max_utmX'],
                'min_utmY' => $utmCoordinates['min_utmY'],
                'max_utmY' => $utmCoordinates['max_utmY']
            ],
            sprintf('Fundmeldungen-%s.pdf', $date)
        );
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
            $this->addFlash('error', $this->translator->trans('delete.foundNotFound', [], 'founds'));
            return $this->redirectToRoute('image_list');
        }

        if(!$this->isCsrfTokenValid('delete' . $entity->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('delete.invalidCSRFToken', [], 'founds'));
            return $this->redirectToRoute('image_list');
        }

        $uploadDirectory = $this->getParameter('uploads_directory');
        $filePath        = $uploadDirectory . '/' . $entity->filePath;
        if(file_exists($filePath)) {
            unlink($filePath);
        }

        $this->foundsImageRepository->remove($entity, TRUE);

        $this->addFlash('success', $this->translator->trans('delete.success', [], 'founds'));
        return $this->redirectToRoute('image_list');
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
