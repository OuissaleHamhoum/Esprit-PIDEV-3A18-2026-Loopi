<?php

namespace App\Tests\Service;

use App\Entity\Evenement;
use App\Service\EvenementManager;
use PHPUnit\Framework\TestCase;

class EvenementManagerTest extends TestCase
{
    public function testValidEvenement()
    {
        $event = new Evenement();
        $event->setTitre('Atelier Recyclage');
        $event->setCapaciteMax(50);

        $manager = new EvenementManager();
        
        // On vérifie que la validation retourne "true" pour un événement correct
        $this->assertTrue($manager->validate($event));
    }

    public function testEvenementWithoutTitre()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre de l\'événement est obligatoire');

        $event = new Evenement();
        $event->setCapaciteMax(50);
        // On laisse le titre vide...

        $manager = new EvenementManager();
        $manager->validate($event);
    }

    public function testEvenementWithInvalidCapacite()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La capacité maximale doit être supérieure à 0');

        $event = new Evenement();
        $event->setTitre('Conférence Écologie');
        $event->setCapaciteMax(0); // Invalide : 0

        $manager = new EvenementManager();
        $manager->validate($event);
    }
}
