<?php

namespace App\Enum;

enum FactureStatut: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case PAYEE = 'PAYEE';
    case ANNULEE = 'ANNULEE';
}
