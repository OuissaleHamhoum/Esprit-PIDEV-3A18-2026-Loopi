<?php

namespace App\Service;

class PhpStanWorkshopService
{
    public function test1UndefinedVariable(): void
    {
        // Correction : on déclare la variable avant de l'utiliser
        $variableExistante = "Bonjour ESPRIT";
        echo $variableExistante;
    }

    public function test4ReturnTypeMismatch(): int
    {
        // Correction : on retourne bien un entier
        return 42;
    }
}
