<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Entity\UserRole;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return new RedirectResponse($this->urlGenerator->generate('app_home'));
        }

        $url = match ($user->getRole()) {
            UserRole::ADMIN => $this->urlGenerator->generate('admin_dashboard'),
            UserRole::ORGANISATEUR => $this->urlGenerator->generate('app_organisateur_dashboard'),
            UserRole::PARTICIPANT => $this->urlGenerator->generate('app_participant_dashboard'),
        };

        return new RedirectResponse($url);
    }
}
