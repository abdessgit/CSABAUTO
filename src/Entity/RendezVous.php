<?php

namespace App\Entity;

use App\Enum\RendezVousStatut;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: RendezVousRepository::class)]
#[ORM\Table(name: 'rendez_vous')]
class RendezVous
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
        #[Groups(['rendezvous:read'])]
private ?int $id = null;
    #[ORM\Column]     #[Groups(['rendezvous:read'])]
private ?\DateTimeImmutable $dateHeure = null;
    #[ORM\Column(length: 255, nullable: true)]     #[Groups(['rendezvous:read'])]
private ?string $motif = null;
    #[ORM\Column(length: 20, enumType: RendezVousStatut::class)]     #[Groups(['rendezvous:read'])]
private ?RendezVousStatut $statut = null;
    #[ORM\Column]     #[Groups(['rendezvous:read'])]
private ?\DateTimeImmutable $dateCreation = null;
    #[ORM\ManyToOne(inversedBy: 'rendezVousClient')] #[ORM\JoinColumn(nullable: false)]     #[Groups(['rendezvous:read'])]
private ?Utilisateur $client = null;
    #[ORM\ManyToOne(inversedBy: 'rendezVous')] #[ORM\JoinColumn(nullable: false)]     #[Groups(['rendezvous:read'])]
private ?Vehicule $vehicule = null;
    #[ORM\ManyToOne(inversedBy: 'rendezVousModerateur')]     #[Groups(['rendezvous:read'])]
private ?Utilisateur $moderateur = null;
    #[ORM\OneToOne(mappedBy: 'rendezVous', targetEntity: Intervention::class)]     #[Groups(['rendezvous:read'])]
private ?Intervention $intervention = null;
    public function getId(): ?int { return $this->id; }
    public function getDateHeure(): ?\DateTimeImmutable { return $this->dateHeure; } public function setDateHeure(\DateTimeImmutable $dateHeure): static { $this->dateHeure = $dateHeure; return $this; }
    public function getMotif(): ?string { return $this->motif; } public function setMotif(?string $motif): static { $this->motif = $motif; return $this; }
    public function getStatut(): ?RendezVousStatut { return $this->statut; } public function setStatut(RendezVousStatut $statut): static { $this->statut = $statut; return $this; }
    public function getDateCreation(): ?\DateTimeImmutable { return $this->dateCreation; } public function setDateCreation(\DateTimeImmutable $dateCreation): static { $this->dateCreation = $dateCreation; return $this; }
    public function getClient(): ?Utilisateur { return $this->client; } public function setClient(?Utilisateur $client): static { $this->client = $client; return $this; }
    public function getVehicule(): ?Vehicule { return $this->vehicule; } public function setVehicule(?Vehicule $vehicule): static { $this->vehicule = $vehicule; return $this; }
    public function getModerateur(): ?Utilisateur { return $this->moderateur; } public function setModerateur(?Utilisateur $moderateur): static { $this->moderateur = $moderateur; return $this; }
    public function getIntervention(): ?Intervention { return $this->intervention; }
    public function setIntervention(?Intervention $intervention): static { if ($intervention === null && $this->intervention !== null) { $this->intervention->setRendezVous(null); } if ($intervention !== null && $intervention->getRendezVous() !== $this) { $intervention->setRendezVous($this); } $this->intervention = $intervention; return $this; }
}
