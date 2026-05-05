<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\Favoris;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\BadWordFilterService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('landing.html.twig');
    }

    #[Route('/admin', name: 'app_admin')]
    public function admin(): Response
    {
        return $this->render('admin-backoffice.html.twig');
    }

    #[Route('/organisateur', name: 'app_organisateur')]
    public function organisateur(): Response
    {
        return $this->render('organisateur-dashboard.html.twig');
    }

    #[Route('/participant', name: 'app_participant')]
    public function participant(): Response
    {
        return $this->render('participant-loopi.html.twig');
    }

    #[Route('/qr-desktop-login', name: 'app_qr_desktop_login')]
    public function qrDesktopLogin(): Response
    {
        return $this->render('qr-desktop-login.html.twig');
    }

    #[Route('/product/{id}', name: 'app_product_details', methods: ['GET'])]
    public function productDetails(int $id, ManagerRegistry $doctrine): Response
    {
        $product = $doctrine->getRepository(Produit::class)->find($id);
        
        if (!$product) {
            throw new NotFoundHttpException('Produit non trouvé');
        }

        // Get user favorites for display
        $user = $this->getUser();
        $favorisIds = [];
        if ($user) {
            $favorites = $doctrine->getRepository(Favoris::class)->findBy(['user' => $user]);
            $favorisIds = array_map(fn($f) => $f->getProduit()->getId(), $favorites);
        }

        // Calculate feedback statistics
        $feedbacks = $doctrine->getRepository(\App\Entity\Feedback::class)->findBy(['produit' => $product]);
        $totalFeedbacks = count($feedbacks);
        $averageNote = 0;
        if ($totalFeedbacks > 0) {
            $totalNotes = array_sum(array_map(fn($f) => $f->getNote(), $feedbacks));
            $averageNote = $totalNotes / $totalFeedbacks;
        }

        // Get user's feedback for this product
        $userFeedback = null;
        if ($user) {
            $userFeedback = $doctrine->getRepository(\App\Entity\Feedback::class)->findOneBy([
                'produit' => $product,
                'user' => $user
            ]);
        }

        // Get similar products (same category, excluding current product)
        $similarProducts = [];
        if ($product->getCategory()) {
            $similarProducts = $doctrine->getRepository(Produit::class)->findBy([
                'category' => $product->getCategory()
            ], ['createdAt' => 'DESC'], 6, 0);
            
            // Remove current product from similar products
            $similarProducts = array_filter($similarProducts, function($p) use ($product) {
                return $p->getId() !== $product->getId();
            });
            
            // Limit to 4 products
            $similarProducts = array_slice($similarProducts, 0, 4);
        }

        return $this->render('detaile_produit.html.twig', [
            'produit' => $product,
            'favorisIds' => $favorisIds,
            'totalFeedbacks' => $totalFeedbacks,
            'averageNote' => $averageNote,
            'userFeedback' => $userFeedback,
            'feedbacks' => $feedbacks,
            'similarProducts' => $similarProducts
        ]);
    }

    #[Route('/favoris/toggle/{id}', name: 'app_favoris_toggle', methods: ['POST'])]
    public function toggleFavoris(int $id, ManagerRegistry $doctrine, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour gérer vos favoris');
            return $this->redirectToRoute('app_login');
        }

        $product = $doctrine->getRepository(Produit::class)->find($id);
        if (!$product) {
            throw new NotFoundHttpException('Produit non trouvé');
        }

        $entityManager = $doctrine->getManager();
        $existingFavorite = $doctrine->getRepository(Favoris::class)->findOneBy(['user' => $user, 'produit' => $product]);

        if ($existingFavorite) {
            $entityManager->remove($existingFavorite);
            $this->addFlash('success', 'Retiré des favoris');
        } else {
            $favorite = new Favoris();
            $favorite->setUser($user);
            $favorite->setProduit($product);
            $favorite->setCreatedAt(new \DateTime());
            $entityManager->persist($favorite);
            $this->addFlash('success', 'Ajouté aux favoris');
        }

        $entityManager->flush();

        // Redirect back to product page or referer
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_product_details', ['id' => $id]);
    }

    #[Route('/feedback/add/{id}', name: 'app_feedback_add', methods: ['POST'])]
    public function addFeedback(int $id, ManagerRegistry $doctrine, Request $request, BadWordFilterService $filter): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour laisser un avis');
            return $this->redirectToRoute('app_login');
        }

        $product = $doctrine->getRepository(Produit::class)->find($id);
        if (!$product) {
            throw new NotFoundHttpException('Produit non trouvé');
        }

        $note = $request->request->get('note');
        $commentaire = $request->request->get('commentaire');

        if ($note && $commentaire) {
            $feedback = new \App\Entity\Feedback();
            $feedback->setUser($user);
            $feedback->setProduit($product);
            $feedback->setNote((int)$note);
            $feedback->setCommentaire($commentaire);
            $feedback->setDateCommentaire(new \DateTime());

            if ($filter->isInappropriate($commentaire)) {
                $feedback->setStatus('flagged');
                $this->addFlash('warning', 'Votre avis a été publié mais contient des termes inappropriés et sera examiné par nos modérateurs.');
            } else {
                $feedback->setStatus('published');
                $this->addFlash('success', 'Merci pour votre avis !');
            }

            $em = $doctrine->getManager();
            $em->persist($feedback);
            $em->flush();
        }

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_product_details', ['id' => $id]);
    }


    #[Route('/feedback/edit/{id}', name: 'app_feedback_edit', methods: ['POST'])]
    public function editFeedback(int $id, ManagerRegistry $doctrine, Request $request, BadWordFilterService $filter): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour modifier un avis');
            return $this->redirectToRoute('app_login');
        }

        $feedback = $doctrine->getRepository(\App\Entity\Feedback::class)->find($id);
        if (!$feedback || $feedback->getUser() !== $user) {
            throw new NotFoundHttpException('Avis non trouvé ou accès non autorisé');
        }

        $note = $request->request->get('note');
        $commentaire = $request->request->get('commentaire');

        if ($note && $commentaire) {
            $feedback->setNote((int)$note);
            $feedback->setCommentaire($commentaire);
            $feedback->setDateCommentaire(new \DateTime());

            if ($filter->isInappropriate($commentaire)) {
                $feedback->setStatus('flagged');
                $this->addFlash('warning', 'Votre avis a été modifié mais contient des termes inappropriés.');
            } else {
                $feedback->setStatus('published');
                $this->addFlash('success', 'Avis mis à jour !');
            }

            $doctrine->getManager()->flush();
        }

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_product_details', ['id' => $feedback->getProduit()->getId()]);
    }

    #[Route('/feedback/delete/{id}', name: 'app_feedback_delete', methods: ['POST'])]
    public function deleteFeedback(int $id, ManagerRegistry $doctrine, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour supprimer un avis');
            return $this->redirectToRoute('app_login');
        }

        $feedback = $doctrine->getRepository(\App\Entity\Feedback::class)->find($id);
        if (!$feedback || $feedback->getUser() !== $user) {
            throw new NotFoundHttpException('Avis non trouvé ou accès non autorisé');
        }

        $productId = $feedback->getProduit()->getId();

        $entityManager = $doctrine->getManager();
        $entityManager->remove($feedback);
        $entityManager->flush();

        $this->addFlash('success', 'Avis supprimé avec succès');

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_product_details', ['id' => $productId]);
    }
}