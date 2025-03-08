<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends FinderAbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Benutzerstatistiken
        $totalUsers = $this->userRepository->count([]);
        $activeUsers = $this->userRepository->count(['isActive' => true]);
        $verifiedUsers = $this->userRepository->count(['isVerified' => true]);

        // Fundmeldungen
        $query = $this->entityManager->createQuery(
            'SELECT COUNT(f) as total, SUM(CASE WHEN f.isPublic = true THEN 1 ELSE 0 END) as public
             FROM App\Entity\FoundsImage f'
        );
        $findsStats = $query->getSingleResult();

        // Speicherplatznutzung (Fundbilder-Verzeichnis)
        $fundbilderDir = $this->getParameter('kernel.project_dir') . '/public/fundbilder';
        $totalSize = 0;
        if (is_dir($fundbilderDir)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fundbilderDir));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $totalSize += $file->getSize();
                }
            }
        }
        $totalSizeMB = round($totalSize / (1024 * 1024), 2);

        // Letzte Aktivitäten
        $recentFindsQuery = $this->entityManager->createQuery(
            'SELECT f, u FROM App\Entity\FoundsImage f
            JOIN f.user u
            ORDER BY f.createdAt DESC'
        )->setMaxResults(5);
        
        $recentFinds = $recentFindsQuery->getResult();

        $recentUsersQuery = $this->entityManager->createQuery(
            'SELECT u FROM App\Entity\User u
            ORDER BY u.id DESC'
        )->setMaxResults(5);
        
        $recentUsers = $recentUsersQuery->getResult();
        
        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers,
                'verifiedUsers' => $verifiedUsers,
                'totalFinds' => $findsStats['total'] ?? 0,
                'publicFinds' => $findsStats['public'] ?? 0,
                'totalSizeMB' => $totalSizeMB
            ],
            'recentFinds' => $recentFinds,
            'recentUsers' => $recentUsers
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function users(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $users = $this->userRepository->findAll();
        
        return $this->render('admin/users.html.twig', [
            'users' => $users
        ]);
    }

    #[Route('/user/toggle-status/{id}', name: 'admin_user_toggle_status', methods: ['POST'])]
    public function toggleUserStatus(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Benutzer nicht gefunden'], 404);
        }
        
        $user->setIsActive(!$user->isActive());
        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'newStatus' => $user->isActive(),
            'message' => $user->isActive() ? 'Benutzer aktiviert' : 'Benutzer deaktiviert'
        ]);
    }

    #[Route('/user/delete/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Benutzer nicht gefunden'], 404);
        }
        
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Benutzer erfolgreich gelöscht'
        ]);
    }

    #[Route('/finds', name: 'admin_finds')]
    public function finds(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $query = $this->entityManager->createQuery(
            'SELECT fi, u FROM App\Entity\FoundsImage fi
            JOIN fi.user u
            ORDER BY u.email ASC, fi.createdAt DESC'
        );
        
        $finds = $query->getResult();

        return $this->render('admin/finds.html.twig', [
            'finds' => $finds
        ]);
    }
} 