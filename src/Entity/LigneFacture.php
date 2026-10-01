<?php

namespace App\Entity;

use App\Repository\LigneFactureRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: LigneFactureRepository::class)]
#[ORM\Table(name: 'ligne_facture')]
class LigneFacture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['lignefacture:read', 'facture:read', 'intervention:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['lignefacture:read', 'facture:read', 'intervention:read'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['lignefacture:read', 'facture:read', 'intervention:read'])]
    private ?int $quantite = 1;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['lignefacture:read', 'facture:read', 'intervention:read'])]
    private ?string $prixUnitaire = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['lignefacture:read', 'facture:read', 'intervention:read'])]
    private ?string $sousTotal = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, options: ['default' => '20.00'])]
    #[Groups(['lignefacture:read', 'facture:read', 'intervention:read'])]
    private string $tauxTva = '20.00';

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['lignefacture:read', 'facture:read', 'intervention:read'])]
    private int $ordre = 0;

    #[ORM\ManyToOne(inversedBy: 'lignesFacture')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['lignefacture:read'])]
    private ?Facture $facture = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    #[Groups(['lignefacture:read', 'facture:read', 'intervention:read'])]
    public function getDesignation(): ?string
    {
        return $this->description;
    }

    public function setDesignation(string $designation): static
    {
        $this->description = $designation;
        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;
        return $this;
    }

    public function getPrixUnitaire(): ?string
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(string|float $prixUnitaire): static
    {
        $this->prixUnitaire = number_format((float) $prixUnitaire, 2, '.', '');
        return $this;
    }

    public function getSousTotal(): ?string
    {
        return $this->sousTotal;
    }

    public function setSousTotal(string|float $sousTotal): static
    {
        $this->sousTotal = number_format((float) $sousTotal, 2, '.', '');
        return $this;
    }

    public function getTauxTva(): string
    {
        return $this->tauxTva;
    }

    public function setTauxTva(string|float $tauxTva): static
    {
        $this->tauxTva = number_format((float) $tauxTva, 2, '.', '');
        return $this;
    }

    #[Groups(['lignefacture:read', 'facture:read', 'intervention:read'])]
    public function getMontant(): ?string
    {
        return $this->getMontantHT();
    }

    public function setMontant(string|float $montant): static
    {
        $formatted = number_format((float) $montant, 2, '.', '');
        $this->prixUnitaire = $formatted;
        $this->sousTotal = $formatted;
        return $this;
    }

    #[Groups(['lignefacture:read', 'facture:read', 'intervention:read'])]
    public function getMontantHT(): string
    {
        return $this->sousTotal ?? $this->prixUnitaire ?? '0.00';
    }

    #[Groups(['lignefacture:read', 'facture:read', 'intervention:read'])]
    public function getMontantTVA(): string
    {
        $ht = (float) $this->getMontantHT();
        $tva = round($ht * ((float) $this->tauxTva / 100), 2);
        return number_format($tva, 2, '.', '');
    }

    #[Groups(['lignefacture:read', 'facture:read', 'intervention:read'])]
    public function getMontantTTC(): string
    {
        $ht = (float) $this->getMontantHT();
        $tva = (float) $this->getMontantTVA();
        return number_format($ht + $tva, 2, '.', '');
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;
        return $this;
    }

    public function getFacture(): ?Facture
    {
        return $this->facture;
    }

    public function setFacture(?Facture $facture): static
    {
        $this->facture = $facture;
        return $this;
    }
}
