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
            if ($this->montant === null || $this->montant === '') {
                $context->buildViolation("Le montant de l'intervention est obligatoire.")
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

    public static function fromRequest(array $data): self
    {
        $d = new self();
        $d->statut = isset($data['statut']) ? (string) $data['statut'] : null;
        $d->montant = $data['montant'] ?? $data['coutTotal'] ?? null;
        return $d;
    }
}
