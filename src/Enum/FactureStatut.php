<?php

namespace App\Enum;

enum FactureStatut: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case PAYEE = 'PAYEE';
    case EN_RETARD = 'EN_RETARD';
    case ANNULEE = 'ANNULEE';
}
