<?php

namespace App\Service;

use App\Entity\Collection;
use InvalidArgumentException;

class CollectionManager
{
    public function validate(Collection $collection): bool
    {
        // Règle métier 1 : Le titre est obligatoire
        if (empty(trim((string)$collection->getTitle()))) {
            throw new InvalidArgumentException('Le titre de la collection est obligatoire');
        }

        // Règle métier 2 : L'objectif doit être supérieur à zéro
        if ((float)$collection->getGoalAmount() <= 0) {
            throw new InvalidArgumentException('L\'objectif de collecte doit être supérieur à 0');
        }

        return true;
    }
}
