<?php

namespace App\EventListener;

use App\Entity\Utilisateur;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_created')]
class JwtCreatedListener
{
    public function __invoke(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        if ($user instanceof Utilisateur) {
            $payload = $event->getData();
            $payload['id'] = $user->getId();
            if ($user->getPasswordChangedAt() !== null) {
                $payload['pwd_changed_at'] = $user->getPasswordChangedAt()->getTimestamp();
            }
            $event->setData($payload);
        }
    }
}
