<?php

declare(strict_types=1);

namespace App\Controller\Organisateur;

use App\Entity\Collection;
use App\Entity\User;
use App\Entity\UserRole;
use App\Form\CollectionType;
use App\Repository\CollectionRepository;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/organisateur/collections')]
final class OrganisateurCollectionController extends AbstractController
{
    #[Route('', name: 'org_collections')]
    public function index(CollectionRepository $collectionRepository): Response
    {
        $user = $this->orgUser();

        return $this->render('organisateur/collection/index.html.twig', [
            'collections' => $collectionRepository->findByOwner((int) $user->getId()),
        ]);
    }

    #[Route('/nouveau', name: 'org_collections_new')]
    public function new(Request $request, EntityManagerInterface $em, FileUploadService $uploads): Response
    {
        $user = $this->orgUser();
        $c = new Collection();
        $c->setOwner($user);
        $form = $this->createForm(CollectionType::class, $c, ['hide_owner' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageFile')->getData();
            $c->setImageCollection($file ? $uploads->uploadCollection($file) : '');
            $c->setCreatedAt(new \DateTimeImmutable());
            $c->setUpdatedAt(new \DateTimeImmutable());
            $em->persist($c);
            $em->flush();
            $this->addFlash('success', 'Campagne créée.');

            return $this->redirectToRoute('org_collections');
        }

        return $this->render('organisateur/collection/form.html.twig', [
            'form' => $form,
            'title' => 'Nouvelle collecte',
        ]);
    }

    #[Route('/{id}/modifier', name: 'org_collections_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Collection $collection, EntityManagerInterface $em, FileUploadService $uploads): Response
    {
        $user = $this->orgUser();
        $this->denyUnlessOwner($collection->getOwner(), $user);

        $form = $this->createForm(CollectionType::class, $collection, ['hide_owner' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageFile')->getData();
            if ($file) {
                $collection->setImageCollection($uploads->uploadCollection($file));
            }
            $collection->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();
            $this->addFlash('success', 'Campagne mise à jour.');

            return $this->redirectToRoute('org_collections');
        }

        return $this->render('organisateur/collection/form.html.twig', [
            'form' => $form,
            'title' => 'Modifier la collecte',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'org_collections_delete', methods: ['POST'])]
    public function delete(Request $request, Collection $collection, EntityManagerInterface $em): Response
    {
        $user = $this->orgUser();
        $this->denyUnlessOwner($collection->getOwner(), $user);

        if ($this->isCsrfTokenValid('delete'.$collection->getId(), (string) $request->request->get('_token'))) {
            $em->remove($collection);
            $em->flush();
            $this->addFlash('success', 'Campagne supprimée.');
        }

        return $this->redirectToRoute('org_collections');
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
