<?php

namespace App\Controller\Api;

use App\Dto\CreateUtilisateurDto;
use App\Entity\Utilisateur;
use App\Enum\UtilisateurRole;
use App\Repository\UtilisateurRepository;
use App\Service\EmailVerifier;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/utilisateurs')]
class UtilisateurController extends AbstractApiController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $hasher,
        private Security $security,
        private EmailVerifier $emailVerifier,
        #[Autowire(service: 'limiter.registration_attempts')]
        private RateLimiterFactory $registrationLimiter,
    ) {
    }

    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function list(UtilisateurRepository $repo): JsonResponse
    {
        return $this->jsonRead($repo->findAll(), 'utilisateur:read', $this->serializer);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Utilisateur $utilisateur): JsonResponse
    {
        if ($this->security->getUser() !== $utilisateur && !$this->security->isGranted('ROLE_MODERATEUR')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        return $this->jsonRead($utilisateur, 'utilisateur:read', $this->serializer);
    }

    /**
     * Inscription publique : crée exclusivement un compte ROLE_CLIENT.
     * Tout paramètre "role" présent dans le payload est strictement ignoré.
     */
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $limiter = $this->registrationLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return new JsonResponse(['error' => 'Trop de tentatives d\'inscription, réessayez plus tard.'], 429);
        }

        $data = $this->data($request);
        // Sécurité critique : suppression de tout champ "role" transmis par le client
        unset($data['role']);

        $dto = CreateUtilisateurDto::fromRequest($data);
        if ($r = $this->validateDto($dto, $this->validator)) {
            return $r;
        }

        $u = (new Utilisateur())
            ->setNom($dto->nom)
            ->setPrenom($dto->prenom)
            ->setEmail($dto->email)
            ->setTelephone($dto->telephone)
            ->setAdresse($dto->adresse)
            ->setRoleEnums([UtilisateurRole::CLIENT])
            ->setDateCreation(new \DateTimeImmutable())
            ->setIsVerified(false);
        $u->setPassword($this->hasher->hashPassword($u, $dto->password));

        try {
            $this->em->persist($u);
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            return new JsonResponse([
                'error' => 'Cette adresse e-mail est déjà associée à un compte.',
                'errors' => ['email' => 'Cette adresse e-mail est déjà associée à un compte.'],
            ], 409);
        }

        // Envoi de l'e-mail de confirmation avec jeton sécurisé
        $this->emailVerifier->sendVerificationEmail($u);

        return new JsonResponse([
            'message' => 'Votre compte a été créé avec succès. Un e-mail de confirmation vient de vous être envoyé pour activer votre compte.',
            'email' => $u->getEmail(),
            'id' => $u->getId(),
        ], 201);
    }

    /**
     * Création interne par un administrateur authentifié.
     * Permet la création directe d'un compte avec le rôle de son choix (MODERATEUR, ADMIN, CLIENT).
     */
    #[Route('/admin', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createByAdmin(Request $request): JsonResponse
    {
        $data = $this->data($request);
        $dto = CreateUtilisateurDto::fromRequest($data);
        if ($r = $this->validateDto($dto, $this->validator)) {
            return $r;
        }

        $roleValue = $dto->role ? strtoupper(trim($dto->role)) : UtilisateurRole::MODERATEUR->value;
        $role = UtilisateurRole::tryFrom($roleValue);
        if (!$role) {
            return new JsonResponse(['error' => 'Rôle invalide (valeurs acceptées : CLIENT, MODERATEUR, ADMIN).'], 400);
        }

        $u = (new Utilisateur())
            ->setNom($dto->nom)
            ->setPrenom($dto->prenom)
            ->setEmail($dto->email)
            ->setTelephone($dto->telephone)
            ->setAdresse($dto->adresse)
            ->setRoleEnums([$role])
            ->setDateCreation(new \DateTimeImmutable())
            ->setIsVerified(true)
            ->setEmailVerifiedAt(new \DateTimeImmutable());
        $u->setPassword($this->hasher->hashPassword($u, $dto->password));

        try {
            $this->em->persist($u);
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            return new JsonResponse([
                'error' => 'Cette adresse e-mail est déjà associée à un compte.',
                'errors' => ['email' => 'Cette adresse e-mail est déjà associée à un compte.'],
            ], 409);
        }

        return $this->jsonRead($u, 'utilisateur:read', $this->serializer, 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(Utilisateur $utilisateur, Request $request): JsonResponse
    {
        if ($this->security->getUser() !== $utilisateur && !$this->security->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $data = $this->data($request);
        // Seul un administrateur peut modifier le rôle d'un compte
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $data['role'] = ($utilisateur->getRoleEnums()[0] ?? UtilisateurRole::CLIENT)->value;
        }

        $dto = CreateUtilisateurDto::fromRequest($data);
        if ($r = $this->validateDto($dto, $this->validator)) {
            return $r;
        }

        $role = UtilisateurRole::tryFrom($dto->role ?? '');
        if (!$role) {
            return new JsonResponse(['error' => 'Rôle invalide'], 400);
        }

        $utilisateur
            ->setNom($dto->nom)
            ->setPrenom($dto->prenom)
            ->setEmail($dto->email)
            ->setTelephone($dto->telephone)
            ->setAdresse($dto->adresse)
            ->setRoleEnums([$role])
            ->setPassword($this->hasher->hashPassword($utilisateur, $dto->password));

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            return new JsonResponse([
                'error' => 'Cette adresse e-mail est déjà associée à un compte.',
                'errors' => ['email' => 'Cette adresse e-mail est déjà associée à un compte.'],
            ], 409);
        }

        return $this->jsonRead($utilisateur, 'utilisateur:read', $this->serializer);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Utilisateur $utilisateur): JsonResponse
    {
        $this->em->remove($utilisateur);
        $this->em->flush();

        return new JsonResponse(null, 204);
    }
}
