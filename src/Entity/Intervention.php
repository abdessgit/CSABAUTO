<?php

namespace App\Entity;

use App\Enum\InterventionStatut;
use App\Repository\InterventionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: InterventionRepository::class)]
#[ORM\Table(name: 'intervention')]
class Intervention
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column]     #[Groups(['intervention:read'])]
private ?int $id = null;
    #[ORM\Column(type: 'date_immutable')]     #[Groups(['intervention:read'])]
private ?\DateTimeImmutable $dateIntervention = null;
    #[ORM\Column(type: 'text', nullable: true)]     #[Groups(['intervention:read'])]
private ?string $description = null;
    #[ORM\Column(nullable: true)]     #[Groups(['intervention:read'])]
private ?int $kilometrageReleve = null;
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]     #[Groups(['intervention:read'])]
private ?string $coutTotal = null;
    #[ORM\Column(length: 20, enumType: InterventionStatut::class)]     #[Groups(['intervention:read'])]
private ?InterventionStatut $statut = null;
    #[ORM\ManyToOne(inversedBy: 'interventions')] #[ORM\JoinColumn(nullable: false)]     #[Groups(['intervention:read'])]
private ?Vehicule $vehicule = null;
    #[ORM\OneToOne(inversedBy: 'intervention', targetEntity: RendezVous::class)]     #[Groups(['intervention:read'])]
private ?RendezVous $rendezVous = null;
    /** @var Collection<int, InterventionService> */
    #[ORM\OneToMany(mappedBy: 'intervention', targetEntity: InterventionService::class, orphanRemoval: true)] private Collection $interventionServices;
    #[ORM\OneToOne(mappedBy: 'intervention', targetEntity: Facture::class)]     #[Groups(['intervention:read'])]
private ?Facture $facture = null;
    public function __construct() { $this->interventionServices = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function getDateIntervention(): ?\DateTimeImmutable { return $this->dateIntervention; } public function setDateIntervention(\DateTimeImmutable $dateIntervention): static { $this->dateIntervention = $dateIntervention; return $this; }
    public function getDescription(): ?string { return $this->description; } public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getKilometrageReleve(): ?int { return $this->kilometrageReleve; } public function setKilometrageReleve(?int $kilometrageReleve): static { $this->kilometrageReleve = $kilometrageReleve; return $this; }
    public function getCoutTotal(): ?string { return $this->coutTotal; } public function setCoutTotal(string $coutTotal): static { $this->coutTotal = $coutTotal; return $this; }
    public function getStatut(): ?InterventionStatut { return $this->statut; } public function setStatut(InterventionStatut $statut): static { $this->statut = $statut; return $this; }
    public function getVehicule(): ?Vehicule { return $this->vehicule; } public function setVehicule(?Vehicule $vehicule): static { $this->vehicule = $vehicule; return $this; }
    public function getRendezVous(): ?RendezVous { return $this->rendezVous; } public function setRendezVous(?RendezVous $rendezVous): static { $this->rendezVous = $rendezVous; return $this; }
    public function getInterventionServices(): Collection { return $this->interventionServices; }
    public function addInterventionService(InterventionService $interventionService): static { if (!$this->interventionServices->contains($interventionService)) { $this->interventionServices->add($interventionService); $interventionService->setIntervention($this); } return $this; }
    public function removeInterventionService(InterventionService $interventionService): static { if ($this->interventionServices->removeElement($interventionService) && $interventionService->getIntervention() === $this) { $interventionService->setIntervention(null); } return $this; }
    public function getFacture(): ?Facture { return $this->facture; }
    public function setFacture(Facture $facture): static { if ($facture->getIntervention() !== $this) { $facture->setIntervention($this); } $this->facture = $facture; return $this; }
}
