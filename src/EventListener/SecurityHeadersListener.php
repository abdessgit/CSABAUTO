<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityHeadersListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        // Protection contre le MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Protection contre le détournement de clic (Clickjacking)
        $response->headers->set('X-Frame-Options', 'DENY');

        // Politique de référencement stricte
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restriction des fonctionnalités sensibles du navigateur
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content Security Policy adaptée aux besoins réels de l'application
        $csp = "default-src 'self'; "
            . "script-src 'self'; "
            . "style-src 'self' 'unsafe-inline'; "
            . "img-src 'self' data: https:; "
            . "font-src 'self' data:; "
            . "connect-src 'self' https:; "
            . "frame-ancestors 'none'; "
            . "base-uri 'self'; "
            . "form-action 'self'";
        $response->headers->set('Content-Security-Policy', $csp);

        // HSTS (Strict-Transport-Security) : activé sur HTTPS ou en production
        if ($request->isSecure() || $request->server->get('APP_ENV') === 'prod') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }
}
