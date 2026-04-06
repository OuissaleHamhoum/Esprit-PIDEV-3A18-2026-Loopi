<?php

declare(strict_types=1);

namespace App\Controller\Organisateur;

use App\Entity\User;
use App\Entity\UserRole;
use App\Repository\ParticipationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/organisateur/participants')]
final class OrganisateurParticipantController extends AbstractController
{
    #[Route('', name: 'org_participants')]
    public function index(ParticipationRepository $participationRepository): Response
    {
        $user = $this->orgUser();
        $list = $participationRepository->findForOrganisateurEvents((int) $user->getId());

        $stats = ['total' => 0, 'inscrit' => 0, 'present' => 0, 'absent' => 0];
        foreach ($list as $p) {
            ++$stats['total'];
            $statut = $p->getStatut();
            if ($statut === 'inscrit') {
                ++$stats['inscrit'];
            } elseif ($statut === 'present') {
                ++$stats['present'];
            } elseif ($statut === 'absent') {
                ++$stats['absent'];
            }
        }

        return $this->render('organisateur/participant/index.html.twig', [
            'participations' => $list,
            'stats' => $stats,
        ]);
    }

    private function orgUser(): User
    {
        $u = $this->getUser();
        if (!$u instanceof User || $u->getRole() !== UserRole::ORGANISATEUR) {
            throw $this->createAccessDeniedException();
        }

        return $u;
    }
}
