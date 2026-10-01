<?php

namespace App\Dto;

use App\Enum\InterventionStatut;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UpdateInterventionStatutDto
{
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    public ?string $statut = null;

    public mixed $montant = null;

    /** @var array<int, array{designation?: mixed, montant?: mixed}>|null */
    public ?array $lignes = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        $statusEnum = InterventionStatut::tryFrom($this->statut ?? '');
        if (!$statusEnum) {
            $context->buildViolation('Statut invalide.')
                ->atPath('statut')
                ->addViolation();
            return;
        }

        if ($statusEnum === InterventionStatut::TERMINEE) {
            if ($this->lignes !== null) {
                if (!is_array($this->lignes) || count($this->lignes) === 0) {
                    $context->buildViolation("Au moins une prestation ou ligne d'intervention est obligatoire pour clôturer l'intervention.")
                        ->atPath('lignes')
                        ->addViolation();
                    return;
                }

                foreach ($this->lignes as $index => $ligne) {
                    $designation = isset($ligne['designation']) ? trim((string) $ligne['designation']) : '';
                    if ($designation === '') {
                        $context->buildViolation("La désignation de la ligne " . ($index + 1) . " est obligatoire.")
                            ->atPath("lignes[{$index}].designation")
                            ->addViolation();
                    }

                    $montantLigne = $ligne['montant'] ?? null;
                    if ($montantLigne === null || $montantLigne === '' || !is_numeric($montantLigne) || (float) $montantLigne <= 0) {
                        $context->buildViolation("Le montant de la ligne " . ($index + 1) . " doit être un nombre supérieur à 0.")
                            ->atPath("lignes[{$index}].montant")
                            ->addViolation();
                    }
                }
            } else {
                // Rétrocompatibilité : si 'lignes' n'est pas envoyé, on vérifie 'montant' unique
                if ($this->montant === null || $this->montant === '') {
                    $context->buildViolation("Le montant ou la liste des lignes de l'intervention est obligatoire.")
                        ->atPath('montant')
                        ->addViolation();
                    return;
                }

                if (!is_numeric($this->montant) || (float) $this->montant <= 0) {
                    $context->buildViolation("Le montant de l'intervention doit être supérieur à 0.")
                        ->atPath('montant')
                        ->addViolation();
                }
            }
        }
    }

    /**
     * @return array<int, array{designation: string, montant: float}>
     */
    public function getNormalizedLignes(string $defaultDesignation = 'Prestation atelier'): array
    {
        if ($this->lignes !== null && count($this->lignes) > 0) {
            $result = [];
            foreach ($this->lignes as $l) {
                $designation = isset($l['designation']) ? trim((string) $l['designation']) : '';
                $montant = isset($l['montant']) && is_numeric($l['montant']) ? (float) $l['montant'] : 0.0;
                if ($designation !== '' && $montant > 0) {
                    $result[] = [
                        'designation' => $designation,
                        'montant' => $montant,
                    ];
                }
            }
            if (count($result) > 0) {
                return $result;
            }
        }

        if ($this->montant !== null && is_numeric($this->montant) && (float) $this->montant > 0) {
            return [
                [
                    'designation' => $defaultDesignation,
                    'montant' => (float) $this->montant,
                ]
            ];
        }

        return [];
    }

    public static function fromRequest(array $data): self
    {
        $d = new self();
        $d->statut = isset($data['statut']) ? (string) $data['statut'] : null;
        $d->montant = $data['montant'] ?? $data['coutTotal'] ?? null;
        $d->lignes = isset($data['lignes']) && is_array($data['lignes']) ? $data['lignes'] : null;
        return $d;
    }
}
