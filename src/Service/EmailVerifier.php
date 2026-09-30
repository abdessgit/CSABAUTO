<?php

namespace App\Service;

use App\Entity\EmailVerificationToken;
use App\Entity\Utilisateur;
use App\Repository\EmailVerificationTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailVerifier
{
    public function __construct(
        private EntityManagerInterface $em,
        private EmailVerificationTokenRepository $tokenRepo,
        private MailerInterface $mailer,
        #[Autowire('%env(FRONTEND_URL)%')]
        private string $frontendUrl,
        #[Autowire('%env(MAILER_FROM)%')]
        private string $mailerFrom
    ) {}

    /**
     * Génère un token sécurisé, l'enregistre hashé en base et envoie l'e-mail de confirmation.
     */
    public function sendVerificationEmail(Utilisateur $user): void
    {
        // Invalider les anciens tokens non utilisés pour cet utilisateur
        $this->tokenRepo->invalidateTokensForUser($user);

        // 1. Génération cryptographiquement sûre d'un token aléatoire (32 octets = 64 caractères hex)
        $rawToken = bin2hex(random_bytes(32));

        // 2. Stockage exclusif du hash SHA256 en base (jamais le token en clair)
        $tokenHash = hash('sha256', $rawToken);

        $verificationToken = (new EmailVerificationToken())
            ->setUser($user)
            ->setTokenHash($tokenHash)
            ->setExpiresAt((new \DateTimeImmutable())->modify('+24 hours'));

        $this->em->persist($verificationToken);
        $this->em->flush();

        // 3. Lien de confirmation pointant vers le frontend avec le token en clair
        $confirmationUrl = sprintf(
            '%s/verifier-email?token=%s',
            rtrim($this->frontendUrl, '/'),
            $rawToken
        );

        $recipientName = trim(sprintf('%s %s', $user->getPrenom() ?? '', $user->getNom() ?? ''));
        if ($recipientName === '') {
            $recipientName = 'Client';
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFrom, 'CS AB AUTO'))
            ->to(new Address($user->getEmail(), $recipientName))
            ->subject('Confirmez votre adresse e-mail — CS AB AUTO')
            ->htmlTemplate('emails/verification_email.html.twig')
            ->textTemplate('emails/verification_email.txt.twig')
            ->context([
                'user' => $user,
                'confirmationUrl' => $confirmationUrl,
                'expiresInHours' => 24,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Valide le token fourni, active l'utilisateur et marque le token comme utilisé.
     *
     * @throws \DomainException Avec l'un des codes : TOKEN_INVALID, TOKEN_ALREADY_USED, TOKEN_EXPIRED
     */
    public function verifyEmail(string $rawToken): Utilisateur
    {
        if (trim($rawToken) === '') {
            throw new \DomainException('TOKEN_INVALID');
        }

        $tokenHash = hash('sha256', $rawToken);

        /** @var EmailVerificationToken|null $token */
        $token = $this->tokenRepo->findOneBy(['tokenHash' => $tokenHash]);

        if (!$token || !hash_equals($token->getTokenHash(), $tokenHash)) {
            throw new \DomainException('TOKEN_INVALID');
        }

        if ($token->isUsed()) {
            throw new \DomainException('TOKEN_ALREADY_USED');
        }

        if ($token->isExpired()) {
            throw new \DomainException('TOKEN_EXPIRED');
        }

        // Marquer le token comme consommé
        $token->setUsedAt(new \DateTimeImmutable());

        // Activer l'utilisateur
        $user = $token->getUser();
        $user->setIsVerified(true);
        $user->setEmailVerifiedAt(new \DateTimeImmutable());

        $this->em->flush();

        return $user;
    }
}
