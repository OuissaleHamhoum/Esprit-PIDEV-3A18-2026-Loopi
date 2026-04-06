<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Collection;
use App\Form\CollectionType;
use App\Repository\CollectionRepository;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/collections')]
final class CollectionAdminController extends AbstractController
{
    #[Route('', name: 'admin_collections')]
    public function index(CollectionRepository $collectionRepository): Response
    {
        $all = $collectionRepository->findBy([], ['id' => 'DESC']);
        $totalGoal = array_sum(array_map(fn (Collection $c) => $c->getGoalAmount(), $all));

        return $this->render('admin/collection/index.html.twig', [
            'collections' => $all,
            'totalGoal' => $totalGoal,
        ]);
    }

    #[Route('/nouveau', name: 'admin_collections_new')]
    public function new(Request $request, EntityManagerInterface $em, FileUploadService $uploads): Response
    {
        $c = new Collection();
        $form = $this->createForm(CollectionType::class, $c);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageFile')->getData();
            if ($file) {
                $c->setImageCollection($uploads->uploadCollection($file));
            } else {
                $c->setImageCollection('');
            }
            $c->setCreatedAt(new \DateTimeImmutable());
            $c->setUpdatedAt(new \DateTimeImmutable());
            $em->persist($c);
            $em->flush();
            $this->addFlash('success', 'Collection créée.');

            return $this->redirectToRoute('admin_collections');
        }

        return $this->render('admin/collection/form.html.twig', [
            'form' => $form,
            'title' => 'Nouvelle collection',
        ]);
    }

    #[Route('/{id}/modifier', name: 'admin_collections_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Collection $collection, EntityManagerInterface $em, FileUploadService $uploads): Response
    {
        $form = $this->createForm(CollectionType::class, $collection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageFile')->getData();
            if ($file) {
                $collection->setImageCollection($uploads->uploadCollection($file));
            }
            $collection->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();
            $this->addFlash('success', 'Collection mise à jour.');

            return $this->redirectToRoute('admin_collections');
        }

        return $this->render('admin/collection/form.html.twig', [
            'form' => $form,
            'title' => 'Modifier collection',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'admin_collections_delete', methods: ['POST'])]
    public function delete(Request $request, Collection $collection, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$collection->getId(), (string) $request->request->get('_token'))) {
            $em->remove($collection);
            $em->flush();
            $this->addFlash('success', 'Collection supprimée.');
        }

        return $this->redirectToRoute('admin_collections');
    }
}
