<?php

namespace App\Controller\Api;

use App\Dto\CreateMessageContactDto;
use App\Dto\RepondreMessageContactDto;
use App\Entity\MessageContact;
use App\Entity\Utilisateur;
use App\Enum\MessageContactStatut;
use App\Repository\MessageContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ContactController extends AbstractApiController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private MailerInterface $mailer,
        private MessageContactRepository $contactRepo,
        private Security $security,
        #[Autowire(service: 'limiter.contact_attempts')]
        private RateLimiterFactory $contactLimiter,
        #[Autowire(service: 'limiter.contact_reply_attempts')]
        private RateLimiterFactory $contactReplyLimiter,
        #[Autowire('%company.email%')]
        private string $companyEmail,
        #[Autowire('%env(MAILER_FROM)%')]
        private string $mailerFrom,
        #[Autowire('%env(FRONTEND_URL)%')]
        private string $frontendUrl,
    ) {}

    /**
     * Endpoint public : envoi d'un message de contact.
     * Accessible à tous, avec rate limiting (3/heure par IP) et honeypot anti-spam.
     */
    #[Route('/api/contact', name: 'api_contact_create', methods: ['POST'])]
    public function submit(Request $request): JsonResponse
    {
        // 1. Rate Limiting par adresse IP
        $clientIp = $request->getClientIp() ?: '127.0.0.1';
        $limiter = $this->contactLimiter->create($clientIp);
        $limit = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter();
            $waitMinutes = ceil(($retryAfter->getTimestamp() - time()) / 60);

            return new JsonResponse([
                'error' => 'RATE_LIMIT_EXCEEDED',
                'message' => sprintf(
                    'Trop de messages envoyés depuis votre connexion. Veuillez patienter %d minute(s) avant de réessayer.',
                    max(1, $waitMinutes)
                ),
                'retryAfter' => $retryAfter->getTimestamp(),
            ], 429);
        }

        // 2. Désérialisation et validation du DTO
        $data = $this->data($request);
        $dto = new CreateMessageContactDto();
        $dto->nom = trim((string) ($data['nom'] ?? ''));
        $dto->email = trim((string) ($data['email'] ?? ''));
        $dto->telephone = isset($data['telephone']) ? trim((string) $data['telephone']) : null;
        $dto->sujet = isset($data['sujet']) ? trim((string) $data['sujet']) : null;
        $dto->message = trim((string) ($data['message'] ?? ''));
        $dto->site_web = isset($data['site_web']) ? trim((string) $data['site_web']) : null;

        // 3. Honeypot anti-spam silencieux
        // Si le champ 'site_web' est rempli, on feint le succès sans rien enregistrer ni envoyer
        if (!empty($dto->site_web)) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Votre message a bien été envoyé. Notre équipe vous répondra dans les plus brefs délais.',
            ], 200);
        }

        $validationError = $this->validateDto($dto, $this->validator);
        if ($validationError) {
            return $validationError;
        }

        // 4. Création et persistance de l'entité
        $messageContact = (new MessageContact())
            ->setNom($dto->nom)
            ->setEmail($dto->email)
            ->setTelephone($dto->telephone ?: null)
            ->setSujet($dto->sujet ?: null)
            ->setMessage($dto->message)
            ->setIpAdresse($clientIp);

        $this->em->persist($messageContact);
        $this->em->flush();

        // 5. Envoi de l'e-mail de confirmation au visiteur
        try {
            $visitorEmail = (new TemplatedEmail())
                ->from(new Address($this->mailerFrom, 'CS AB AUTO'))
                ->to(new Address($messageContact->getEmail(), $messageContact->getNom()))
                ->subject('Confirmation de réception de votre message — CS AB AUTO')
                ->htmlTemplate('emails/contact_confirmation_email.html.twig')
                ->textTemplate('emails/contact_confirmation_email.txt.twig')
                ->context([
                    'messageContact' => $messageContact,
                ]);

            $this->mailer->send($visitorEmail);
        } catch (\Throwable $e) {
            // Ne bloque pas la confirmation si le mailer échoue
        }

        // 6. Envoi de l'e-mail de notification interne au garage
        try {
            $dashboardUrl = sprintf('%s/dashboard/moderateur/messages-contact', rtrim($this->frontendUrl, '/'));
            $adminEmail = (new TemplatedEmail())
                ->from(new Address($this->mailerFrom, 'CS AB AUTO Contact'))
                ->to(new Address($this->companyEmail, 'CS AB AUTO'))
                ->replyTo(new Address($messageContact->getEmail(), $messageContact->getNom()))
                ->subject(sprintf('[Contact CSAB] Nouveau message de %s', $messageContact->getNom()))
                ->htmlTemplate('emails/contact_notification_admin_email.html.twig')
                ->textTemplate('emails/contact_notification_admin_email.txt.twig')
                ->context([
                    'messageContact' => $messageContact,
                    'dashboardUrl' => $dashboardUrl,
                ]);

            $this->mailer->send($adminEmail);
        } catch (\Throwable $e) {
            // Ne bloque pas la confirmation si le mailer échoue
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Votre message a bien été envoyé. Notre équipe vous répondra dans les plus brefs délais.',
            'id' => $messageContact->getId(),
        ], 201);
    }

    /**
     * Endpoint modérateur/admin : liste paginée des messages de contact.
     */
    #[Route('/api/messages-contact', name: 'api_messages_contact_list', methods: ['GET'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function list(Request $request): JsonResponse
    {
        $statut = $request->query->get('statut');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(50, max(1, $request->query->getInt('limit', 20)));

        $result = $this->contactRepo->findPaginatedFiltered($statut, $page, $limit);

        return $this->jsonRead([
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($result['total'] / $limit),
            'nouveaux' => $result['nouveaux'],
        ], 'message_contact:read', $this->serializer);
    }

    /**
     * Endpoint modérateur/admin : détail complet d'un message de contact.
     */
    #[Route('/api/messages-contact/{id}', name: 'api_messages_contact_detail', methods: ['GET'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function detail(int $id): JsonResponse
    {
        $messageContact = $this->contactRepo->find($id);
        if (!$messageContact) {
            return new JsonResponse(['error' => 'Message non trouvé.'], 404);
        }

        return $this->jsonRead($messageContact, 'message_contact:detail', $this->serializer);
    }

    /**
     * Endpoint modérateur/admin : répondre à un message de contact par e-mail.
     */
    #[Route('/api/messages-contact/{id}/repondre', name: 'api_messages_contact_repondre', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function repondre(int $id, Request $request): JsonResponse
    {
        /** @var Utilisateur $user */
        $user = $this->security->getUser();

        // Rate limiter modérateur pour éviter les envois massifs ou doublons
        $limiter = $this->contactReplyLimiter->create('user_'.$user->getId());
        $limit = $limiter->consume(1);
        if (!$limit->isAccepted()) {
            return new JsonResponse([
                'error' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Trop de réponses envoyées en peu de temps. Veuillez patienter un instant.',
            ], 429);
        }

        $messageContact = $this->contactRepo->find($id);
        if (!$messageContact) {
            return new JsonResponse(['error' => 'Message non trouvé.'], 404);
        }

        // Protection contre les réponses multiples
        if ($messageContact->getStatut() === MessageContactStatut::TRAITE) {
            $repNom = $messageContact->getRepondPar() ? ($messageContact->getRepondPar()->getPrenom() . ' ' . $messageContact->getRepondPar()->getNom()) : 'un administrateur';
            $date = $messageContact->getDateReponse() ? $messageContact->getDateReponse()->format('d/m/Y à H:i') : '';
            return new JsonResponse([
                'error' => 'MESSAGE_ALREADY_REPLIED',
                'message' => sprintf('Une réponse a déjà été envoyée à ce message par %s le %s.', $repNom, $date),
            ], 422);
        }

        $data = $this->data($request);
        $dto = new RepondreMessageContactDto();
        $dto->reponse = trim((string) ($data['reponse'] ?? ''));

        $validationError = $this->validateDto($dto, $this->validator);
        if ($validationError) {
            return $validationError;
        }

        // Mise à jour de l'entité
        $messageContact->setReponse($dto->reponse);
        $messageContact->setDateReponse(new \DateTimeImmutable());
        $messageContact->setRepondPar($user);
        $messageContact->setStatut(MessageContactStatut::TRAITE);

        $this->em->flush();

        // Envoi de l'e-mail au visiteur
        try {
            $replyEmail = (new TemplatedEmail())
                ->from(new Address($this->mailerFrom, 'CS AB AUTO'))
                ->to(new Address($messageContact->getEmail(), $messageContact->getNom()))
                ->replyTo(new Address($this->companyEmail, 'CS AB AUTO'))
                ->subject(sprintf('Réponse de CS AB AUTO à votre message%s', $messageContact->getSujet() ? ' : ' . $messageContact->getSujet() : ''))
                ->htmlTemplate('emails/contact_reponse_email.html.twig')
                ->textTemplate('emails/contact_reponse_email.txt.twig')
                ->context([
                    'messageContact' => $messageContact,
                ]);

            $this->mailer->send($replyEmail);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'EMAIL_SEND_FAILED',
                'message' => 'La réponse a été enregistrée mais l\'e-mail n\'a pas pu être délivré : ' . $e->getMessage(),
            ], 500);
        }

        return $this->jsonRead($messageContact, 'message_contact:detail', $this->serializer);
    }
}
