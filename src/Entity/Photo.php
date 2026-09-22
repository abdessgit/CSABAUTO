<?php

namespace App\Entity;

use App\Repository\PhotoRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PhotoRepository::class)]
#[ORM\Table(name: 'photo')]
class Photo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
        #[Groups(['photo:read'])]
private ?int $id = null;

    #[ORM\Column(length: 500)]
        #[Groups(['photo:read'])]
private ?string $url = null;

    #[ORM\Column(nullable: true)]
        #[Groups(['photo:read'])]
private ?int $ordreAffichage = null;

    #[ORM\ManyToOne(inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false)]
        #[Groups(['photo:read'])]
private ?Annonce $annonce = null;

    public function getId(): ?int { return $this->id; }
    public function getUrl(): ?string { return $this->url; }
    public function setUrl(string $url): static { $this->url = $url; return $this; }
    public function getOrdreAffichage(): ?int { return $this->ordreAffichage; }
    public function setOrdreAffichage(?int $ordreAffichage): static { $this->ordreAffichage = $ordreAffichage; return $this; }
    public function getAnnonce(): ?Annonce { return $this->annonce; }
    public function setAnnonce(?Annonce $annonce): static { $this->annonce = $annonce; return $this; }
}
