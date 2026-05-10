<?php

namespace App\Tests\Service;

use App\Entity\Produit;
use App\Service\GalleryManager;
use PHPUnit\Framework\TestCase;

class GalleryManagerTest extends TestCase
{
    public function testValidProduit()
    {
        $produit = new Produit();
        $produit->setNomProduit('Bouteille Recyclée');
        $produit->setStatus('publié');

        $manager = new GalleryManager();

        // Un produit valide doit retourner true
        $this->assertTrue($manager->validate($produit));
    }

    public function testProduitWithoutNom()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du produit est obligatoire');

        $produit = new Produit();
        $produit->setStatus('publié');
        // Nom vide

        $manager = new GalleryManager();
        $manager->validate($produit);
    }

    public function testProduitWithInvalidStatus()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut du produit est invalide');

        $produit = new Produit();
        $produit->setNomProduit('Verre Recyclé');
        $produit->setStatus('inconnu'); // Statut invalide

        $manager = new GalleryManager();
        $manager->validate($produit);
    }
}
