<?php

namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ORM\Table(name: 'service')]
class Service
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column]     #[Groups(['service:read', 'service:summary'])]
private ?int $id = null;
    #[ORM\Column(length: 150)]     #[Groups(['service:read', 'service:summary'])]
private ?string $nom = null;
    #[ORM\Column(type: 'text', nullable: true)]     #[Groups(['service:read'])]
private ?string $description = null;
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]     #[Groups(['service:read', 'service:summary'])]
private ?string $prixStandard = null;
    #[ORM\Column]     #[Groups(['service:read'])]
private ?int $dureeEstimee = null;
    /** @var Collection<int, InterventionService> */
    #[ORM\OneToMany(mappedBy: 'service', targetEntity: InterventionService::class)] private Collection $interventionServices;
    public function __construct() { $this->interventionServices = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; } public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getDescription(): ?string { return $this->description; } public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getPrixStandard(): ?string { return $this->prixStandard; } public function setPrixStandard(string $prixStandard): static { $this->prixStandard = $prixStandard; return $this; }
    public function getDureeEstimee(): ?int { return $this->dureeEstimee; } public function setDureeEstimee(int $dureeEstimee): static { $this->dureeEstimee = $dureeEstimee; return $this; }
    public function getInterventionServices(): Collection { return $this->interventionServices; }
    public function addInterventionService(InterventionService $interventionService): static { if (!$this->interventionServices->contains($interventionService)) { $this->interventionServices->add($interventionService); $interventionService->setService($this); } return $this; }
    public function removeInterventionService(InterventionService $interventionService): static { if ($this->interventionServices->removeElement($interventionService) && $interventionService->getService() === $this) { $interventionService->setService(null); } return $this; }
}
