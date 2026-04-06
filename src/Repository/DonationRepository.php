<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Donation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Donation>
 */
class DonationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Donation::class);
    }

    /**
     * @return Donation[]
     */
    public function findByUserOrdered(int $userId): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.collection', 'c')->addSelect('c')
            ->join('d.user', 'u')->addSelect('u')
            ->where('u.id = :uid')
            ->setParameter('uid', $userId)
            ->orderBy('d.donationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
