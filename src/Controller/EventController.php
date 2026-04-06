<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Evenement;
use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/evenements')]
final class EventController extends AbstractController
{
    #[Route('', name: 'app_events')]
    public function index(EvenementRepository $evenementRepository): Response
    {
        $events = $evenementRepository->createQueryBuilder('e')
            ->orderBy('e.dateEvenement', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('event/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/{id}', name: 'app_event_show', requirements: ['id' => '\d+'])]
    public function show(Evenement $evenement): Response
    {
        return $this->render('event/show.html.twig', [
            'event' => $evenement,
        ]);
    }
}
