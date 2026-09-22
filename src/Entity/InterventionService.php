<?php

namespace App\Entity;

use App\Repository\InterventionServiceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: InterventionServiceRepository::class)]
#[ORM\Table(name: 'intervention_service')]
class InterventionService
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column]     #[Groups(['interventionservice:read'])]
private ?int $id = null;
    #[ORM\Column]     #[Groups(['interventionservice:read'])]
private ?int $quantite = null;
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]     #[Groups(['interventionservice:read'])]
private ?string $prixApplique = null;
    #[ORM\ManyToOne(inversedBy: 'interventionServices')] #[ORM\JoinColumn(nullable: false)]     #[Groups(['interventionservice:read'])]
private ?Intervention $intervention = null;
    #[ORM\ManyToOne(inversedBy: 'interventionServices')] #[ORM\JoinColumn(nullable: false)]     #[Groups(['interventionservice:read'])]
private ?Service $service = null;
    public function getId(): ?int { return $this->id; }
    public function getQuantite(): ?int { return $this->quantite; } public function setQuantite(int $quantite): static { $this->quantite = $quantite; return $this; }
    public function getPrixApplique(): ?string { return $this->prixApplique; } public function setPrixApplique(string $prixApplique): static { $this->prixApplique = $prixApplique; return $this; }
    public function getIntervention(): ?Intervention { return $this->intervention; } public function setIntervention(?Intervention $intervention): static { $this->intervention = $intervention; return $this; }
    public function getService(): ?Service { return $this->service; } public function setService(?Service $service): static { $this->service = $service; return $this; }
}
