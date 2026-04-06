<?php

declare(strict_types=1);

namespace App\Controller\Participant;

use App\Repository\CollectionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participant/campagnes')]
final class ParticipantCampagneController extends AbstractController
{
    #[Route('', name: 'par_campagnes')]
    public function index(CollectionRepository $collectionRepository): Response
    {
        return $this->render('participant/campagne/index.html.twig', [
            'collections' => $collectionRepository->findBy([], ['id' => 'DESC']),
        ]);
    }
}
