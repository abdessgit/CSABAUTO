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
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['facture:read', 'intervention:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['facture:read', 'intervention:read'])]
    private ?string $numeroFacture = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Groups(['facture:read', 'intervention:read'])]
    private ?\DateTimeImmutable $dateEmission = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['facture:read', 'intervention:read'])]
    private ?\DateTimeImmutable $datePrestation = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, options: ['default' => '20.00'])]
    #[Groups(['facture:read', 'intervention:read'])]
    private string $tauxTva = '20.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    #[Groups(['facture:read', 'intervention:read'])]
    private string $montantHT = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    #[Groups(['facture:read', 'intervention:read'])]
    private string $montantTVA = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    #[Groups(['facture:read', 'intervention:read'])]
    private string $montantTTC = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['facture:read', 'intervention:read'])]
    private ?string $montantTotal = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['facture:read', 'intervention:read'])]
    private ?\DateTimeImmutable $dateEcheance = null;

    #[ORM\Column(length: 20, enumType: FactureStatut::class)]
    #[Groups(['facture:read', 'intervention:read'])]
    private ?FactureStatut $statut = FactureStatut::EN_ATTENTE;

    #[ORM\OneToOne(inversedBy: 'facture', targetEntity: Intervention::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['facture:read'])]
    private ?Intervention $intervention = null;

    /** @var Collection<int, LigneFacture> */
    #[ORM\OneToMany(mappedBy: 'facture', targetEntity: LigneFacture::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(['facture:read', 'intervention:read'])]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $lignesFacture;

    public function __construct()
    {
        $this->lignesFacture = new ArrayCollection();
        $this->dateEmission = new \DateTimeImmutable();
        $this->datePrestation = new \DateTimeImmutable();
        $this->dateEcheance = (new \DateTimeImmutable())->modify('+30 days');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroFacture(): ?string
    {
        return $this->numeroFacture;
    }

    public function setNumeroFacture(string $numeroFacture): static
    {
        $this->numeroFacture = $numeroFacture;
        return $this;
    }

    #[Groups(['facture:read', 'intervention:read'])]
    public function getNumero(): ?string
    {
        return $this->numeroFacture;
    }

    public function getDateEmission(): ?\DateTimeImmutable
    {
        return $this->dateEmission;
    }

    public function setDateEmission(\DateTimeImmutable $dateEmission): static
    {
        $this->dateEmission = $dateEmission;
        return $this;
    }

    public function getDatePrestation(): ?\DateTimeImmutable
    {
        return $this->datePrestation;
    }

    public function setDatePrestation(?\DateTimeImmutable $datePrestation): static
    {
        $this->datePrestation = $datePrestation;
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

    public function getMontantHT(): string
    {
        return $this->montantHT;
    }

    public function setMontantHT(string|float $montantHT): static
    {
        $this->montantHT = number_format((float) $montantHT, 2, '.', '');
        return $this;
    }

    public function getMontantTVA(): string
    {
        return $this->montantTVA;
    }

    public function setMontantTVA(string|float $montantTVA): static
    {
        $this->montantTVA = number_format((float) $montantTVA, 2, '.', '');
        return $this;
    }

    public function getMontantTTC(): string
    {
        return $this->montantTTC;
    }

    public function setMontantTTC(string|float $montantTTC): static
    {
        $formatted = number_format((float) $montantTTC, 2, '.', '');
        $this->montantTTC = $formatted;
        $this->montantTotal = $formatted;
        return $this;
    }

    public function getMontantTotal(): ?string
    {
        return $this->montantTotal ?? $this->montantTTC;
    }

    public function setMontantTotal(string|float $montantTotal): static
    {
        $formatted = number_format((float) $montantTotal, 2, '.', '');
        $this->montantTotal = $formatted;
        $this->montantTTC = $formatted;
        return $this;
    }

    public function getDateEcheance(): ?\DateTimeImmutable
    {
        return $this->dateEcheance;
    }

    public function setDateEcheance(?\DateTimeImmutable $dateEcheance): static
    {
        $this->dateEcheance = $dateEcheance;
        return $this;
    }

    public function getStatut(): ?FactureStatut
    {
        return $this->statut;
    }

    public function setStatut(FactureStatut $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    #[Groups(['facture:read', 'intervention:read'])]
    public function getStatutPaiement(): ?FactureStatut
    {
        return $this->statut;
    }

    public function setStatutPaiement(?FactureStatut $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getIntervention(): ?Intervention
    {
        return $this->intervention;
    }

    public function setIntervention(Intervention $intervention): static
    {
        $this->intervention = $intervention;
        return $this;
    }

    public function getLignesFacture(): Collection
    {
        return $this->lignesFacture;
    }

    public function addLignesFacture(LigneFacture $lignesFacture): static
    {
        if (!$this->lignesFacture->contains($lignesFacture)) {
            $this->lignesFacture->add($lignesFacture);
            $lignesFacture->setFacture($this);
        }
        return $this;
    }

    public function removeLignesFacture(LigneFacture $lignesFacture): static
    {
        if ($this->lignesFacture->removeElement($lignesFacture) && $lignesFacture->getFacture() === $this) {
            $lignesFacture->setFacture(null);
        }
        return $this;
    }
}
