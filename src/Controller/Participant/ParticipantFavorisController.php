<?php

declare(strict_types=1);

namespace App\Controller\Participant;

use App\Entity\User;
use App\Entity\UserRole;
use App\Repository\FavorisRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participant/favoris')]
final class ParticipantFavorisController extends AbstractController
{
    #[Route('', name: 'par_favoris')]
    public function index(FavorisRepository $favorisRepository): Response
    {
        $user = $this->participant();

        return $this->render('participant/favoris/index.html.twig', [
            'favoris' => $favorisRepository->findByUserOrdered((int) $user->getId()),
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
