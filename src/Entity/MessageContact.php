<?php

namespace App\Entity;

use App\Enum\MessageContactStatut;
use App\Repository\MessageContactRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MessageContactRepository::class)]
#[ORM\Table(name: 'message_contact')]
#[ORM\Index(name: 'idx_message_contact_statut', fields: ['statut'])]
#[ORM\Index(name: 'idx_message_contact_date_envoi', fields: ['dateEnvoi'])]
class MessageContact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['message_contact:read', 'message_contact:detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['message_contact:read', 'message_contact:detail'])]
    private ?string $nom = null;

    #[ORM\Column(length: 180)]
    #[Groups(['message_contact:read', 'message_contact:detail'])]
    private ?string $email = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Groups(['message_contact:read', 'message_contact:detail'])]
    private ?string $telephone = null;

    #[ORM\Column(length: 150, nullable: true)]
    #[Groups(['message_contact:read', 'message_contact:detail'])]
    private ?string $sujet = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['message_contact:read', 'message_contact:detail'])]
    private ?string $message = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['message_contact:read', 'message_contact:detail'])]
    private ?\DateTimeImmutable $dateEnvoi = null;

    #[ORM\Column(length: 20, enumType: MessageContactStatut::class)]
    #[Groups(['message_contact:read', 'message_contact:detail'])]
    private MessageContactStatut $statut = MessageContactStatut::NOUVEAU;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['message_contact:read', 'message_contact:detail'])]
    private ?string $reponse = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['message_contact:read', 'message_contact:detail'])]
    private ?\DateTimeImmutable $dateReponse = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['message_contact:read', 'message_contact:detail'])]
    private ?Utilisateur $repondPar = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Groups(['message_contact:detail'])]
    private ?string $ipAdresse = null;

    public function __construct()
    {
        $this->dateEnvoi = new \DateTimeImmutable();
        $this->statut = MessageContactStatut::NOUVEAU;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getSujet(): ?string
    {
        return $this->sujet;
    }

    public function setSujet(?string $sujet): static
    {
        $this->sujet = $sujet;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getDateEnvoi(): ?\DateTimeImmutable
    {
        return $this->dateEnvoi;
    }

    public function setDateEnvoi(\DateTimeImmutable $dateEnvoi): static
    {
        $this->dateEnvoi = $dateEnvoi;
        return $this;
    }

    public function getStatut(): MessageContactStatut
    {
        return $this->statut;
    }

    public function setStatut(MessageContactStatut $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getReponse(): ?string
    {
        return $this->reponse;
    }

    public function setReponse(?string $reponse): static
    {
        $this->reponse = $reponse;
        return $this;
    }

    public function getDateReponse(): ?\DateTimeImmutable
    {
        return $this->dateReponse;
    }

    public function setDateReponse(?\DateTimeImmutable $dateReponse): static
    {
        $this->dateReponse = $dateReponse;
        return $this;
    }

    public function getRepondPar(): ?Utilisateur
    {
        return $this->repondPar;
    }

    public function setRepondPar(?Utilisateur $repondPar): static
    {
        $this->repondPar = $repondPar;
        return $this;
    }

    public function getIpAdresse(): ?string
    {
        return $this->ipAdresse;
    }

    public function setIpAdresse(?string $ipAdresse): static
    {
        $this->ipAdresse = $ipAdresse;
        return $this;
    }
}
