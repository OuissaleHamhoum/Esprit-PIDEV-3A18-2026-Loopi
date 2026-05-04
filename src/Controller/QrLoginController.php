<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Security;

class QrLoginController extends AbstractController
{
    #[Route('/qr-login/{token}', name: 'app_qr_login', methods: ['GET'])]
    public function qrLogin(string $token, Request $request, CacheItemPoolInterface $cachePool, ManagerRegistry $doctrine): RedirectResponse
    {
        $cacheKey = 'qr_login_' . $token;
        $item = $cachePool->getItem($cacheKey);

        if (!$item->isHit()) {
            return $this->redirectToRoute('app_home', ['auth' => 'login', 'error' => 'qr_expired']);
        }

        $userId = (int) $item->get();
        $cachePool->deleteItem($cacheKey);

        $user = $doctrine->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_home', ['auth' => 'login', 'error' => 'qr_invalid']);
        }

        // Manual login (session-based) compatible with authenticator manager.
        $firewallName = 'main';
        $tokenObj = new UsernamePasswordToken($user, $firewallName, $user->getRoles());
        $this->container->get('security.token_storage')->setToken($tokenObj);

        $request->getSession()->set('_security_' . $firewallName, serialize($tokenObj));
        $request->getSession()->set(Security::LAST_USERNAME, $user->getUserIdentifier());

        // Redirect based on role
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return $this->redirectToRoute('app_admin');
        }
        if (in_array('ROLE_ORGANISATEUR', $roles, true)) {
            return $this->redirectToRoute('app_organisateur');
        }
        return $this->redirectToRoute('app_participant');
    }
}

