<?php

namespace App\Enum;

enum UtilisateurRole: string
{
    case CLIENT = 'CLIENT';
    case MODERATEUR = 'MODERATEUR';
    case ADMIN = 'ADMIN';
}
