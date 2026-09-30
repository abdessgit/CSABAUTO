<?php

namespace App\EventListener;

use App\Entity\Utilisateur;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_authenticated')]
class JwtRevocationListener
{
    public function __invoke(JWTAuthenticatedEvent $event): void
    {
        $payload = $event->getPayload();
        $user = $event->getToken()->getUser();

        if ($user instanceof Utilisateur && $user->getPasswordChangedAt() !== null) {
            $changedAtTimestamp = $user->getPasswordChangedAt()->getTimestamp();
            $tokenIat = $payload['iat'] ?? null;
            $tokenPwdChangedAt = $payload['pwd_changed_at'] ?? null;

            // Si le jeton JWT a été émis avant la modification du mot de passe
            if ($tokenIat !== null && $tokenIat < $changedAtTimestamp) {
                throw new InvalidTokenException('Votre session a expiré suite à la modification de votre mot de passe. Veuillez vous reconnecter.');
            }

            // Si le claim spécifique est présent mais plus ancien que la date en base
            if ($tokenPwdChangedAt !== null && $tokenPwdChangedAt < $changedAtTimestamp) {
                throw new InvalidTokenException('Votre session a expiré suite à la modification de votre mot de passe. Veuillez vous reconnecter.');
            }
        }
    }
}
