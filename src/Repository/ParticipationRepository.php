<?php

namespace App\Repository;

use App\Entity\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Participation>
 */
class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }

    public function findByEvent($eventId)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id_evenement = :eventId')
            ->setParameter('eventId', $eventId)
            ->orderBy('p.date_inscription', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByEvent($eventId)
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.id_evenement = :eventId')
            ->setParameter('eventId', $eventId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}