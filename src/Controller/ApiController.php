<?php

namespace App\Controller;

use App\Entity\Participation;
use App\Entity\User;
use App\Entity\Produit;
use App\Entity\CategoryProduit;
use App\Entity\Collection;
use App\Entity\Donation;
use App\Entity\Coupon;
use App\Entity\Feedback;
use App\Entity\Favoris;
use App\Repository\GenreRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/health', name: 'api_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => 'API is working',
            'user' => $this->getUser() ? $this->getUser()->getEmail() : 'anonymous'
        ]);
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function registerApi(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        // Input validation
        $errors = [];
        
        $email = trim($data['email'] ?? '');
        $nom = trim($data['nom'] ?? '');
        $prenom = trim($data['prenom'] ?? '');
        $password = $data['password'] ?? '';
        $role = trim($data['role'] ?? 'participant');

        // Email validation
        if (!$email) {
            $errors['email'] = 'Email est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide.';
        }

        // Name validation
        if (!$nom) {
            $errors['nom'] = 'Nom est requis.';
        } elseif (strlen($nom) < 2) {
            $errors['nom'] = 'Nom doit contenir au moins 2 caractères.';
        }

        // Prenom validation
        if (!$prenom) {
            $errors['prenom'] = 'Prénom est requis.';
        } elseif (strlen($prenom) < 2) {
            $errors['prenom'] = 'Prénom doit contenir au moins 2 caractères.';
        }

        // Password validation
        if (!$password) {
            $errors['password'] = 'Mot de passe est requis.';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Mot de passe doit contenir au moins 6 caractères.';
        }

        // Role validation
        if (!in_array($role, ['participant', 'organisateur', 'admin'], true)) {
            $role = 'participant';
        }

        if (!empty($errors)) {
            return new JsonResponse([
                'success' => false,
                'errors' => $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if user exists
        $existingUser = $doctrine->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return new JsonResponse([
                'success' => false,
                'errors' => ['email' => 'Cet email existe déjà.']
            ], Response::HTTP_CONFLICT);
        }

        // Create user
        try {
            $user = new User();
            $user->setEmail($email);
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setRole(in_array($role, ['admin', 'organisateur'], true) ? $role : 'participant');
            $user->setPassword($password);
            $user->setCreatedAt(new \DateTime());
            $user->setUpdatedAt(new \DateTime());

            $entityManager = $doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Compte créé avec succès. Veuillez vous connecter.',
                'userId' => $user->getId()
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la création du compte.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/login-validate', name: 'api_login_validate', methods: ['POST'])]
    public function loginValidate(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $errors = [];
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!$email) {
            $errors['email'] = 'Email est requis.';
        }
        if (!$password) {
            $errors['password'] = 'Mot de passe est requis.';
        }

        if (!empty($errors)) {
            return new JsonResponse([
                'success' => false,
                'errors' => $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $doctrine->getRepository(User::class)->findOneBy(['email' => $email]);
        
        if (!$user || $user->getPassword() !== $password) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Email ou mot de passe incorrect.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Données valides.',
            'role' => $user->getRole(),
            'userId' => $user->getId()
        ]);
    }

    private function serializeUser(User $user): array
    {
        $photoUrl = $user->getPhoto() && $user->getPhoto() !== 'default.jpg' 
            ? '/uploads/users/' . $user->getPhoto() 
            : '/uploads/users/default.jpg';
            
        return [
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'genre' => $user->getGenre() ? $user->getGenre()->getSexe() : null,
            'photo' => $user->getPhoto(),
            'photoUrl' => $photoUrl,
            'createdAt' => $user->getCreatedAt() ? $user->getCreatedAt()->format('Y-m-d H:i:s') : null,
            'updatedAt' => $user->getUpdatedAt() ? $user->getUpdatedAt()->format('Y-m-d H:i:s') : null,
            'displayName' => trim($user->getPrenom() . ' ' . $user->getNom()),
        ];
    }

    private function normalizeRole(string $role): string
    {
        return in_array($role, ['admin', 'organisateur', 'participant'], true) ? $role : 'participant';
    }

    private function findGenre(string $genre, GenreRepository $genreRepository): ?Genre
    {
        return $genreRepository->findOneBy(['sexe' => $genre]);
    }

    #[Route('/admin/users', name: 'api_admin_users', methods: ['GET'])]
    public function listAdminUsers(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = trim($request->query->get('q', ''));
        $role = trim($request->query->get('role', ''));

        $qb = $doctrine->getRepository(User::class)->createQueryBuilder('u');

        if ($role && in_array($role, ['admin', 'organisateur', 'participant'], true)) {
            $qb->andWhere('u.role = :role')->setParameter('role', $role);
        }

        if ($search) {
            $qb->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $users = $qb->orderBy('u.created_at', 'DESC')->getQuery()->getResult();

        return new JsonResponse([
            'success' => true,
            'users' => array_map([$this, 'serializeUser'], $users)
        ]);
    }

    #[Route('/admin/user', name: 'api_admin_user_create', methods: ['POST'])]
    public function createAdminUser(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true) ?? [];
        $errors = [];

        $email = trim($data['email'] ?? '');
        $nom = trim($data['nom'] ?? '');
        $prenom = trim($data['prenom'] ?? '');
        $password = $data['password'] ?? '';
        $role = $this->normalizeRole(trim($data['role'] ?? 'participant'));
        $genre = trim($data['genre'] ?? '');
        $photo = $data['photo'] ?? null;

        if (!$email) {
            $errors['email'] = 'Email est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide.';
        }

        if (!$nom) {
            $errors['nom'] = 'Nom est requis.';
        } elseif (strlen($nom) < 2) {
            $errors['nom'] = 'Nom doit contenir au moins 2 caractères.';
        }

        if (!$prenom) {
            $errors['prenom'] = 'Prénom est requis.';
        } elseif (strlen($prenom) < 2) {
            $errors['prenom'] = 'Prénom doit contenir au moins 2 caractères.';
        }

        if (!$password) {
            $errors['password'] = 'Mot de passe est requis.';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Mot de passe doit contenir au moins 6 caractères.';
        }

        if (!empty($errors)) {
            return new JsonResponse(['success' => false, 'errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $existingUser = $doctrine->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return new JsonResponse(['success' => false, 'errors' => ['email' => 'Cet email existe déjà.']], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setRole($role);
        $user->setPassword($password);
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());

        if ($genre) {
            $genreEntity = $this->findGenre($genre, $doctrine->getRepository(Genre::class));
            if ($genreEntity) {
                $user->setGenre($genreEntity);
            }
        }

        if ($photo) {
            $photoFilename = $this->saveBase64Photo($photo, $user->getId() ?? uniqid());
            $user->setPhoto($photoFilename);
        } else {
            $user->setPhoto('default.jpg');
        }

        $entityManager = $doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'user' => $this->serializeUser($user)], Response::HTTP_CREATED);
    }

    #[Route('/admin/user/{id}', name: 'api_admin_user_update', methods: ['PUT', 'PATCH'])]
    public function updateAdminUser(int $id, Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true) ?? [];
        $user = $doctrine->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $errors = [];

        $email = trim($data['email'] ?? $user->getEmail());
        $nom = trim($data['nom'] ?? $user->getNom());
        $prenom = trim($data['prenom'] ?? $user->getPrenom());
        $password = $data['password'] ?? null;
        $role = $this->normalizeRole(trim($data['role'] ?? $user->getRole()));
        $genre = trim($data['genre'] ?? ($user->getGenre() ? $user->getGenre()->getSexe() : ''));
        $photo = $data['photo'] ?? null;

        if (!$email) {
            $errors['email'] = 'Email est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide.';
        }

        if (!$nom) {
            $errors['nom'] = 'Nom est requis.';
        } elseif (strlen($nom) < 2) {
            $errors['nom'] = 'Nom doit contenir au moins 2 caractères.';
        }

        if (!$prenom) {
            $errors['prenom'] = 'Prénom est requis.';
        } elseif (strlen($prenom) < 2) {
            $errors['prenom'] = 'Prénom doit contenir au moins 2 caractères.';
        }

        if ($password !== null && $password !== '' && strlen($password) < 6) {
            $errors['password'] = 'Mot de passe doit contenir au moins 6 caractères.';
        }

        if (!empty($errors)) {
            return new JsonResponse(['success' => false, 'errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $existingEmailUser = $doctrine->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingEmailUser && $existingEmailUser->getId() !== $user->getId()) {
            return new JsonResponse(['success' => false, 'errors' => ['email' => 'Cet email est déjà utilisé.']], Response::HTTP_CONFLICT);
        }

        $user->setEmail($email);
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setRole($role);

        if ($password) {
            $user->setPassword($password);
        }

        if ($genre) {
            $genreEntity = $this->findGenre($genre, $doctrine->getRepository(Genre::class));
            if ($genreEntity) {
                $user->setGenre($genreEntity);
            }
        } else {
            $user->setGenre(null);
        }

        if ($photo) {
            $photoFilename = $this->saveBase64Photo($photo, $user->getId());
            $user->setPhoto($photoFilename);
        }

        $user->setUpdatedAt(new \DateTime());
        $doctrine->getManager()->flush();

        return new JsonResponse(['success' => true, 'user' => $this->serializeUser($user)]);
    }

    #[Route('/admin/user/{id}', name: 'api_admin_user_delete', methods: ['DELETE'])]
    public function deleteAdminUser(int $id, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $doctrine->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $entityManager = $doctrine->getManager();
        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Utilisateur supprimé avec succès.']);
    }

    #[Route('/admin/user/{id}', name: 'api_admin_user_show', methods: ['GET'])]
    public function showAdminUser(int $id, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $doctrine->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['success' => true, 'user' => $this->serializeUser($user)]);
    }

    #[Route('/admin/users/import', name: 'api_admin_users_import', methods: ['POST'])]
    public function importAdminUsers(Request $request, ManagerRegistry $doctrine, GenreRepository $genreRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $file = $request->files->get('csv');
        if (!$file || !$file->isValid()) {
            return new JsonResponse(['success' => false, 'message' => 'Fichier CSV invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $handle = fopen($file->getPathname(), 'r');
        if (!$handle) {
            return new JsonResponse(['success' => false, 'message' => 'Impossible de lire le fichier CSV.'], Response::HTTP_BAD_REQUEST);
        }

        $header = fgetcsv($handle, 0, ',');
        if (!$header) {
            fclose($handle);
            return new JsonResponse(['success' => false, 'message' => 'CSV vide ou mal formé.'], Response::HTTP_BAD_REQUEST);
        }

        $header = array_map('strtolower', array_map('trim', $header));
        $mapping = array_flip($header);
        $allowed = ['email', 'nom', 'prenom', 'role', 'genre', 'password'];
        $rows = [];
        $results = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (count(array_filter($row, 'strlen')) === 0) {
                continue;
            }

            $data = [];
            foreach ($allowed as $field) {
                $data[$field] = isset($mapping[$field]) ? trim($row[$mapping[$field]]) : '';
            }

            $email = $data['email'];
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $results['errors'][] = 'Email invalide sur une ligne.';
                $results['skipped']++;
                continue;
            }

            $user = $doctrine->getRepository(User::class)->findOneBy(['email' => $email]);
            $isNew = $user === null;
            if ($isNew) {
                $user = new User();
                $user->setCreatedAt(new \DateTime());
            }

            $user->setEmail($email);
            $user->setNom($data['nom'] ?: 'Inconnu');
            $user->setPrenom($data['prenom'] ?: 'Utilisateur');
            $user->setRole($this->normalizeRole($data['role']));
            if ($data['password']) {
                $user->setPassword($data['password']);
            } elseif ($isNew) {
                $user->setPassword('changeme');
            }

            $genreEntity = $this->findGenre($data['genre'], $genreRepository);
            $user->setGenre($genreEntity);
            $user->setUpdatedAt(new \DateTime());

            $entityManager = $doctrine->getManager();
            $entityManager->persist($user);
            $isNew ? $results['created']++ : $results['updated']++;
        }

        fclose($handle);
        $doctrine->getManager()->flush();

        return new JsonResponse(['success' => true, 'message' => 'Import CSV terminé.', 'results' => $results]);
    }

    private function saveBase64Photo(string $base64Data, $userId): string
    {
        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/users';
        
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        if (preg_match('/^data:image\/([a-z]+);base64,(.+)$/i', $base64Data, $matches)) {
            $extension = $matches[1];
            $data = base64_decode($matches[2]);
            $filename = 'user_' . $userId . '_' . time() . '.' . $extension;
            file_put_contents($uploadsDir . '/' . $filename, $data);
            return $filename;
        }
        
        return 'default.jpg';
    }

    private function saveUploadedFile(UploadedFile $uploadedFile): string
    {
        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/users';
        
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $filename = uniqid('user_') . '.' . $uploadedFile->guessExtension();
        $uploadedFile->move($uploadsDir, $filename);
        
        return $filename;
    }

    #[Route('/admin/events', name: 'api_admin_events', methods: ['GET'])]
    public function getAdminEvents(ManagerRegistry $doctrine): JsonResponse
    {
        // Temporarily disable auth for testing
        // $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $events = $doctrine->getRepository('App\Entity\Evenement')->findAll();
        $users = $doctrine->getRepository('App\Entity\User')->findAll();
        $participationRepo = $doctrine->getRepository('App\Entity\Participation');

        $userMap = [];
        foreach ($users as $user) {
            $userMap[$user->getId()] = $user;
        }

        $eventsData = [];
        foreach ($events as $event) {
            $organisateur = isset($userMap[$event->getIdOrganisateur()]) ? $userMap[$event->getIdOrganisateur()] : null;
            try {
                $participantsCount = $participationRepo->count(['id_evenement' => $event->getIdEvenement()]);
            } catch (\Exception $e) {
                $participantsCount = 0;
            }

            $eventsData[] = [
                'id' => $event->getIdEvenement(),
                'titre' => $event->getTitre(),
                'description' => $event->getDescription(),
                'date_evenement' => $event->getDateEvenement() ? $event->getDateEvenement()->format('Y-m-d') : null,
                'heure_evenement' => $event->getDateEvenement() ? $event->getDateEvenement()->format('H:i') : null,
                'lieu' => $event->getLieu(),
                'organisateur' => $organisateur ? $organisateur->getDisplayName() : 'Inconnu',
                'id_organisateur' => $event->getIdOrganisateur(),
                'capacite_max' => $event->getCapaciteMax(),
                'image_evenement' => $event->getImageEvenement(),
                'statut' => $event->getStatutValidation() ?: 'en_attente',
                'participants_count' => $participantsCount,
                'created_at' => $event->getCreatedAt() ? $event->getCreatedAt()->format('Y-m-d H:i:s') : null,
                'updated_at' => $event->getUpdatedAt() ? $event->getUpdatedAt()->format('Y-m-d H:i:s') : null,
            ];
        }

        return new JsonResponse(['success' => true, 'events' => $eventsData]);
    }

    #[Route('/admin/events', name: 'api_admin_events_create', methods: ['POST'])]
    public function createEvent(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        // Temporarily disable auth for testing
        // $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $contentType = $request->headers->get('content-type') ?: '';
        if (str_contains($contentType, 'application/json')) {
            $data = json_decode($request->getContent(), true) ?? [];
        } else {
            $data = $request->request->all();
        }
        $errors = [];

        // Validation des données
        $titre = trim($data['titre'] ?? '');
        $description = trim($data['description'] ?? '');
        $dateEvenement = $data['date_evenement'] ?? '';
        $heureEvenement = $data['heure_evenement'] ?? '09:00';
        $lieu = trim($data['lieu'] ?? '');
        $idOrganisateur = (int)($data['id_organisateur'] ?? 0);
        $capaciteMax = (int)($data['capacite_max'] ?? 0);

        if (!$titre) {
            $errors['titre'] = 'Le titre est requis.';
        } elseif (strlen($titre) < 3) {
            $errors['titre'] = 'Le titre doit contenir au moins 3 caractères.';
        } elseif (strlen($titre) > 200) {
            $errors['titre'] = 'Le titre ne peut pas dépasser 200 caractères.';
        }

        if (!$description) {
            $errors['description'] = 'La description est requise.';
        } elseif (strlen($description) < 10) {
            $errors['description'] = 'La description doit contenir au moins 10 caractères.';
        }

        if (!$dateEvenement) {
            $errors['date_evenement'] = 'La date de l\'événement est requise.';
        } else {
            try {
                $dateTimeString = $dateEvenement;
                if (strpos($dateEvenement, 'T') === false) {
                    $dateTimeString = $dateEvenement . ' ' . $heureEvenement;
                }
                $dateObj = new \DateTime($dateTimeString);
                if ($dateObj < new \DateTime()) {
                    $errors['date_evenement'] = 'La date de l\'événement ne peut pas être dans le passé.';
                }
            } catch (\Exception $e) {
                $errors['date_evenement'] = 'Format de date invalide.';
            }
        }

        if (!$lieu) {
            $errors['lieu'] = 'Le lieu est requis.';
        } elseif (strlen($lieu) > 200) {
            $errors['lieu'] = 'Le lieu ne peut pas dépasser 200 caractères.';
        }

        if ($idOrganisateur <= 0) {
            $errors['id_organisateur'] = 'L\'organisateur est requis.';
        } else {
            $organisateur = $doctrine->getRepository('App\Entity\User')->find($idOrganisateur);
            if (!$organisateur || !in_array($organisateur->getRole(), ['organisateur', 'admin'])) {
                $errors['id_organisateur'] = 'Organisateur invalide.';
            }
        }

        if ($capaciteMax <= 0) {
            $errors['capacite_max'] = 'La capacité maximale doit être supérieure à 0.';
        } elseif ($capaciteMax > 1000) {
            $errors['capacite_max'] = 'La capacité maximale ne peut pas dépasser 1000 participants.';
        }

        if (!empty($errors)) {
            return new JsonResponse(['success' => false, 'errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // Créer l'événement
        $event = new \App\Entity\Evenement();
        $event->setTitre($titre);
        $event->setDescription($description);
        $dateTimeString = strpos($dateEvenement, 'T') === false ? $dateEvenement . ' ' . $heureEvenement : $dateEvenement;
        $event->setDateEvenement(new \DateTime($dateTimeString));
        $event->setLieu($lieu);
        $event->setIdOrganisateur($idOrganisateur);
        $event->setCapaciteMax($capaciteMax);

        $uploadedImage = $request->files->get('image_evenement');
        if ($uploadedImage instanceof UploadedFile && $uploadedImage->isValid()) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $extension = strtolower($uploadedImage->guessExtension() ?: '');
            if (!in_array($extension, $allowedExtensions, true)) {
                return new JsonResponse(['success' => false, 'errors' => ['image_evenement' => 'Format d\'image non pris en charge.']], Response::HTTP_BAD_REQUEST);
            }

            if ($uploadedImage->getSize() > 5 * 1024 * 1024) {
                return new JsonResponse(['success' => false, 'errors' => ['image_evenement' => 'L\'image ne peut pas dépasser 5 Mo.']], Response::HTTP_BAD_REQUEST);
            }

            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/events';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            $filename = uniqid('event_', true) . '.' . $extension;
            $uploadedImage->move($uploadsDir, $filename);
            $event->setImageEvenement($filename);
        }

        $event->setStatut('en_attente');
        $event->setCreatedAt(new \DateTime());
        $event->setUpdatedAt(new \DateTime());

        $entityManager = $doctrine->getManager();
        $entityManager->persist($event);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Événement créé avec succès.',
            'event' => [
                'id' => $event->getIdEvenement(),
                'titre' => $event->getTitre(),
                'description' => $event->getDescription(),
                'date_evenement' => $event->getDateEvenement()->format('Y-m-d'),
                'heure_evenement' => $event->getDateEvenement()->format('H:i'),
                'lieu' => $event->getLieu(),
                'organisateur' => $organisateur->getDisplayName(),
                'id_organisateur' => $event->getIdOrganisateur(),
                'capacite_max' => $event->getCapaciteMax(),
                'image_evenement' => $event->getImageEvenement(),
                'statut' => $event->getStatut(),
                'created_at' => $event->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $event->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]
        ], Response::HTTP_CREATED);
    }

    private function getRequestData(Request $request): array
    {
        $contentType = $request->headers->get('content-type') ?: '';
        if (str_starts_with($contentType, 'application/json')) {
            return json_decode($request->getContent(), true) ?? [];
        }
        return $request->request->all();
    }

    private function uploadEventImage(Request $request): ?string
    {
        $uploadedFile = $request->files->get('image_evenement');
        if (!$uploadedFile instanceof UploadedFile) {
            return null;
        }

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($uploadedFile->getMimeType(), $allowedTypes, true)) {
            return null;
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $uploadsDir = $projectDir . '/public/uploads/events';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $extension = $uploadedFile->guessExtension() ?: $uploadedFile->getClientOriginalExtension();
        $filename = uniqid('event_', true) . '.' . ($extension ?: 'jpg');

        $uploadedFile->move($uploadsDir, $filename);

        return $filename;
    }

    #[Route('/organisateur/events', name: 'api_organisateur_events_create', methods: ['POST'])]
    public function createEventOrganisateur(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        try {
            // Temporarily remove auth check for testing
            // $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');

            $user = $this->getUser();
            $userId = $user ? $user->getId() : 2; // Default to user 2 for testing
            $data = json_decode($request->getContent(), true) ?? [];

            if (!$data) {
                return new JsonResponse(['success' => false, 'message' => 'Données JSON invalides'], 400);
            }

            $errors = [];

            // Validation des données
            $titre = trim($data['titre'] ?? '');
            $description = trim($data['description'] ?? '');
            $dateEvenement = $data['date_evenement'] ?? '';
            $heureEvenement = $data['heure_evenement'] ?? '09:00';
            $lieu = trim($data['lieu'] ?? '');
            $capaciteMax = (int)($data['capacite_max'] ?? 0);

            if (!$titre) {
                $errors['titre'] = 'Le titre est requis.';
            } elseif (strlen($titre) < 3) {
                $errors['titre'] = 'Le titre doit contenir au moins 3 caractères.';
            } elseif (strlen($titre) > 200) {
                $errors['titre'] = 'Le titre ne peut pas dépasser 200 caractères.';
            }

            if (!$description) {
                $errors['description'] = 'La description est requise.';
            } elseif (strlen($description) < 10) {
                $errors['description'] = 'La description doit contenir au moins 10 caractères.';
            }

            if (!$dateEvenement) {
                $errors['date_evenement'] = 'La date de l\'événement est requise.';
            } else {
                try {
                    $dateTimeString = $dateEvenement . ' ' . $heureEvenement;
                    $dateObj = new \DateTime($dateTimeString);
                    // Temporarily disable past date validation for testing
                    // if ($dateObj < new \DateTime()) {
                    //     $errors['date_evenement'] = 'La date de l\'événement ne peut pas être dans le passé.';
                    // }
                } catch (\Exception $e) {
                    $errors['date_evenement'] = 'Format de date invalide.';
                }
            }

            if (!$lieu) {
                $errors['lieu'] = 'Le lieu est requis.';
            } elseif (strlen($lieu) > 200) {
                $errors['lieu'] = 'Le lieu ne peut pas dépasser 200 caractères.';
            }

            if ($capaciteMax <= 0) {
                $errors['capacite_max'] = 'La capacité maximale doit être supérieure à 0.';
            } elseif ($capaciteMax > 1000) {
                $errors['capacite_max'] = 'La capacité maximale ne peut pas dépasser 1000 participants.';
            }

            if (!empty($errors)) {
                return new JsonResponse(['success' => false, 'errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            // Créer l'événement
            $event = new \App\Entity\Evenement();
            $event->setTitre($titre);
            $event->setDescription($description);
            $dateTimeString = $dateEvenement . ' ' . $heureEvenement;
            $event->setDateEvenement(new \DateTime($dateTimeString));
            $event->setLieu($lieu);
            $event->setIdOrganisateur($userId);
            $event->setCapaciteMax($capaciteMax);
            $event->setStatut('en_attente');
            $event->setStatutValidation('en_attente');
            $event->setDateSoumission(new \DateTime());
            $event->setCreatedAt(new \DateTime());
            $event->setUpdatedAt(new \DateTime());

            $entityManager = $doctrine->getManager();
            $entityManager->persist($event);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Événement créé avec succès. Il sera soumis à validation par un administrateur.',
                'event' => [
                    'id' => $event->getIdEvenement(),
                    'titre' => $event->getTitre(),
                    'description' => $event->getDescription(),
                    'date_evenement' => $event->getDateEvenement()->format('Y-m-d'),
                    'heure_evenement' => $event->getDateEvenement()->format('H:i'),
                    'lieu' => $event->getLieu(),
                    'organisateur' => $user ? $user->getDisplayName() : 'Test User',
                    'id_organisateur' => $event->getIdOrganisateur(),
                    'capacite_max' => $event->getCapaciteMax(),
                    'statut' => $event->getStatut(),
                    'created_at' => $event->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $event->getUpdatedAt()->format('Y-m-d H:i:s'),
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            error_log('Create Event API Error: ' . $e->getMessage());
            return new JsonResponse(['success' => false, 'message' => 'Erreur interne du serveur: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/organisateur/events', name: 'api_organisateur_events', methods: ['GET'])]
    public function getOrganisateurEvents(ManagerRegistry $doctrine): JsonResponse
    {
        try {
            // Temporarily remove auth check for testing
            // $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');

            $user = $this->getUser();
            $userId = $user ? $user->getId() : 2; // Default to user 2 for testing

            $events = $doctrine->getRepository('App\Entity\Evenement')->findBy(['id_organisateur' => $userId]);
            $participationRepo = $doctrine->getRepository('App\Entity\Participation');

            $eventsData = [];
            foreach ($events as $event) {
                try {
                    $participantsCount = $participationRepo->countByEvent($event->getIdEvenement());

                    $eventsData[] = [
                        'id' => $event->getIdEvenement(),
                        'id_evenement' => $event->getIdEvenement(),
                        'titre' => $event->getTitre(),
                        'description' => $event->getDescription(),
                        'date_evenement' => $event->getDateEvenement() ? $event->getDateEvenement()->format('Y-m-d') : null,
                        'heure_evenement' => $event->getDateEvenement() ? $event->getDateEvenement()->format('H:i') : null,
                        'lieu' => $event->getLieu(),
                        'organisateur' => $user ? $user->getDisplayName() : 'Test User',
                        'capacite_max' => $event->getCapaciteMax(),
                        'image_evenement' => $event->getImageEvenement(),
                        'statut' => $event->getStatutValidation() ?: 'en_attente',
                        'statut_validation' => $event->getStatutValidation() ?: 'en_attente',
                        'participants_count' => $participantsCount,
                        'places_left' => max(0, $event->getCapaciteMax() - $participantsCount),
                        'created_at' => $event->getCreatedAt() ? $event->getCreatedAt()->format('Y-m-d H:i:s') : null,
                        'updated_at' => $event->getUpdatedAt() ? $event->getUpdatedAt()->format('Y-m-d H:i:s') : null,
                    ];
                } catch (\Exception $e) {
                    // Log error for this event
                    error_log('Error processing event ' . $event->getIdEvenement() . ': ' . $e->getMessage());
                    continue;
                }
            }

            return new JsonResponse(['success' => true, 'events' => $eventsData]);
        } catch (\Exception $e) {
            error_log('API Error: ' . $e->getMessage());
            return new JsonResponse(['success' => false, 'message' => 'Erreur interne du serveur: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/organisateur/events/{id}', name: 'api_organisateur_events_update', methods: ['PUT'])]
    public function updateOrganisateurEvent(int $id, Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        try {
            // $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');
            $user = $this->getUser();
            $userId = $user ? $user->getId() : 2;

            $event = $doctrine->getRepository('App\Entity\Evenement')->find($id);
            if (!$event) {
                return new JsonResponse(['success' => false, 'message' => 'Événement introuvable.'], Response::HTTP_NOT_FOUND);
            }

            if ($event->getIdOrganisateur() !== $userId) {
                return new JsonResponse(['success' => false, 'message' => 'Accès non autorisé.'], Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true) ?? [];
            $titre = trim($data['titre'] ?? $event->getTitre());
            $description = trim($data['description'] ?? $event->getDescription());
            $dateEvenement = $data['date_evenement'] ?? ($event->getDateEvenement() ? $event->getDateEvenement()->format('Y-m-d') : '');
            $heureEvenement = $data['heure_evenement'] ?? ($event->getDateEvenement() ? $event->getDateEvenement()->format('H:i') : '09:00');
            $lieu = trim($data['lieu'] ?? $event->getLieu());
            $capaciteMax = (int)($data['capacite_max'] ?? $event->getCapaciteMax());

            $errors = [];
            if (!$titre) {
                $errors['titre'] = 'Le titre est requis.';
            } elseif (strlen($titre) < 3) {
                $errors['titre'] = 'Le titre doit contenir au moins 3 caractères.';
            } elseif (strlen($titre) > 200) {
                $errors['titre'] = 'Le titre ne peut pas dépasser 200 caractères.';
            }
            if (!$description) {
                $errors['description'] = 'La description est requise.';
            } elseif (strlen($description) < 10) {
                $errors['description'] = 'La description doit contenir au moins 10 caractères.';
            }
            if (!$dateEvenement) {
                $errors['date_evenement'] = 'La date de l\'événement est requise.';
            } else {
                try {
                    $dateTimeString = $dateEvenement . ' ' . $heureEvenement;
                    $dateObj = new \DateTime($dateTimeString);
                    if ($dateObj < new \DateTime() && $event->getStatutValidation() === 'en_attente') {
                        $errors['date_evenement'] = 'La date de l\'événement ne peut pas être dans le passé.';
                    }
                } catch (\Exception $e) {
                    $errors['date_evenement'] = 'Format de date invalide.';
                }
            }
            if (!$lieu) {
                $errors['lieu'] = 'Le lieu est requis.';
            } elseif (strlen($lieu) > 200) {
                $errors['lieu'] = 'Le lieu ne peut pas dépasser 200 caractères.';
            }
            if ($capaciteMax <= 0) {
                $errors['capacite_max'] = 'La capacité maximale doit être supérieure à 0.';
            } elseif ($capaciteMax > 1000) {
                $errors['capacite_max'] = 'La capacité maximale ne peut pas dépasser 1000 participants.';
            }
            if (!empty($errors)) {
                return new JsonResponse(['success' => false, 'errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $event->setTitre($titre);
            $event->setDescription($description);
            $dateTimeString = $dateEvenement . ' ' . $heureEvenement;
            $event->setDateEvenement(new \DateTime($dateTimeString));
            $event->setLieu($lieu);
            $event->setCapaciteMax($capaciteMax);
            $event->setUpdatedAt(new \DateTime());

            $doctrine->getManager()->flush();

            return new JsonResponse(['success' => true, 'message' => 'Événement mis à jour avec succès.', 'event' => [
                'id' => $event->getIdEvenement(),
                'titre' => $event->getTitre(),
                'description' => $event->getDescription(),
                'date_evenement' => $event->getDateEvenement()->format('Y-m-d'),
                'heure_evenement' => $event->getDateEvenement()->format('H:i'),
                'lieu' => $event->getLieu(),
                'id_organisateur' => $event->getIdOrganisateur(),
                'capacite_max' => $event->getCapaciteMax(),
                'statut' => $event->getStatutValidation(),
                'created_at' => $event->getCreatedAt() ? $event->getCreatedAt()->format('Y-m-d H:i:s') : null,
                'updated_at' => $event->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]], Response::HTTP_OK);
        } catch (\Exception $e) {
            error_log('Organisateur Update Event Error: ' . $e->getMessage());
            return new JsonResponse(['success' => false, 'message' => 'Erreur interne du serveur.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/organisateur/events/{id}', name: 'api_organisateur_events_delete', methods: ['DELETE'])]
    public function deleteOrganisateurEvent(int $id, ManagerRegistry $doctrine): JsonResponse
    {
        try {
            // $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');
            $user = $this->getUser();
            $userId = $user ? $user->getId() : 2;
            $event = $doctrine->getRepository('App\Entity\Evenement')->find($id);
            if (!$event) {
                return new JsonResponse(['success' => false, 'message' => 'Événement introuvable.'], Response::HTTP_NOT_FOUND);
            }
            if ($event->getIdOrganisateur() !== $userId) {
                return new JsonResponse(['success' => false, 'message' => 'Accès non autorisé.'], Response::HTTP_FORBIDDEN);
            }
            $entityManager = $doctrine->getManager();
            $entityManager->remove($event);
            $entityManager->flush();
            return new JsonResponse(['success' => true, 'message' => 'Événement supprimé avec succès.']);
        } catch (\Exception $e) {
            error_log('Organisateur Delete Event Error: ' . $e->getMessage());
            return new JsonResponse(['success' => false, 'message' => 'Erreur interne du serveur.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/admin/events/{id}', name: 'api_admin_events_update', methods: ['PUT','POST'])]
    public function updateEvent(int $id, Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        // Temporarily disable auth for testing
        // $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $event = $doctrine->getRepository('App\Entity\Evenement')->find($id);
        if (!$event) {
            return new JsonResponse(['success' => false, 'message' => 'Événement introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $contentType = $request->headers->get('content-type') ?: '';
        if (str_contains($contentType, 'application/json')) {
            $data = json_decode($request->getContent(), true) ?? [];
        } else {
            $data = $request->request->all();
        }
        $errors = [];

        // Validation des données
        $titre = trim($data['titre'] ?? $event->getTitre());
        $description = trim($data['description'] ?? $event->getDescription());
        $dateEvenement = $data['date_evenement'] ?? $event->getDateEvenement()->format('Y-m-d');
        $heureEvenement = $data['heure_evenement'] ?? $event->getDateEvenement()->format('H:i');
        $lieu = trim($data['lieu'] ?? $event->getLieu());
        $idOrganisateur = (int)($data['id_organisateur'] ?? $event->getIdOrganisateur());
        $capaciteMax = (int)($data['capacite_max'] ?? $event->getCapaciteMax());

        if (!$titre) {
            $errors['titre'] = 'Le titre est requis.';
        } elseif (strlen($titre) < 3) {
            $errors['titre'] = 'Le titre doit contenir au moins 3 caractères.';
        } elseif (strlen($titre) > 200) {
            $errors['titre'] = 'Le titre ne peut pas dépasser 200 caractères.';
        }

        if (!$description) {
            $errors['description'] = 'La description est requise.';
        } elseif (strlen($description) < 10) {
            $errors['description'] = 'La description doit contenir au moins 10 caractères.';
        }

        if (!$dateEvenement) {
            $errors['date_evenement'] = 'La date de l\'événement est requise.';
        } else {
            try {
                $dateTimeString = $dateEvenement;
                if (strpos($dateEvenement, 'T') === false) {
                    $dateTimeString = $dateEvenement . ' ' . $heureEvenement;
                }
                $dateObj = new \DateTime($dateTimeString);
                if ($dateObj < new \DateTime() && $event->getStatutValidation() === 'en_attente') {
                    $errors['date_evenement'] = 'La date de l\'événement ne peut pas être dans le passé.';
                }
            } catch (\Exception $e) {
                $errors['date_evenement'] = 'Format de date invalide.';
            }
        }

        if (!$lieu) {
            $errors['lieu'] = 'Le lieu est requis.';
        } elseif (strlen($lieu) > 200) {
            $errors['lieu'] = 'Le lieu ne peut pas dépasser 200 caractères.';
        }

        if ($idOrganisateur <= 0) {
            $errors['id_organisateur'] = 'L\'organisateur est requis.';
        } else {
            $organisateur = $doctrine->getRepository('App\Entity\User')->find($idOrganisateur);
            if (!$organisateur || !in_array($organisateur->getRole(), ['organisateur', 'admin'])) {
                $errors['id_organisateur'] = 'Organisateur invalide.';
            }
        }

        if ($capaciteMax <= 0) {
            $errors['capacite_max'] = 'La capacité maximale doit être supérieure à 0.';
        } elseif ($capaciteMax > 1000) {
            $errors['capacite_max'] = 'La capacité maximale ne peut pas dépasser 1000 participants.';
        }

        if (!empty($errors)) {
            return new JsonResponse(['success' => false, 'errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // Mettre à jour l'événement
        $event->setTitre($titre);
        $event->setDescription($description);

        $uploadedImage = $request->files->get('image_evenement');
        if ($uploadedImage instanceof UploadedFile && $uploadedImage->isValid()) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $extension = strtolower($uploadedImage->guessExtension() ?: '');
            if (!in_array($extension, $allowedExtensions, true)) {
                return new JsonResponse(['success' => false, 'errors' => ['image_evenement' => 'Format d\'image non pris en charge.']], Response::HTTP_BAD_REQUEST);
            }

            if ($uploadedImage->getSize() > 5 * 1024 * 1024) {
                return new JsonResponse(['success' => false, 'errors' => ['image_evenement' => 'L\'image ne peut pas dépasser 5 Mo.']], Response::HTTP_BAD_REQUEST);
            }

            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/events';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            $filename = uniqid('event_', true) . '.' . $extension;
            $uploadedImage->move($uploadsDir, $filename);
            $event->setImageEvenement($filename);
        }

        $dateTimeString = strpos($dateEvenement, 'T') === false ? $dateEvenement . ' ' . $heureEvenement : $dateEvenement;
        $event->setDateEvenement(new \DateTime($dateTimeString));
        $event->setLieu($lieu);
        $event->setIdOrganisateur($idOrganisateur);
        $event->setCapaciteMax($capaciteMax);
        $event->setUpdatedAt(new \DateTime());

        $entityManager = $doctrine->getManager();
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Événement mis à jour avec succès.',
            'event' => [
                'id' => $event->getIdEvenement(),
                'titre' => $event->getTitre(),
                'description' => $event->getDescription(),
                'date_evenement' => $event->getDateEvenement()->format('Y-m-d H:i:s'),
                'lieu' => $event->getLieu(),
                'organisateur' => $organisateur->getDisplayName(),
                'id_organisateur' => $event->getIdOrganisateur(),
                'capacite_max' => $event->getCapaciteMax(),
                'statut' => $event->getStatut(),
                'created_at' => $event->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $event->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    #[Route('/admin/events/{id}', name: 'api_admin_events_show', methods: ['GET'])]
    public function showEvent(int $id, ManagerRegistry $doctrine): JsonResponse
    {
        // Temporarily disable auth for testing
        // $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $event = $doctrine->getRepository('App\Entity\Evenement')->find($id);
        if (!$event) {
            return new JsonResponse(['success' => false, 'message' => 'Événement introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $organisateur = $doctrine->getRepository('App\Entity\User')->find($event->getIdOrganisateur());

        // Récupérer les participants
        $conn = $doctrine->getConnection();
        $sql = 'SELECT p.id AS id, p.id_user, p.id_evenement, p.contact, p.age, p.date_inscription, p.statut, u.nom, u.prenom, u.email FROM participation p LEFT JOIN users u ON p.id_user = u.id WHERE p.id_evenement = :eventId';
        $result = $conn->executeQuery($sql, ['eventId' => $id]);
        $rows = $result->fetchAllAssociative();

        $participants = [];
        foreach ($rows as $row) {
            $participants[] = [
                'id' => $row['id'],
                'nom' => $row['nom'],
                'prenom' => $row['prenom'],
                'email' => $row['email'],
                'contact' => $row['contact'],
                'age' => $row['age'],
                'statut' => $row['statut'],
                'date_inscription' => $row['date_inscription'],
            ];
        }

        return new JsonResponse([
            'success' => true,
            'event' => [
                'id' => $event->getIdEvenement(),
                'titre' => $event->getTitre(),
                'description' => $event->getDescription(),
                'date_evenement' => $event->getDateEvenement() ? $event->getDateEvenement()->format('Y-m-d H:i:s') : null,
                'lieu' => $event->getLieu(),
                'organisateur' => $organisateur ? $organisateur->getDisplayName() : 'Inconnu',
                'id_organisateur' => $event->getIdOrganisateur(),
                'capacite_max' => $event->getCapaciteMax(),
                'image_evenement' => $event->getImageEvenement(),
                'statut' => $event->getStatutValidation(),
                'created_at' => $event->getCreatedAt() ? $event->getCreatedAt()->format('Y-m-d H:i:s') : null,
                'updated_at' => $event->getUpdatedAt() ? $event->getUpdatedAt()->format('Y-m-d H:i:s') : null,
            ],
            'participants' => $participants,
        ]);
    }

    #[Route('/admin/events/{id}', name: 'api_admin_events_delete', methods: ['DELETE'])]
    public function deleteEvent(int $id, ManagerRegistry $doctrine): JsonResponse
    {
        // Temporarily disable auth for testing
        // $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $event = $doctrine->getRepository('App\Entity\Evenement')->find($id);
        if (!$event) {
            return new JsonResponse(['success' => false, 'message' => 'Événement introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $entityManager = $doctrine->getManager();
        $entityManager->remove($event);
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Événement supprimé avec succès.']);
    }

    #[Route('/admin/events/{id}/validate', name: 'api_admin_event_validate', methods: ['POST'])]
    public function validateEvent(int $id, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $event = $doctrine->getRepository('App\Entity\Evenement')->find($id);
        if (!$event) {
            return new JsonResponse(['success' => false, 'message' => 'Événement introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $event->setStatut('valide');
        $event->setStatutValidation('valide');
        $event->setDateValidation(new \DateTime());
        $event->setUpdatedAt(new \DateTime());

        $entityManager = $doctrine->getManager();
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Événement validé avec succès.']);
    }

    #[Route('/admin/events/{id}/reject', name: 'api_admin_event_reject', methods: ['POST'])]
    public function rejectEvent(int $id, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $event = $doctrine->getRepository('App\Entity\Evenement')->find($id);
        if (!$event) {
            return new JsonResponse(['success' => false, 'message' => 'Événement introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $event->setStatut('rejete');
        $event->setStatutValidation('rejete');
        $event->setDateValidation(new \DateTime());
        $event->setUpdatedAt(new \DateTime());

        $entityManager = $doctrine->getManager();
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Événement rejeté avec succès.']);
    }

    #[Route('/admin/organisateurs', name: 'api_admin_organisateurs', methods: ['GET'])]
    public function getOrganisateurs(ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $qb = $doctrine->getRepository('App\Entity\User')->createQueryBuilder('u');
        $qb->where($qb->expr()->in('u.role', ':roles'))
            ->setParameter('roles', ['organisateur', 'admin'])
            ->orderBy('u.nom', 'ASC');

        $organisateurs = $qb->getQuery()->getResult();

        $data = [];
        foreach ($organisateurs as $user) {
            $data[] = [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'displayName' => $user->getDisplayName(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
            ];
        }

        return new JsonResponse(['success' => true, 'organisateurs' => $data]);
    }

    // ─── PARTICIPANT EVENTS ───

    #[Route('/events', name: 'api_participant_events', methods: ['GET'])]
    public function getParticipantEvents(ManagerRegistry $doctrine): JsonResponse
    {
        try {
            $participationRepo = $doctrine->getRepository('App\Entity\Participation');
            $events = $doctrine->getRepository('App\Entity\Evenement')->findBy(
                ['statut_validation' => 'valide'],
                ['date_evenement' => 'ASC']
            );

            $user = $this->getUser();
            $userId = $user ? $user->getId() : null;
            
            $eventsData = [];
            foreach ($events as $event) {
                try {
                    $participantsCount = $participationRepo->countByEvent($event->getIdEvenement());
                    $isRegistered = false;
                    
                    if ($userId) {
                        $participation = $participationRepo->findOneBy([
                            'id_user' => $userId,
                            'id_evenement' => $event->getIdEvenement()
                        ]);
                        $isRegistered = $participation !== null;
                    }

                    $organisateur = $doctrine->getRepository('App\Entity\User')->find($event->getIdOrganisateur());
                    
                    $eventsData[] = [
                        'id' => $event->getIdEvenement(),
                        'titre' => $event->getTitre(),
                        'description' => $event->getDescription(),
                        'date_evenement' => $event->getDateEvenement() ? $event->getDateEvenement()->format('Y-m-d') : null,
                        'heure_evenement' => $event->getDateEvenement() ? $event->getDateEvenement()->format('H:i') : null,
                        'lieu' => $event->getLieu(),
                        'organisateur' => $organisateur ? $organisateur->getDisplayName() : 'Inconnu',
                        'capacite_max' => $event->getCapaciteMax(),
                        'image_evenement' => $event->getImageEvenement(),
                        'participants_count' => $participantsCount,
                        'is_registered' => $isRegistered,
                        'places_left' => max(0, $event->getCapaciteMax() - $participantsCount),
                    ];
                } catch (\Exception $e) {
                    error_log('Error processing event ' . $event->getIdEvenement() . ': ' . $e->getMessage());
                    continue;
                }
            }

            return new JsonResponse(['success' => true, 'events' => $eventsData]);
        } catch (\Exception $e) {
            error_log('API Error: ' . $e->getMessage());
            return new JsonResponse(['success' => false, 'message' => 'Erreur interne du serveur'], 500);
        }
    }

    #[Route('/events/{id}', name: 'api_participant_event_show', methods: ['GET'])]
    public function getParticipantEvent(int $id, ManagerRegistry $doctrine): JsonResponse
    {
        try {
            $event = $doctrine->getRepository('App\Entity\Evenement')->find($id);
            if (!$event) {
                return new JsonResponse(['success' => false, 'message' => 'Événement introuvable.'], Response::HTTP_NOT_FOUND);
            }

            if ($event->getStatutValidation() !== 'valide') {
                return new JsonResponse(['success' => false, 'message' => 'Cet événement n\'est pas disponible.'], Response::HTTP_FORBIDDEN);
            }

            $user = $this->getUser();
            $userId = $user ? $user->getId() : null;
            $participationRepo = $doctrine->getRepository('App\Entity\Participation');
            $participantsCount = $participationRepo->countByEvent($event->getIdEvenement());
            
            $isRegistered = false;
            if ($userId) {
                $participation = $participationRepo->findOneBy([
                    'id_user' => $userId,
                    'id_evenement' => $event->getIdEvenement()
                ]);
                $isRegistered = $participation !== null;
            }

            $organisateur = $doctrine->getRepository('App\Entity\User')->find($event->getIdOrganisateur());

            return new JsonResponse([
                'success' => true,
                'event' => [
                    'id' => $event->getIdEvenement(),
                    'titre' => $event->getTitre(),
                    'description' => $event->getDescription(),
                    'date_evenement' => $event->getDateEvenement() ? $event->getDateEvenement()->format('Y-m-d') : null,
                    'heure_evenement' => $event->getDateEvenement() ? $event->getDateEvenement()->format('H:i') : null,
                    'lieu' => $event->getLieu(),
                    'organisateur' => $organisateur ? $organisateur->getDisplayName() : 'Inconnu',
                    'capacite_max' => $event->getCapaciteMax(),
                    'image_evenement' => $event->getImageEvenement(),
                    'participants_count' => $participantsCount,
                    'is_registered' => $isRegistered,
                    'places_left' => max(0, $event->getCapaciteMax() - $participantsCount),
                    'created_at' => $event->getCreatedAt() ? $event->getCreatedAt()->format('Y-m-d H:i:s') : null,
                ]
            ]);
        } catch (\Exception $e) {
            error_log('API Error: ' . $e->getMessage());
            return new JsonResponse(['success' => false, 'message' => 'Erreur interne du serveur'], 500);
        }
    }

    #[Route('/events/{id}/subscribe', name: 'api_participant_event_subscribe', methods: ['POST'])]
    public function subscribeEvent(int $id, Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse(['success' => false, 'message' => 'Vous devez être connecté.'], Response::HTTP_UNAUTHORIZED);
            }

            $event = $doctrine->getRepository('App\Entity\Evenement')->find($id);
            if (!$event) {
                return new JsonResponse(['success' => false, 'message' => 'Événement introuvable.'], Response::HTTP_NOT_FOUND);
            }

            if ($event->getStatutValidation() !== 'valide') {
                return new JsonResponse(['success' => false, 'message' => 'Cet événement n\'est pas disponible.'], Response::HTTP_FORBIDDEN);
            }

            $participationRepo = $doctrine->getRepository('App\Entity\Participation');
            $participantsCount = $participationRepo->countByEvent($event->getIdEvenement());
            
            if ($participantsCount >= $event->getCapaciteMax()) {
                return new JsonResponse(['success' => false, 'message' => 'L\'événement est complet.'], Response::HTTP_CONFLICT);
            }

            $existingParticipation = $participationRepo->findOneBy([
                'id_user' => $user->getId(),
                'id_evenement' => $event->getIdEvenement()
            ]);

            if ($existingParticipation) {
                return new JsonResponse(['success' => false, 'message' => 'Vous êtes déjà inscrit à cet événement.'], Response::HTTP_CONFLICT);
            }

            $data = json_decode($request->getContent(), true) ?? [];
            $contact = trim($data['contact'] ?? $user->getEmail());
            $age = (int)($data['age'] ?? 0);

            if (!$contact) {
                return new JsonResponse(['success' => false, 'message' => 'Le contact est requis.'], Response::HTTP_BAD_REQUEST);
            }

            $participation = new \App\Entity\Participation();
            $participation->setIdUser($user->getId());
            $participation->setIdEvenement($event->getIdEvenement());
            $participation->setContact($contact);
            $participation->setAge($age ?: null);
            $participation->setStatut('inscrit');
            $participation->setDateInscription(new \DateTime());

            $entityManager = $doctrine->getManager();
            $entityManager->persist($participation);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Inscription à l\'événement réussie!',
                'participation' => [
                    'id' => $participation->getId(),
                    'contact' => $participation->getContact(),
                    'age' => $participation->getAge(),
                    'date_inscription' => $participation->getDateInscription()->format('Y-m-d H:i:s'),
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            error_log('Subscribe Error: ' . $e->getMessage());
            return new JsonResponse(['success' => false, 'message' => 'Erreur interne du serveur'], 500);
        }
    }

    #[Route('/my-events', name: 'api_participant_my_events', methods: ['GET'])]
    public function getMyEvents(ManagerRegistry $doctrine): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse(['success' => false, 'message' => 'Vous devez être connecté.'], Response::HTTP_UNAUTHORIZED);
            }

            $participations = $doctrine->getRepository('App\Entity\Participation')->findBy(
                ['id_user' => $user->getId()],
                ['date_inscription' => 'DESC']
            );

            $eventsData = [];
            foreach ($participations as $participation) {
                try {
                    $event = $doctrine->getRepository('App\Entity\Evenement')->find($participation->getIdEvenement());
                    if (!$event) {
                        continue;
                    }

                    $organisateur = $doctrine->getRepository('App\Entity\User')->find($event->getIdOrganisateur());
                    $participationRepo = $doctrine->getRepository('App\Entity\Participation');
                    $participantsCount = $participationRepo->countByEvent($event->getIdEvenement());

                    $eventsData[] = [
                        'id' => $event->getIdEvenement(),
                        'titre' => $event->getTitre(),
                        'description' => $event->getDescription(),
                        'date_evenement' => $event->getDateEvenement() ? $event->getDateEvenement()->format('Y-m-d') : null,
                        'heure_evenement' => $event->getDateEvenement() ? $event->getDateEvenement()->format('H:i') : null,
                        'lieu' => $event->getLieu(),
                        'organisateur' => $organisateur ? $organisateur->getDisplayName() : 'Inconnu',
                        'capacite_max' => $event->getCapaciteMax(),
                        'participants_count' => $participantsCount,
                        'participation_statut' => $participation->getStatut(),
                        'date_inscription' => $participation->getDateInscription() ? $participation->getDateInscription()->format('Y-m-d H:i:s') : null,
                    ];
                } catch (\Exception $e) {
                    error_log('Error processing participation: ' . $e->getMessage());
                    continue;
                }
            }

            return new JsonResponse(['success' => true, 'events' => $eventsData]);
        } catch (\Exception $e) {
            error_log('API Error: ' . $e->getMessage());
            return new JsonResponse(['success' => false, 'message' => 'Erreur interne du serveur'], 500);
        }
    }

    #[Route('/admin/events/export', name: 'api_admin_events_export', methods: ['GET'])]
    public function exportEvents(ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $events = $doctrine->getRepository('App\Entity\Evenement')->findAll();
        $users = $doctrine->getRepository('App\Entity\User')->findAll();
        $participationRepo = $doctrine->getRepository('App\Entity\Participation');

        $userMap = [];
        foreach ($users as $user) {
            $userMap[$user->getId()] = $user;
        }

        // Créer le contenu CSV
        $csvContent = "ID,Titre,Organisateur,Date,Lieu,Capacité,Participants,Statut,Créé le,Modifié le\n";

        foreach ($events as $event) {
            $organisateur = isset($userMap[$event->getIdOrganisateur()]) ? $userMap[$event->getIdOrganisateur()] : null;
            $participantsCount = $participationRepo->countByEvent($event->getIdEvenement());

            $row = [
                $event->getIdEvenement(),
                '"' . str_replace('"', '""', $event->getTitre()) . '"',
                '"' . str_replace('"', '""', $organisateur ? $organisateur->getDisplayName() : 'Inconnu') . '"',
                $event->getDateEvenement() ? $event->getDateEvenement()->format('Y-m-d H:i:s') : '',
                '"' . str_replace('"', '""', $event->getLieu()) . '"',
                $event->getCapaciteMax(),
                $participantsCount,
                $this->getStatusText($event->getStatut()),
                $event->getCreatedAt() ? $event->getCreatedAt()->format('Y-m-d H:i:s') : '',
                $event->getUpdatedAt() ? $event->getUpdatedAt()->format('Y-m-d H:i:s') : '',
            ];

            $csvContent .= implode(',', $row) . "\n";
        }

        // Retourner le fichier CSV
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="evenements_' . date('Y-m-d') . '.csv"');

        return $response;
    }

    private function getStatusText(?string $status): string
    {
        switch ($status) {
            case 'valide': return 'Validé';
            case 'rejete': return 'Rejeté';
            case 'en_attente': return 'En attente';
            default: return $status ?? 'Inconnu';
        }
    }

    // ─── ADMIN PRODUCTS ───

    #[Route('/admin/products', name: 'api_admin_products', methods: ['GET'])]
    public function getAdminProducts(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = $request->query->get('search', '');
        $category = $request->query->get('category', '');
        $organisateur = $request->query->get('organisateur', '');

        $qb = $doctrine->getRepository(Produit::class)->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.user', 'u')
            ->addSelect('c', 'u');

        if ($search) {
            $qb->andWhere('p.nomProduit LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($category) {
            $qb->andWhere('c.nomCat = :category')
               ->setParameter('category', $category);
        }

        if ($organisateur) {
            $qb->andWhere('u.nom LIKE :organisateur OR u.prenom LIKE :organisateur')
               ->setParameter('organisateur', '%' . $organisateur . '%');
        }

        $products = $qb->getQuery()->getResult();

        $data = [];
        foreach ($products as $product) {
            $feedbacks = $doctrine->getRepository(Feedback::class)->findBy(['produit' => $product]);
            $avgRating = count($feedbacks) > 0 ? array_sum(array_map(fn($f) => $f->getNote(), $feedbacks)) / count($feedbacks) : 0;

            $data[] = [
                'id' => $product->getId(),
                'nomProduit' => $product->getNomProduit(),
                'description' => $product->getDescription(),
                'imageProduit' => $product->getImageProduit(),
                'category' => $product->getCategory() ? $product->getCategory()->getNomCat() : null,
                'organisateur' => $product->getUser() ? $product->getUser()->getNom() . ' ' . $product->getUser()->getPrenom() : null,
                'createdAt' => $product->getCreatedAt()?->format('Y-m-d'),
                'favoris' => count($doctrine->getRepository(Favoris::class)->findBy(['produit' => $product])),
                'noteMoyenne' => round($avgRating, 1),
            ];
        }

        return new JsonResponse(['success' => true, 'data' => $data]);
    }

    #[Route('/admin/products/{id}', name: 'api_admin_products_delete', methods: ['DELETE'])]
    public function deleteAdminProduct(int $id, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product = $doctrine->getRepository(Produit::class)->find($id);
        if (!$product) {
            return new JsonResponse(['success' => false, 'message' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $entityManager = $doctrine->getManager();
        $entityManager->remove($product);
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Produit supprimé']);
    }

    // ─── ADMIN COLLECTIONS ───

    #[Route('/admin/collections', name: 'api_admin_collections', methods: ['GET'])]
    public function getAdminCollections(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = $request->query->get('search', '');
        $organisateur = $request->query->get('organisateur', '');

        $qb = $doctrine->getRepository(Collection::class)->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->addSelect('u');

        if ($search) {
            $qb->andWhere('c.title LIKE :search OR c.materialType LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($organisateur) {
            $qb->andWhere('u.nom LIKE :organisateur OR u.prenom LIKE :organisateur')
               ->setParameter('organisateur', '%' . $organisateur . '%');
        }

        $collections = $qb->getQuery()->getResult();

        $data = [];
        foreach ($collections as $collection) {
            $donations = $doctrine->getRepository(Donation::class)->findBy(['collection' => $collection, 'status' => 'confirmé']);
            $totalDonated = array_sum(array_map(fn($d) => (float)$d->getAmount(), $donations));

            $data[] = [
                'id' => $collection->getId(),
                'title' => $collection->getTitle(),
                'materialType' => $collection->getMaterialType(),
                'goalAmount' => $collection->getGoalAmount(),
                'currentAmount' => $totalDonated,
                'unit' => $collection->getUnit(),
                'status' => $collection->getStatus(),
                'organisateur' => $collection->getUser() ? $collection->getUser()->getNom() . ' ' . $collection->getUser()->getPrenom() : null,
                'createdAt' => $collection->getCreatedAt()?->format('Y-m-d'),
                'progress' => $collection->getGoalAmount() > 0 ? ($totalDonated / (float)$collection->getGoalAmount()) * 100 : 0,
            ];
        }

        return new JsonResponse(['success' => true, 'data' => $data]);
    }

    // ─── ADMIN DONATIONS ───

    #[Route('/admin/donations', name: 'api_admin_donations', methods: ['GET'])]
    public function getAdminDonations(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');

        $qb = $doctrine->getRepository(Donation::class)->createQueryBuilder('d')
            ->leftJoin('d.user', 'u')
            ->leftJoin('d.collection', 'c')
            ->addSelect('u', 'c')
            ->orderBy('d.donationDate', 'DESC');

        if ($search) {
            $qb->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search OR c.title LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($status) {
            $qb->andWhere('d.status = :status')
               ->setParameter('status', $status);
        }

        $donations = $qb->getQuery()->getResult();

        $data = [];
        foreach ($donations as $donation) {
            $data[] = [
                'id' => $donation->getId(),
                'donateur' => $donation->getUser() ? [
                    'id' => $donation->getUser()->getId(),
                    'nom' => $donation->getUser()->getNom(),
                    'prenom' => $donation->getUser()->getPrenom(),
                    'email' => $donation->getUser()->getEmail(),
                ] : null,
                'collection' => $donation->getCollection() ? $donation->getCollection()->getTitle() : null,
                'amount' => $donation->getAmount(),
                'unit' => $donation->getCollection() ? $donation->getCollection()->getUnit() : null,
                'donationDate' => $donation->getDonationDate()?->format('Y-m-d H:i'),
                'status' => $donation->getStatus(),
            ];
        }

        return new JsonResponse(['success' => true, 'data' => $data]);
    }

    // ─── ADMIN COUPONS ───

    #[Route('/admin/coupons', name: 'api_admin_coupons', methods: ['GET'])]
    public function getAdminCoupons(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');

        $qb = $doctrine->getRepository(Coupon::class)->createQueryBuilder('co')
            ->leftJoin('co.user', 'u')
            ->leftJoin('co.donation', 'd')
            ->addSelect('u', 'd');

        if ($search) {
            $qb->andWhere('co.code LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($status) {
            $qb->andWhere('co.isUsed = :isUsed')
               ->setParameter('isUsed', $status === 'used' ? true : false);
        }

        $coupons = $qb->getQuery()->getResult();

        $data = [];
        foreach ($coupons as $coupon) {
            $data[] = [
                'id' => $coupon->getId(),
                'code' => $coupon->getCode(),
                'discountPercent' => $coupon->getDiscountPercent(),
                'user' => $coupon->getUser() ? $coupon->getUser()->getNom() . ' ' . $coupon->getUser()->getPrenom() : null,
                'donation' => $coupon->getDonation() ? 'Donation #' . $coupon->getDonation()->getId() : null,
                'expirationDate' => $coupon->getExpirationDate()?->format('Y-m-d'),
                'used' => $coupon->isUsed(),
                'createdAt' => $coupon->getCreatedAt()?->format('Y-m-d'),
            ];
        }

        return new JsonResponse(['success' => true, 'data' => $data]);
    }

    #[Route('/admin/coupons/{id}', name: 'api_admin_coupons_delete', methods: ['DELETE'])]
    public function deleteAdminCoupon(int $id, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $coupon = $doctrine->getRepository(Coupon::class)->find($id);
        if (!$coupon) {
            return new JsonResponse(['success' => false, 'message' => 'Coupon non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $entityManager = $doctrine->getManager();
        $entityManager->remove($coupon);
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Coupon supprimé']);
    }

    // ─── ADMIN FEEDBACKS ───

    #[Route('/admin/feedbacks', name: 'api_admin_feedbacks', methods: ['GET'])]
    public function getAdminFeedbacks(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = $request->query->get('search', '');
        $rating = $request->query->get('rating', '');

        $qb = $doctrine->getRepository(Feedback::class)->createQueryBuilder('f')
            ->leftJoin('f.produit', 'p')
            ->leftJoin('f.user', 'u')
            ->addSelect('p', 'u')
            ->orderBy('f.dateCommentaire', 'DESC');

        if ($search) {
            $qb->andWhere('p.nomProduit LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search OR f.commentaire LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($rating) {
            $qb->andWhere('f.note = :rating')
               ->setParameter('rating', (int)$rating);
        }

        $feedbacks = $qb->getQuery()->getResult();

        $data = [];
        foreach ($feedbacks as $feedback) {
            $data[] = [
                'id' => $feedback->getId(),
                'produit' => $feedback->getProduit() ? $feedback->getProduit()->getNomProduit() : null,
                'user' => $feedback->getUser() ? $feedback->getUser()->getNom() . ' ' . $feedback->getUser()->getPrenom() : null,
                'note' => $feedback->getNote(),
                'commentaire' => $feedback->getCommentaire(),
                'dateCommentaire' => $feedback->getDateCommentaire()?->format('Y-m-d'),
            ];
        }

        return new JsonResponse(['success' => true, 'data' => $data]);
    }

    #[Route('/admin/feedbacks/{id}', name: 'api_admin_feedbacks_delete', methods: ['DELETE'])]
    public function deleteAdminFeedback(int $id, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $feedback = $doctrine->getRepository(Feedback::class)->find($id);
        if (!$feedback) {
            return new JsonResponse(['success' => false, 'message' => 'Avis non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $entityManager = $doctrine->getManager();
        $entityManager->remove($feedback);
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Avis supprimé']);
    }
}
