<?php

declare(strict_types=1);

namespace App\Controller\Participant;

use App\Entity\User;
use App\Entity\UserRole;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participant/coupons')]
final class ParticipantCouponController extends AbstractController
{
    #[Route('', name: 'par_coupons')]
    public function index(Connection $connection): Response
    {
        $user = $this->participant();
        $rows = $connection->fetchAllAssociative(
            'SELECT id_coupon, code, discount_percent, expiration_date, used, created_at FROM coupon WHERE id_user = ? ORDER BY id_coupon DESC',
            [(int) $user->getId()]
        );

        return $this->render('participant/coupon/index.html.twig', [
            'coupons' => $rows,
        ]);
    }

    private function participant(): User
    {
        $u = $this->getUser();
        if (!$u instanceof User || $u->getRole() !== UserRole::PARTICIPANT) {
            throw $this->createAccessDeniedException();
        }

        return $u;
    }
}
