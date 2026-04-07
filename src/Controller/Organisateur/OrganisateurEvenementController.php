<?php

declare(strict_types=1);

namespace App\Controller\Organisateur;

use App\Entity\Evenement;
use App\Entity\User;
use App\Entity\UserRole;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/organisateur/evenements')]
final class OrganisateurEvenementController extends AbstractController
{
    #[Route('', name: 'org_evenements')]
    public function index(EvenementRepository $evenementRepository): Response
    {
        $user = $this->orgUser();

        return $this->render('organisateur/evenement/index.html.twig', [
            'events' => $evenementRepository->findBy(['organisateur' => $user], ['dateEvenement' => 'DESC']),
        ]);
    }

    #[Route('/nouveau', name: 'org_evenements_new')]
    public function new(Request $request, EntityManagerInterface $em, FileUploadService $uploads): Response
    {
        $user = $this->orgUser();
        $e = new Evenement();
        $e->setOrganisateur($user);
        $form = $this->createForm(EvenementType::class, $e, ['hide_organisateur' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageFile')->getData();
            if ($file) {
                $e->setImageEvenement($uploads->uploadEvenement($file));
            }
            if ($e->getStatutValidation() === null || $e->getStatutValidation() === '') {
                $e->setStatutValidation('en_attente');
            }
            $e->setCreatedAt(new \DateTimeImmutable());
            if ($e->getDateSoumission() === null) {
                $e->setDateSoumission(new \DateTimeImmutable());
            }
            $em->persist($e);
            $em->flush();
            $this->addFlash('success', 'Événement créé.');

            return $this->redirectToRoute('org_evenements');
        }

        return $this->render('organisateur/evenement/form.html.twig', [
            'form' => $form,
            'title' => 'Nouvel événement',
        ]);
    }

    #[Route('/{id}/modifier', name: 'org_evenements_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $em, FileUploadService $uploads): Response
    {
        $user = $this->orgUser();
        $this->denyUnlessOrg($evenement->getOrganisateur(), $user);

        $form = $this->createForm(EvenementType::class, $evenement, ['hide_organisateur' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageFile')->getData();
            if ($file) {
                $evenement->setImageEvenement($uploads->uploadEvenement($file));
            }
            if ($evenement->getStatutValidation() === null || $evenement->getStatutValidation() === '') {
                $evenement->setStatutValidation('en_attente');
            }
            $em->flush();
            $this->addFlash('success', 'Événement mis à jour.');

            return $this->redirectToRoute('org_evenements');
        }

        return $this->render('organisateur/evenement/form.html.twig', [
            'form' => $form,
            'title' => 'Modifier l’événement',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'org_evenements_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $em): Response
    {
        $user = $this->orgUser();
        $this->denyUnlessOrg($evenement->getOrganisateur(), $user);

        if ($this->isCsrfTokenValid('delete'.$evenement->getId(), (string) $request->request->get('_token'))) {
            $em->remove($evenement);
            $em->flush();
            $this->addFlash('success', 'Événement supprimé.');
        }

        return $this->redirectToRoute('org_evenements');
    }

    private function orgUser(): User
    {
        $u = $this->getUser();
        if (!$u instanceof User || $u->getRole() !== UserRole::ORGANISATEUR) {
            throw $this->createAccessDeniedException();
        }

        return $u;
    }

    private function denyUnlessOrg(?User $org, User $current): void
    {
        if ($org === null || $org->getId() !== $current->getId()) {
            throw $this->createAccessDeniedException();
        }
    }
}
