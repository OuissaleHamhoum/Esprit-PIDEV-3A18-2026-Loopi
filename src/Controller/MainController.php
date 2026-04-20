<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Entity\Produit;
use App\Entity\Favoris;
use App\Repository\CategoryProduitRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    public function __construct(
        private ProduitRepository $produitRepository,
        private CategoryProduitRepository $categoryRepository,
        private EntityManagerInterface $entityManager
    )
    {
    }

    private function isDuplicateImage(UploadedFile $uploadedFile, ?int $excludeProduitId = null): bool
    {
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        if (!file_exists($uploadedFile->getPathname())) {
            return false;
        }

        $newHash = md5_file($uploadedFile->getPathname());
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('p.imageProduit')
            ->from(Produit::class, 'p')
            ->where('p.imageProduit IS NOT NULL');

        if ($excludeProduitId !== null) {
            $qb->andWhere('p.id != :excludeId')
               ->setParameter('excludeId', $excludeProduitId);
        }

        $existingImages = $qb->getQuery()->getArrayResult();
        foreach ($existingImages as $row) {
            if (empty($row['imageProduit'])) {
                continue;
            }

            $existingPath = $uploadDir . '/' . $row['imageProduit'];
            if (!file_exists($existingPath)) {
                continue;
            }

            if (md5_file($existingPath) === $newHash) {
                return true;
            }
        }

        return false;
    }

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
    #[Route('/organisateur/{page}', name: 'app_organisateur_page')]
    public function organisateur(string $page = 'dashboard'): Response
    {
        $user = $this->getUser();
        $produits = [];
        $categories = $this->categoryRepository->findAll();
        
        if ($user) {
            $produits = $this->produitRepository->findBy(['user' => $user]);
        }

        $stats = [
            'total' => count($produits),
            'publies' => 0,
            'archives' => 0,
        ];

        foreach ($produits as $p) {
            if ($p->getStatus() === 'publié') {
                $stats['publies']++;
            } else {
                $stats['archives']++;
            }
        }
        
        return $this->render('organisateur-dashboard.html.twig', [
            'produits' => $produits,
            'categories' => $categories,
            'activePage' => $page,
            'stats' => $stats,
        ]);
    }

    #[Route('/participant', name: 'app_participant')]
    public function participant(\App\Repository\FavorisRepository $favorisRepository): Response
    {
        $produits = $this->produitRepository->findBy(['status' => 'publié']);
        $categories = $this->categoryRepository->findAll();
        
        $categoryCounts = [];
        foreach ($categories as $category) {
            $count = 0;
            foreach ($produits as $produit) {
                if ($produit->getCategory() === $category) {
                    $count++;
                }
            }
            $categoryCounts[$category->getNomCat()] = $count;
        }

        $favorisIds = [];
        $user = $this->getUser();
        if ($user) {
            $userFavoris = $favorisRepository->findBy(['user' => $user]);
            foreach ($userFavoris as $fav) {
                $favorisIds[] = $fav->getProduit()->getId();
            }
        }
        
        return $this->render('participant-loopi.html.twig', [
            'produits' => $produits,
            'categories' => $categories,
            'categoryCounts' => $categoryCounts,
            'favorisIds' => $favorisIds,
        ]);
    }

    #[Route('/participant/produit/details/{id}', name: 'app_detaile_produit')]
    public function detaileProduit(int $id, \App\Repository\FeedbackRepository $feedbackRepository, \App\Repository\FavorisRepository $favorisRepository): Response
    {
        $produit = $this->produitRepository->find($id);
        
        if (!$produit) {
            $this->addFlash('error', 'Produit non trouvé');
            return new RedirectResponse($this->generateUrl('app_participant'));
        }

        if ($produit->getStatus() === 'archivé' && $produit->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Ce produit n\'est plus disponible.');
            return new RedirectResponse($this->generateUrl('app_participant'));
        }
        
        $feedbacks = $feedbackRepository->findBy(['produit' => $produit], ['dateCommentaire' => 'DESC']);
        
        $userFeedback = null;
        $user = $this->getUser();
        if ($user) {
            $userFeedback = $feedbackRepository->findOneBy(['produit' => $produit, 'user' => $user]);
        }
        
        // Calculate average note
        $averageNote = 0;
        if (count($feedbacks) > 0) {
            $totalNote = 0;
            foreach ($feedbacks as $fb) {
                $totalNote += $fb->getNote();
            }
            $averageNote = round($totalNote / count($feedbacks), 1);
        }
        
        // Fetch similar products
        $similarProducts = [];
        if ($produit->getCategory()) {
            $similarProducts = $this->produitRepository->createQueryBuilder('p')
                ->where('p.category = :category')
                ->andWhere('p.id != :id')
                ->andWhere('p.status = :status')
                ->setParameter('category', $produit->getCategory())
                ->setParameter('id', $produit->getId())
                ->setParameter('status', 'publié')
                ->setMaxResults(3)
                ->getQuery()
                ->getResult();
        }
        
        $favorisIds = [];
        if ($user) {
            $userFavoris = $favorisRepository->findBy(['user' => $user]);
            foreach ($userFavoris as $fav) {
                $favorisIds[] = $fav->getProduit()->getId();
            }
        }
        
        return $this->render('detaile_produit.html.twig', [
            'produit' => $produit,
            'feedbacks' => $feedbacks,
            'userFeedback' => $userFeedback,
            'averageNote' => $averageNote,
            'totalFeedbacks' => count($feedbacks),
            'similarProducts' => $similarProducts,
            'favorisIds' => $favorisIds,
        ]);
    }

    #[Route('/participant/produit/feedback/add/{id}', name: 'app_feedback_add', methods: ['POST'])]
    public function addFeedback(int $id, Request $request, \App\Repository\FeedbackRepository $feedbackRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter pour laisser un avis.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_participant'));
        }

        $produit = $this->produitRepository->find($id);
        if (!$produit) {
            $this->addFlash('error', 'Produit introuvable.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_participant'));
        }

        $existingFeedback = $feedbackRepository->findOneBy(['user' => $user, 'produit' => $produit]);
        if ($existingFeedback) {
            $this->addFlash('error', 'Vous avez déjà laissé un avis pour ce produit.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_participant'));
        }

        $note = (int) $request->request->get('note');
        $commentaire = trim((string) $request->request->get('commentaire', ''));

        if ($note < 1 || $note > 5) {
            $this->addFlash('error', 'La note doit être comprise entre 1 et 5.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_participant'));
        }

        if (empty($commentaire)) {
            $this->addFlash('error', 'Le commentaire est obligatoire.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_participant'));
        }

        $feedback = new \App\Entity\Feedback();
        $feedback->setUser($user);
        $feedback->setProduit($produit);
        $feedback->setNote($note);
        $feedback->setCommentaire($commentaire);
        $feedback->setDateCommentaire(new \DateTime());

        $this->entityManager->persist($feedback);
        $this->entityManager->flush();

        $this->addFlash('success', 'Votre avis a été publié avec succès !');
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_participant'));
    }

    #[Route('/participant/produit/feedback/edit/{id}', name: 'app_feedback_edit', methods: ['POST'])]
    public function editFeedback(int $id, Request $request, \App\Repository\FeedbackRepository $feedbackRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_participant'));
        }

        $feedback = $feedbackRepository->find($id);
        if (!$feedback || $feedback->getUser() !== $user) {
            $this->addFlash('error', 'Avis introuvable ou vous n\'avez pas la permission de le modifier.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_participant'));
        }

        $note = (int) $request->request->get('note');
        $commentaire = trim((string) $request->request->get('commentaire', ''));

        if ($note < 1 || $note > 5) {
            $this->addFlash('error', 'La note doit être comprise entre 1 et 5.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_participant'));
        }

        if (empty($commentaire)) {
            $this->addFlash('error', 'Le commentaire est obligatoire.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_participant'));
        }

        $feedback->setNote($note);
        $feedback->setCommentaire($commentaire);
        // On pourrait éventuellement mettre à jour la date, ou garder la date d'origine.
        
        $this->entityManager->flush();

        $this->addFlash('success', 'Votre avis a été modifié avec succès !');
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_participant'));
    }

    #[Route('/participant/produit/feedback/delete/{id}', name: 'app_feedback_delete', methods: ['POST'])]
    public function deleteFeedback(int $id, Request $request, \App\Repository\FeedbackRepository $feedbackRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_participant'));
        }

        $feedback = $feedbackRepository->find($id);
        if (!$feedback || $feedback->getUser() !== $user) {
            $this->addFlash('error', 'Avis introuvable ou vous n\'avez pas la permission de le supprimer.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_participant'));
        }

        $this->entityManager->remove($feedback);
        $this->entityManager->flush();

        $this->addFlash('success', 'Votre avis a été supprimé.');
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_participant'));
    }

    #[Route('/participant/favoris', name: 'app_favoris')]
    public function favoris(\App\Repository\FavorisRepository $favorisRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            // For testing purposes if no login system is enforced
            $user = $this->entityManager->getRepository(\App\Entity\User::class)->findOneBy([]);
            if (!$user) {
                $this->addFlash('error', 'Veuillez vous connecter.');
                return new RedirectResponse($this->generateUrl('app_home'));
            }
        }

        $favorisList = $favorisRepository->findBy(['user' => $user]);

        return $this->render('favoris.html.twig', [
            'favorisList' => $favorisList,
        ]);
    }

    #[Route('/participant/favoris/toggle/{id}', name: 'app_favoris_toggle')]
    public function toggleFavoris(int $id, \App\Repository\FavorisRepository $favorisRepository, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $user = $this->entityManager->getRepository(\App\Entity\User::class)->findOneBy([]);
            if (!$user) {
                $this->addFlash('error', 'Veuillez vous connecter pour ajouter aux favoris.');
                return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_participant'));
            }
        }

        $produit = $this->produitRepository->find($id);
        if (!$produit) {
            $this->addFlash('error', 'Produit introuvable.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_participant'));
        }

        $favori = $favorisRepository->findOneBy(['user' => $user, 'produit' => $produit]);

        if ($favori) {
            $this->entityManager->remove($favori);
            $this->entityManager->flush();
            $this->addFlash('success', 'Produit retiré de vos favoris.');
        } else {
            $favori = new Favoris();
            $favori->setUser($user);
            $favori->setProduit($produit);
            $favori->setDateAjout(new \DateTime());
            $this->entityManager->persist($favori);
            $this->entityManager->flush();
            $this->addFlash('success', 'Produit ajouté à vos favoris ❤️');
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_participant'));
    }

    #[Route('/organisateur/produit/add', name: 'app_produit_add', methods: ['POST'])]
    public function addProduit(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new RedirectResponse($this->generateUrl('app_home'));
        }
        
        $nomProduit = trim((string) $request->request->get('nomProduit', ''));
        $description = trim((string) $request->request->get('description', ''));
        $categoryId = $request->request->get('category');
        $status = $request->request->get('status', 'publié');
        
        if ($nomProduit === '' || strlen($nomProduit) < 3 || !preg_match('/^[A-Za-z0-9]+$/', $nomProduit)) {
            $this->addFlash('error', 'Le nom doit contenir au moins 3 caractères et seulement des lettres ou des chiffres.');
            return new RedirectResponse($this->generateUrl('app_organisateur_page', ['page' => 'produits']));
        }

        if ($description === '') {
            $this->addFlash('error', 'La description est obligatoire.');
            return new RedirectResponse($this->generateUrl('app_organisateur_page', ['page' => 'produits']));
        }

        if (!$categoryId) {
            $this->addFlash('error', 'La catégorie est requise.');
            return new RedirectResponse($this->generateUrl('app_organisateur_page', ['page' => 'produits']));
        }

        $uploadedFile = $request->files->get('imageProduit');
        if (!$uploadedFile || !$uploadedFile->isValid()) {
            $this->addFlash('error', 'L\'image est requise et doit être valide.');
            return new RedirectResponse($this->generateUrl('app_organisateur_page', ['page' => 'produits']));
        }

        if ($this->isDuplicateImage($uploadedFile)) {
            $this->addFlash('error', 'Cette image est déjà utilisée par un autre produit.');
            return new RedirectResponse($this->generateUrl('app_organisateur_page', ['page' => 'produits']));
        }

        $category = $this->categoryRepository->find($categoryId);
        
        if (!$category) {
            $this->addFlash('error', 'Catégorie non trouvée');
            return new RedirectResponse($this->generateUrl('app_organisateur_page', ['page' => 'produits']));
        }
        
        // Ensure upload directory exists
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
            error_log('Created upload directory: ' . $uploadDir);
        }
        
        $produit = new Produit();
        $produit->setNomProduit($nomProduit);
        $produit->setDescription($description);
        $produit->setCategory($category);
        $produit->setUser($user);
        $produit->setStatus($status);
        $produit->setCreatedAt(new \DateTime());
        $produit->setUpdatedAt(new \DateTime());
        
        // Handle image upload
        $uploadedFile = $request->files->get('imageProduit');
        
        error_log('FILES in request: ' . print_r($request->files->all(), true));
        error_log('Uploaded file object: ' . ($uploadedFile ? 'present' : 'null'));
        
        if ($uploadedFile) {
            error_log('File details: name=' . $uploadedFile->getClientOriginalName() . ', size=' . $uploadedFile->getSize() . ', type=' . $uploadedFile->getMimeType() . ', error=' . $uploadedFile->getError());
        }
        
        if ($uploadedFile && $uploadedFile->isValid()) {
            try {
                $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $uploadedFile->guessExtension();
                
                // Generate unique filename
                $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName) . '.' . $extension;
                
                error_log('Generated filename: ' . $filename);
                
                // Move file to uploads directory
                $uploadedFile->move($uploadDir, $filename);
                
                // Save filename to database
                $produit->setImageProduit($filename);
                
                // Log successful upload
                error_log('Image uploaded successfully: ' . $uploadDir . '/' . $filename);
            } catch (\Exception $e) {
                // Log but continue
                error_log('Image upload error: ' . $e->getMessage());
            }
        } else {
            error_log('No valid file uploaded or file field empty');
            if ($uploadedFile) {
                error_log('File error: ' . $uploadedFile->getErrorMessage());
            }
        }
        
        $this->entityManager->persist($produit);
        $this->entityManager->flush();
        
        // Add success message
        $this->addFlash('success', 'Produit ajouté avec succès!');
        
        return new RedirectResponse($this->generateUrl('app_organisateur_page', ['page' => 'produits']));
    }

    #[Route('/organisateur/produit/delete/{id}', name: 'app_produit_delete', methods: ['POST'])]
    public function deleteProduit(int $id): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new RedirectResponse($this->generateUrl('app_home'));
        }
        
        $produit = $this->produitRepository->find($id);
        
        if (!$produit || $produit->getUser() !== $user) {
            $this->addFlash('error', 'Produit non trouvé ou accès non autorisé');
            return new RedirectResponse($this->generateUrl('app_organisateur_page', ['page' => 'produits']));
        }
        
        // Remove related favorites and feedback first to avoid foreign key constraint violations
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Favoris f WHERE f.produit = :produit')
            ->setParameter('produit', $produit)
            ->execute();
        
        $this->entityManager->createQuery('DELETE FROM App\\Entity\\Feedback fb WHERE fb.produit = :produit')
            ->setParameter('produit', $produit)
            ->execute();
        
        // Delete the image file if it exists
        if ($produit->getImageProduit()) {
            $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $produit->getImageProduit();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        $this->entityManager->remove($produit);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Produit supprimé avec succès!');
        
        return new RedirectResponse($this->generateUrl('app_organisateur_page', ['page' => 'produits']));
    }

    #[Route('/organisateur/produit/edit/{id}', name: 'app_produit_edit', methods: ['GET', 'POST'])]
    public function editProduit(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new RedirectResponse($this->generateUrl('app_home'));
        }
        
        $produit = $this->produitRepository->find($id);
        
        if (!$produit || $produit->getUser() !== $user) {
            $this->addFlash('error', 'Produit non trouvé ou accès non autorisé');
            return new RedirectResponse($this->generateUrl('app_organisateur_page', ['page' => 'produits']));
        }
        
        $categories = $this->categoryRepository->findAll();
        
        if ($request->isMethod('POST')) {
            $nomProduit = trim((string) $request->request->get('nomProduit', ''));
            $description = trim((string) $request->request->get('description', ''));
            $categoryId = $request->request->get('category');
            $status = $request->request->get('status', 'publié');
            
            if ($nomProduit === '' || strlen($nomProduit) < 3 || !preg_match('/^[A-Za-z0-9]+$/', $nomProduit)) {
                $this->addFlash('error', 'Le nom doit contenir au moins 3 caractères et seulement des lettres ou des chiffres.');
                return $this->render('organisateur-dashboard.html.twig', [
                    'produits' => $this->produitRepository->findBy(['user' => $user]),
                    'categories' => $categories,
                    'editProduit' => $produit,
                    'activePage' => 'produits',
                ]);
            }
            
            if ($description === '') {
                $this->addFlash('error', 'La description est obligatoire.');
                return $this->render('organisateur-dashboard.html.twig', [
                    'produits' => $this->produitRepository->findBy(['user' => $user]),
                    'categories' => $categories,
                    'editProduit' => $produit,
                    'activePage' => 'produits',
                ]);
            }
            
            if (!$categoryId) {
                $this->addFlash('error', 'La catégorie est requise.');
                return $this->render('organisateur-dashboard.html.twig', [
                    'produits' => $this->produitRepository->findBy(['user' => $user]),
                    'categories' => $categories,
                    'editProduit' => $produit,
                    'activePage' => 'produits',
                ]);
            }
            
            $category = $this->categoryRepository->find($categoryId);
            
            if (!$category) {
                $this->addFlash('error', 'Catégorie non trouvée');
                return $this->render('organisateur-dashboard.html.twig', [
                    'produits' => $this->produitRepository->findBy(['user' => $user]),
                    'categories' => $categories,
                    'editProduit' => $produit,
                    'activePage' => 'produits',
                ]);
            }
            
            // Handle image upload (optional for edit)
            $uploadedFile = $request->files->get('imageProduit');
            
            if ($uploadedFile && $uploadedFile->isValid()) {
                if ($this->isDuplicateImage($uploadedFile, $produit->getId())) {
                    $this->addFlash('error', 'Cette image est déjà utilisée par un autre produit.');
                    return $this->render('organisateur-dashboard.html.twig', [
                        'produits' => $this->produitRepository->findBy(['user' => $user]),
                        'categories' => $categories,
                        'editProduit' => $produit,
                        'activePage' => 'produits',
                    ]);
                }
                // Delete old image if exists
                if ($produit->getImageProduit()) {
                    $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $produit->getImageProduit();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                
                try {
                    $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $uploadedFile->guessExtension();
                    
                    // Generate unique filename
                    $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName) . '.' . $extension;
                    
                    // Move file to uploads directory
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
                    $uploadedFile->move($uploadDir, $filename);
                    
                    // Save filename to database
                    $produit->setImageProduit($filename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image');
                    return $this->render('organisateur-dashboard.html.twig', [
                        'produits' => $this->produitRepository->findBy(['user' => $user]),
                        'categories' => $categories,
                        'editProduit' => $produit,
                        'activePage' => 'produits',
                    ]);
                }
            }
            
            $produit->setNomProduit($nomProduit);
            $produit->setDescription($description);
            $produit->setCategory($category);
            $produit->setStatus($status);
            $produit->setUpdatedAt(new \DateTime());
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Produit modifié avec succès!');
            
            return new RedirectResponse($this->generateUrl('app_organisateur_page', ['page' => 'produits']));
        }
        
        return $this->render('organisateur-dashboard.html.twig', [
            'produits' => $this->produitRepository->findBy(['user' => $user]),
            'categories' => $categories,
            'editProduit' => $produit,
            'activePage' => 'produits',
        ]);
    }

    #[Route('/organisateur/produit/toggle-status/{id}', name: 'app_produit_toggle_status', methods: ['POST'])]
    public function toggleStatus(int $id): Response
    {
        $user = $this->getUser();
        if (!$user) return new RedirectResponse($this->generateUrl('app_home'));

        $produit = $this->produitRepository->find($id);
        if (!$produit || $produit->getUser() !== $user) {
            $this->addFlash('error', 'Produit non trouvé');
            return new RedirectResponse($this->generateUrl('app_organisateur_page', ['page' => 'produits']));
        }

        $newStatus = $produit->getStatus() === 'publié' ? 'archivé' : 'publié';
        $produit->setStatus($newStatus);
        $this->entityManager->flush();

        $this->addFlash('success', 'Statut mis à jour : ' . $newStatus);
        return new RedirectResponse($this->generateUrl('app_organisateur_page', ['page' => 'produits']));
    }
}