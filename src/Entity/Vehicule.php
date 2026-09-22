<?php

namespace App\Entity;

use App\Repository\VehiculeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: VehiculeRepository::class)]
#[ORM\Table(name: 'vehicule')]
#[ORM\UniqueConstraint(name: 'UNIQ_VEHICULE_IMMATRICULATION', fields: ['immatriculation'])]
class Vehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
        #[Groups(['vehicule:read', 'vehicule:summary'])]
private ?int $id = null;
    #[ORM\Column(length: 100)]     #[Groups(['vehicule:read', 'vehicule:summary'])]
private ?string $marque = null;
    #[ORM\Column(length: 100)]     #[Groups(['vehicule:read', 'vehicule:summary'])]
private ?string $modele = null;
    #[ORM\Column]     #[Groups(['vehicule:read'])]
private ?int $annee = null;
    #[ORM\Column(length: 20)]     #[Groups(['vehicule:read', 'vehicule:summary'])]
private ?string $immatriculation = null;
    #[ORM\Column(length: 50, nullable: true)]     #[Groups(['vehicule:read'])]
private ?string $vin = null;
    #[ORM\Column]     #[Groups(['vehicule:read'])]
private ?int $kilometrage = null;
    #[ORM\Column(length: 50, nullable: true)]     #[Groups(['vehicule:read'])]
private ?string $couleur = null;
    #[ORM\Column]     #[Groups(['vehicule:read'])]
private ?\DateTimeImmutable $dateAjout = null;
    #[ORM\ManyToOne(inversedBy: 'vehicules')] #[ORM\JoinColumn(nullable: false)]     #[Groups(['vehicule:read'])]
private ?Utilisateur $proprietaire = null;
    /** @var Collection<int, Annonce> */ #[ORM\OneToMany(mappedBy: 'vehicule', targetEntity: Annonce::class)] private Collection $annonces;
    /** @var Collection<int, RendezVous> */ #[ORM\OneToMany(mappedBy: 'vehicule', targetEntity: RendezVous::class)] private Collection $rendezVous;
    /** @var Collection<int, Intervention> */ #[ORM\OneToMany(mappedBy: 'vehicule', targetEntity: Intervention::class)] private Collection $interventions;
    public function __construct() { $this->annonces = new ArrayCollection(); $this->rendezVous = new ArrayCollection(); $this->interventions = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function getMarque(): ?string { return $this->marque; } public function setMarque(string $marque): static { $this->marque = $marque; return $this; }
    public function getModele(): ?string { return $this->modele; } public function setModele(string $modele): static { $this->modele = $modele; return $this; }
    public function getAnnee(): ?int { return $this->annee; } public function setAnnee(int $annee): static { $this->annee = $annee; return $this; }
    public function getImmatriculation(): ?string { return $this->immatriculation; } public function setImmatriculation(string $immatriculation): static { $this->immatriculation = $immatriculation; return $this; }
    public function getVin(): ?string { return $this->vin; } public function setVin(?string $vin): static { $this->vin = $vin; return $this; }
    public function getKilometrage(): ?int { return $this->kilometrage; } public function setKilometrage(int $kilometrage): static { $this->kilometrage = $kilometrage; return $this; }
    public function getCouleur(): ?string { return $this->couleur; } public function setCouleur(?string $couleur): static { $this->couleur = $couleur; return $this; }
    public function getDateAjout(): ?\DateTimeImmutable { return $this->dateAjout; } public function setDateAjout(\DateTimeImmutable $dateAjout): static { $this->dateAjout = $dateAjout; return $this; }
    public function getProprietaire(): ?Utilisateur { return $this->proprietaire; } public function setProprietaire(?Utilisateur $proprietaire): static { $this->proprietaire = $proprietaire; return $this; }
    public function getAnnonces(): Collection { return $this->annonces; } public function addAnnonce(Annonce $annonce): static { if (!$this->annonces->contains($annonce)) { $this->annonces->add($annonce); $annonce->setVehicule($this); } return $this; } public function removeAnnonce(Annonce $annonce): static { if ($this->annonces->removeElement($annonce) && $annonce->getVehicule() === $this) { $annonce->setVehicule(null); } return $this; }
    public function getRendezVous(): Collection { return $this->rendezVous; } public function addRendezVous(RendezVous $rendezVous): static { if (!$this->rendezVous->contains($rendezVous)) { $this->rendezVous->add($rendezVous); $rendezVous->setVehicule($this); } return $this; } public function removeRendezVous(RendezVous $rendezVous): static { if ($this->rendezVous->removeElement($rendezVous) && $rendezVous->getVehicule() === $this) { $rendezVous->setVehicule(null); } return $this; }
    public function getInterventions(): Collection { return $this->interventions; } public function addIntervention(Intervention $intervention): static { if (!$this->interventions->contains($intervention)) { $this->interventions->add($intervention); $intervention->setVehicule($this); } return $this; } public function removeIntervention(Intervention $intervention): static { if ($this->interventions->removeElement($intervention) && $intervention->getVehicule() === $this) { $intervention->setVehicule(null); } return $this; }
}