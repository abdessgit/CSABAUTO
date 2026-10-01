<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateMessageContactDto
{
    #[Assert\NotBlank(message: 'Votre nom est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le nom ne peut pas dépasser 100 caractères.')]
    public ?string $nom = null;

    #[Assert\NotBlank(message: 'Votre adresse e-mail est obligatoire.')]
    #[Assert\Email(message: 'L\'adresse e-mail n\'est pas valide.')]
    #[Assert\Length(max: 180, maxMessage: 'L\'adresse e-mail ne peut pas dépasser 180 caractères.')]
    public ?string $email = null;

    #[Assert\Length(max: 30, maxMessage: 'Le numéro de téléphone ne peut pas dépasser 30 caractères.')]
    public ?string $telephone = null;

    #[Assert\Length(max: 150, maxMessage: 'Le sujet ne peut pas dépasser 150 caractères.')]
    public ?string $sujet = null;

    #[Assert\NotBlank(message: 'Le message est obligatoire.')]
    #[Assert\Length(
        min: 10,
        max: 2000,
        minMessage: 'Votre message doit contenir au moins 10 caractères.',
        maxMessage: 'Votre message ne peut pas dépasser 2000 caractères.'
    )]
    public ?string $message = null;

    // Honeypot field (hidden from real users, bots often fill this)
    public ?string $site_web = null;

    public static function fromRequest(array $data): self
    {
        $d = new self();
        $d->nom = isset($data['nom']) ? trim((string) $data['nom']) : null;
        $d->email = isset($data['email']) ? trim((string) $data['email']) : null;
        $d->telephone = isset($data['telephone']) ? trim((string) $data['telephone']) : null;
        $d->sujet = isset($data['sujet']) ? trim((string) $data['sujet']) : null;
        $d->message = isset($data['message']) ? trim((string) $data['message']) : null;
        $d->site_web = isset($data['site_web']) ? trim((string) $data['site_web']) : null;
        return $d;
    }
}
