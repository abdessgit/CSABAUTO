<?php

namespace App\Entity;

use App\Repository\LigneFactureRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: LigneFactureRepository::class)]
#[ORM\Table(name: 'ligne_facture')]
class LigneFacture
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column]     #[Groups(['lignefacture:read'])]
private ?int $id = null;
    #[ORM\Column(length: 255)]     #[Groups(['lignefacture:read'])]
private ?string $description = null;
    #[ORM\Column]     #[Groups(['lignefacture:read'])]
private ?int $quantite = null;
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]     #[Groups(['lignefacture:read'])]
private ?string $prixUnitaire = null;
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]     #[Groups(['lignefacture:read'])]
private ?string $sousTotal = null;
    #[ORM\ManyToOne(inversedBy: 'lignesFacture')] #[ORM\JoinColumn(nullable: false)]     #[Groups(['lignefacture:read'])]
private ?Facture $facture = null;
    public function getId(): ?int { return $this->id; }
    public function getDescription(): ?string { return $this->description; } public function setDescription(string $description): static { $this->description = $description; return $this; }
    public function getQuantite(): ?int { return $this->quantite; } public function setQuantite(int $quantite): static { $this->quantite = $quantite; return $this; }
    public function getPrixUnitaire(): ?string { return $this->prixUnitaire; } public function setPrixUnitaire(string $prixUnitaire): static { $this->prixUnitaire = $prixUnitaire; return $this; }
    public function getSousTotal(): ?string { return $this->sousTotal; } public function setSousTotal(string $sousTotal): static { $this->sousTotal = $sousTotal; return $this; }
    public function getFacture(): ?Facture { return $this->facture; } public function setFacture(?Facture $facture): static { $this->facture = $facture; return $this; }
}
