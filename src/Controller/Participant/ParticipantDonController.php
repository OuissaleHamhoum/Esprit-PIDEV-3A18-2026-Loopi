<?php

declare(strict_types=1);

namespace App\Controller\Participant;

use App\Entity\User;
use App\Entity\UserRole;
use App\Repository\DonationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participant/dons')]
final class ParticipantDonController extends AbstractController
{
    #[Route('', name: 'par_dons')]
    public function index(DonationRepository $donationRepository): Response
    {
        $user = $this->participant();

        return $this->render('participant/don/index.html.twig', [
            'dons' => $donationRepository->findByUserOrdered((int) $user->getId()),
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
