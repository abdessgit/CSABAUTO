<?php

namespace App\Controller\Api;

use App\Repository\UtilisateurRepository;
use App\Service\EmailVerifier;
use App\Service\PasswordResetManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
class AuthController extends AbstractApiController
{
    public function __construct(
        private EmailVerifier $emailVerifier,
        private PasswordResetManager $passwordResetManager,
        private UtilisateurRepository $userRepo,
        #[Autowire(service: 'limiter.resend_verification_attempts')]
        private RateLimiterFactory $resendLimiter,
        #[Autowire(service: 'limiter.forgot_password_attempts')]
        private RateLimiterFactory $forgotPasswordLimiter
    ) {}

    /**
     * Endpoint public : vérification et activation du compte via token.
     */
    #[Route('/verify-email', methods: ['POST'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $data = $this->data($request);
        $rawToken = trim((string) ($data['token'] ?? ''));

        if ($rawToken === '') {
            return new JsonResponse([
                'error' => 'TOKEN_MISSING',
                'message' => 'Le jeton de confirmation est obligatoire.',
            ], 400);
        }

        try {
            $user = $this->emailVerifier->verifyEmail($rawToken);

            return new JsonResponse([
                'success' => true,
                'message' => 'Votre adresse e-mail a été confirmée avec succès. Vous pouvez maintenant vous connecter.',
                'email' => $user->getEmail(),
            ], 200);
        } catch (\DomainException $e) {
            $code = $e->getMessage();

            return match ($code) {
                'TOKEN_ALREADY_USED' => new JsonResponse([
                    'error' => 'TOKEN_ALREADY_USED',
                    'message' => 'Ce lien de confirmation a déjà été utilisé. Votre compte est peut-être déjà actif.',
                ], 410),
                'TOKEN_EXPIRED' => new JsonResponse([
                    'error' => 'TOKEN_EXPIRED',
                    'message' => 'Ce lien de confirmation a expiré (validité de 24 heures). Veuillez demander un nouvel e-mail.',
                ], 410),
                default => new JsonResponse([
                    'error' => 'TOKEN_INVALID',
                    'message' => 'Ce lien de confirmation est invalide ou corrompu.',
                ], 400),
            };
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'SERVER_ERROR',
                'message' => 'Une erreur est survenue lors de la vérification de l\'e-mail.',
            ], 400);
        }
    }

    /**
     * Endpoint public : renvoi d'un nouvel e-mail de confirmation avec rate limiting.
     */
    #[Route('/resend-verification', methods: ['POST'])]
    public function resendVerification(Request $request): JsonResponse
    {
        $data = $this->data($request);
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'error' => 'INVALID_EMAIL',
                'message' => 'Veuillez saisir une adresse e-mail valide.',
            ], 400);
        }

        // Rate limiting combinant IP + email pour éviter le spam
        $ip = $request->getClientIp() ?? 'unknown';
        $key = sprintf('resend_%s_%s', md5($ip), md5($email));
        $limiter = $this->resendLimiter->create($key);

        if (!$limiter->consume(1)->isAccepted()) {
            return new JsonResponse([
                'error' => 'TOO_MANY_REQUESTS',
                'message' => 'Trop de demandes de renvoi pour cette adresse. Veuillez patienter avant de réessayer.',
            ], 429);
        }

        // Réponse générique pour éviter d'énumérer les comptes inscrits
        $genericResponse = new JsonResponse([
            'success' => true,
            'message' => 'Si un compte non vérifié est associé à cette adresse e-mail, un nouveau lien de confirmation vient de vous être envoyé.',
        ], 200);

        $user = $this->userRepo->findOneBy(['email' => $email]);

        // Si l'utilisateur n'existe pas ou est déjà vérifié, ne rien faire de plus
        if (!$user || $user->isVerified()) {
            return $genericResponse;
        }

        try {
            $this->emailVerifier->sendVerificationEmail($user);
        } catch (\Throwable $e) {
            // Log l'erreur côté serveur sans exposer les détails sensibles
        }

        return $genericResponse;
    }

    /**
     * Endpoint public : demande de réinitialisation de mot de passe (anti-énumération et rate limited).
     */
    #[Route('/forgot-password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $this->data($request);
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'error' => 'INVALID_EMAIL',
                'message' => 'Veuillez saisir une adresse e-mail valide.',
            ], 400);
        }

        // Rate limiting combinant IP + email pour éviter le spam et le déni de service
        $ip = $request->getClientIp() ?? 'unknown';
        $key = sprintf('forgot_pwd_%s_%s', md5($ip), md5($email));
        $limiter = $this->forgotPasswordLimiter->create($key);

        if (!$limiter->consume(1)->isAccepted()) {
            return new JsonResponse([
                'error' => 'TOO_MANY_REQUESTS',
                'message' => 'Trop de demandes de réinitialisation pour cette adresse. Veuillez patienter avant de réessayer.',
            ], 429);
        }

        // Réponse générique pour éviter l'énumération de comptes
        $genericResponse = new JsonResponse([
            'success' => true,
            'message' => 'Si un compte existe avec cette adresse, un e-mail contenant les instructions de réinitialisation vous a été envoyé.',
        ], 200);

        $user = $this->userRepo->findOneBy(['email' => $email]);

        if (!$user) {
            return $genericResponse;
        }

        try {
            $this->passwordResetManager->requestPasswordReset($user, $request->getClientIp());
        } catch (\Throwable $e) {
            // Erreur silencieuse pour ne pas révéler l'existence du compte ni faire crasher l'API
        }

        return $genericResponse;
    }

    /**
     * Endpoint public : validation préalable du token de réinitialisation (affichera immédiatement si expiré).
     */
    #[Route('/reset-password/validate', methods: ['GET'])]
    public function validateResetPasswordToken(Request $request): JsonResponse
    {
        $rawToken = trim((string) $request->query->get('token', ''));

        if ($rawToken === '') {
            return new JsonResponse([
                'valid' => false,
                'error' => 'TOKEN_MISSING',
                'message' => 'Le jeton de réinitialisation est obligatoire.',
            ], 400);
        }

        try {
            $this->passwordResetManager->validateToken($rawToken);

            return new JsonResponse([
                'valid' => true,
                'message' => 'Le jeton de réinitialisation est valide.',
            ], 200);
        } catch (\DomainException $e) {
            $code = $e->getMessage();

            return match ($code) {
                'TOKEN_ALREADY_USED' => new JsonResponse([
                    'valid' => false,
                    'error' => 'TOKEN_ALREADY_USED',
                    'message' => 'Ce lien de réinitialisation a déjà été utilisé. Veuillez effectuer une nouvelle demande.',
                ], 410),
                'TOKEN_EXPIRED' => new JsonResponse([
                    'valid' => false,
                    'error' => 'TOKEN_EXPIRED',
                    'message' => 'Ce lien de réinitialisation a expiré (validité de 1 heure). Veuillez effectuer une nouvelle demande.',
                ], 410),
                default => new JsonResponse([
                    'valid' => false,
                    'error' => 'TOKEN_INVALID',
                    'message' => 'Ce lien de réinitialisation est invalide ou corrompu.',
                ], 400),
            };
        } catch (\Throwable $e) {
            return new JsonResponse([
                'valid' => false,
                'error' => 'SERVER_ERROR',
                'message' => 'Impossible de valider le jeton.',
            ], 400);
        }
    }

    /**
     * Endpoint public : réinitialisation définitive du mot de passe via token.
     */
    #[Route('/reset-password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $this->data($request);

        $rawToken = trim((string) ($data['token'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $confirmPassword = (string) ($data['confirmPassword'] ?? '');

        if ($rawToken === '') {
            return new JsonResponse([
                'error' => 'TOKEN_MISSING',
                'message' => 'Le jeton de réinitialisation est obligatoire.',
            ], 400);
        }

        // Règles de robustesse et conformité du mot de passe
        if (strlen($password) < 8) {
            return new JsonResponse([
                'error' => 'VALIDATION_ERROR',
                'message' => 'Le nouveau mot de passe doit comporter au moins 8 caractères.',
            ], 422);
        }

        if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return new JsonResponse([
                'error' => 'VALIDATION_ERROR',
                'message' => 'Le mot de passe doit contenir au moins une lettre et un chiffre.',
            ], 422);
        }

        if ($password !== $confirmPassword) {
            return new JsonResponse([
                'error' => 'VALIDATION_ERROR',
                'message' => 'Les mots de passe saisis ne correspondent pas.',
            ], 422);
        }

        try {
            $user = $this->passwordResetManager->resetPassword($rawToken, $password);

            return new JsonResponse([
                'success' => true,
                'message' => 'Votre mot de passe a été modifié avec succès. Vous pouvez maintenant vous connecter.',
                'email' => $user->getEmail(),
            ], 200);
        } catch (\DomainException $e) {
            $code = $e->getMessage();

            return match ($code) {
                'TOKEN_ALREADY_USED' => new JsonResponse([
                    'error' => 'TOKEN_ALREADY_USED',
                    'message' => 'Ce lien de réinitialisation a déjà été utilisé. Veuillez redemander un lien.',
                ], 410),
                'TOKEN_EXPIRED' => new JsonResponse([
                    'error' => 'TOKEN_EXPIRED',
                    'message' => 'Ce lien de réinitialisation a expiré (validité de 1 heure). Veuillez redemander un lien.',
                ], 410),
                default => new JsonResponse([
                    'error' => 'TOKEN_INVALID',
                    'message' => 'Ce lien de réinitialisation est invalide ou corrompu.',
                ], 400),
            };
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'SERVER_ERROR',
                'message' => 'Une erreur est survenue lors de la réinitialisation de votre mot de passe.',
            ], 400);
        }
    }
}

