<?php

<<<<<<< HEAD
=======
declare(strict_types=1);

>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
namespace App\Repository;

use App\Entity\CategoryProduit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategoryProduit>
 */
class CategoryProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoryProduit::class);
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
