<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/catalogue')]
final class CatalogController extends AbstractController
{
    #[Route('', name: 'app_catalog')]
    public function index(ProduitRepository $produitRepository): Response
    {
        $produits = $produitRepository->findBy([], ['id' => 'DESC']);

        return $this->render('catalog/index.html.twig', [
            'produits' => $produits,
        ]);
    }
}
