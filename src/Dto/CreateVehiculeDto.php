<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateVehiculeDto
{
    #[Assert\NotBlank(message: 'La marque est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'La marque ne peut pas dépasser 100 caractères.')]
    public ?string $marque = null;

    #[Assert\NotBlank(message: 'Le modèle est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le modèle ne peut pas dépasser 100 caractères.')]
    public ?string $modele = null;

    #[Assert\NotNull(message: "L'année est obligatoire.")]
    #[Assert\Range(min: 1900, max: 2030, notInRangeMessage: "L'année doit être cohérente (entre {{ min }} et {{ max }}).")]
    public ?int $annee = null;

    #[Assert\NotBlank(message: "L'immatriculation est obligatoire.")]
    #[Assert\Length(max: 20, maxMessage: "L'immatriculation ne peut pas dépasser 20 caractères.")]
    public ?string $immatriculation = null;

    #[Assert\Length(max: 50, maxMessage: 'Le numéro VIN ne peut pas dépasser 50 caractères.')]
    public ?string $vin = null;

    #[Assert\NotNull(message: 'Le kilométrage est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'Le kilométrage doit être positif ou nul.')]
    public ?int $kilometrage = null;

    #[Assert\Length(max: 50, maxMessage: 'La couleur ne peut pas dépasser 50 caractères.')]
    public ?string $couleur = null;

    #[Assert\Positive(message: 'Le propriétaire sélectionné est invalide.')]
    public ?int $proprietaireId = null;

    public static function normalizeImmatriculation(?string $immat): ?string
    {
        if ($immat === null) {
            return null;
        }

        $raw = strtoupper(trim($immat));
        if ($raw === '') {
            return null;
        }

        $clean = preg_replace('/[^A-Z0-9]/', '', $raw);

        // Format SIV moderne: AA-123-AA (2 lettres, 3 chiffres, 2 lettres)
        if (preg_match('/^([A-Z]{2})([0-9]{3})([A-Z]{2})$/', $clean, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        // Format FNI ancien: 1234-AB-75 (1 à 4 chiffres, 2 ou 3 lettres, 2 chiffres)
        if (preg_match('/^([0-9]{1,4})([A-Z]{2,3})([0-9]{2})$/', $clean, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        return $raw;
    }

    public static function fromRequest(array $data): self
    {
        $d = new self();
        $d->marque = isset($data['marque']) && trim((string) $data['marque']) !== '' ? trim((string) $data['marque']) : null;
        $d->modele = isset($data['modele']) && trim((string) $data['modele']) !== '' ? trim((string) $data['modele']) : null;
        $d->immatriculation = isset($data['immatriculation']) ? self::normalizeImmatriculation((string) $data['immatriculation']) : null;
        $d->annee = isset($data['annee']) && is_numeric($data['annee']) ? (int) $data['annee'] : null;
        $d->kilometrage = isset($data['kilometrage']) && is_numeric($data['kilometrage']) ? (int) $data['kilometrage'] : null;
        $d->vin = isset($data['vin']) && trim((string) $data['vin']) !== '' ? trim((string) $data['vin']) : null;
        $d->couleur = isset($data['couleur']) && trim((string) $data['couleur']) !== '' ? trim((string) $data['couleur']) : null;
        $d->proprietaireId = isset($data['proprietaireId']) && is_numeric($data['proprietaireId']) ? (int) $data['proprietaireId'] : null;

        return $d;
    }
}