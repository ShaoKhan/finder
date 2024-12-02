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
        Request $request,
        GeoService $geoService,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $locationData = [];
        $form = $this->createForm(FoundsImageUploadType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFiles = $form->get('files')->getData();
            $isPublic = $form->get('isPublic')->getData();

            if (!$uploadedFiles) {
                $this->addFlash('error', $this->translator->trans('form.noFilesUploaded', [], 'founds'));
                return $this->redirectToRoute('photo_upload');
            }

            foreach ($uploadedFiles as $uploadedFile) {
                try {
                    $filePath = $this->handleFileUpload($uploadedFile);

                    $exifData = $this->imageService->extractExifData($filePath);
                    if ($exifData === []) {
                        $this->addFlash('error', $this->translator->trans('noExifData', [], 'founds'));
                    }

                    $latitude = $exifData['latitude'] ?? 0.0;
                    $longitude = $exifData['longitude'] ?? 0.0;

                    if ($latitude === 0.0 || $longitude === 0.0) {
                        $this->addFlash('error', $this->translator->trans('noLongLat', [], 'founds'));
                    }

                    if ($longitude > 0 && $latitude > 0) {
                        $locationData = $this->getLocationData($geoService, $latitude, $longitude);
                    }

                    if ($locationData === []) {
                        $this->addFlash('error', $this->translator->trans('noLocationData', [], 'founds'));
                    }

                    $utmCoordinates = $geoService->convertToUTM33($latitude, $longitude);
                    if ($utmCoordinates === []) {
                        $this->addFlash('error', $this->translator->trans('noUTM33Data', [], 'founds'));
                    }

                    $photo = new FoundsImage();
                    $this->populatePhotoEntity($photo, $exifData, $locationData, $utmCoordinates, $filePath, $isPublic);

                    $this->foundsImageRepository->save($photo, true);
                } catch (Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            }

            $this->addFlash('success', $this->translator->trans('photosUploadSuccess', [], 'founds'));
            return $this->redirectToRoute('photo_upload');
        }

        return $this->render('founds/upload.html.twig', [
            'form' => $form->createView(),
        ]);
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

    #[NoReturn] private function populatePhotoEntity(
        FoundsImage $photo,
        array       $exifData,
        array       $locationData,
        array       $utmCoordinates,
        string      $filePath,
        bool        $isPublic,
    ): void {

        $latitude       = $exifData['latitude'] ?? 0.0;
        $longitude      = $exifData['longitude'] ?? 0.0;
        $distanceChurch = NULL;
        $distanceTown   = NULL;
        $distance       = NULL;
        $nearestChurch  = $this->geoService->findNearestChurch($latitude, $longitude);
        $nearestTown    = $this->geoService->getNearestTown($latitude, $longitude);

        if($nearestChurch !== NULL) {
            $distanceChurch     = $this->geoService->calculateDistance($latitude, $longitude, $nearestChurch['latitude'], $nearestChurch['longitude']);
            $churchOrCenterName = 'Kirche: ' . $nearestChurch['name'];
        } elseif($nearestTown !== NULL) {
            $this->addFlash('notice', $this->translator->trans('noChurchFound', [], 'founds'));
            $distanceTown       = $this->geoService->calculateDistance($latitude, $longitude, $nearestTown['latitude'], $nearestTown['longitude']);
            $churchOrCenterName = 'Ort: ' . $nearestTown['name'];
        } else {
            $this->addFlash('notice', $this->translator->trans('noChurchOrTownFound', [], 'founds'));
            $churchOrCenterName = 'unknown';
        }

        if($distanceChurch !== NULL && $distanceTown !== NULL) {
            $distance = min($distanceChurch, $distanceTown);
        } elseif($distanceChurch !== NULL) {
            $distance = $distanceChurch;
        } elseif($distanceTown !== NULL) {
            $distance = $distanceTown;
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
        $photo->parcel                   = $locationData['parcel'] ?? 'Unknown';
        $photo->district                 = $locationData['address']['city'] ?? NULL;
        $photo->county                   = $locationData['address']['county'] ?? NULL;
        $photo->state                    = $locationData['address']['state'] ?? NULL;
        $photo->nearestStreet            = $locationData['address']['road'] ?? NULL;
        $photo->nearestTown              = $locationData['nearestTown'] ?? 'Unknown';
        $photo->distanceToChurchOrCenter = $distance ?? NULL;
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
        $sortField  = $request->query->get('sort', 'name');
        $sortOrder  = $request->query->get('order', 'asc');
        $page       = $request->query->getInt('page', 1);
        $limit      = $request->query->getInt('limit', 10);
        $search     = $request->query->get('search', '');
        $query      = $foundsImageRepository->findAllFiltered($sortField, $sortOrder, $search, $this->getUser());
        $pagination = $paginator->paginate(
            $query,
            $page,
            $limit,
        );

        $images = [];
        /** @var FoundsImage $image */
        foreach($pagination->getItems() as $image) {
            $images[] = [
                'id'                       => $image->getId(),
                'name'                     => $image->getName(),
                'latitude'                 => $image->latitude,
                'longitude'                => $image->longitude,
                'church_or_center_name'    => $image->churchOrCenterName,
                'distanceToChurchOrCenter' => $image->distanceToChurchOrCenter,
                'nearestTown'              => $image->nearestTown,
                'state'                    => $image->state,
                'county'                   => $image->county,
                'district'                 => $image->district,
                'parcel'                   => $image->parcel,
                'filePath'                 => $image->filePath,
                'hasCoordinates'           => $image->latitude !== 0.0 && $image->longitude !== 0.0,
                'createdAt'                => $image->createdAt,
                'utm'                      => ($image->utmY > 0.0 && $image->utmX > 0.0)
                    ? number_format($image->utmX, 2, '.', '') . ', ' . number_format($image->utmY, 2, '.', '')
                    : NULL,
                'csrf'                     => $this->csrfTokenManager->getToken('delete' . $image->getId()),
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

    #[Route('/generate-pdf/{id}', name: 'generate_pdf', methods: ['GET'])]
    public function generatePdf(
        int                   $id,
        FoundsImageRepository $foundsImageRepository,
        PdfService            $pdfService,
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_USER');
        $image = $foundsImageRepository->find($id);

        if(!$image) {
            throw $this->createNotFoundException('Das Bild mit der angegebenen ID wurde nicht gefunden.');
        }

        return $pdfService->generatePdf('pdf/upload_report.html.twig', [
            'image' => $image,
        ],                              sprintf('upload-report-%d.pdf', $id));
    }

    #[Route('/generate-word/{id}', name: 'generate_word', methods: ['GET'])]
    public function generateWord(
        int                   $id,
        FoundsImageRepository $foundsImageRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $image = $foundsImageRepository->find($id);

        if(!$image) {
            throw $this->createNotFoundException('Das Bild mit der angegebenen ID wurde nicht gefunden.');
        }

        // HTML-Inhalt für Word
        $html = $this->renderView('word/upload_report.html.twig', [
            'image' => $image,
        ]);

        // Header für Word-Dokument setzen
        $response = new Response($html);
        $response->headers->set('Content-Type', 'application/msword');
        $response->headers->set('Content-Disposition', 'attachment; filename="upload-report-' . $id . '.doc"');

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
