<?php

namespace App\Entity;

use App\Enum\ConversationStatut;
use App\Repository\ConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
#[ORM\Table(name: 'conversation')]
class Conversation
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column]     #[Groups(['conversation:read'])]
private ?int $id = null;
    #[ORM\Column]     #[Groups(['conversation:read'])]
private ?\DateTimeImmutable $dateCreation = null;
    #[ORM\Column(length: 20, enumType: ConversationStatut::class)]     #[Groups(['conversation:read'])]
private ?ConversationStatut $statut = null;
    #[ORM\ManyToOne(inversedBy: 'conversationsClient')] #[ORM\JoinColumn(nullable: false)]     #[Groups(['conversation:read'])]
private ?Utilisateur $client = null;
    #[ORM\ManyToOne(inversedBy: 'conversationsModerateur')]     #[Groups(['conversation:read'])]
private ?Utilisateur $moderateur = null;
    #[ORM\ManyToOne(inversedBy: 'conversations')]     #[Groups(['conversation:read'])]
private ?Annonce $annonce = null;
    /** @var Collection<int, Message> */
    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: Message::class, orphanRemoval: true)] private Collection $messages;
    public function __construct() { $this->messages = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function getDateCreation(): ?\DateTimeImmutable { return $this->dateCreation; } public function setDateCreation(\DateTimeImmutable $dateCreation): static { $this->dateCreation = $dateCreation; return $this; }
    public function getStatut(): ?ConversationStatut { return $this->statut; } public function setStatut(ConversationStatut $statut): static { $this->statut = $statut; return $this; }
    public function getClient(): ?Utilisateur { return $this->client; } public function setClient(?Utilisateur $client): static { $this->client = $client; return $this; }
    public function getModerateur(): ?Utilisateur { return $this->moderateur; } public function setModerateur(?Utilisateur $moderateur): static { $this->moderateur = $moderateur; return $this; }
    public function getAnnonce(): ?Annonce { return $this->annonce; } public function setAnnonce(?Annonce $annonce): static { $this->annonce = $annonce; return $this; }
    public function getMessages(): Collection { return $this->messages; }
    public function addMessage(Message $message): static { if (!$this->messages->contains($message)) { $this->messages->add($message); $message->setConversation($this); } return $this; }
    public function removeMessage(Message $message): static { if ($this->messages->removeElement($message) && $message->getConversation() === $this) { $message->setConversation(null); } return $this; }
}
