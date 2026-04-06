<?php

namespace App\Controller;

use App\Entity\Genre;
use App\Entity\User;
use App\Repository\GenreRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
}
