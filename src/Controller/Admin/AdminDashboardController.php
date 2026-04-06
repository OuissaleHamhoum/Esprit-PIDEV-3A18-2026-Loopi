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
            $r = (string) ($row['role'] ?? '');
            if (isset($roles[$r])) {
                $roles[$r] = (int) $row['cnt'];
            }
        }

        $participationCount = (int) $connection->fetchOne('SELECT COUNT(*) FROM participation');
        $favorisCount = (int) $connection->fetchOne('SELECT COUNT(*) FROM favoris');
        $feedbackCount = (int) $connection->fetchOne('SELECT COUNT(*) FROM feedback');
        $couponCount = (int) $connection->fetchOne('SELECT COUNT(*) FROM coupon');
        $donationCount = (int) $connection->fetchOne('SELECT COUNT(*) FROM donation');
        $donationConfirmedCount = (int) $connection->fetchOne("SELECT COUNT(*) FROM donation WHERE status = 'confirmé'");
        $donationTotal = (float) $connection->fetchOne("SELECT COALESCE(SUM(amount), 0) FROM donation WHERE status = 'confirmé'");
        $shareCount = (int) $connection->fetchOne('SELECT COUNT(*) FROM social_shares');

        $pendingEvents = (int) $connection->fetchOne(
            "SELECT COUNT(*) FROM evenement WHERE statut_validation = 'en_attente' OR statut_validation IS NULL OR statut_validation = ''"
        );

        $eventStatusRows = $connection->fetchAllAssociative(
            'SELECT COALESCE(NULLIF(TRIM(statut_validation), \'\'), \'en_attente\') AS st, COUNT(*) AS c '
            .'FROM evenement GROUP BY COALESCE(NULLIF(TRIM(statut_validation), \'\'), \'en_attente\') ORDER BY c DESC'
        );
        $eventStatusLabels = [];
        $eventStatusData = [];
        foreach ($eventStatusRows as $er) {
            $eventStatusLabels[] = (string) $er['st'];
            $eventStatusData[] = (int) $er['c'];
        }

        $notificationsCount = 0;
        try {
            $notificationsCount = (int) $connection->fetchOne('SELECT COUNT(*) FROM notifications');
        } catch (\Throwable) {
        }

        return $this->render('admin/dashboard/index.html.twig', [
            'userCount' => $userCount,
            'produitCount' => $produitRepository->count([]),
            'eventCount' => $evenementRepository->count([]),
            'collectionCount' => $collectionRepository->count([]),
            'roles' => $roles,
            'participationCount' => $participationCount,
            'favorisCount' => $favorisCount,
            'feedbackCount' => $feedbackCount,
            'couponCount' => $couponCount,
            'donationCount' => $donationCount,
            'donationConfirmedCount' => $donationConfirmedCount,
            'donationTotal' => $donationTotal,
            'shareCount' => $shareCount,
            'pendingEvents' => $pendingEvents,
            'eventStatusLabels' => $eventStatusLabels,
            'eventStatusData' => $eventStatusData,
            'notificationsCount' => $notificationsCount,
        ]);
    }
}
