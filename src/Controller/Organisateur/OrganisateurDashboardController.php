<?php

declare(strict_types=1);

namespace App\Controller\Organisateur;

use App\Entity\User;
use App\Entity\UserRole;
use App\Repository\CollectionRepository;
use App\Repository\EvenementRepository;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/organisateur')]
final class OrganisateurDashboardController extends AbstractController
{
    #[Route('', name: 'app_organisateur_dashboard')]
    public function index(
        ProduitRepository $produitRepository,
        EvenementRepository $evenementRepository,
        CollectionRepository $collectionRepository,
    ): Response {
        $user = $this->assertOrganisateur();

        $nbProd = $produitRepository->count(['owner' => $user]);
        $nbEvt = $evenementRepository->count(['organisateur' => $user]);
        $nbColl = $collectionRepository->count(['owner' => $user]);

        return $this->render('organisateur/dashboard/index.html.twig', [
            'nbProduits' => $nbProd,
            'nbEvenements' => $nbEvt,
            'nbCollections' => $nbColl,
            'user' => $user,
        ]);
    }

    private function assertOrganisateur(): User
    {
        $u = $this->getUser();
        if (!$u instanceof User || $u->getRole() !== UserRole::ORGANISATEUR) {
            throw $this->createAccessDeniedException();
        }

        return $u;
    }
}
