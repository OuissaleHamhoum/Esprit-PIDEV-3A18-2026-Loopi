<?php

<<<<<<< HEAD
=======
declare(strict_types=1);

>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
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

<<<<<<< HEAD
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
=======
    /**
     * Participations aux événements d'un organisateur.
     *
     * @return Participation[]
     */
    public function findForOrganisateurEvents(int $organisateurId): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.evenement', 'e')->addSelect('e')
            ->join('p.user', 'u')->addSelect('u')
            ->join('e.organisateur', 'o')
            ->where('o.id = :oid')
            ->setParameter('oid', $organisateurId)
            ->orderBy('p.dateInscription', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
