<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateRendezVousDto
{
    #[Assert\NotBlank(message: 'La date et l\'heure sont obligatoires.')]
    #[Assert\AtLeastOneOf([
        new Assert\DateTime(format: 'Y-m-d\TH:i', message: 'Format de date invalide (attendu: AAAA-MM-JJTHH:MM).'),
        new Assert\DateTime(format: 'Y-m-d\TH:i:s', message: 'Format de date invalide.'),
        new Assert\DateTime(format: \DateTimeInterface::ATOM, message: 'Format de date invalide.'),
    ], message: 'Format de date et heure invalide.')]
    public ?string $dateHeure = null;

    #[Assert\Length(max: 255, maxMessage: 'Le motif ne peut pas dépasser 255 caractères.')]
    public ?string $motif = null;

    #[Assert\NotNull(message: 'Le client est obligatoire.')]
    #[Assert\Positive]
    public ?int $clientId = null;

    #[Assert\NotNull(message: 'Le véhicule est obligatoire.')]
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