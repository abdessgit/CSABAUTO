<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'message')]
class Message
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column]     #[Groups(['message:read'])]
private ?int $id = null;
    #[ORM\Column(type: 'text')]     #[Groups(['message:read'])]
private ?string $contenu = null;
    #[ORM\Column]     #[Groups(['message:read'])]
private ?\DateTimeImmutable $dateEnvoi = null;
    #[ORM\Column(options: ['default' => false])]     #[Groups(['message:read'])]
private ?bool $lu = false;
    #[ORM\ManyToOne(inversedBy: 'messages')] #[ORM\JoinColumn(nullable: false)]     #[Groups(['message:read'])]
private ?Conversation $conversation = null;
    #[ORM\ManyToOne(inversedBy: 'messages')] #[ORM\JoinColumn(nullable: false)]     #[Groups(['message:read'])]
private ?Utilisateur $expediteur = null;
    public function getId(): ?int { return $this->id; }
    public function getContenu(): ?string { return $this->contenu; } public function setContenu(string $contenu): static { $this->contenu = $contenu; return $this; }
    public function getDateEnvoi(): ?\DateTimeImmutable { return $this->dateEnvoi; } public function setDateEnvoi(\DateTimeImmutable $dateEnvoi): static { $this->dateEnvoi = $dateEnvoi; return $this; }
    public function isLu(): ?bool { return $this->lu; } public function setLu(bool $lu): static { $this->lu = $lu; return $this; }
    public function getConversation(): ?Conversation { return $this->conversation; } public function setConversation(?Conversation $conversation): static { $this->conversation = $conversation; return $this; }
    public function getExpediteur(): ?Utilisateur { return $this->expediteur; } public function setExpediteur(?Utilisateur $expediteur): static { $this->expediteur = $expediteur; return $this; }
}
