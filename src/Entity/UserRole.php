<?php

declare(strict_types=1);

namespace App\Entity;

enum UserRole: string
{
    case ADMIN = 'admin';
    case ORGANISATEUR = 'organisateur';
    case PARTICIPANT = 'participant';
}
