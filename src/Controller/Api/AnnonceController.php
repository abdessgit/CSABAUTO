<?php

namespace App\Controller\Api;

use App\Dto\CreateAnnonceDto;
use App\Entity\Annonce;
use App\Enum\AnnonceStatut;
use App\Repository\AnnonceRepository;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/annonces')]
class AnnonceController extends AbstractApiController
{
    public function __construct(private EntityManagerInterface $em, private SerializerInterface $serializer, private ValidatorInterface $validator, private VehiculeRepository $vehicules, private Security $security) {}
    #[Route('', methods: ['GET'])] public function list(AnnonceRepository $repo, Request $request): JsonResponse { $statut = $request->query->get('statut'); $items = $statut ? $repo->findBy(['statut' => AnnonceStatut::tryFrom($statut) ?? $statut]) : $repo->findAll(); return $this->jsonRead($items, 'annonce:read', $this->serializer); }
    #[Route('/{id}', methods: ['GET'])] public function show(Annonce $annonce): JsonResponse { return $this->jsonRead($annonce, 'annonce:read', $this->serializer); }
    #[Route('', methods: ['POST'])] #[IsGranted('ROLE_MODERATEUR')] public function create(Request $request): JsonResponse { $dto = CreateAnnonceDto::fromRequest($this->data($request)); if ($r = $this->validateDto($dto, $this->validator)) return $r; $v = $this->vehicules->find($dto->vehiculeId); if (!$v) return new JsonResponse(['error' => 'Véhicule introuvable'], 404); $a = (new Annonce())->setTitre($dto->titre)->setDescription($dto->description)->setPrix($dto->prix)->setVehicule($v)->setStatut(AnnonceStatut::EN_VENTE)->setDatePublication(new \DateTimeImmutable()); $this->em->persist($a); $this->em->flush(); return $this->jsonRead($a, 'annonce:read', $this->serializer, 201); }
    #[Route('/{id}', methods: ['PUT'])] #[IsGranted('ROLE_MODERATEUR')] public function update(Annonce $annonce, Request $request): JsonResponse { $dto = CreateAnnonceDto::fromRequest($this->data($request)); if ($r = $this->validateDto($dto, $this->validator)) return $r; $v = $this->vehicules->find($dto->vehiculeId); if (!$v) return new JsonResponse(['error' => 'Véhicule introuvable'], 404); $annonce->setTitre($dto->titre)->setDescription($dto->description)->setPrix($dto->prix)->setVehicule($v); $this->em->flush(); return $this->jsonRead($annonce, 'annonce:read', $this->serializer); }
    #[Route('/{id}', methods: ['DELETE'])] #[IsGranted('ROLE_MODERATEUR')] public function delete(Annonce $annonce): JsonResponse { $this->em->remove($annonce); $this->em->flush(); return new JsonResponse(null, 204); }
}
