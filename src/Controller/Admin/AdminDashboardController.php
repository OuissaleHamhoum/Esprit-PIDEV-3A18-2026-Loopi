<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\CollectionRepository;
use App\Repository\EvenementRepository;
use App\Repository\ProduitRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class AdminDashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard')]
    public function index(
        UserRepository $userRepository,
        ProduitRepository $produitRepository,
        EvenementRepository $evenementRepository,
        CollectionRepository $collectionRepository,
        Connection $connection,
    ): Response {
        $userCount = $userRepository->count([]);
        $roleRows = $connection->fetchAllAssociative('SELECT role, COUNT(*) AS cnt FROM users GROUP BY role');
        $roles = ['admin' => 0, 'organisateur' => 0, 'participant' => 0];
        foreach ($roleRows as $row) {
            $r = $row['role'] ?? '';
            if (isset($roles[$r])) {
                $roles[$r] = (int) $row['cnt'];
            }
        }

        return $this->render('admin/dashboard/index.html.twig', [
            'userCount' => $userCount,
            'produitCount' => $produitRepository->count([]),
            'eventCount' => $evenementRepository->count([]),
            'collectionCount' => $collectionRepository->count([]),
            'roles' => $roles,
        ]);
    }
}
