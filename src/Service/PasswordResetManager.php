<?php

namespace App\Service;

use App\Entity\PasswordResetToken;
use App\Entity\Utilisateur;
use App\Repository\PasswordResetTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private PasswordResetTokenRepository $tokenRepo,
        private MailerInterface $mailer,
        private EmailVerifier $emailVerifier,
        private UserPasswordHasherInterface $hasher,
        #[Autowire('%env(FRONTEND_URL)%')]
        private string $frontendUrl,
        #[Autowire('%env(MAILER_FROM)%')]
        private string $mailerFrom
    ) {}

    /**
     * Traite la demande de réinitialisation :
     * - Si le compte n'est pas vérifié, renvoie l'e-mail d'activation du compte.
     * - Si le compte est vérifié, génère un token sécurisé de 1h et envoie l'e-mail de réinitialisation.
     */
    public function requestPasswordReset(Utilisateur $user, ?string $requestIp = null): void
    {
        // Si le compte n'est pas encore vérifié, on envoie le lien d'activation de compte
        if (!$user->isVerified()) {
            $this->emailVerifier->sendVerificationEmail($user);
            return;
        }

        // Invalider les anciens tokens de réinitialisation pour cet utilisateur
        $this->tokenRepo->invalidateTokensForUser($user);

        // 1. Génération cryptographiquement sécurisée d'un token aléatoire (32 octets = 64 caractères hex)
        $rawToken = bin2hex(random_bytes(32));

        // 2. Stockage exclusif du hash SHA256 en base
        $tokenHash = hash('sha256', $rawToken);

        $resetToken = (new PasswordResetToken())
            ->setUser($user)
            ->setTokenHash($tokenHash)
            ->setRequestIp($requestIp)
            ->setExpiresAt((new \DateTimeImmutable())->modify('+1 hour'));

        $this->em->persist($resetToken);
        $this->em->flush();

        // 3. Lien de réinitialisation vers le frontend
        $resetUrl = sprintf(
            '%s/reinitialiser-mot-de-passe?token=%s',
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
            ->subject('Réinitialisation de votre mot de passe — CS AB AUTO')
            ->htmlTemplate('emails/reset_password_email.html.twig')
            ->textTemplate('emails/reset_password_email.txt.twig')
            ->context([
                'user' => $user,
                'resetUrl' => $resetUrl,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Valide le jeton fourni en temps constant et vérifie expiration/usage.
     *
     * @throws \DomainException Avec l'un des codes : TOKEN_MISSING, TOKEN_INVALID, TOKEN_ALREADY_USED, TOKEN_EXPIRED
     */
    public function validateToken(string $rawToken): PasswordResetToken
    {
        $rawToken = trim($rawToken);
        if ($rawToken === '') {
            throw new \DomainException('TOKEN_MISSING');
        }

        $tokenHash = hash('sha256', $rawToken);

        /** @var PasswordResetToken|null $token */
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

        return $token;
    }

    /**
     * Réinitialise le mot de passe de l'utilisateur, invalide les sessions JWT antérieures et notifie l'utilisateur.
     *
     * @throws \DomainException Si le jeton est invalide ou expiré
     */
    public function resetPassword(string $rawToken, string $newPassword): Utilisateur
    {
        $token = $this->validateToken($rawToken);
        $user = $token->getUser();

        $now = new \DateTimeImmutable();

        // Hachage sécurisé du nouveau mot de passe avec l'algorithme Symfony 'auto'
        $user->setPassword($this->hasher->hashPassword($user, $newPassword));
        // Met à jour la date de changement pour révoquer automatiquement tout ancien JWT actif
        $user->setPasswordChangedAt($now);

        // Marque le token comme consommé
        $token->setUsedAt($now);

        // Supprime les autres tokens de réinitialisation de cet utilisateur
        $this->tokenRepo->deleteTokensForUser($user, $token);

        $this->em->flush();

        // Envoi d'un e-mail d'alerte informant que le mot de passe a été modifié
        $this->sendPasswordChangedNotification($user, $now);

        return $user;
    }

    /**
     * Envoie un e-mail de notification indiquant que le mot de passe a été modifié.
     */
    private function sendPasswordChangedNotification(Utilisateur $user, \DateTimeImmutable $changedAt): void
    {
        try {
            $recipientName = trim(sprintf('%s %s', $user->getPrenom() ?? '', $user->getNom() ?? ''));
            if ($recipientName === '') {
                $recipientName = 'Client';
            }

            $email = (new TemplatedEmail())
                ->from(new Address($this->mailerFrom, 'CS AB AUTO'))
                ->to(new Address($user->getEmail(), $recipientName))
                ->subject('Votre mot de passe a été modifié — CS AB AUTO')
                ->htmlTemplate('emails/password_changed_email.html.twig')
                ->textTemplate('emails/password_changed_email.txt.twig')
                ->context([
                    'user' => $user,
                    'changedAt' => $changedAt,
                ]);

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            // Ne pas bloquer la réinitialisation si la notification échoue
        }
    }
}
