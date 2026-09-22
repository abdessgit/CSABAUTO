<?php

namespace App\Entity;

use App\Enum\FactureStatut;
use App\Repository\FactureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: FactureRepository::class)]
#[ORM\Table(name: 'facture')]
#[ORM\UniqueConstraint(name: 'UNIQ_FACTURE_NUMERO', fields: ['numeroFacture'])]
class Facture
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column]     #[Groups(['facture:read'])]
private ?int $id = null;
    #[ORM\Column(length: 50)]     #[Groups(['facture:read'])]
private ?string $numeroFacture = null;
    #[ORM\Column(type: 'date_immutable')]     #[Groups(['facture:read'])]
private ?\DateTimeImmutable $dateEmission = null;
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]     #[Groups(['facture:read'])]
private ?string $montantTotal = null;
    #[ORM\Column(length: 20, enumType: FactureStatut::class)]     #[Groups(['facture:read'])]
private ?FactureStatut $statut = null;
    #[ORM\OneToOne(inversedBy: 'facture', targetEntity: Intervention::class)] #[ORM\JoinColumn(nullable: false)]     #[Groups(['facture:read'])]
private ?Intervention $intervention = null;
    /** @var Collection<int, LigneFacture> */
    #[ORM\OneToMany(mappedBy: 'facture', targetEntity: LigneFacture::class, orphanRemoval: true)] private Collection $lignesFacture;
    public function __construct() { $this->lignesFacture = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function getNumeroFacture(): ?string { return $this->numeroFacture; } public function setNumeroFacture(string $numeroFacture): static { $this->numeroFacture = $numeroFacture; return $this; }
    public function getDateEmission(): ?\DateTimeImmutable { return $this->dateEmission; } public function setDateEmission(\DateTimeImmutable $dateEmission): static { $this->dateEmission = $dateEmission; return $this; }
    public function getMontantTotal(): ?string { return $this->montantTotal; } public function setMontantTotal(string $montantTotal): static { $this->montantTotal = $montantTotal; return $this; }
    public function getStatut(): ?FactureStatut { return $this->statut; } public function setStatut(FactureStatut $statut): static { $this->statut = $statut; return $this; }
    public function getIntervention(): ?Intervention { return $this->intervention; } public function setIntervention(Intervention $intervention): static { $this->intervention = $intervention; return $this; }
    public function getLignesFacture(): Collection { return $this->lignesFacture; }
    public function addLignesFacture(LigneFacture $lignesFacture): static { if (!$this->lignesFacture->contains($lignesFacture)) { $this->lignesFacture->add($lignesFacture); $lignesFacture->setFacture($this); } return $this; }
    public function removeLignesFacture(LigneFacture $lignesFacture): static { if ($this->lignesFacture->removeElement($lignesFacture) && $lignesFacture->getFacture() === $this) { $lignesFacture->setFacture(null); } return $this; }
}
