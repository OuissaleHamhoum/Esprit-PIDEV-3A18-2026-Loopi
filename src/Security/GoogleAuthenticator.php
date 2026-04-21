<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\HttpFoundation\Response;

class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private ManagerRegistry $doctrine,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge('google_oauth', function () use ($client, $accessToken): UserInterface {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email = trim((string) $googleUser->getEmail());
                if ($email === '') {
                    throw new AuthenticationException('Google n’a pas fourni un email.');
                }

                $repo = $this->doctrine->getRepository(User::class);
                $user = $repo->findOneBy(['email' => $email]);
                if ($user) {
                    return $user;
                }

                $user = new User();
                $user->setEmail($email);
                $user->setNom($googleUser->getLastName() ?: 'Google');
                $user->setPrenom($googleUser->getFirstName() ?: 'User');
                $user->setRole('participant');
                $user->setPassword(bin2hex(random_bytes(16))); // not used for OAuth login
                $user->setPhoto('default.jpg');
                $user->setCreatedAt(new \DateTime());
                $user->setUpdatedAt(new \DateTime());

                $em = $this->doctrine->getManager();
                $em->persist($user);
                $em->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        $roles = $token->getRoleNames();
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_admin'));
        }
        if (in_array('ROLE_ORGANISATEUR', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_organisateur'));
        }
        return new RedirectResponse($this->urlGenerator->generate('app_participant'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new RedirectResponse($this->urlGenerator->generate('app_home', ['auth' => 'login']));
    }
}

