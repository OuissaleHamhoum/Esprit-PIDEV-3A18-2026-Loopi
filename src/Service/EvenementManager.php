<?php

namespace App\Service;

use App\Entity\Evenement;
use InvalidArgumentException;

class EvenementManager
{
    public function validate(Evenement $event): bool
    {
        // Règle métier 1 : Le titre est obligatoire
        if (empty(trim((string)$event->getTitre()))) {
            throw new InvalidArgumentException('Le titre de l\'événement est obligatoire');
        }

        // Règle métier 2 : La capacité doit être strictement supérieure à 0
        if ($event->getCapaciteMax() <= 0) {
            throw new InvalidArgumentException('La capacité maximale doit être supérieure à 0');
        }

        return true;
    }
}
