<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateUtilisateurDto
{
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le nom ne peut pas dépasser 100 caractères.')]
    public ?string $nom = null;

    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le prénom ne peut pas dépasser 100 caractères.')]
    public ?string $prenom = null;

    #[Assert\NotBlank(message: 'L\'adresse e-mail est obligatoire.')]
    #[Assert\Email(message: 'L\'adresse e-mail n\'est pas valide.')]
    #[Assert\Length(max: 180, maxMessage: 'L\'adresse e-mail ne peut pas dépasser 180 caractères.')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.')]
    #[Assert\Length(min: 8, max: 255, minMessage: 'Le mot de passe doit comporter au moins 8 caractères.')]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-zA-Z])(?=.*\d)/',
        message: 'Le mot de passe doit contenir au moins une lettre et un chiffre.'
    )]
    public ?string $password = null;

    #[Assert\Length(max: 20, maxMessage: 'Le numéro de téléphone ne peut pas dépasser 20 caractères.')]
    public ?string $telephone = null;

    #[Assert\Length(max: 255, maxMessage: 'L\'adresse ne peut pas dépasser 255 caractères.')]
    public ?string $adresse = null;

    public ?string $role = null;

    public static function fromRequest(array $data): self
    {
        $d = new self();
        foreach ($data as $k => $v) {
            if (property_exists($d, $k)) {
                $d->$k = is_string($v) ? trim($v) : $v;
            }
        }
        return $d;
    }
}