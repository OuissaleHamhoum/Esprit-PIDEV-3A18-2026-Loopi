<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Point d'entrée générique : redirige vers l'espace selon le rôle.
 */
final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        return match ($user->getRole()) {
            UserRole::ADMIN => $this->redirectToRoute('admin_dashboard'),
            UserRole::ORGANISATEUR => $this->redirectToRoute('app_organisateur_dashboard'),
            UserRole::PARTICIPANT => $this->redirectToRoute('app_participant_dashboard'),
        };
    }
}
