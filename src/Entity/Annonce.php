<?php

namespace App\Entity;

use App\Enum\AnnonceStatut;
use App\Repository\AnnonceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: AnnonceRepository::class)]
#[ORM\Table(name: 'annonce')]
class Annonce
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['annonce:read', 'annonce:summary'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['annonce:read', 'annonce:summary'])]
    private ?string $titre = null;

    #[ORM\Column(length: 100)]
    #[Groups(['annonce:read', 'annonce:summary'])]
    private ?string $marque = null;

    #[ORM\Column(length: 100)]
    #[Groups(['annonce:read', 'annonce:summary'])]
    private ?string $modele = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['annonce:read', 'annonce:summary'])]
    private ?int $annee = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['annonce:read', 'annonce:summary'])]
    private ?int $kilometrage = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['annonce:read', 'annonce:summary'])]
    private ?string $prix = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['annonce:read', 'annonce:summary'])]
    private ?string $carburant = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['annonce:read', 'annonce:summary'])]
    private ?string $boite = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['annonce:read', 'annonce:summary'])]
    private ?int $puissance = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['annonce:read', 'annonce:summary'])]
    private ?string $couleur = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['annonce:read', 'annonce:summary'])]
    private ?int $nbPortes = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['annonce:read', 'annonce:summary'])]
    private ?int $nbPlaces = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['annonce:read', 'annonce:summary'])]
    private ?string $description = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['annonce:read', 'annonce:summary'])]
    private ?array $photos = [];

    #[ORM\Column(length: 20, enumType: AnnonceStatut::class)]
    #[Groups(['annonce:read', 'annonce:summary'])]
    private ?AnnonceStatut $statut = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['annonce:read', 'annonce:summary'])]
    private ?\DateTimeImmutable $datePublication = null;

    #[ORM\Column]
    #[Groups(['annonce:read', 'annonce:summary'])]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\ManyToOne(inversedBy: 'annonces')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['annonce:read'])]
    private ?Vehicule $vehicule = null;

    /** @var Collection<int, Photo> */
    #[ORM\OneToMany(mappedBy: 'annonce', targetEntity: Photo::class, orphanRemoval: true)]
    private Collection $photoEntities;

    /** @var Collection<int, Conversation> */
    #[ORM\OneToMany(mappedBy: 'annonce', targetEntity: Conversation::class)]
    private Collection $conversations;

    public function __construct()
    {
        $this->photoEntities = new ArrayCollection();
        $this->conversations = new ArrayCollection();
        $this->photos = [];
        $this->statut = AnnonceStatut::BROUILLON;
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getMarque(): ?string { return $this->marque; }
    public function setMarque(string $marque): static { $this->marque = $marque; return $this; }

    public function getModele(): ?string { return $this->modele; }
    public function setModele(string $modele): static { $this->modele = $modele; return $this; }

    public function getAnnee(): ?int { return $this->annee; }
    public function setAnnee(int $annee): static { $this->annee = $annee; return $this; }

    public function getKilometrage(): ?int { return $this->kilometrage; }
    public function setKilometrage(int $kilometrage): static { $this->kilometrage = $kilometrage; return $this; }

    public function getPrix(): ?string { return $this->prix; }
    public function setPrix(string|float|int $prix): static {
        $this->prix = is_numeric($prix) ? number_format((float) $prix, 2, '.', '') : (string) $prix;
        return $this;
    }

    public function getCarburant(): ?string { return $this->carburant; }
    public function setCarburant(?string $carburant): static { $this->carburant = $carburant; return $this; }

    public function getBoite(): ?string { return $this->boite; }
    public function setBoite(?string $boite): static { $this->boite = $boite; return $this; }

    public function getPuissance(): ?int { return $this->puissance; }
    public function setPuissance(?int $puissance): static { $this->puissance = $puissance; return $this; }

    public function getCouleur(): ?string { return $this->couleur; }
    public function setCouleur(?string $couleur): static { $this->couleur = $couleur; return $this; }

    public function getNbPortes(): ?int { return $this->nbPortes; }
    public function setNbPortes(?int $nbPortes): static { $this->nbPortes = $nbPortes; return $this; }

    public function getNbPlaces(): ?int { return $this->nbPlaces; }
    public function setNbPlaces(?int $nbPlaces): static { $this->nbPlaces = $nbPlaces; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    /**
     * @return string[]
     */
    public function getPhotos(): array
    {
        if (!empty($this->photos)) {
            return array_values($this->photos);
        }

        if (!$this->photoEntities->isEmpty()) {
            $list = $this->photoEntities->toArray();
            usort($list, fn(Photo $a, Photo $b) => ($a->getOrdreAffichage() ?? 0) <=> ($b->getOrdreAffichage() ?? 0));
            return array_values(array_map(fn(Photo $p) => $p->getUrl(), $list));
        }

        return [];
    }

    public function setPhotos(?array $photos): static
    {
        $this->photos = $photos ? array_values(array_filter($photos)) : [];
        return $this;
    }

    #[Groups(['annonce:read', 'annonce:summary'])]
    public function getImagePrincipale(): ?string
    {
        $p = $this->getPhotos();
        return $p[0] ?? null;
    }

    public function getStatut(): ?AnnonceStatut { return $this->statut; }
    public function setStatut(AnnonceStatut $statut): static { $this->statut = $statut; return $this; }

    public function getDatePublication(): ?\DateTimeImmutable { return $this->datePublication; }
    public function setDatePublication(?\DateTimeImmutable $datePublication): static { $this->datePublication = $datePublication; return $this; }

    public function getDateCreation(): ?\DateTimeImmutable { return $this->dateCreation; }
    public function setDateCreation(\DateTimeImmutable $dateCreation): static { $this->dateCreation = $dateCreation; return $this; }

    public function getVehicule(): ?Vehicule { return $this->vehicule; }
    public function setVehicule(?Vehicule $vehicule): static { $this->vehicule = $vehicule; return $this; }

    public function getPhotoEntities(): Collection { return $this->photoEntities; }
    public function addPhotoEntity(Photo $photo): static { if (!$this->photoEntities->contains($photo)) { $this->photoEntities->add($photo); $photo->setAnnonce($this); } return $this; }
    public function removePhotoEntity(Photo $photo): static { if ($this->photoEntities->removeElement($photo) && $photo->getAnnonce() === $this) { $photo->setAnnonce(null); } return $this; }

    public function getConversations(): Collection { return $this->conversations; }
    public function addConversation(Conversation $conversation): static { if (!$this->conversations->contains($conversation)) { $this->conversations->add($conversation); $conversation->setAnnonce($this); } return $this; }
    public function removeConversation(Conversation $conversation): static { if ($this->conversations->removeElement($conversation) && $conversation->getAnnonce() === $this) { $conversation->setAnnonce(null); } return $this; }
}
