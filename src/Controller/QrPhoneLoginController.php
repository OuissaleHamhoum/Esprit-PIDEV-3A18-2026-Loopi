<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QrPhoneLoginController extends AbstractController
{
    #[Route('/qr-phone-login/{token}', name: 'app_qr_phone_login', methods: ['GET'])]
    public function phoneLogin(string $token): Response
    {
        return $this->render('qr-phone-login.html.twig', [
            'token' => $token,
        ]);
    }
}

