<?php

namespace App\Enum;

enum AnnonceStatut: string
{
    case BROUILLON = 'BROUILLON';
    case PUBLIEE = 'PUBLIEE';
    case VENDUE = 'VENDUE';

    // Rétrocompatibilité
    case EN_VENTE = 'EN_VENTE';
    case VENDU = 'VENDU';
    case RETIRE = 'RETIRE';
}
