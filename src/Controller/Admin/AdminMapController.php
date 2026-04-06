<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/carte')]
final class AdminMapController extends AbstractController
{
    #[Route('', name: 'admin_map')]
    public function index(EvenementRepository $evenementRepository): Response
    {
        $events = $evenementRepository->findAll();
        $markers = [];
        foreach ($events as $e) {
            if ($e->getLatitude() !== null && $e->getLongitude() !== null) {
                $markers[] = [
                    'lat' => $e->getLatitude(),
                    'lng' => $e->getLongitude(),
                    'titre' => $e->getTitre(),
                    'lieu' => $e->getLieu(),
                ];
            }
        }

        return $this->render('admin/map/index.html.twig', [
            'markers' => $markers,
        ]);
    }
}
