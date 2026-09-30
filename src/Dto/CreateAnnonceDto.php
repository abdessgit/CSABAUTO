<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateAnnonceDto
{
    #[Assert\NotBlank(message: 'Le titre de l\'annonce est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'Le titre ne peut pas dépasser 255 caractères.')]
    public ?string $titre = null;

    #[Assert\NotBlank(message: 'La marque est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'La marque ne peut pas dépasser 100 caractères.')]
    public ?string $marque = null;

    #[Assert\NotBlank(message: 'Le modèle est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le modèle ne peut pas dépasser 100 caractères.')]
    public ?string $modele = null;

    #[Assert\NotNull(message: 'L\'année est obligatoire.')]
    #[Assert\Range(min: 1900, max: 2030, notInRangeMessage: 'L\'année doit être cohérente (entre {{ min }} et {{ max }}).')]
    public ?int $annee = null;

    #[Assert\NotNull(message: 'Le kilométrage est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'Le kilométrage doit être un nombre positif ou nul.')]
    public ?int $kilometrage = null;

    #[Assert\NotNull(message: 'Le prix est obligatoire.')]
    #[Assert\Positive(message: 'Le prix doit être strictement supérieur à 0.')]
    public ?string $prix = null;

    #[Assert\Choice(
        choices: ['essence', 'diesel', 'hybride', 'electrique', 'électrique', 'gpl', 'GPL', 'Essence', 'Diesel', 'Hybride', 'Électrique', null],
        message: 'Carburant invalide (essence, diesel, hybride, électrique, GPL).'
    )]
    public ?string $carburant = null;

    #[Assert\Choice(
        choices: ['manuelle', 'automatique', 'Manuelle', 'Automatique', null],
        message: 'Boîte de vitesse invalide (manuelle ou automatique).'
    )]
    public ?string $boite = null;

    #[Assert\Positive(message: 'La puissance doit être positive.')]
    public ?int $puissance = null;

    #[Assert\Length(max: 50, maxMessage: 'La couleur ne peut pas dépasser 50 caractères.')]
    public ?string $couleur = null;

    #[Assert\Positive(message: 'Le nombre de portes doit être positif.')]
    public ?int $nbPortes = null;

    #[Assert\Positive(message: 'Le nombre de places doit être positif.')]
    public ?int $nbPlaces = null;

    public ?string $description = null;

    #[Assert\Count(max: 8, maxMessage: 'Une annonce ne peut pas contenir plus de 8 photos.')]
    public ?array $photos = [];

    #[Assert\Choice(
        choices: ['BROUILLON', 'PUBLIEE', 'VENDUE', 'EN_VENTE', 'VENDU', null],
        message: 'Statut invalide (BROUILLON, PUBLIEE ou VENDUE).'
    )]
    public ?string $statut = null;

    public ?int $vehiculeId = null;

    public static function fromRequest(array $data): self
    {
        $d = new self();
        foreach ($data as $k => $v) {
            if (property_exists($d, $k)) {
                if (in_array($k, ['annee', 'kilometrage', 'puissance', 'nbPortes', 'nbPlaces', 'vehiculeId'], true)) {
                    $d->$k = ($v !== '' && $v !== null) ? (int) $v : null;
                } elseif ($k === 'prix') {
                    $d->$k = ($v !== '' && $v !== null) ? (string) $v : null;
                } elseif ($k === 'photos' && is_array($v)) {
                    $d->$k = array_values(array_filter($v, fn($item) => is_string($item) && trim($item) !== ''));
                } else {
                    $d->$k = is_string($v) ? trim($v) : $v;
                }
            }
        }
        return $d;
    }
}