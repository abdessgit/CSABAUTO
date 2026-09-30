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
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private UtilisateurRepository $users,
        private VehiculeRepository $vehicules,
        private Security $security
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = $this->security->isGranted('ROLE_MODERATEUR')
            ? $this->vehicules->findAll()
            : $this->vehicules->findBy(['proprietaire' => $this->security->getUser()]);
        return $this->jsonRead($items, 'vehicule:read', $this->serializer);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Vehicule $vehicule): JsonResponse
    {
        if ($vehicule->getProprietaire() !== $this->security->getUser() && !$this->security->isGranted('ROLE_MODERATEUR')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }
        return $this->jsonRead($vehicule, 'vehicule:read', $this->serializer);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof \App\Entity\Utilisateur) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $data = $this->data($request);

        // Si l'utilisateur n'est pas modérateur, ou si aucun propriétaire n'a été spécifié,
        // on associe automatiquement le véhicule au compte de l'utilisateur connecté.
        if (!$this->security->isGranted('ROLE_MODERATEUR') || empty($data['proprietaireId'])) {
            $data['proprietaireId'] = $user->getId();
        }

        $dto = CreateVehiculeDto::fromRequest($data);
        if ($r = $this->validateDto($dto, $this->validator)) {
            return $r;
        }

        if ($dto->immatriculation !== null) {
            $existing = $this->vehicules->findOneByImmatriculationInsensitive($dto->immatriculation);
            if ($existing) {
                return new JsonResponse([
                    'error' => 'Un véhicule avec cette immatriculation existe déjà.',
                    'errors' => ['immatriculation' => 'Un véhicule avec cette immatriculation existe déjà.'],
                ], 409);
            }
        }

        $p = $this->users->find($dto->proprietaireId);
        if (!$p) {
            return new JsonResponse([
                'error' => 'Propriétaire introuvable',
                'errors' => ['proprietaireId' => 'Le propriétaire sélectionné n\'existe pas.'],
            ], 404);
        }

        $v = (new Vehicule())
            ->setMarque($dto->marque)
            ->setModele($dto->modele)
            ->setAnnee($dto->annee)
            ->setImmatriculation($dto->immatriculation)
            ->setVin($dto->vin)
            ->setKilometrage($dto->kilometrage)
            ->setCouleur($dto->couleur)
            ->setDateAjout(new \DateTimeImmutable())
            ->setProprietaire($p);

        try {
            $this->em->persist($v);
            $this->em->flush();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            return new JsonResponse([
                'error' => 'Un véhicule avec cette immatriculation existe déjà.',
                'errors' => ['immatriculation' => 'Un véhicule avec cette immatriculation existe déjà.'],
            ], 409);
        } catch (\Doctrine\DBAL\Exception\IntegrityConstraintViolationException $e) {
            return new JsonResponse([
                'error' => 'Une contrainte d\'intégrité a été violée lors de l\'enregistrement.',
            ], 409);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Une erreur interne est survenue lors de l\'enregistrement du véhicule.',
            ], 500);
        }

        return $this->jsonRead($v, 'vehicule:read', $this->serializer, 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(Vehicule $vehicule, Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof \App\Entity\Utilisateur) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        if ($vehicule->getProprietaire() !== $user && !$this->security->isGranted('ROLE_MODERATEUR')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $data = $this->data($request);

        // Un client ne peut pas réassigner son véhicule à quelqu'un d'autre
        if (!$this->security->isGranted('ROLE_MODERATEUR') || empty($data['proprietaireId'])) {
            $data['proprietaireId'] = $vehicule->getProprietaire()->getId();
        }

        $dto = CreateVehiculeDto::fromRequest($data);
        if ($r = $this->validateDto($dto, $this->validator)) {
            return $r;
        }

        if ($dto->immatriculation !== null) {
            $existing = $this->vehicules->findOneByImmatriculationInsensitive($dto->immatriculation);
            if ($existing && $existing->getId() !== $vehicule->getId()) {
                return new JsonResponse([
                    'error' => 'Un véhicule avec cette immatriculation existe déjà.',
                    'errors' => ['immatriculation' => 'Un véhicule avec cette immatriculation existe déjà.'],
                ], 409);
            }
        }

        $p = $this->users->find($dto->proprietaireId);
        if (!$p) {
            return new JsonResponse([
                'error' => 'Propriétaire introuvable',
                'errors' => ['proprietaireId' => 'Le propriétaire sélectionné n\'existe pas.'],
            ], 404);
        }

        $vehicule
            ->setMarque($dto->marque)
            ->setModele($dto->modele)
            ->setAnnee($dto->annee)
            ->setImmatriculation($dto->immatriculation)
            ->setVin($dto->vin)
            ->setKilometrage($dto->kilometrage)
            ->setCouleur($dto->couleur)
            ->setProprietaire($p);

        try {
            $this->em->flush();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            return new JsonResponse([
                'error' => 'Un véhicule avec cette immatriculation existe déjà.',
                'errors' => ['immatriculation' => 'Un véhicule avec cette immatriculation existe déjà.'],
            ], 409);
        } catch (\Doctrine\DBAL\Exception\IntegrityConstraintViolationException $e) {
            return new JsonResponse([
                'error' => 'Une contrainte d\'intégrité a été violée lors de l\'enregistrement.',
            ], 409);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Une erreur interne est survenue lors de la mise à jour du véhicule.',
            ], 500);
        }

        return $this->jsonRead($vehicule, 'vehicule:read', $this->serializer);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function delete(Vehicule $vehicule): JsonResponse
    {
        try {
            $this->em->remove($vehicule);
            $this->em->flush();
            return new JsonResponse(null, 204);
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            return new JsonResponse([
                'error' => 'Impossible de supprimer ce véhicule car il est lié à des rendez-vous ou des interventions.',
            ], 409);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la suppression du véhicule.',
            ], 500);
        }
    }
}
