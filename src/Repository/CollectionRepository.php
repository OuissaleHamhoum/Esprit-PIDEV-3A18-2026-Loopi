<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Collection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Collection>
 */
class CollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Collection::class);
    }

    /**
     * @return Collection[]
     */
    public function findByOwner(int $userId): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.owner', 'u')
            ->where('u.id = :uid')
            ->setParameter('uid', $userId)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
