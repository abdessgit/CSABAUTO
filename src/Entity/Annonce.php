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

    #[ORM\Column(type: 'text')]
        #[Groups(['annonce:read'])]
private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
        #[Groups(['annonce:read', 'annonce:summary'])]
private ?string $prix = null;

    #[ORM\Column(length: 20, enumType: AnnonceStatut::class)]
        #[Groups(['annonce:read', 'annonce:summary'])]
private ?AnnonceStatut $statut = null;

    #[ORM\Column]
        #[Groups(['annonce:read'])]
private ?\DateTimeImmutable $datePublication = null;

    #[ORM\ManyToOne(inversedBy: 'annonces')]
    #[ORM\JoinColumn(nullable: false)]
        #[Groups(['annonce:read'])]
private ?Vehicule $vehicule = null;

    /** @var Collection<int, Photo> */
    #[ORM\OneToMany(mappedBy: 'annonce', targetEntity: Photo::class, orphanRemoval: true)]
    private Collection $photos;

    /** @var Collection<int, Conversation> */
    #[ORM\OneToMany(mappedBy: 'annonce', targetEntity: Conversation::class)]
    private Collection $conversations;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
        $this->conversations = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }
    public function getPrix(): ?string { return $this->prix; }
    public function setPrix(string $prix): static { $this->prix = $prix; return $this; }
    public function getStatut(): ?AnnonceStatut { return $this->statut; }
    public function setStatut(AnnonceStatut $statut): static { $this->statut = $statut; return $this; }
    public function getDatePublication(): ?\DateTimeImmutable { return $this->datePublication; }
    public function setDatePublication(\DateTimeImmutable $datePublication): static { $this->datePublication = $datePublication; return $this; }
    public function getVehicule(): ?Vehicule { return $this->vehicule; }
    public function setVehicule(?Vehicule $vehicule): static { $this->vehicule = $vehicule; return $this; }
    public function getPhotos(): Collection { return $this->photos; }
    public function addPhoto(Photo $photo): static { if (!$this->photos->contains($photo)) { $this->photos->add($photo); $photo->setAnnonce($this); } return $this; }
    public function removePhoto(Photo $photo): static { if ($this->photos->removeElement($photo) && $photo->getAnnonce() === $this) { $photo->setAnnonce(null); } return $this; }
    public function getConversations(): Collection { return $this->conversations; }
    public function addConversation(Conversation $conversation): static { if (!$this->conversations->contains($conversation)) { $this->conversations->add($conversation); $conversation->setAnnonce($this); } return $this; }
    public function removeConversation(Conversation $conversation): static { if ($this->conversations->removeElement($conversation) && $conversation->getAnnonce() === $this) { $conversation->setAnnonce(null); } return $this; }
}
