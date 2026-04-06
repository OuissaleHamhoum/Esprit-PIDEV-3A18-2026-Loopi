<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('landing.html.twig');
    }

    #[Route('/admin', name: 'app_admin')]
    public function admin(): Response
    {
        return $this->render('admin-backoffice.html.twig');
    }

    #[Route('/organisateur', name: 'app_organisateur')]
    public function organisateur(): Response
    {
        return $this->render('organisateur-dashboard.html.twig');
    }

    #[Route('/participant', name: 'app_participant')]
    public function participant(): Response
    {
        return $this->render('participant-loopi.html.twig');
    }
}