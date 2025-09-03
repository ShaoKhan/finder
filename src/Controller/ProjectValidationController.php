<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/project-validation')]
class ProjectValidationController extends AbstractController
{
    #[Route('/check-name', name: 'project_check_name', methods: ['POST'])]
    public function checkName(Request $request, ProjectRepository $projectRepository): JsonResponse
    {
        $name = $request->request->get('name');
        $excludeId = $request->request->get('excludeId'); // Für Bearbeitung, um aktuelles Projekt auszuschließen
        
        if (empty($name)) {
            return new JsonResponse([
                'valid' => false,
                'message' => 'Projektname darf nicht leer sein.'
            ]);
        }

        if (strlen($name) < 3) {
            return new JsonResponse([
                'valid' => false,
                'message' => 'Projektname muss mindestens 3 Zeichen lang sein.'
            ]);
        }

        if (strlen($name) > 255) {
            return new JsonResponse([
                'valid' => false,
                'message' => 'Projektname darf maximal 255 Zeichen lang sein.'
            ]);
        }

        // Prüfe, ob der Name bereits existiert
        $existingProject = $projectRepository->findByName($name);
        
        if ($existingProject && (!$excludeId || $existingProject->getId() != $excludeId)) {
            return new JsonResponse([
                'valid' => false,
                'message' => 'Ein Projekt mit diesem Namen existiert bereits.'
            ]);
        }

        return new JsonResponse([
            'valid' => true,
            'message' => 'Projektname ist verfügbar.'
        ]);
    }
}
