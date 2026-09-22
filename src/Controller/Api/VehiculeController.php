<?php

namespace App\Controller\Api;

use App\Dto\CreateVehiculeDto;
use App\Entity\Vehicule;
use App\Repository\UtilisateurRepository;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/vehicules')]
class VehiculeController extends AbstractApiController
{
    public function __construct(private EntityManagerInterface $em, private SerializerInterface $serializer, private ValidatorInterface $validator, private UtilisateurRepository $users, private Security $security) {}

    #[Route('', methods: ['GET'])]
    public function list(VehiculeRepository $repo): JsonResponse
    {
        $items = $this->security->isGranted('ROLE_MODERATEUR') ? $repo->findAll() : $repo->findBy(['proprietaire' => $this->security->getUser()]);
        return $this->jsonRead($items, 'vehicule:read', $this->serializer);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Vehicule $vehicule): JsonResponse
    {
        if ($vehicule->getProprietaire() !== $this->security->getUser() && !$this->security->isGranted('ROLE_MODERATEUR')) return new JsonResponse(['error' => 'Accès refusé'], 403);
        return $this->jsonRead($vehicule, 'vehicule:read', $this->serializer);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = CreateVehiculeDto::fromRequest($this->data($request));
        if ($r = $this->validateDto($dto, $this->validator)) return $r;
        if (!$this->security->isGranted('ROLE_MODERATEUR') && $dto->proprietaireId !== $this->security->getUser()?->getId()) return new JsonResponse(['error' => 'Accès refusé'], 403);
        $p = $this->users->find($dto->proprietaireId);
        if (!$p) return new JsonResponse(['error' => 'Propriétaire introuvable'], 404);
        $v = (new Vehicule())->setMarque($dto->marque)->setModele($dto->modele)->setAnnee($dto->annee)->setImmatriculation($dto->immatriculation)->setVin($dto->vin)->setKilometrage($dto->kilometrage)->setCouleur($dto->couleur)->setDateAjout(new \DateTimeImmutable())->setProprietaire($p);
        $this->em->persist($v); $this->em->flush();
        return $this->jsonRead($v, 'vehicule:read', $this->serializer, 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(Vehicule $vehicule, Request $request): JsonResponse
    {
        if ($vehicule->getProprietaire() !== $this->security->getUser() && !$this->security->isGranted('ROLE_MODERATEUR')) return new JsonResponse(['error' => 'Accès refusé'], 403);
        $dto = CreateVehiculeDto::fromRequest($this->data($request));
        if ($r = $this->validateDto($dto, $this->validator)) return $r;
        $p = $this->users->find($dto->proprietaireId);
        if (!$p) return new JsonResponse(['error' => 'Propriétaire introuvable'], 404);
        $vehicule->setMarque($dto->marque)->setModele($dto->modele)->setAnnee($dto->annee)->setImmatriculation($dto->immatriculation)->setVin($dto->vin)->setKilometrage($dto->kilometrage)->setCouleur($dto->couleur)->setProprietaire($p);
        $this->em->flush();
        return $this->jsonRead($vehicule, 'vehicule:read', $this->serializer);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function delete(Vehicule $vehicule): JsonResponse { $this->em->remove($vehicule); $this->em->flush(); return new JsonResponse(null, 204); }
}
