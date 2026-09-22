<?php

namespace App\Enum;

enum AnnonceStatut: string
{
    case EN_VENTE = 'EN_VENTE';
    case VENDU = 'VENDU';
    case RETIRE = 'RETIRE';
}
