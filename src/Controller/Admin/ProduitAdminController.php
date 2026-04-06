<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/produits')]
final class ProduitAdminController extends AbstractController
{
    #[Route('', name: 'admin_produits')]
    public function index(ProduitRepository $produitRepository): Response
    {
        return $this->render('admin/produit/index.html.twig', [
            'produits' => $produitRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/nouveau', name: 'admin_produits_new')]
    public function new(Request $request, EntityManagerInterface $em, FileUploadService $uploads): Response
    {
        $p = new Produit();
        $form = $this->createForm(ProduitType::class, $p);
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
            $this->addFlash('success', 'Produit créé.');

            return $this->redirectToRoute('admin_produits');
        }

        return $this->render('admin/produit/form.html.twig', [
            'form' => $form,
            'title' => 'Nouveau produit',
        ]);
    }

    #[Route('/{id}/modifier', name: 'admin_produits_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $em, FileUploadService $uploads): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageFile')->getData();
            if ($file) {
                $produit->setImage($uploads->uploadProduit($file));
            }
            $produit->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();
            $this->addFlash('success', 'Produit mis à jour.');

            return $this->redirectToRoute('admin_produits');
        }

        return $this->render('admin/produit/form.html.twig', [
            'form' => $form,
            'title' => 'Modifier produit',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'admin_produits_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$produit->getId(), (string) $request->request->get('_token'))) {
            $em->remove($produit);
            $em->flush();
            $this->addFlash('success', 'Produit supprimé.');
        }

        return $this->redirectToRoute('admin_produits');
    }
}
