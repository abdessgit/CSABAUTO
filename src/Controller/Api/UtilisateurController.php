<?php

namespace App\Controller\Api;

use App\Dto\CreateUtilisateurDto;
use App\Entity\Utilisateur;
use App\Enum\UtilisateurRole;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = CreateUtilisateurDto::fromRequest($this->data($request));
        if ($r = $this->validateDto($dto, $this->validator)) {
            return $r;
        }

        $role = UtilisateurRole::tryFrom($dto->role);
        if (!$role) {
            return new JsonResponse(['error' => 'Rôle invalide'], 400);
        }

        $u = (new Utilisateur())
            ->setNom($dto->nom)
            ->setPrenom($dto->prenom)
            ->setEmail($dto->email)
            ->setTelephone($dto->telephone)
            ->setAdresse($dto->adresse)
            ->setRoleEnums([$role])
            ->setDateCreation(new \DateTimeImmutable());
        $u->setPassword($this->hasher->hashPassword($u, $dto->password));

        $this->em->persist($u);
        $this->em->flush();

        return $this->jsonRead($u, 'utilisateur:read', $this->serializer, 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(Utilisateur $utilisateur, Request $request): JsonResponse
    {
        if ($this->security->getUser() !== $utilisateur && !$this->security->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $data = $this->data($request);
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $data['role'] = ($utilisateur->getRoleEnums()[0] ?? UtilisateurRole::CLIENT)->value;
        }

        $dto = CreateUtilisateurDto::fromRequest($data);
        if ($r = $this->validateDto($dto, $this->validator)) {
            return $r;
        }

        $role = UtilisateurRole::tryFrom($dto->role);
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
        $this->em->flush();

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
