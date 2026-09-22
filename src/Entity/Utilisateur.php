<?php

namespace App\Entity;

use App\Enum\UtilisateurRole;
use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
#[ORM\UniqueConstraint(name: 'UNIQ_UTILISATEUR_EMAIL', fields: ['email'])]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
        #[Groups(['utilisateur:read', 'utilisateur:summary'])]
private ?int $id = null;

    #[ORM\Column(length: 100)]
        #[Groups(['utilisateur:read', 'utilisateur:summary'])]
private ?string $nom = null;

    #[ORM\Column(length: 100)]
        #[Groups(['utilisateur:read', 'utilisateur:summary'])]
private ?string $prenom = null;

    #[ORM\Column(length: 180)]
        #[Groups(['utilisateur:read', 'utilisateur:summary'])]
private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 20, nullable: true)]
        #[Groups(['utilisateur:read'])]
private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
        #[Groups(['utilisateur:read'])]
private ?string $adresse = null;

    #[ORM\Column(type: 'json', enumType: UtilisateurRole::class)]
        #[Groups(['utilisateur:read'])]
private array $roles = [];

    #[ORM\Column]
        #[Groups(['utilisateur:read'])]
private ?\DateTimeImmutable $dateCreation = null;

    /** @var Collection<int, Vehicule> */
    #[ORM\OneToMany(mappedBy: 'proprietaire', targetEntity: Vehicule::class)]
    private Collection $vehicules;

    /** @var Collection<int, RendezVous> */
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: RendezVous::class)]
    private Collection $rendezVousClient;

    /** @var Collection<int, RendezVous> */
    #[ORM\OneToMany(mappedBy: 'moderateur', targetEntity: RendezVous::class)]
    private Collection $rendezVousModerateur;

    /** @var Collection<int, Conversation> */
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Conversation::class)]
    private Collection $conversationsClient;

    /** @var Collection<int, Conversation> */
    #[ORM\OneToMany(mappedBy: 'moderateur', targetEntity: Conversation::class)]
    private Collection $conversationsModerateur;

    /** @var Collection<int, Message> */
    #[ORM\OneToMany(mappedBy: 'expediteur', targetEntity: Message::class)]
    private Collection $messages;

    public function __construct()
    {
        $this->vehicules = new ArrayCollection();
        $this->rendezVousClient = new ArrayCollection();
        $this->rendezVousModerateur = new ArrayCollection();
        $this->conversationsClient = new ArrayCollection();
        $this->conversationsModerateur = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }
    public function getUserIdentifier(): string { return (string) $this->email; }
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }
    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }
    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): static { $this->adresse = $adresse; return $this; }
    public function getRoleEnums(): array { return $this->roles; }
    public function setRoleEnums(array $roles): static { $this->roles = $roles; return $this; }
    public function getRoles(): array { return array_values(array_unique(array_map(static fn (UtilisateurRole $role): string => 'ROLE_' . $role->value, $this->roles))); }
    public function eraseCredentials(): void {}
    public function getDateCreation(): ?\DateTimeImmutable { return $this->dateCreation; }
    public function setDateCreation(\DateTimeImmutable $dateCreation): static { $this->dateCreation = $dateCreation; return $this; }
    public function getVehicules(): Collection { return $this->vehicules; }
    public function addVehicule(Vehicule $vehicule): static { if (!$this->vehicules->contains($vehicule)) { $this->vehicules->add($vehicule); $vehicule->setProprietaire($this); } return $this; }
    public function removeVehicule(Vehicule $vehicule): static { if ($this->vehicules->removeElement($vehicule) && $vehicule->getProprietaire() === $this) { $vehicule->setProprietaire(null); } return $this; }
    public function getRendezVousClient(): Collection { return $this->rendezVousClient; }
    public function addRendezVousClient(RendezVous $rendezVousClient): static { if (!$this->rendezVousClient->contains($rendezVousClient)) { $this->rendezVousClient->add($rendezVousClient); $rendezVousClient->setClient($this); } return $this; }
    public function removeRendezVousClient(RendezVous $rendezVousClient): static { if ($this->rendezVousClient->removeElement($rendezVousClient) && $rendezVousClient->getClient() === $this) { $rendezVousClient->setClient(null); } return $this; }
    public function getRendezVousModerateur(): Collection { return $this->rendezVousModerateur; }
    public function addRendezVousModerateur(RendezVous $rendezVousModerateur): static { if (!$this->rendezVousModerateur->contains($rendezVousModerateur)) { $this->rendezVousModerateur->add($rendezVousModerateur); $rendezVousModerateur->setModerateur($this); } return $this; }
    public function removeRendezVousModerateur(RendezVous $rendezVousModerateur): static { if ($this->rendezVousModerateur->removeElement($rendezVousModerateur) && $rendezVousModerateur->getModerateur() === $this) { $rendezVousModerateur->setModerateur(null); } return $this; }
    public function getConversationsClient(): Collection { return $this->conversationsClient; }
    public function addConversationsClient(Conversation $conversationsClient): static { if (!$this->conversationsClient->contains($conversationsClient)) { $this->conversationsClient->add($conversationsClient); $conversationsClient->setClient($this); } return $this; }
    public function removeConversationsClient(Conversation $conversationsClient): static { if ($this->conversationsClient->removeElement($conversationsClient) && $conversationsClient->getClient() === $this) { $conversationsClient->setClient(null); } return $this; }
    public function getConversationsModerateur(): Collection { return $this->conversationsModerateur; }
    public function addConversationsModerateur(Conversation $conversationsModerateur): static { if (!$this->conversationsModerateur->contains($conversationsModerateur)) { $this->conversationsModerateur->add($conversationsModerateur); $conversationsModerateur->setModerateur($this); } return $this; }
    public function removeConversationsModerateur(Conversation $conversationsModerateur): static { if ($this->conversationsModerateur->removeElement($conversationsModerateur) && $conversationsModerateur->getModerateur() === $this) { $conversationsModerateur->setModerateur(null); } return $this; }
    public function getMessages(): Collection { return $this->messages; }
    public function addMessage(Message $message): static { if (!$this->messages->contains($message)) { $this->messages->add($message); $message->setExpediteur($this); } return $this; }
    public function removeMessage(Message $message): static { if ($this->messages->removeElement($message) && $message->getExpediteur() === $this) { $message->setExpediteur(null); } return $this; }
}