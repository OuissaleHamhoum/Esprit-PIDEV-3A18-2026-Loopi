<?php

declare(strict_types=1);

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
