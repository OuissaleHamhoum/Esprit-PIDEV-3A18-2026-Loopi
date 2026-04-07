<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /** Événements visibles sur le site public et l’espace participant (après validation admin). */
    public function findApprovedOrdered(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.statutValidation = :st')
            ->setParameter('st', 'approuve')
            ->orderBy('e.dateEvenement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Evenement>
     */
    public function findForAdminList(?string $filter): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.organisateur', 'o')->addSelect('o')
            ->orderBy('e.dateEvenement', 'DESC');

        if ($filter === 'en_attente') {
            $qb->andWhere('e.statutValidation = :p OR e.statutValidation IS NULL OR e.statutValidation = :empty')
                ->setParameter('p', 'en_attente')
                ->setParameter('empty', '');
        } elseif ($filter === 'approuve') {
            $qb->andWhere('e.statutValidation = :p')->setParameter('p', 'approuve');
        } elseif ($filter === 'refuse') {
            $qb->andWhere('e.statutValidation = :p')->setParameter('p', 'refuse');
        }

        return $qb->getQuery()->getResult();
    }
}
