<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateRendezVousDto
{
    #[Assert\NotBlank]
    #[Assert\DateTime(format: 'Y-m-d\TH:i')]
    public ?string $dateHeure = null;

    #[Assert\Length(max: 255)]
    public ?string $motif = null;

    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $clientId = null;

    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $vehiculeId = null;

    #[Assert\Positive]
    public ?int $moderateurId = null;

    public static function fromRequest(array $data): self
    {
        $d = new self();

        foreach ($data as $k => $v) {
            if (property_exists($d, $k)) {
                $d->$k = $v;
            }
        }

        return $d;
    }
}