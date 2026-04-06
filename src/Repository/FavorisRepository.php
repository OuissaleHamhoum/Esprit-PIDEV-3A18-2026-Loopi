<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Favoris;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favoris>
 */
class FavorisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favoris::class);
    }

    /**
     * @return Favoris[]
     */
    public function findByUserOrdered(int $userId): array
    {
        return $this->createQueryBuilder('f')
            ->join('f.produit', 'p')->addSelect('p')
            ->join('f.user', 'u')->addSelect('u')
            ->where('u.id = :uid')
            ->setParameter('uid', $userId)
            ->orderBy('f.dateAjout', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
