<?php

namespace App\Service;

use App\Entity\Produit;
use InvalidArgumentException;

class GalleryManager
{
    public function validate(Produit $produit): bool
    {
        // Règle métier 1 : Le nom du produit est obligatoire
        if (empty(trim((string)$produit->getNomProduit()))) {
            throw new InvalidArgumentException('Le nom du produit est obligatoire');
        }

        // Règle métier 2 : Le statut doit être valide (non vide)
        $allowedStatuses = ['publié', 'archivé', 'brouillon'];
        $status = $produit->getStatus();
        if (empty($status) || !in_array($status, $allowedStatuses, true)) {
            throw new InvalidArgumentException('Le statut du produit est invalide');
        }

        return true;
    }
}
