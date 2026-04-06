<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection;

/**
 * Statistiques front-office depuis les tables existantes (sans modifier le schéma).
 */
final class UserStatsService
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @return array{participations: int, favoris: int, feedback_count: int}
     */
    public function getParticipantStats(int $userId): array
    {
        return [
            'participations' => $this->countOne('SELECT COUNT(*) FROM participation WHERE id_user = ?', $userId),
            'favoris' => $this->countOne('SELECT COUNT(*) FROM favoris WHERE id_user = ?', $userId),
            'feedback_count' => $this->countOne('SELECT COUNT(*) FROM feedback WHERE id_user = ?', $userId),
        ];
    }

    /**
     * @return array{evenements: int, produits: int, collections: int}
     */
    public function getOrganisateurStats(int $userId): array
    {
        return [
            'evenements' => $this->countOne('SELECT COUNT(*) FROM evenement WHERE id_organisateur = ?', $userId),
            'produits' => $this->countOne('SELECT COUNT(*) FROM produit WHERE id_user = ?', $userId),
            'collections' => $this->countOne('SELECT COUNT(*) FROM collection WHERE id_user = ?', $userId),
        ];
    }

    private function countOne(string $sql, int $userId): int
    {
        $v = $this->connection->fetchOne($sql, [$userId]);

        return (int) $v;
    }
}
