<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/api')]
class ApiController extends AbstractController
{
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
}
