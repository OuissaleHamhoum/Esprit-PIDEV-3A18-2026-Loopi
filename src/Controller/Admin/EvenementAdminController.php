<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Evenement;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/evenements')]
final class EvenementAdminController extends AbstractController
{
    #[Route('', name: 'admin_evenements')]
    public function index(EvenementRepository $evenementRepository): Response
    {
        return $this->render('admin/evenement/index.html.twig', [
            'events' => $evenementRepository->findBy([], ['dateEvenement' => 'DESC']),
        ]);
    }

    #[Route('/nouveau', name: 'admin_evenements_new')]
    public function new(Request $request, EntityManagerInterface $em, FileUploadService $uploads): Response
    {
        $e = new Evenement();
        $form = $this->createForm(EvenementType::class, $e);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageFile')->getData();
            if ($file) {
                $e->setImageEvenement($uploads->uploadEvenement($file));
            }
            $e->setCreatedAt(new \DateTimeImmutable());
            if ($e->getDateSoumission() === null) {
                $e->setDateSoumission(new \DateTimeImmutable());
            }
            $em->persist($e);
            $em->flush();
            $this->addFlash('success', 'Événement créé.');

            return $this->redirectToRoute('admin_evenements');
        }

        return $this->render('admin/evenement/form.html.twig', [
            'form' => $form,
            'title' => 'Nouvel événement',
        ]);
    }

    #[Route('/{id}/modifier', name: 'admin_evenements_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $em, FileUploadService $uploads): Response
    {
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageFile')->getData();
            if ($file) {
                $evenement->setImageEvenement($uploads->uploadEvenement($file));
            }
            $em->flush();
            $this->addFlash('success', 'Événement mis à jour.');

            return $this->redirectToRoute('admin_evenements');
        }

        return $this->render('admin/evenement/form.html.twig', [
            'form' => $form,
            'title' => 'Modifier événement',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'admin_evenements_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$evenement->getId(), (string) $request->request->get('_token'))) {
            $em->remove($evenement);
            $em->flush();
            $this->addFlash('success', 'Événement supprimé.');
        }

        return $this->redirectToRoute('admin_evenements');
    }
}
