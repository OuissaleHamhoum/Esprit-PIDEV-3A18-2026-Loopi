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
    public function index(Request $request, EvenementRepository $evenementRepository): Response
    {
        $raw = $request->query->get('statut');
        $filter = \is_string($raw) && $raw !== '' ? $raw : null;
        if ($filter !== null && !\in_array($filter, ['en_attente', 'approuve', 'refuse'], true)) {
            $filter = null;
        }

        return $this->render('admin/evenement/index.html.twig', [
            'events' => $evenementRepository->findForAdminList($filter),
            'filter' => $filter,
        ]);
    }

    #[Route('/{id}/validation', name: 'admin_evenements_validation', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function validation(Request $request, Evenement $evenement, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('event_validation'.$evenement->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Session expirée ou jeton invalide.');

            return $this->redirectToRoute('admin_evenements');
        }
        $action = (string) $request->request->get('action');
        $now = new \DateTimeImmutable();
        if ($action === 'approve') {
            $evenement->setStatutValidation('approuve');
            $evenement->setDateValidation($now);
            $this->addFlash('success', 'Événement approuvé — visible sur le site et pour les participants.');
        } elseif ($action === 'refuse') {
            $evenement->setStatutValidation('refuse');
            $evenement->setDateValidation($now);
            $this->addFlash('success', 'Événement refusé.');
        } else {
            $this->addFlash('danger', 'Action non reconnue.');

            return $this->redirectToRoute('admin_evenements');
        }
        $em->flush();

        $redirectFilter = $request->request->get('redirect_filter');

        return $this->redirectToRoute('admin_evenements', [
            'statut' => \is_string($redirectFilter) && $redirectFilter !== '' ? $redirectFilter : null,
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
            if (($e->getStatutValidation() ?? '') === '') {
                $e->setStatutValidation('approuve');
                $e->setDateValidation(new \DateTimeImmutable());
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
