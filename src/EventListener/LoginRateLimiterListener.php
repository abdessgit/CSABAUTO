<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

class LoginRateLimiterListener implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'limiter.login_attempts')]
        private RateLimiterFactory $loginLimiter,
        private RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => 'onCheckPassport',
        ];
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        // Ne s'applique QUE sur la route de login, jamais sur les requêtes API authentifiées par JWT
        if (!$request || $request->getPathInfo() !== '/api/login_check') {
            return;
        }

        $passport = $event->getPassport();

        if (!$passport->hasBadge(UserBadge::class)) {
            return;
        }

        $ip = $request->getClientIp() ?? 'unknown';

        // Clé combinant IP + email tenté, pour limiter par couple attaquant/cible
        $email = $passport->getBadge(UserBadge::class)->getUserIdentifier();
        $key = $ip . '_' . $email;

        $limiter = $this->loginLimiter->create($key);

        if (!$limiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException(null, 'Trop de tentatives de connexion, réessayez plus tard.');
        }
    }
}
