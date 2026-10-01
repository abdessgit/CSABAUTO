<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RepondreMessageContactDto
{
    #[Assert\NotBlank(message: 'La réponse ne peut pas être vide.')]
    #[Assert\Length(
        min: 5,
        max: 4000,
        minMessage: 'La réponse doit contenir au moins 5 caractères.',
        maxMessage: 'La réponse ne peut pas dépasser 4000 caractères.'
    )]
    public ?string $reponse = null;

    public static function fromRequest(array $data): self
    {
        $d = new self();
        $d->reponse = isset($data['reponse']) ? trim((string) $data['reponse']) : null;
        return $d;
    }
}
