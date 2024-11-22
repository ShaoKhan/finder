<?php

declare(strict_types = 1);

namespace App\Controller;


use App\Entity\FoundsImage;
use App\Form\FoundsImageUploadType;
use App\Repository\FoundsImageRepository;
use App\Service\GeoService;
use App\Service\ImageService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FoundsController extends FinderAbstractController
{

    public function __construct(
        private readonly GeoService            $geoService,
        private readonly ImageService          $imageService,
        private readonly FoundsImageRepository $foundsImageRepository,
    ) {
    }

    #[Route('/founds/index', name: 'founds_index')]
    public function index(): Response
    {
        return $this->render('founds/index.html.twig');
    }

    #[Route('/photo/upload', name: 'photo_upload')]
    public function upload(Request $request, EntityManagerInterface $em): Response
    {
        $photo = new FoundsImage();
        $form  = $this->createForm(FoundsImageUploadType::class, $photo);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();
            $username     = $this->getUser()?->getUsername() ?? 'anonymous';

            if($uploadedFile) {
                $newFilename = uniqid() . '.' . $uploadedFile->guessExtension();

                try {
                    $filePath = $this->getParameter('uploads_directory') . '/' . $newFilename;
                    $uploadedFile->move($this->getParameter('uploads_directory'), $newFilename);

                    // EXIF-Daten extrahieren
                    $exifData = $this->imageService->extractExifData($filePath);

                    // Position aus EXIF-Daten verwenden, falls vorhanden
                    $latitude  = $exifData['latitude'] ?? 0.0;
                    $longitude = $exifData['longitude'] ?? 0.0;

                    // Fetch UTM Coordinates
                    if($latitude !== 0.0 && $longitude !== 0.0) {
                        $utmCoordinates = $this->geoService->convertToUTM33($latitude, $longitude);
                    }

                    // Fetch Location Data
                    $locationData = $this->geoService->getLocationData($latitude, $longitude);
                    $district     = $locationData['address']['city']
                                    ?? $locationData['address']['town']
                                       ?? $locationData['address']['village']
                                          ?? NULL;
                    $parcel       = 'unknown until ALKIS or similar is implemented';

                    // Find nearest church and calculate distance
                    $nearestPlace       = $this->geoService->findNearestChurch($latitude, $longitude);
                    $nearestTown        = $this->geoService->getNearestTown($latitude, $longitude);
                    $distance           = NULL;
                    $churchOrCenterName = 'Ortskern';

                    if(!empty($nearestPlace)) {
                        $nearest            = $nearestPlace[0];
                        $distance           = $this->geoService->calculateDistance($latitude, $longitude, $nearest['lat'], $nearest['lon']);
                        $churchOrCenterName = $nearest['tags']['name'] ?? 'Unknown';
                    }

                    $photo->latitude                 = $latitude;
                    $photo->longitude                = $longitude;
                    $photo->cameraModel              = $exifData['camera_model'] ?? NULL;
                    $photo->exposureTime             = $exifData['exposure_time'] ?? NULL;
                    $photo->fNumber                  = $exifData['f_number'] ?? NULL;
                    $photo->iso                      = $exifData['iso'] ?? NULL;
                    $photo->dateTime                 = isset($exifData['DateTime']) ? \DateTime::createFromFormat('Y:m:d H:i:s', $exifData['DateTime']) : new \DateTime();
                    $photo->filePath                 = $newFilename;
                    $photo->username                 = $username;
                    $photo->createdAt                = new DateTime();
                    $photo->utmX                     = $utmCoordinates['utmX'] ?? 0.0;
                    $photo->utmY                     = $utmCoordinates['utmY'] ?? 0.0;
                    $photo->parcel                   = $parcel;
                    $photo->district                 = $district;
                    $photo->county                   = $locationData['address']['county'] ?? NULL;
                    $photo->state                    = $locationData['address']['state'] ?? NULL;
                    $photo->nearestStreet            = $locationData['address']['road'] ?? NULL;
                    $photo->nearestTown              = $nearestTown;
                    $photo->distanceToChurchOrCenter = $distance;
                    $photo->churchOrCenterName       = $churchOrCenterName;
                    $photo->isPublic                 = $form->get('isPublic')->getData();

                    $em->persist($photo);
                    $em->flush();

                    $this->addFlash('success', 'Photo uploaded successfully with metadata.');
                    return $this->redirectToRoute('photo_upload');
                }
                catch(FileException $e) {
                    $this->addFlash('error', 'File upload failed: ' . $e->getMessage());
                }
                catch(\Exception $e) {
                    $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
                }
            }
        }

        return $this->render('founds/upload.html.twig', [
            'form' => $form->createView(),
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
        $dateTime            = \DateTime::createFromFormat('Y-m-d H:i:s', $formattedDateString);

        if($dateTime === FALSE) {
            $dateTime = new \DateTime();
        }

        return $dateTime;
    }

    #[Route('/images', name: 'image_list')]
    public function listImages(
        Request $request,
        PaginatorInterface $paginator,
        FoundsImageRepository $foundsImageRepository,
    ): Response {
        $sortField = $request->query->get('sort', 'name'); // Standard: Name
        $sortOrder = $request->query->get('order', 'asc'); // Standard: Aufsteigend
        $page      = $request->query->getInt('page', 1);   // Standard: Seite 1
        $limit     = $request->query->getInt('limit', 10); // Standard: 10 Einträge pro Seite
        $search = $request->query->get('search', '');

        $query = $foundsImageRepository->findAllFiltered($sortField, $sortOrder, $search);

        // Paginierung
        $pagination = $paginator->paginate(
            $query,
            $page,
            $limit,
        );

        // Kartenrelevante Daten vorbereiten (Koordinaten prüfen)
        $images = [];
        /** @var FoundsImage $image */
        foreach ($pagination->getItems() as $image) {
            $images[] = [
                'id' => $image->getId(),
                'name' => $image->getName(),
                'latitude' => $image->latitude,
                'longitude' => $image->longitude,
                'distanceToChurchOrCenter' => $image->distanceToChurchOrCenter,
                'nearestTown' => $image->nearestTown,
                'filePath' => $image->filePath,
                'hasCoordinates' => $image->latitude !== 0.0 && $image->longitude !== 0.0,
                'createdAt' => $image->createdAt,
                'utm' => ($image->utmY > 0.0 && $image->utmX > 0.0)
                    ? number_format($image->utmX, 2, '.', '') . ', ' . number_format($image->utmY, 2, '.', '')
                    : null,
            ];
        }

        return $this->render('founds/list.html.twig', [
            'pagination' => $pagination,
            'images'     => $images,
            'sort'       => $sortField,
            'order'      => $sortOrder,
            'limit'      => $limit,
        ]);
    }


    #[Route('founds/gallery', name: 'found_gallery')]
    public function galeryAction()
    {
        $photos = $this->foundsImageRepository->findBy(['isPublic' => TRUE]);

        return $this->render(
            'founds/galery.html.twig',
            [
                'images' => $photos,
            ],
        );
    }
}
