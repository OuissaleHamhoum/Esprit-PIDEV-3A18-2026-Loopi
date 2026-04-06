<?php

declare(strict_types=1);

namespace App\Controller\Participant;

use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/participant/galerie')]
final class ParticipantGalerieController extends AbstractController
{
    #[Route('', name: 'par_galerie')]
    public function index(ProduitRepository $produitRepository): Response
    {
        return $this->render('participant/galerie/index.html.twig', [
            'produits' => $produitRepository->findBy([], ['id' => 'DESC']),
        ]);
    }
}
