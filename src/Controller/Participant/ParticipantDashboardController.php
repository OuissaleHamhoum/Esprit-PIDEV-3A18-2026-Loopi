<?php

declare(strict_types=1);

namespace App\Controller\Participant;

use App\Entity\User;
use App\Entity\UserRole;
use App\Service\UserStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participant')]
final class ParticipantDashboardController extends AbstractController
{
    #[Route('', name: 'app_participant_dashboard')]
    public function index(UserStatsService $userStatsService): Response
    {
        $user = $this->participant();
        $stats = $userStatsService->getParticipantStats((int) $user->getId());

        return $this->render('participant/dashboard/index.html.twig', [
            'participantStats' => $stats,
            'user' => $user,
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
