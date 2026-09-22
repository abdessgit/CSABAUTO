<?php

namespace App\Controller\Api;

use App\Dto\CreateMessageDto;
use App\Entity\Conversation;
use App\Entity\Message;
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

#[Route('/api/messages')]
class MessageController extends AbstractApiController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private ConversationRepository $conversations,
        private UtilisateurRepository $users,
        private Security $security,
    ) {}

    private function canAccessConversation(Conversation $conversation): bool
    {
        $user = $this->security->getUser();
        return $conversation->getClient() === $user
            || $conversation->getModerateur() === $user
            || $this->security->isGranted('ROLE_MODERATEUR');
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = CreateMessageDto::fromRequest($this->data($request));
        if ($r = $this->validateDto($dto, $this->validator)) {
            return $r;
        }

        if ($dto->expediteurId !== $this->security->getUser()?->getId()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $conversation = $this->conversations->find($dto->conversationId);
        if (!$conversation) {
            return new JsonResponse(['error' => 'Conversation introuvable'], 404);
        }

        if (!$this->canAccessConversation($conversation)) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $expediteur = $this->users->find($dto->expediteurId);

        $message = (new Message())
            ->setContenu($dto->contenu)
            ->setConversation($conversation)
            ->setExpediteur($expediteur)
            ->setDateEnvoi(new \DateTimeImmutable())
            ->setLu(false);

        $this->em->persist($message);
        $this->em->flush();

        return $this->jsonRead($message, 'message:read', $this->serializer, 201);
    }

    #[Route('/{id}/lu', methods: ['PATCH'])]
    public function marquerLu(Message $message): JsonResponse
    {
        $conversation = $message->getConversation();
        $user = $this->security->getUser();

        // le destinataire "logique" = l'autre participant de la conversation
        $estDestinataire = $message->getExpediteur() !== $user
            && ($conversation->getClient() === $user || $conversation->getModerateur() === $user);

        if (!$estDestinataire && !$this->security->isGranted('ROLE_MODERATEUR')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $message->setLu(true);
        $this->em->flush();

        return $this->jsonRead($message, 'message:read', $this->serializer);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function delete(Message $message): JsonResponse
    {
        $this->em->remove($message);
        $this->em->flush();
        return new JsonResponse(null, 204);
    }
}