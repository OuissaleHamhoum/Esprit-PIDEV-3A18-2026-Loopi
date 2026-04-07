<?php

<<<<<<< HEAD
namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($request->isMethod('GET') && !$error) {
            return $this->redirectToRoute('app_home', ['auth' => 'login']);
        }

        return $this->render('landing.html.twig', [
            'auth' => 'login',
            'last_username' => $lastUsername,
            'error' => $error ? $error->getMessage() : null,
=======
declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
<<<<<<< HEAD
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, ManagerRegistry $doctrine): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('GET')) {
            return $this->redirectToRoute('app_home', ['auth' => 'register']);
        }

        if ($request->isMethod('POST')) {
            $email = (string) $request->request->get('email');
            $nom = (string) $request->request->get('nom');
            $prenom = (string) $request->request->get('prenom');
            $plainPassword = (string) $request->request->get('password');
            $role = (string) $request->request->get('role', 'participant');

            if (!$email || !$plainPassword || !$nom || !$prenom) {
                return $this->render('landing.html.twig', [
                    'auth' => 'register',
                    'error' => 'Tous les champs sont requis.',
                ]);
            }

            $existingUser = $doctrine->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                return $this->render('landing.html.twig', [
                    'auth' => 'register',
                    'error' => 'Cet email existe déjà.',
                ]);
            }

            $user = new User();
            $user->setEmail($email);
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setRole(in_array($role, ['admin', 'organisateur'], true) ? $role : 'participant');
            $user->setPassword($plainPassword);
            $user->setCreatedAt(new \DateTime());
            $user->setUpdatedAt(new \DateTime());

            $entityManager = $doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_login');
        }

        return $this->redirectToRoute('app_home', ['auth' => 'register']);
=======
        throw new \LogicException('Intercepted by security firewall.');
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    }
}
