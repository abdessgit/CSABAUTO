<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateConversationDto
{
    #[Assert\NotBlank(message: 'L\'objet de la demande est obligatoire.')]
    #[Assert\Length(max: 120, maxMessage: 'L\'objet ne peut pas dépasser 120 caractères.')]
    public ?string $objet = null;

    #[Assert\NotNull(message: 'Le client est obligatoire.')]
    #[Assert\Positive]
    public ?int $clientId = null;

    #[Assert\Positive]
    public ?int $moderateurId = null;

    #[Assert\Positive(message: 'L\'identifiant d\'annonce doit être un entier positif.')]
    public ?int $annonceId = null;

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