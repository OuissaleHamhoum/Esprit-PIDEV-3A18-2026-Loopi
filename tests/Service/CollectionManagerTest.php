<?php

namespace App\Tests\Service;

use App\Entity\Collection;
use App\Service\CollectionManager;
use PHPUnit\Framework\TestCase;

class CollectionManagerTest extends TestCase
{
    public function testValidCollection()
    {
        $collection = new Collection();
        $collection->setTitle('Collecte de Plastique');
        $collection->setGoalAmount('500');

        $manager = new CollectionManager();

        // Une collection valide doit retourner true
        $this->assertTrue($manager->validate($collection));
    }

    public function testCollectionWithoutTitle()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre de la collection est obligatoire');

        $collection = new Collection();
        $collection->setGoalAmount('200');
        // Titre vide

        $manager = new CollectionManager();
        $manager->validate($collection);
    }

    public function testCollectionWithZeroGoal()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'objectif de collecte doit être supérieur à 0');

        $collection = new Collection();
        $collection->setTitle('Collecte de Verre');
        $collection->setGoalAmount('0'); // Objectif invalide

        $manager = new CollectionManager();
        $manager->validate($collection);
    }
}
