<?php

declare(strict_types=1);

namespace App\Controller\Organisateur;

use App\Entity\Produit;
use App\Entity\User;
use App\Entity\UserRole;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/organisateur/produits')]
final class OrganisateurProduitController extends AbstractController
{
    #[Route('', name: 'org_produits')]
    public function index(ProduitRepository $produitRepository): Response
    {
        $user = $this->orgUser();

        return $this->render('organisateur/produit/index.html.twig', [
            'produits' => $produitRepository->findBy(['owner' => $user], ['id' => 'DESC']),
        ]);
    }

    #[Route('/nouveau', name: 'org_produits_new')]
    public function new(Request $request, EntityManagerInterface $em, FileUploadService $uploads): Response
    {
        $user = $this->orgUser();
        $p = new Produit();
        $p->setOwner($user);
        $form = $this->createForm(ProduitType::class, $p, ['hide_owner' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageFile')->getData();
            if ($file) {
                $p->setImage($uploads->uploadProduit($file));
            }
            $p->setCreatedAt(new \DateTimeImmutable());
            $p->setUpdatedAt(new \DateTimeImmutable());
            $em->persist($p);
            $em->flush();
            $this->addFlash('success', 'Produit ajouté à votre galerie.');

            return $this->redirectToRoute('org_produits');
        }

        return $this->render('organisateur/produit/form.html.twig', [
            'form' => $form,
            'title' => 'Nouveau produit',
        ]);
    }

    #[Route('/{id}/modifier', name: 'org_produits_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $em, FileUploadService $uploads): Response
    {
        $user = $this->orgUser();
        $this->denyUnlessOwner($produit->getOwner(), $user);

        $form = $this->createForm(ProduitType::class, $produit, ['hide_owner' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageFile')->getData();
            if ($file) {
                $produit->setImage($uploads->uploadProduit($file));
            }
            $produit->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();
            $this->addFlash('success', 'Produit mis à jour.');

            return $this->redirectToRoute('org_produits');
        }

        return $this->render('organisateur/produit/form.html.twig', [
            'form' => $form,
            'title' => 'Modifier le produit',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'org_produits_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $em): Response
    {
        $user = $this->orgUser();
        $this->denyUnlessOwner($produit->getOwner(), $user);

        if ($this->isCsrfTokenValid('delete'.$produit->getId(), (string) $request->request->get('_token'))) {
            $em->remove($produit);
            $em->flush();
            $this->addFlash('success', 'Produit supprimé.');
        }

        return $this->redirectToRoute('org_produits');
    }

    private function orgUser(): User
    {
        $u = $this->getUser();
        if (!$u instanceof User || $u->getRole() !== UserRole::ORGANISATEUR) {
            throw $this->createAccessDeniedException();
        }

        return $u;
    }

    private function denyUnlessOwner(?User $owner, User $current): void
    {
        if ($owner === null || $owner->getId() !== $current->getId()) {
            throw $this->createAccessDeniedException();
        }
    }
}
