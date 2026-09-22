<?php

namespace App\Controller\Api;

use App\Dto\CreateConversationDto;
use App\Entity\Conversation;
use App\Enum\ConversationStatut;
use App\Repository\AnnonceRepository;
use App\Repository\ConversationRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/conversations')]
class ConversationController extends AbstractApiController
{
    public function __construct(private EntityManagerInterface $em, private SerializerInterface $serializer, private ValidatorInterface $validator, private UtilisateurRepository $users, private AnnonceRepository $annonces, private Security $security) {}
    private function canAccess(Conversation $conversation): bool { $user = $this->security->getUser(); return $conversation->getClient() === $user || $conversation->getModerateur() === $user || $this->security->isGranted('ROLE_MODERATEUR'); }
    #[Route('', methods: ['GET'])] public function list(ConversationRepository $repo): JsonResponse { $items = $this->security->isGranted('ROLE_MODERATEUR') ? $repo->findAll() : $repo->findBy(['client' => $this->security->getUser()]); return $this->jsonRead($items, 'conversation:read', $this->serializer); }
    #[Route('/{id}', methods: ['GET'])] public function show(Conversation $conversation): JsonResponse { if (!$this->canAccess($conversation)) return new JsonResponse(['error' => 'Accès refusé'], 403); return $this->jsonRead($conversation, 'conversation:read', $this->serializer); }
    #[Route('/{id}/messages', methods: ['GET'])] public function messages(Conversation $conversation): JsonResponse { if (!$this->canAccess($conversation)) return new JsonResponse(['error' => 'Accès refusé'], 403); $m = $conversation->getMessages()->toArray(); usort($m, fn($a, $b) => $a->getDateEnvoi() <=> $b->getDateEnvoi()); return $this->jsonRead($m, 'message:read', $this->serializer); }
    #[Route('', methods: ['POST'])] public function create(Request $request): JsonResponse { $dto = CreateConversationDto::fromRequest($this->data($request)); if ($r = $this->validateDto($dto, $this->validator)) return $r; if (!$this->security->isGranted('ROLE_MODERATEUR') && $dto->clientId !== $this->security->getUser()?->getId()) return new JsonResponse(['error' => 'Accès refusé'], 403); $client = $this->users->find($dto->clientId); if (!$client) return new JsonResponse(['error' => 'Client introuvable'], 404); $mod = $dto->moderateurId ? $this->users->find($dto->moderateurId) : null; if ($dto->moderateurId && !$mod) return new JsonResponse(['error' => 'Modérateur introuvable'], 404); $ann = $dto->annonceId ? $this->annonces->find($dto->annonceId) : null; if ($dto->annonceId && !$ann) return new JsonResponse(['error' => 'Annonce introuvable'], 404); $c = (new Conversation())->setClient($client)->setModerateur($mod)->setAnnonce($ann)->setStatut(ConversationStatut::OUVERTE)->setDateCreation(new \DateTimeImmutable()); $this->em->persist($c); $this->em->flush(); return $this->jsonRead($c, 'conversation:read', $this->serializer, 201); }
    #[Route('/{id}', methods: ['PUT'])] public function update(Conversation $conversation, Request $request): JsonResponse { if (!$this->canAccess($conversation)) return new JsonResponse(['error' => 'Accès refusé'], 403); $dto = CreateConversationDto::fromRequest($this->data($request)); if ($r = $this->validateDto($dto, $this->validator)) return $r; $client = $this->users->find($dto->clientId); if (!$client) return new JsonResponse(['error' => 'Client introuvable'], 404); $conversation->setClient($client); $this->em->flush(); return $this->jsonRead($conversation, 'conversation:read', $this->serializer); }
    #[Route('/{id}', methods: ['DELETE'])] #[IsGranted('ROLE_MODERATEUR')] public function delete(Conversation $conversation): JsonResponse { $this->em->remove($conversation); $this->em->flush(); return new JsonResponse(null, 204); }
}
