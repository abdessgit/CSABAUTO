<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccountStatusException;

#[AsEventListener(event: 'lexik_jwt_authentication.on_authentication_failure', method: 'onAuthenticationFailure')]
class AuthenticationFailureListener
{
    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $exception = $event->getException();

        if ($exception instanceof AccountStatusException && $exception->getMessageKey() === 'EMAIL_NOT_VERIFIED') {
            $response = new JsonResponse([
                'code' => 'EMAIL_NOT_VERIFIED',
                'error' => 'EMAIL_NOT_VERIFIED',
                'message' => 'Votre adresse e-mail n\'a pas encore été confirmée. Veuillez vérifier votre boîte mail.',
            ], 403);

            $event->setResponse($response);
        }
    }
}
