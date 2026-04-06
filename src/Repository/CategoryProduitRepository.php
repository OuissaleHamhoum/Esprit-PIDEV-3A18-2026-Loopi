<?php

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
}