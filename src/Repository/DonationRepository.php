<?php

<<<<<<< HEAD
=======
declare(strict_types=1);

>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
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
<<<<<<< HEAD
}
=======

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
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
