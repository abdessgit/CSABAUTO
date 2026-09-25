<?php

namespace App\EventListener;

use App\Entity\Utilisateur;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_created', method: 'onJwtCreated')]
class JwtCreatedListener
{
    public function onJwtCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof Utilisateur) {
            return;
        }

        $payload = $event->getData();
        $payload['id'] = $user->getId();
        $event->setData($payload);
    }
}
