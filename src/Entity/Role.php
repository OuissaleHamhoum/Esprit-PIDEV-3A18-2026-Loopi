<?php

namespace App\Entity;

enum Role: string
{
    case ADMIN = 'admin';
    case ORGANISATEUR = 'organisateur';
    case PARTICIPANT = 'participant';
}