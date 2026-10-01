<?php

namespace App\Service;

use App\Repository\FactureRepository;

class FactureNumberGenerator
{
    public function __construct(private FactureRepository $factureRepository) {}

    /**
     * Génère un numéro séquentiel unique au format FAC-YYYY-0001
     */
    public function generateNextNumber(?int $year = null): string
    {
        $year = $year ?? (int) date('Y');
        $prefix = sprintf('FAC-%d-', $year);

        $lastNumero = $this->factureRepository->findLatestNumeroForYear($prefix);

        if (!$lastNumero) {
            $nextSequence = 1;
        } else {
            $parts = explode('-', $lastNumero);
            $lastSeq = (int) end($parts);
            $nextSequence = $lastSeq + 1;
        }

        return sprintf('%s%04d', $prefix, $nextSequence);
    }
}
