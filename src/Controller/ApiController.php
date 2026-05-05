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
use App\Entity\Genre;
use App\Repository\GenreRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/api')]
class ApiController extends AbstractController
{
    private const QR_LOGIN_TTL_SECONDS = 300;

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

        $errors = [];

        $email = trim($data['email'] ?? '');
        $nom = trim($data['nom'] ?? '');
        $prenom = trim($data['prenom'] ?? '');
        $password = $data['password'] ?? '';
        $role = trim($data['role'] ?? 'participant');
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

        if (!in_array($role, ['participant', 'organisateur', 'admin'], true)) {
            $role = 'participant';
        }

        if (!empty($errors)) {
            return new JsonResponse([
                'success' => false,
                'errors' => $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        $existingUser = $doctrine->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return new JsonResponse([
                'success' => false,
                'errors' => ['email' => 'Cet email existe déjà.']
            ], Response::HTTP_CONFLICT);
        }

        try {
            $user = new User();
            $user->setEmail($email);
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setRole(in_array($role, ['admin', 'organisateur'], true) ? $role : 'participant');
            $user->setPassword($password);
            $user->setCreatedAt(new \DateTime());
            $user->setUpdatedAt(new \DateTime());

            if (is_string($photo) && $photo !== '') {
                $photoFilename = $this->saveBase64Photo($photo, uniqid('reg_', true));
                $user->setPhoto($photoFilename);
            } else {
                $user->setPhoto('default.jpg');
            }

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

    #[Route('/login-qr-token', name: 'api_login_qr_token', methods: ['POST'])]
    public function createQrLoginToken(Request $request, ManagerRegistry $doctrine, CacheItemPoolInterface $cachePool): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $email = trim($data['email'] ?? '');
        $password = (string) ($data['password'] ?? '');

        if (!$email || !$password) {
            return new JsonResponse(['success' => false, 'message' => 'Email et mot de passe sont requis.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $doctrine->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Email ou mot de passe incorrect.'], Response::HTTP_UNAUTHORIZED);
        }

        $storedPassword = (string) $user->getPassword();
        $isValidPassword = false;
        if (str_starts_with($storedPassword, '$2y$') || str_starts_with($storedPassword, '$2a$') || str_starts_with($storedPassword, '$2b$')) {
            $isValidPassword = password_verify($password, $storedPassword);
        } else {
            $isValidPassword = hash_equals($storedPassword, $password);
        }

        if (!$isValidPassword) {
            return new JsonResponse(['success' => false, 'message' => 'Email ou mot de passe incorrect.'], Response::HTTP_UNAUTHORIZED);
        }

        $token = bin2hex(random_bytes(16));
        $cacheKey = 'qr_login_' . $token;
        $item = $cachePool->getItem($cacheKey);
        $item->set((int) $user->getId());
        $item->expiresAfter(300);
        $cachePool->save($item);

        return new JsonResponse([
            'success' => true,
            'token' => $token,
            'expiresIn' => self::QR_LOGIN_TTL_SECONDS,
            'loginUrl' => '/qr-login/' . $token,
        ]);
    }

    #[Route('/qr-login/start', name: 'api_qr_login_start', methods: ['POST'])]
    public function startQrLogin(CacheItemPoolInterface $cachePool): JsonResponse
    {
        $token = bin2hex(random_bytes(16));
        $cacheKey = 'qr_login_pending_' . $token;

        $item = $cachePool->getItem($cacheKey);
        $item->set(true);
        $item->expiresAfter(self::QR_LOGIN_TTL_SECONDS);
        $cachePool->save($item);

        return new JsonResponse([
            'success' => true,
            'token' => $token,
            'expiresIn' => self::QR_LOGIN_TTL_SECONDS,
            'phoneUrl' => '/qr-phone-login/' . $token,
        ]);
    }

    #[Route('/qr-login/status/{token}', name: 'api_qr_login_status', methods: ['GET'])]
    public function qrLoginStatus(string $token, CacheItemPoolInterface $cachePool): JsonResponse
    {
        $approvedKey = 'qr_login_' . $token;
        $approved = $cachePool->getItem($approvedKey);
        if ($approved->isHit()) {
            return new JsonResponse(['success' => true, 'status' => 'approved']);
        }

        $pendingKey = 'qr_login_pending_' . $token;
        $pending = $cachePool->getItem($pendingKey);
        if ($pending->isHit()) {
            return new JsonResponse(['success' => true, 'status' => 'pending']);
        }

        return new JsonResponse(['success' => true, 'status' => 'expired']);
    }

    #[Route('/qr-login/approve/{token}', name: 'api_qr_login_approve', methods: ['POST'])]
    public function approveQrLogin(string $token, Request $request, ManagerRegistry $doctrine, CacheItemPoolInterface $cachePool): JsonResponse
    {
        $pendingKey = 'qr_login_pending_' . $token;
        $pending = $cachePool->getItem($pendingKey);
        if (!$pending->isHit()) {
            return new JsonResponse(['success' => false, 'message' => 'QR code expiré.'], Response::HTTP_GONE);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $email = trim($data['email'] ?? '');
        $password = (string) ($data['password'] ?? '');

        if (!$email || !$password) {
            return new JsonResponse(['success' => false, 'message' => 'Email et mot de passe requis.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $doctrine->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Email ou mot de passe incorrect.'], Response::HTTP_UNAUTHORIZED);
        }

        $storedPassword = (string) $user->getPassword();
        $isValidPassword = false;
        if (str_starts_with($storedPassword, '$2y$') || str_starts_with($storedPassword, '$2a$') || str_starts_with($storedPassword, '$2b$')) {
            $isValidPassword = password_verify($password, $storedPassword);
        } else {
            $isValidPassword = hash_equals($storedPassword, $password);
        }

        if (!$isValidPassword) {
            return new JsonResponse(['success' => false, 'message' => 'Email ou mot de passe incorrect.'], Response::HTTP_UNAUTHORIZED);
        }

        $approvedKey = 'qr_login_' . $token;
        $item = $cachePool->getItem($approvedKey);
        $item->set((int) $user->getId());
        $item->expiresAfter(self::QR_LOGIN_TTL_SECONDS);
        $cachePool->save($item);

        $cachePool->deleteItem($pendingKey);

        return new JsonResponse(['success' => true, 'message' => 'Connexion approuvée.']);
    }

    #[Route('/face-login/verify', name: 'api_face_login_verify', methods: ['POST'])]
    public function faceLoginVerify(Request $request, ManagerRegistry $doctrine, CacheItemPoolInterface $cachePool): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $email = trim($data['email'] ?? '');
        $snapshot = (string) ($data['snapshot'] ?? '');

        if (!$email || !$snapshot) {
            return new JsonResponse(['success' => false, 'message' => 'Email et snapshot requis.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $doctrine->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $photo = (string) ($user->getPhoto() ?: 'default.jpg');
        if ($photo === 'default.jpg') {
            return new JsonResponse(['success' => false, 'message' => 'Aucune photo de profil enregistrée pour ce compte.'], Response::HTTP_BAD_REQUEST);
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $photoPath = $projectDir . '/public/uploads/users/' . $photo;
        if (!is_file($photoPath)) {
            return new JsonResponse(['success' => false, 'message' => 'Photo de profil introuvable sur le serveur.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $snapshotHash = $this->averageHashFromDataUrl($snapshot);
            $profileHash = $this->averageHashFromFile($photoPath);
            $distance = $this->hammingDistance($snapshotHash, $profileHash);

            if ($distance > 18) {
                return new JsonResponse(['success' => false, 'message' => 'Visage non reconnu (démo).'], Response::HTTP_UNAUTHORIZED);
            }
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur de traitement image.'], Response::HTTP_BAD_REQUEST);
        }

        $token = bin2hex(random_bytes(16));
        $cacheKey = 'qr_login_' . $token;
        $item = $cachePool->getItem($cacheKey);
        $item->set((int) $user->getId());
        $item->expiresAfter(120);
        $cachePool->save($item);

        return new JsonResponse(['success' => true, 'token' => $token]);
    }

    #[Route('/face-login/identify', name: 'api_face_login_identify', methods: ['POST'])]
    public function faceLoginIdentify(Request $request, ManagerRegistry $doctrine, CacheItemPoolInterface $cachePool): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $snapshot = (string) ($data['snapshot'] ?? '');
        if (!$snapshot) {
            return new JsonResponse(['success' => false, 'message' => 'Snapshot requis.'], Response::HTTP_BAD_REQUEST);
        }

        $projectDir = $this->getParameter('kernel.project_dir');

        try {
            $snapshotHash = $this->averageHashFromDataUrl($snapshot);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => 'Snapshot invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $users = $doctrine->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.photo IS NOT NULL')
            ->andWhere('u.photo != :def')
            ->setParameter('def', 'default.jpg')
            ->getQuery()
            ->getResult();

        if (!$users) {
            return new JsonResponse(['success' => false, 'message' => 'Aucune photo de profil disponible pour identification.'], Response::HTTP_BAD_REQUEST);
        }

        $bestUser = null;
        $bestDistance = PHP_INT_MAX;

        foreach ($users as $user) {
            $photo = (string) $user->getPhoto();
            $path = $projectDir . '/public/uploads/users/' . $photo;
            if (!is_file($path)) {
                continue;
            }
            try {
                $profileHash = $this->averageHashFromFile($path);
                $d = $this->hammingDistance($snapshotHash, $profileHash);
                if ($d < $bestDistance) {
                    $bestDistance = $d;
                    $bestUser = $user;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        if (!$bestUser || $bestDistance > 16) {
            return new JsonResponse(['success' => false, 'message' => 'Aucun visage correspondant (démo).'], Response::HTTP_UNAUTHORIZED);
        }

        $token = bin2hex(random_bytes(16));
        $cacheKey = 'qr_login_' . $token;
        $item = $cachePool->getItem($cacheKey);
        $item->set((int) $bestUser->getId());
        $item->expiresAfter(120);
        $cachePool->save($item);

        return new JsonResponse(['success' => true, 'token' => $token]);
    }

    private function averageHashFromFile(string $path): string
    {
        $bytes = file_get_contents($path);
        if ($bytes === false) {
            throw new \RuntimeException('Cannot read file');
        }
        return $this->averageHashFromBytes($bytes);
    }

    private function averageHashFromDataUrl(string $dataUrl): string
    {
        if (!str_contains($dataUrl, 'base64,')) {
            throw new \InvalidArgumentException('Invalid data URL');
        }
        $base64 = explode('base64,', $dataUrl, 2)[1];
        $bytes = base64_decode($base64, true);
        if ($bytes === false) {
            throw new \InvalidArgumentException('Invalid base64');
        }
        return $this->averageHashFromBytes($bytes);
    }

    private function averageHashFromBytes(string $bytes): string
    {
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('GD not available');
        }
        $img = @imagecreatefromstring($bytes);
        if (!$img) {
            throw new \RuntimeException('Invalid image bytes');
        }

        $w = 8;
        $h = 8;
        $resized = imagecreatetruecolor($w, $h);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $w, $h, imagesx($img), imagesy($img));
        imagedestroy($img);

        $gray = [];
        $sum = 0;
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($resized, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $v = (int) round(($r + $g + $b) / 3);
                $gray[] = $v;
                $sum += $v;
            }
        }
        imagedestroy($resized);

        $avg = $sum / 64.0;
        $bits = '';
        foreach ($gray as $v) {
            $bits .= ($v >= $avg) ? '1' : '0';
        }
        return $bits;
    }

    private function hammingDistance(string $a, string $b): int
    {
        $len = min(strlen($a), strlen($b));
        $d = 0;
        for ($i = 0; $i < $len; $i++) {
            if ($a[$i] !== $b[$i]) {
                $d++;
            }
        }
        $d += abs(strlen($a) - strlen($b));
        return $d;
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

        $isValidPassword = false;
        if ($user) {
            $storedPassword = (string) $user->getPassword();
            if (str_starts_with($storedPassword, '$2y$') || str_starts_with($storedPassword, '$2a$') || str_starts_with($storedPassword, '$2b$')) {
                $isValidPassword = password_verify($password, $storedPassword);
            } else {
                $isValidPassword = hash_equals($storedPassword, $password);
            }
        }

        if (!$user || !$isValidPassword) {
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
                'statut' => $event->getStatut() ?: 'en_attente',
                'statut_validation' => $event->getStatutValidation() ?: 'en_attente',
                'date_soumission' => $event->getDateSoumission() ? $event->getDateSoumission()->format('Y-m-d H:i:s') : null,
                'date_validation' => $event->getDateValidation() ? $event->getDateValidation()->format('Y-m-d H:i:s') : null,
                'commentaire_validation' => $event->getCommentaireValidation(),
                'latitude' => $event->getLatitude(),
                'longitude' => $event->getLongitude(),
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
        $contentType = $request->headers->get('content-type') ?: '';
        if (str_contains($contentType, 'application/json')) {
            $data = json_decode($request->getContent(), true) ?? [];
        } else {
            $data = $request->request->all();
        }
        $errors = [];

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
            $organisateur = $doctrine->getRepository(User::class)->find($idOrganisateur);
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

        $event = new \App\Entity\Evenement();
        $event->setTitre($titre);
        $event->setDescription($description);
        $dateTimeString = strpos($dateEvenement, 'T') === false ? $dateEvenement . ' ' . $heureEvenement : $dateEvenement;
        $event->setDateEvenement(new \DateTime($dateTimeString));
        $event->setLieu($lieu);
        $event->setIdOrganisateur($idOrganisateur);
        $event->setCapaciteMax($capaciteMax);
        $latitude = isset($data['latitude']) ? trim((string)$data['latitude']) : null;
        $longitude = isset($data['longitude']) ? trim((string)$data['longitude']) : null;
        if ($latitude !== null && $longitude !== null && is_numeric($latitude) && is_numeric($longitude)) {
            $event->setLatitude($latitude);
            $event->setLongitude($longitude);
        }

        $uploadedImage = $request->files->get('image_evenement');
        if ($uploadedImage instanceof UploadedFile && $uploadedImage->isValid()) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
            $extension = strtolower($uploadedImage->guessExtension() ?: $uploadedImage->getClientOriginalExtension());
            $extension = $extension === 'svg+xml' ? 'svg' : $extension;
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
            $user = $this->getUser();
            $userId = $user ? $user->getId() : 2;
            $data = $this->getRequestData($request);

            $errors = [];

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

            // Handle image upload
            $uploadedFilename = $this->uploadEventImage($request);
            if ($uploadedFilename) {
                $event->setImageEvenement($uploadedFilename);
            } elseif (isset($data['generatedImage'])) {
                // Handle base64 generated image
                $base64Data = $data['generatedImage'];
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                    $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                    $type = strtolower($type[1]); // jpg, png, gif

                    if (in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $base64Data = str_replace(' ', '+', $base64Data);
                        $imageData = base64_decode($base64Data);

                        if ($imageData !== false) {
                            $projectDir = $this->getParameter('kernel.project_dir');
                            $uploadsDir = $projectDir . '/public/uploads/events';
                            if (!is_dir($uploadsDir)) {
                                mkdir($uploadsDir, 0755, true);
                            }

                            $filename = uniqid('event_ai_', true) . '.' . $type;
                            if (file_put_contents($uploadsDir . '/' . $filename, $imageData)) {
                                $event->setImageEvenement($filename);
                            }
                        }
                    }
                }
            }

            if (isset($data['latitude']) && isset($data['longitude'])) {
                $event->setLatitude((float)$data['latitude']);
                $event->setLongitude((float)$data['longitude']);
            }

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
            $user = $this->getUser();
            $userId = $user ? $user->getId() : 2;

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

    #[Route('/organisateur/events/{id}', name: 'api_organisateur_events_update', methods: ['POST', 'PUT'])]
    public function updateOrganisateurEvent(int $id, Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        try {
            $user = $this->getUser();
            $userId = $user ? $user->getId() : 2;

            $event = $doctrine->getRepository('App\Entity\Evenement')->find($id);
            if (!$event) {
                return new JsonResponse(['success' => false, 'message' => 'Événement introuvable.'], Response::HTTP_NOT_FOUND);
            }

            if ($event->getIdOrganisateur() !== $userId) {
                return new JsonResponse(['success' => false, 'message' => 'Accès non autorisé.'], Response::HTTP_FORBIDDEN);
            }

            $data = $this->getRequestData($request);
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

            if (isset($data['latitude']) && isset($data['longitude'])) {
                $event->setLatitude((float)$data['latitude']);
                $event->setLongitude((float)$data['longitude']);
            }

            // Handle image upload
            $uploadedFilename = $this->uploadEventImage($request);
            if ($uploadedFilename) {
                $event->setImageEvenement($uploadedFilename);
            } elseif (isset($data['generatedImage'])) {
                // Handle base64 generated image
                $base64Data = $data['generatedImage'];
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                    $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                    $type = strtolower($type[1]); // jpg, png, gif

                    if (in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $base64Data = str_replace(' ', '+', $base64Data);
                        $imageData = base64_decode($base64Data);

                        if ($imageData !== false) {
                            $projectDir = $this->getParameter('kernel.project_dir');
                            $uploadsDir = $projectDir . '/public/uploads/events';
                            if (!is_dir($uploadsDir)) {
                                mkdir($uploadsDir, 0755, true);
                            }

                            $filename = uniqid('event_ai_', true) . '.' . $type;
                            if (file_put_contents($uploadsDir . '/' . $filename, $imageData)) {
                                $event->setImageEvenement($filename);
                            }
                        }
                    }
                }
            }

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

    // ==============================================
    // GÉNÉRATION D'IMAGE - VERSION QUI FONCTIONNE
    // ==============================================

    #[Route('/admin/events/generate-image', name: 'api_admin_events_generate_image', methods: ['POST'])]
    public function generateEventImage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        
        $titre = trim($data['titre'] ?? '');
        $description = trim($data['description'] ?? '');

        if (empty($titre)) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Le titre est requis pour générer une image'
            ], Response::HTTP_BAD_REQUEST);
        }

        // 1. Try Stability AI if key is present
        $stabilityApiKey = $_ENV['STABILITY_AI_API_KEY'] ?? null;
        if ($stabilityApiKey) {
            $imageData = $this->generateImageWithStabilityAI($titre, $description, $stabilityApiKey);
            if ($imageData) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Image générée avec succès par IA (Stability AI)',
                    'imageData' => 'data:image/png;base64,' . $imageData,
                    'mimeType' => 'image/png'
                ]);
            }
        }

        // Essayer d'abord la méthode GD (locale, toujours disponible)
        try {
            $imageData = $this->generateImageWithGD($titre, $description);
            if ($imageData) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Image générée avec succès',
                    'imageData' => 'data:image/png;base64,' . $imageData,
                    'mimeType' => 'image/png'
                ]);
            }
        } catch (\Exception $e) {
            error_log('GD error: ' . $e->getMessage());
        }

        // Fallback SVG
        $imageData = $this->generateFallbackImage($titre, $description);
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Image générée',
            'imageData' => 'data:image/svg+xml;base64,' . $imageData,
            'mimeType' => 'image/svg+xml'
        ]);
    }

    private function generateImageWithStabilityAI(string $titre, string $description, string $apiKey): ?string
    {
        $prompt = "A high quality, professional, and aesthetic event cover image for an event titled '$titre'. Description: " . substr($description, 0, 500) . ". No text in the image, clean composition, vivid colors.";
        
        $ch = curl_init('https://api.stability.ai/v1/generation/stable-diffusion-v1-6/text-to-image');
        
        $data = [
            'text_prompts' => [
                ['text' => $prompt]
            ],
            'cfg_scale' => 7,
            'height' => 512,
            'width' => 512,
            'samples' => 1,
            'steps' => 30,
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $result = json_decode($response, true);
            if (isset($result['artifacts'][0]['base64'])) {
                return $result['artifacts'][0]['base64'];
            }
        }
        
        error_log("Stability AI Error ($httpCode): $response");
        return null;
    }

    /**
     * Génère une image avec GD
     */
    private function generateImageWithGD(string $titre, string $description): ?string
    {
        if (!extension_loaded('gd')) {
            error_log('GD extension not loaded');
            return null;
        }

        try {
            $width = 800;
            $height = 450;
            $image = imagecreatetruecolor($width, $height);
            if (!$image) {
                error_log('Failed to create image');
                return null;
            }

            $hash = md5($titre);
            $r = hexdec(substr($hash, 0, 2)) % 200 + 55;
            $g = hexdec(substr($hash, 2, 2)) % 200 + 55;
            $b = hexdec(substr($hash, 4, 2)) % 200 + 55;
            
            $bgColor = imagecolorallocate($image, $r, $g, $b);
            imagefill($image, 0, 0, $bgColor);

            for ($i = 0; $i < $height; $i++) {
                $factor = $i / $height;
                $lineR = min($r + $factor * 40, 255);
                $lineG = min($g + $factor * 40, 255);
                $lineB = min($b + $factor * 40, 255);
                $lineColor = imagecolorallocate($image, (int)$lineR, (int)$lineG, (int)$lineB);
                imageline($image, 0, $i, $width, $i, $lineColor);
            }

            $brightness = ($r * 0.299 + $g * 0.587 + $b * 0.114);
            $textColor = $brightness > 186 ? imagecolorallocate($image, 0, 0, 0) : imagecolorallocate($image, 255, 255, 255);
            $accentColor = imagecolorallocate($image, 255, 215, 0);

            imagefilledellipse($image, $width - 60, 60, 100, 100, $accentColor);
            imagefilledellipse($image, 60, $height - 60, 80, 80, $accentColor);
            imagefilledellipse($image, $width - 100, $height - 80, 50, 50, $accentColor);

            $fontSize = 5;
            $charWidth = imagefontwidth($fontSize);
            
            $titleLength = strlen($titre);
            $maxTitleWidth = $width - 80;
            if ($titleLength * $charWidth > $maxTitleWidth) {
                $titre = substr($titre, 0, (int)($maxTitleWidth / $charWidth) - 3) . '...';
            }
            $titleX = max(20, ($width - strlen($titre) * $charWidth) / 2);
            imagestring($image, $fontSize, (int)$titleX, 180, $titre, $textColor);

            if (!empty($description)) {
                $descShort = substr($description, 0, 70);
                $descLength = strlen($descShort);
                $descX = max(20, ($width - $descLength * $charWidth) / 2);
                imagestring($image, 4, (int)$descX, 230, $descShort, $textColor);
            }

            $footerText = '🌿 Événement Loopi 🌿';
            $footerLength = strlen($footerText);
            $footerX = ($width - $footerLength * $charWidth) / 2;
            imagestring($image, 3, (int)$footerX, 400, $footerText, $textColor);

            ob_start();
            imagepng($image);
            $imageContent = ob_get_clean();
            imagedestroy($image);

            if (empty($imageContent)) {
                error_log('Empty image content');
                return null;
            }

            return base64_encode($imageContent);
        } catch (\Exception $e) {
            error_log('GD image generation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Génère une image SVG de fallback
     */
    private function generateFallbackImage(string $titre, string $description): string
    {
        $hash = md5($titre);
        $r = hexdec(substr($hash, 0, 2));
        $g = hexdec(substr($hash, 2, 2));
        $b = hexdec(substr($hash, 4, 2));

        $color = sprintf('#%02x%02x%02x', $r, $g, $b);
        $textColor = ($r + $g + $b > 382) ? '#000000' : '#FFFFFF';

        $titleEscaped = htmlspecialchars($titre, ENT_QUOTES, 'UTF-8');
        $descEscaped = htmlspecialchars(substr($description, 0, 80), ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
<svg width="800" height="450" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:$color;stop-opacity:1" />
      <stop offset="100%" style="stop-color:rgb(80, 80, 100);stop-opacity:1" />
    </linearGradient>
  </defs>
  <rect width="800" height="450" fill="url(#grad)"/>
  <circle cx="750" cy="50" r="60" fill="rgba(255,255,255,0.15)"/>
  <circle cx="50" cy="400" r="50" fill="rgba(255,255,255,0.1)"/>
  <circle cx="400" cy="50" r="30" fill="rgba(255,255,255,0.08)"/>
  <text x="400" y="200" font-size="42" font-weight="bold" text-anchor="middle" fill="$textColor" font-family="Arial, sans-serif">
    $titleEscaped
  </text>
  <text x="400" y="280" font-size="20" text-anchor="middle" fill="$textColor" opacity="0.9" font-family="Arial, sans-serif">
    $descEscaped
  </text>
  <text x="400" y="400" font-size="18" text-anchor="middle" fill="$textColor" opacity="0.7" font-family="Arial, sans-serif">
    🌿 Événement éco-responsable 🌿
  </text>
</svg>
SVG;

        return base64_encode($svg);
    }

    /**
     * Sauvegarde une image générée (base64) dans le dossier uploads/events
     */
    private function saveGeneratedImage(string $base64Data, string $titre): ?string
    {
        // Extraire le type MIME et les données
        if (preg_match('/^data:image\/([a-zA-Z0-9]+);base64,(.+)$/', $base64Data, $matches)) {
            $extension = $matches[1];
            $data = base64_decode($matches[2]);
            
            // Gérer les cas où l'extension n'est pas standard
            if ($extension === 'svg+xml') {
                $extension = 'svg';
            }
            
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/events';
            
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            
            $filename = uniqid('event_', true) . '.' . $extension;
            $filepath = $uploadsDir . '/' . $filename;
            
            if (file_put_contents($filepath, $data)) {
                return $filename;
            }
            
            error_log("Failed to save generated image: $filepath");
            return null;
        }
        
        error_log("Invalid base64 image data format");
        return null;
    }

    #[Route('/admin/events/{id<\d+>}', name: 'api_admin_events_update', methods: ['PUT', 'POST'])]
    public function updateEvent(int $id, Request $request, ManagerRegistry $doctrine): JsonResponse
    {
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

        $titre = trim($data['titre'] ?? $event->getTitre());
        $description = trim($data['description'] ?? $event->getDescription());
        $dateEvenement = $data['date_evenement'] ?? ($event->getDateEvenement() ? $event->getDateEvenement()->format('Y-m-d') : '');
        $heureEvenement = $data['heure_evenement'] ?? ($event->getDateEvenement() ? $event->getDateEvenement()->format('H:i') : '00:00');
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
            $organisateur = $doctrine->getRepository(User::class)->find($idOrganisateur);
            if (!$organisateur) {
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

        $event->setTitre($titre);
        $event->setDescription($description);

        // === GESTION DE L'IMAGE GÉNÉRÉE ===
        $generatedImage = $data['generatedImage'] ?? null;
        
        // Si une image a été générée et envoyée
        if ($generatedImage && !empty($generatedImage)) {
            $savedImage = $this->saveGeneratedImage($generatedImage, $titre);
            if ($savedImage) {
                $event->setImageEvenement($savedImage);
            }
        }
        // Sinon, si un fichier a été uploadé
        else {
            $uploadedImage = $request->files->get('image_evenement');
            if ($uploadedImage instanceof UploadedFile && $uploadedImage->isValid()) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
                $extension = strtolower($uploadedImage->guessExtension() ?: $uploadedImage->getClientOriginalExtension());
                $extension = $extension === 'svg+xml' ? 'svg' : $extension;
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
        }

        $dateTimeString = strpos($dateEvenement, 'T') === false ? $dateEvenement . ' ' . $heureEvenement : $dateEvenement;
        $event->setDateEvenement(new \DateTime($dateTimeString));
        $event->setLieu($lieu);
        $event->setIdOrganisateur($idOrganisateur);
        $event->setCapaciteMax($capaciteMax);
        $latitude = isset($data['latitude']) ? trim((string)$data['latitude']) : null;
        $longitude = isset($data['longitude']) ? trim((string)$data['longitude']) : null;
        if ($latitude !== null && $longitude !== null && is_numeric($latitude) && is_numeric($longitude)) {
            $event->setLatitude($latitude);
            $event->setLongitude($longitude);
        }
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
                'date_evenement' => $event->getDateEvenement() ? $event->getDateEvenement()->format('Y-m-d H:i:s') : null,
                'lieu' => $event->getLieu(),
                'organisateur' => $organisateur->getDisplayName(),
                'id_organisateur' => $event->getIdOrganisateur(),
                'capacite_max' => $event->getCapaciteMax(),
                'image_evenement' => $event->getImageEvenement(),
                'statut' => $event->getStatut(),
                'created_at' => $event->getCreatedAt() ? $event->getCreatedAt()->format('Y-m-d H:i:s') : null,
                'updated_at' => $event->getUpdatedAt() ? $event->getUpdatedAt()->format('Y-m-d H:i:s') : null,
            ]
        ]);
    }

    #[Route('/admin/events/{id<\d+>}', name: 'api_admin_events_show', methods: ['GET'])]
    public function showEvent(int $id, ManagerRegistry $doctrine): JsonResponse
    {
        $event = $doctrine->getRepository('App\Entity\Evenement')->find($id);
        if (!$event) {
            return new JsonResponse(['success' => false, 'message' => 'Événement introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $organisateur = $doctrine->getRepository('App\Entity\User')->find($event->getIdOrganisateur());

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

    #[Route('/admin/events/{id<\d+>}', name: 'api_admin_events_delete', methods: ['DELETE'])]
    public function deleteEvent(int $id, ManagerRegistry $doctrine): JsonResponse
    {
        $event = $doctrine->getRepository('App\Entity\Evenement')->find($id);
        if (!$event) {
            return new JsonResponse(['success' => false, 'message' => 'Événement introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $entityManager = $doctrine->getManager();
        $entityManager->remove($event);
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Événement supprimé avec succès.']);
    }

    #[Route('/admin/events/{id<\d+>}/validate', name: 'api_admin_event_validate', methods: ['POST'])]
    public function validateEvent(int $id, ManagerRegistry $doctrine): JsonResponse
    {
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

    #[Route('/admin/events/{id<\d+>}/reject', name: 'api_admin_event_reject', methods: ['POST'])]
    public function rejectEvent(int $id, ManagerRegistry $doctrine): JsonResponse
    {
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

    // ==============================================
    // PARTICIPANT EVENTS
    // ==============================================

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

    // ─── PUBLIC GALLERY ───

    #[Route('/gallery', name: 'api_public_gallery', methods: ['GET'])]
    public function getPublicGallery(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $search = $request->query->get('search', '');
        $category = $request->query->get('category', '');

        $qb = $doctrine->getRepository(Produit::class)->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.user', 'u')
            ->addSelect('c', 'u')
            ->where('p.imageProduit IS NOT NULL')
            ->orderBy('p.createdAt', 'DESC');

        if ($search) {
            $qb->andWhere('p.nomProduit LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($category) {
            $qb->andWhere('c.nomCat = :category')
               ->setParameter('category', $category);
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

    #[Route('/gallery/categories', name: 'api_gallery_categories', methods: ['GET'])]
    public function getGalleryCategories(ManagerRegistry $doctrine): JsonResponse
    {
        $categories = $doctrine->getRepository(CategoryProduit::class)->findAll();
        
        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'id' => $category->getId(),
                'nomCat' => $category->getNomCat(),
            ];
        }

        return new JsonResponse(['success' => true, 'data' => $data]);
    }

    // ─── FAVORIS & RECOMMENDATIONS ───

    #[Route('/favorites', name: 'api_user_favorites', methods: ['GET'])]
    public function getUserFavorites(ManagerRegistry $doctrine): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            // Return empty favorites for non-authenticated users
            return new JsonResponse(['success' => true, 'data' => []]);
        }

        $favorites = $doctrine->getRepository(Favoris::class)->findBy(['user' => $user]);
        
        $data = [];
        foreach ($favorites as $favorite) {
            $product = $favorite->getProduit();
            if ($product) {
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
                    'addedAt' => $favorite->getCreatedAt()?->format('Y-m-d H:i:s'),
                ];
            }
        }

        return new JsonResponse(['success' => true, 'data' => $data]);
    }

    #[Route('/favorites/{productId}', name: 'api_toggle_favorite', methods: ['POST'])]
    public function toggleFavorite(int $productId, ManagerRegistry $doctrine): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            // For demo purposes, simulate toggle for non-authenticated users
            $product = $doctrine->getRepository(Produit::class)->find($productId);
            if (!$product) {
                return new JsonResponse(['success' => false, 'message' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
            }

            // Get current favorites count
            $favoritesCount = count($doctrine->getRepository(Favoris::class)->findBy(['produit' => $product]));
            
            return new JsonResponse([
                'success' => true, 
                'message' => 'Action simulée (connexion requise pour sauvegarder)',
                'isFavorite' => false,
                'favoritesCount' => $favoritesCount
            ]);
        }

        $product = $doctrine->getRepository(Produit::class)->find($productId);
        if (!$product) {
            return new JsonResponse(['success' => false, 'message' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $entityManager = $doctrine->getManager();
        $existingFavorite = $doctrine->getRepository(Favoris::class)->findOneBy(['user' => $user, 'produit' => $product]);

        if ($existingFavorite) {
            // Remove from favorites
            $entityManager->remove($existingFavorite);
            $isFavorite = false;
            $message = 'Retiré des favoris';
        } else {
            // Add to favorites
            $favorite = new Favoris();
            $favorite->setUser($user);
            $favorite->setProduit($product);
            $favorite->setCreatedAt(new \DateTime());
            $entityManager->persist($favorite);
            $isFavorite = true;
            $message = 'Ajouté aux favoris';
        }

        $entityManager->flush();

        // Get updated favorites count
        $favoritesCount = count($doctrine->getRepository(Favoris::class)->findBy(['produit' => $product]));

        return new JsonResponse([
            'success' => true, 
            'message' => $message,
            'isFavorite' => $isFavorite,
            'favoritesCount' => $favoritesCount
        ]);
    }

    #[Route('/recommendations', name: 'api_recommendations', methods: ['GET'])]
    public function getRecommendations(ManagerRegistry $doctrine): JsonResponse
    {
        $user = $this->getUser();
        
        // Get all products with ratings
        $qb = $doctrine->getRepository(Produit::class)->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.user', 'u')
            ->addSelect('c', 'u')
            ->where('p.imageProduit IS NOT NULL')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(12);

        $products = $qb->getQuery()->getResult();

        $data = [];
        foreach ($products as $product) {
            $feedbacks = $doctrine->getRepository(Feedback::class)->findBy(['produit' => $product]);
            $avgRating = count($feedbacks) > 0 ? array_sum(array_map(fn($f) => $f->getNote(), $feedbacks)) / count($feedbacks) : 0;
            $favoritesCount = count($doctrine->getRepository(Favoris::class)->findBy(['produit' => $product]));

            // Prioritize products with high ratings and favorites
            $score = ($avgRating * 0.7) + (min($favoritesCount / 10, 1) * 0.3);

            $data[] = [
                'id' => $product->getId(),
                'nomProduit' => $product->getNomProduit(),
                'description' => $product->getDescription(),
                'imageProduit' => $product->getImageProduit(),
                'category' => $product->getCategory() ? $product->getCategory()->getNomCat() : null,
                'organisateur' => $product->getUser() ? $product->getUser()->getNom() . ' ' . $product->getUser()->getPrenom() : null,
                'createdAt' => $product->getCreatedAt()?->format('Y-m-d'),
                'favoris' => $favoritesCount,
                'noteMoyenne' => round($avgRating, 1),
                'score' => round($score, 2),
            ];
        }

        // Sort by recommendation score
        usort($data, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return new JsonResponse(['success' => true, 'data' => array_slice($data, 0, 8)]);
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

    #[Route('/admin/donations/{id}', name: 'api_admin_donations_update', methods: ['POST', 'PUT'])]
    public function updateAdminDonation(int $id, Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $donation = $doctrine->getRepository(Donation::class)->find($id);
        if (!$donation) {
            return new JsonResponse(['success' => false, 'message' => 'Donation introuvable'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        if (!$data && $request->request->count() > 0) {
            $data = $request->request->all();
        }
        
        if (isset($data['status']) && in_array($data['status'], ['confirmé', 'en_attente', 'annulé'])) {
            $donation->setStatus($data['status']);
        }
        
        // Update collection amount if status changes from or to annulé
        // Simplification for the test: just set status.
        
        $doctrine->getManager()->flush();
        return new JsonResponse(['success' => true, 'message' => 'Donation mise à jour']);
    }

    #[Route('/admin/donations/{id}', name: 'api_admin_donations_delete', methods: ['DELETE'])]
    public function deleteAdminDonation(int $id, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $donation = $doctrine->getRepository(Donation::class)->find($id);
        if (!$donation) {
            return new JsonResponse(['success' => false, 'message' => 'Donation non trouvée'], Response::HTTP_NOT_FOUND);
        }
        
        $entityManager = $doctrine->getManager();
        $entityManager->remove($donation);
        $entityManager->flush();
        
        return new JsonResponse(['success' => true, 'message' => 'Donation supprimée']);
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

    // ══════════════════════════════════════════════════════════════════════
    // ─── SHARED COLLECTION HELPERS ────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════

    private function serializeCollection(Collection $c, ManagerRegistry $doctrine): array
    {
        $donations = $doctrine->getRepository(Donation::class)->findBy(['collection' => $c, 'status' => 'confirmé']);
        $totalDonated = array_sum(array_map(fn($d) => (float)$d->getAmount(), $donations));
        $goal = (float)$c->getGoalAmount();
        $imageUrl = $c->getImageCollection() ? '/uploads/collections/' . $c->getImageCollection() : null;

        return [
            'id'             => $c->getId(),
            'title'          => $c->getTitle(),
            'materialType'   => $c->getMaterialType(),
            'goalAmount'     => $goal,
            'currentAmount'  => $totalDonated,
            'unit'           => $c->getUnit(),
            'status'         => $c->getStatus(),
            'imageUrl'       => $imageUrl,
            'organisateur'   => $c->getUser() ? $c->getUser()->getNom() . ' ' . $c->getUser()->getPrenom() : null,
            'organisateurId' => $c->getUser() ? $c->getUser()->getId() : null,
            'createdAt'      => $c->getCreatedAt()?->format('Y-m-d'),
            'progress'       => $goal > 0 ? round(($totalDonated / $goal) * 100, 1) : 0,
            'donationsCount' => count($donations),
        ];
    }

    private function saveCollectionImage(UploadedFile $file): string
    {
        $dir = $this->getParameter('kernel.project_dir') . '/public/uploads/collections';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = uniqid('coll_') . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $filename);
        return $filename;
    }

    private function validateCollectionData(array $data, bool $requireImage, $uploadedFile = null, ManagerRegistry $doctrine = null, ?int $excludeId = null): array
    {
        $errors = [];
        $title        = trim($data['title'] ?? '');
        $materialType = trim($data['materialType'] ?? '');
        $goalAmount   = $data['goalAmount'] ?? '';
        $unit         = trim($data['unit'] ?? '');
        $status       = trim($data['status'] ?? 'actif');
        $description  = trim($data['description'] ?? '');

        if (!$title) {
            $errors['title'] = 'Le titre est requis.';
        } elseif (strlen($title) < 3) {
            $errors['title'] = 'Le titre doit contenir au moins 3 caractères.';
        }

        if (!$materialType) {
            $errors['materialType'] = 'Le type de matériau est requis.';
        }

        if (!$goalAmount) {
            $errors['goalAmount'] = 'L\'objectif est requis.';
        } elseif (!is_numeric($goalAmount) || (float)$goalAmount <= 0) {
            $errors['goalAmount'] = 'L\'objectif doit être un nombre positif.';
        }

        if (!$unit) {
            $errors['unit'] = "L'unité est requise.";
        }

        if ($requireImage && (!$uploadedFile || !$uploadedFile->isValid())) {
            $errors['image'] = 'Une image est requise.';
        }

        return $errors;
    }

    // ──── ORGANISATEUR → COLLECTIONS ─────────────────────────────────────

    #[Route('/organisateur/collections', name: 'api_org_collections_list', methods: ['GET'])]
    public function listOrgCollections(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');
        $user = $this->getUser();

        $search   = trim($request->query->get('search', ''));
        $material = trim($request->query->get('material', ''));

        $qb = $doctrine->getRepository(Collection::class)->createQueryBuilder('c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC');

        if ($search) {
            $qb->andWhere('c.title LIKE :search OR c.materialType LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        if ($material) {
            $qb->andWhere('c.materialType = :material')
               ->setParameter('material', $material);
        }

        $collections = $qb->getQuery()->getResult();
        $data = array_map(fn($c) => $this->serializeCollection($c, $doctrine), $collections);

        // -- Plant Gamification: Calculate total collected amount across ALL collections
        $totalCollected = 0.0;
        $allUserCols = $doctrine->getRepository(Collection::class)->findBy(['user' => $user]);
        foreach ($allUserCols as $col) {
            $totalCollected += (float)$col->getCurrentAmount();
        }

        return new JsonResponse(['success' => true, 'data' => $data, 'totalCollected' => $totalCollected]);
    }

    #[Route('/organisateur/collections', name: 'api_org_collections_create', methods: ['POST'])]
    public function createOrgCollection(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        try {
            $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');
            $user = $this->getUser();

            $data = [
                'title'        => $request->request->get('title', ''),
                'materialType' => $request->request->get('materialType', ''),
                'goalAmount'   => $request->request->get('goalAmount', ''),
                'unit'         => $request->request->get('unit', ''),
                'status'       => $request->request->get('status', 'active'),
            ];

            $imageFile = $request->files->get('image');
            $errors = $this->validateCollectionData($data, true, $imageFile, $doctrine);

            if (!empty($errors)) {
                return new JsonResponse(['success' => false, 'errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $imageFilename = $this->saveCollectionImage($imageFile);

            $collection = new Collection();
            $collection->setTitle(trim($data['title']));
            $collection->setMaterialType(trim($data['materialType']));
            $collection->setGoalAmount((string)(float)$data['goalAmount']);
            $collection->setCurrentAmount('0');
            $collection->setUnit(trim($data['unit']));
            $collection->setStatus(in_array($data['status'], ['active', 'inactive']) ? $data['status'] : 'active');
            $collection->setImageCollection($imageFilename);
            $collection->setUser($user);
            $collection->setCreatedAt(new \DateTime());
            $collection->setUpdatedAt(new \DateTime());

            $em = $doctrine->getManager();
            $em->persist($collection);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Collection créée avec succès.',
                'collection' => $this->serializeCollection($collection, $doctrine)
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Erreur serveur: ' . $e->getMessage() . ' à la ligne ' . $e->getLine()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/organisateur/collections/{id}', name: 'api_org_collections_update', methods: ['POST'])]
    public function updateOrgCollection(int $id, Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');
        $user = $this->getUser();

        $collection = $doctrine->getRepository(Collection::class)->find($id);
        if (!$collection) {
            return new JsonResponse(['success' => false, 'message' => 'Collection introuvable.'], Response::HTTP_NOT_FOUND);
        }
        if ($collection->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'Accès refusé.'], Response::HTTP_FORBIDDEN);
        }

        $data = [
            'title'        => $request->request->get('title', $collection->getTitle()),
            'materialType' => $request->request->get('materialType', $collection->getMaterialType()),
            'goalAmount'   => $request->request->get('goalAmount', $collection->getGoalAmount()),
            'unit'         => $request->request->get('unit', $collection->getUnit()),
            'status'       => $request->request->get('status', $collection->getStatus()),
        ];

        $imageFile = $request->files->get('image');
        $errors = $this->validateCollectionData($data, false, $imageFile, $doctrine, $id);

        if (!empty($errors)) {
            return new JsonResponse(['success' => false, 'errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $collection->setTitle(trim($data['title']));
        $collection->setMaterialType(trim($data['materialType']));
        $collection->setGoalAmount((string)(float)$data['goalAmount']);
        $collection->setUnit(trim($data['unit']));
        $collection->setStatus(in_array($data['status'], ['active', 'inactive']) ? $data['status'] : 'active');
        $collection->setUpdatedAt(new \DateTime());

        if ($imageFile && $imageFile->isValid()) {
            $collection->setImageCollection($this->saveCollectionImage($imageFile));
        }

        $doctrine->getManager()->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Collection mise à jour.',
            'collection' => $this->serializeCollection($collection, $doctrine)
        ]);
    }

    #[Route('/organisateur/collections/{id}', name: 'api_org_collections_delete', methods: ['DELETE'])]
    public function deleteOrgCollection(int $id, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');
        $user = $this->getUser();

        $collection = $doctrine->getRepository(Collection::class)->find($id);
        if (!$collection) {
            return new JsonResponse(['success' => false, 'message' => 'Collection introuvable.'], Response::HTTP_NOT_FOUND);
        }
        if ($collection->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'Accès refusé.'], Response::HTTP_FORBIDDEN);
        }

        $em = $doctrine->getManager();
        $donations = $doctrine->getRepository(Donation::class)->findBy(['collection' => $collection]);
        foreach ($donations as $d) { $em->remove($d); }
        $em->remove($collection);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Collection supprimée.']);
    }

    // ──── PARTICIPANT → BROWSE & DONATE ───────────────────────────────────

    #[Route('/participant/collections', name: 'api_participant_collections', methods: ['GET'])]
    public function participantCollections(Request $request, ManagerRegistry $doctrine, \App\Service\OpenMeteoService $meteoService): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_PARTICIPANT');

        $search   = trim($request->query->get('search', ''));
        $material = trim($request->query->get('material', ''));
        $status   = trim($request->query->get('status', 'active'));

        $qb = $doctrine->getRepository(Collection::class)->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->addSelect('u')
            ->orderBy('c.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('c.status = :status')->setParameter('status', $status);
        }
        if ($search) {
            $qb->andWhere('c.title LIKE :search OR c.materialType LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        if ($material) {
            $qb->andWhere('c.materialType = :material')->setParameter('material', $material);
        }

        $collections = $qb->getQuery()->getResult();
        $data = array_map(fn($c) => $this->serializeCollection($c, $doctrine), $collections);

        return new JsonResponse(['success' => true, 'data' => $data, 'weather' => $meteoService->getCurrentWeatherLive()]);
    }

    #[Route('/participant/donate/{collectionId}', name: 'api_participant_donate', methods: ['POST'])]
    public function participantDonate(int $collectionId, Request $request, ManagerRegistry $doctrine, \App\Service\AiEcoService $aiEcoService): JsonResponse
    {
        try {
            $this->denyAccessUnlessGranted('ROLE_PARTICIPANT');
            $user = $this->getUser();

            $collection = $doctrine->getRepository(Collection::class)->find($collectionId);
            if (!$collection) {
                return new JsonResponse(['success' => false, 'message' => 'Collection introuvable.'], Response::HTTP_NOT_FOUND);
            }
            if ($collection->getStatus() !== 'active') {
                return new JsonResponse(['success' => false, 'message' => "Cette collection n'est plus active."], Response::HTTP_BAD_REQUEST);
            }

            $data   = json_decode($request->getContent(), true) ?? [];
            $amount = $data['amount'] ?? null;

            if ($amount === null || $amount === '') {
                return new JsonResponse(['success' => false, 'errors' => ['amount' => 'Le montant est requis.']], Response::HTTP_BAD_REQUEST);
            }
            if (!is_numeric($amount) || (float)$amount <= 0) {
                return new JsonResponse(['success' => false, 'errors' => ['amount' => 'Le montant doit être un nombre positif.']], Response::HTTP_BAD_REQUEST);
            }

            $donation = new Donation();
            $donation->setUser($user);
            $donation->setCollection($collection);
            $donation->setAmount((string)(float)$amount);
            $donation->setDonationDate(new \DateTime());
            $donation->setStatus('confirmé');

            $newAmount = (float)$collection->getCurrentAmount() + (float)$amount;
            $collection->setCurrentAmount((string)$newAmount);
            $collection->setUpdatedAt(new \DateTime());

            // --- BADGE LOGIC ---
            $material = $collection->getMaterialType();
            $amtFloat = (float)$amount;
            $newlyUnlocked = null;

            $firstTimeBadge = !$user->isHasDonatedFirstTime();
            if ($firstTimeBadge) {
                $user->setHasDonatedFirstTime(true);
            }

            switch ($material) {
                case 'Plastique':
                    $old = (float)$user->getTotalPlastic();
                    if ($old < 50.0 && ($old + $amtFloat) >= 50.0) $newlyUnlocked = "Plastic Pioneer";
                    $user->setTotalPlastic((string)($old + $amtFloat));
                    break;
                case 'Papier':
                    $old = (float)$user->getTotalPaper();
                    if ($old < 30.0 && ($old + $amtFloat) >= 30.0) $newlyUnlocked = "Paper Warrior";
                    $user->setTotalPaper((string)($old + $amtFloat));
                    break;
                case 'Verre':
                    $old = (float)$user->getTotalGlass();
                    if ($old < 20.0 && ($old + $amtFloat) >= 20.0) $newlyUnlocked = "Glass Master";
                    $user->setTotalGlass((string)($old + $amtFloat));
                    break;
                case 'Métal':
                    $old = (float)$user->getTotalMetal();
                    if ($old < 15.0 && ($old + $amtFloat) >= 15.0) $newlyUnlocked = "Metal Titan";
                    $user->setTotalMetal((string)($old + $amtFloat));
                    break;
                case 'Carton':
                case 'Bois':
                    $old = (float)$user->getTotalCardboard();
                    if ($old < 25.0 && ($old + $amtFloat) >= 25.0) $newlyUnlocked = "Cardboard King";
                    $user->setTotalCardboard((string)($old + $amtFloat));
                    break;
            }

            $unlockedBadges = [];
            if ($firstTimeBadge) $unlockedBadges[] = "First Timer";
            if ($newlyUnlocked) $unlockedBadges[] = $newlyUnlocked;

            // --- XP MULTIPLIER ALGORITHM (SUPPLY & DEMAND) ---
            $allActive = $doctrine->getRepository(Collection::class)->findBy(['status' => 'active']);
            $materialGoalGlobal = 0.0;
            $materialCurrentGlobal = 0.0;
            
            foreach ($allActive as $c) {
                if ($c->getMaterialType() === $material) {
                    $materialGoalGlobal += (float)$c->getGoalAmount();
                    $materialCurrentGlobal += (float)$c->getCurrentAmount();
                }
            }

            $multiplier = 1;
            if ($materialGoalGlobal > 0) {
                $missingPct = ($materialGoalGlobal - $materialCurrentGlobal) / $materialGoalGlobal;
                if ($missingPct > 0.8) $multiplier = 3;      // Critical shortage: 3x XP
                elseif ($missingPct > 0.5) $multiplier = 2;  // High demand: 2x XP
            }
            
            // Base XP is 10 per kg
            $awardedXp = (int)($amtFloat * 10 * $multiplier);
            if (method_exists($user, 'setXp')) {
                $user->setXp($user->getXp() + $awardedXp);
            }

            $em = $doctrine->getManager();
            $em->persist($donation);
            $em->flush();

            $aiMessage = $aiEcoService->generateImpactMessage($amtFloat, $material);

            return new JsonResponse([
                'success' => true,
                'collection' => $this->serializeCollection($collection, $doctrine),
                'badges' => $unlockedBadges,
                'xp_awarded' => $awardedXp,
                'multiplier' => $multiplier,
                'ai_message' => $aiMessage
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage() . ' ligne: ' . $e->getLine()], 500);
        }
    }

    #[Route('/participant/donations', name: 'api_participant_donations', methods: ['GET'])]
    public function participantDonations(ManagerRegistry $doctrine): JsonResponse
    {
        try {
            $this->denyAccessUnlessGranted('ROLE_PARTICIPANT');
            /** @var User $user */
            $user = $this->getUser();

            $earnedBadges = [];
            if ($user->isHasDonatedFirstTime()) $earnedBadges[] = "First Timer";
            if ((float)$user->getTotalPlastic() >= 50.0) $earnedBadges[] = "Plastic Pioneer";
            if ((float)$user->getTotalPaper() >= 30.0) $earnedBadges[] = "Paper Warrior";
            if ((float)$user->getTotalGlass() >= 20.0) $earnedBadges[] = "Glass Master";
            if ((float)$user->getTotalMetal() >= 15.0) $earnedBadges[] = "Metal Titan";
            if ((float)$user->getTotalCardboard() >= 25.0) $earnedBadges[] = "Cardboard King";

            $donations = $doctrine->getRepository(Donation::class)->findBy(
                ['user' => $user],
                ['donationDate' => 'DESC']
            );

            $data = [];
            foreach ($donations as $donation) {
                $coll = $donation->getCollection();
                $title = null;
                $materialType = null;
                $unit = null;
                $collectionId = null;

                if ($coll) {
                    try {
                        $title = $coll->getTitle();
                        $materialType = $coll->getMaterialType();
                        $unit = $coll->getUnit();
                        $collectionId = $coll->getId();
                    } catch (\Doctrine\ORM\EntityNotFoundException $e) {
                        $title = 'Collection supprimée';
                    }
                }

                $date   = $donation->getDonationDate() ? $donation->getDonationDate()->format('Y-m-d H:i') : null;
                $data[] = [
                    'id'           => $donation->getId(),
                    'amount'       => $donation->getAmount(),
                    'status'       => $donation->getStatus(),
                    'date'         => $date,
                    'collection'   => $title,
                    'materialType' => $materialType,
                    'unit'         => $unit,
                    'collectionId' => $collectionId,
                ];
            }

            return new JsonResponse(['success' => true, 'data' => $data, 'badges' => $earnedBadges]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage() . ' ligne: ' . $e->getLine()], 500);
        }
    }

    #[Route('/participant/leaderboard', name: 'api_participant_leaderboard', methods: ['GET'])]
    public function leaderboard(ManagerRegistry $doctrine): JsonResponse
    {
        try {
            $this->denyAccessUnlessGranted('ROLE_PARTICIPANT');
            /** @var User $me */
            $me = $this->getUser();
            
            $users = $doctrine->getRepository(User::class)->findBy([], ['xp' => 'DESC'], 50);
            $data = [];
            $rank = 1;
            $myRank = null;
            
            foreach ($users as $u) {
                $xp = method_exists($u, 'getXp') ? $u->getXp() : 0;
                $title = 'Eco-Novice';
                if ($xp >= 5000) $title = 'Gardien de la Terre';
                elseif ($xp >= 2000) $title = 'Héros de la Planète';
                elseif ($xp >= 500) $title = 'Recycleur Actif';

                $data[] = [
                    'rank' => $rank,
                    'name' => $u->getNom() . ' ' . $u->getPrenom(),
                    'xp' => $xp,
                    'title' => $title,
                    'isMe' => $u->getId() === $me->getId()
                ];
                if ($u->getId() === $me->getId()) $myRank = $rank;
                $rank++;
            }

            return new JsonResponse([
                'success' => true, 
                'data' => $data, 
                'myRank' => $myRank, 
                'myXp' => method_exists($me, 'getXp') ? $me->getXp() : 0
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }

    // ──── ADMIN → EDIT & DELETE COLLECTIONS ──────────────────────────────

    #[Route('/admin/collections/{id}', name: 'api_admin_collections_update', methods: ['POST'])]
    public function updateAdminCollection(int $id, Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $collection = $doctrine->getRepository(Collection::class)->find($id);
        if (!$collection) {
            return new JsonResponse(['success' => false, 'message' => 'Collection introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'title'        => $request->request->get('title', $collection->getTitle()),
            'materialType' => $request->request->get('materialType', $collection->getMaterialType()),
            'goalAmount'   => $request->request->get('goalAmount', $collection->getGoalAmount()),
            'unit'         => $request->request->get('unit', $collection->getUnit()),
            'status'       => $request->request->get('status', $collection->getStatus()),
        ];

        $imageFile = $request->files->get('image');
        $errors = $this->validateCollectionData($data, false, $imageFile, $doctrine, $id);
        if (!empty($errors)) {
            return new JsonResponse(['success' => false, 'errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $collection->setTitle(trim($data['title']));
        $collection->setMaterialType(trim($data['materialType']));
        $collection->setGoalAmount((string)(float)$data['goalAmount']);
        $collection->setUnit(trim($data['unit']));
        $collection->setStatus(in_array($data['status'], ['active', 'inactive']) ? $data['status'] : 'active');
        $collection->setUpdatedAt(new \DateTime());

        if ($imageFile && $imageFile->isValid()) {
            $collection->setImageCollection($this->saveCollectionImage($imageFile));
        }

        $doctrine->getManager()->flush();

        return new JsonResponse(['success' => true, 'message' => 'Collection mise à jour.', 'collection' => $this->serializeCollection($collection, $doctrine)]);
    }

    #[Route('/admin/collections/{id}', name: 'api_admin_collections_delete', methods: ['DELETE'])]
    public function deleteAdminCollection(int $id, ManagerRegistry $doctrine): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $collection = $doctrine->getRepository(Collection::class)->find($id);
        if (!$collection) {
            return new JsonResponse(['success' => false, 'message' => 'Collection introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $em = $doctrine->getManager();
        $donations = $doctrine->getRepository(Donation::class)->findBy(['collection' => $collection]);
        foreach ($donations as $d) { $em->remove($d); }
        $em->remove($collection);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Collection supprimée.']);
    }
}
