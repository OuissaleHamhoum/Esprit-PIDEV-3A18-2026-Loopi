<?php

declare(strict_types=1);

namespace App\Controller\Participant;

use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participant/evenements')]
final class ParticipantEvenementController extends AbstractController
{
    #[Route('', name: 'par_evenements')]
    public function index(EvenementRepository $evenementRepository): Response
    {
        return $this->render('participant/evenement/index.html.twig', [
            'events' => $evenementRepository->findApprovedOrdered(),
        ]);
    }
}
