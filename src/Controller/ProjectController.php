<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use App\Entity\FoundsImage;
use App\Entity\User;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use App\Repository\FoundsImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/projects')]
class ProjectController extends AbstractController
{
    #[Route('', name: 'project_list', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository): Response
    {
        $projects = $projectRepository->findByUser($this->getUser());

        return $this->render('project/index.html.twig', [
            'projects' => $projects,
        ]);
    }

    #[Route('/create', name: 'project_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $project = new Project();
        $project->addUser($this->getUser());

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($project);
            $entityManager->flush();

            $this->addFlash('success', 'Projekt wurde erfolgreich erstellt.');

            return $this->redirectToRoute('project_list');
        }

        return $this->render('project/create.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'project_show', methods: ['GET'])]
    public function show(Project $project): Response
    {
        // Prüfen, ob der aktuelle User Zugriff auf das Projekt hat
        if (!$project->getUsers()->contains($this->getUser())) {
            throw $this->createAccessDeniedException('Sie haben keinen Zugriff auf dieses Projekt.');
        }

        return $this->render('project/show.html.twig', [
            'project' => $project,
        ]);
    }

    #[Route('/{id}/edit', name: 'project_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        // Prüfen, ob der aktuelle User Zugriff auf das Projekt hat
        if (!$project->getUsers()->contains($this->getUser())) {
            throw $this->createAccessDeniedException('Sie haben keinen Zugriff auf dieses Projekt.');
        }

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Projekt wurde erfolgreich aktualisiert.');

            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'project_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Project $project, 
        EntityManagerInterface $entityManager,
        FoundsImageRepository $foundsImageRepository
    ): Response {
        // Prüfen, ob der aktuelle User Zugriff auf das Projekt hat
        if (!$project->getUsers()->contains($this->getUser())) {
            throw $this->createAccessDeniedException('Sie haben keinen Zugriff auf dieses Projekt.');
        }

        if ($this->isCsrfTokenValid('delete' . $project->getId(), $request->request->get('_token'))) {
            // WICHTIG: Vor dem Löschen des Projekts alle Verknüpfungen zu Fundmeldungen auflösen
            $foundsImages = $project->getFoundsImages();
            foreach ($foundsImages as $foundImage) {
                // Entferne die Verknüpfung zum Projekt (setze project auf null)
                $foundImage->setProject(null);
                $foundsImageRepository->save($foundImage, false);
            }

            // Jetzt kann das Projekt sicher gelöscht werden
            $entityManager->remove($project);
            $entityManager->flush();

            $this->addFlash('success', 'Projekt wurde erfolgreich gelöscht. Die Fundmeldungen wurden aus dem Projekt entfernt, aber nicht gelöscht.');
        }

        return $this->redirectToRoute('project_list');
    }



    #[Route('/{id}/founds/bulk-delete', name: 'project_founds_bulk_delete', methods: ['POST'])]
    public function bulkDeleteFounds(
        Request $request,
        Project $project,
        FoundsImageRepository $foundsImageRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        // Prüfen, ob der aktuelle User Zugriff auf das Projekt hat
        if (!$project->getUsers()->contains($this->getUser())) {
            throw $this->createAccessDeniedException('Sie haben keinen Zugriff auf dieses Projekt.');
        }

        $ids = $request->request->all('ids');
        $token = $request->request->get('_token');

        if (empty($ids)) {
            return $this->json([
                'success' => false,
                'message' => 'Keine Fundmeldungen zum Löschen ausgewählt.'
            ], 400);
        }

        // CSRF-Token validieren
        if (!$this->isCsrfTokenValid('bulk_delete_project_founds', $token)) {
            return $this->json([
                'success' => false,
                'message' => 'CSRF-Token nicht valide'
            ], 400);
        }

        $deletedCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            $entity = $foundsImageRepository->find($id);
            
            if (!$entity) {
                $errors[] = "Fundmeldung mit ID $id nicht gefunden.";
                continue;
            }

            // Prüfe, ob die Fundmeldung zu diesem Projekt gehört
            if ($entity->getProject() !== $project) {
                $errors[] = "Fundmeldung $id gehört nicht zu diesem Projekt.";
                continue;
            }

            // Prüfe, ob der Benutzer das Recht hat, diese Fundmeldung zu löschen
            /** @var User $user */
            $user = $this->getUser();
            if ($entity->user_uuid !== $user?->getUuid()) {
                $errors[] = "Keine Berechtigung zum Löschen von Fundmeldung $id.";
                continue;
            }

            // Entferne die Fundmeldung aus dem Projekt (setze project auf null)
            // WICHTIG: Die Fundmeldung wird NICHT gelöscht, nur die Verknüpfung entfernt
            $entity->setProject(null);
            $foundsImageRepository->save($entity, false);
            $deletedCount++;
        }

        // Flush alle Änderungen
        $foundsImageRepository->getEntityManager()->flush();

        $message = "$deletedCount Fundmeldungen wurden erfolgreich aus dem Projekt entfernt.";
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
}
