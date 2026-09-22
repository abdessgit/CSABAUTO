<?php

namespace App\Controller\Api;

use App\Dto\CreateInterventionDto;
use App\Entity\Facture;
use App\Entity\Intervention;
use App\Entity\InterventionService;
use App\Entity\LigneFacture;
use App\Enum\FactureStatut;
use App\Enum\InterventionStatut;
use App\Repository\InterventionRepository;
use App\Repository\RendezVousRepository;
use App\Repository\ServiceRepository;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/interventions')]
class InterventionController extends AbstractApiController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private VehiculeRepository $vehicules,
        private RendezVousRepository $rendezVousRepo,
        private ServiceRepository $services,
        private Security $security,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(InterventionRepository $repo): JsonResponse
    {
        $items = $repo->findAll();
        if (!$this->security->isGranted('ROLE_MODERATEUR')) {
            $user = $this->security->getUser();
            $items = array_values(array_filter(
                $items,
                fn(Intervention $i) => $i->getVehicule()->getProprietaire() === $user
            ));
        }
        return $this->jsonRead($items, 'intervention:read', $this->serializer);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Intervention $intervention): JsonResponse
    {
        if ($intervention->getVehicule()->getProprietaire() !== $this->security->getUser()
            && !$this->security->isGranted('ROLE_MODERATEUR')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }
        return $this->jsonRead($intervention, 'intervention:read', $this->serializer);
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function create(Request $request): JsonResponse
    {
        $data = $this->data($request);
        $dto = CreateInterventionDto::fromRequest($data);
        if ($r = $this->validateDto($dto, $this->validator)) {
            return $r;
        }

        $vehicule = $this->vehicules->find($dto->vehiculeId);
        if (!$vehicule) {
            return new JsonResponse(['error' => 'Véhicule introuvable'], 404);
        }

        $rendezVous = $dto->rendezVousId ? $this->rendezVousRepo->find($dto->rendezVousId) : null;
        if ($dto->rendezVousId && !$rendezVous) {
            return new JsonResponse(['error' => 'Rendez-vous introuvable'], 404);
        }

        $intervention = (new Intervention())
            ->setDateIntervention(new \DateTimeImmutable($dto->dateIntervention))
            ->setDescription($dto->description)
            ->setKilometrageReleve($dto->kilometrageReleve)
            ->setVehicule($vehicule)
            ->setRendezVous($rendezVous)
            ->setStatut(InterventionStatut::EN_COURS)
            ->setCoutTotal('0.00');

        $coutTotal = 0.0;
        foreach ($data['services'] ?? [] as $ligne) {
            $service = $this->services->find($ligne['serviceId'] ?? null);
            if (!$service) {
                return new JsonResponse(['error' => 'Service introuvable: ' . ($ligne['serviceId'] ?? '?')], 404);
            }
            $quantite = (int) ($ligne['quantite'] ?? 1);
            $prixApplique = $service->getPrixStandard();

            $is = (new InterventionService())
                ->setIntervention($intervention)
                ->setService($service)
                ->setQuantite($quantite)
                ->setPrixApplique($prixApplique);

            $this->em->persist($is);
            $coutTotal += $quantite * (float) $prixApplique;
        }

        $intervention->setCoutTotal((string) $coutTotal);

        $this->em->persist($intervention);
        $this->em->flush();

        return $this->jsonRead($intervention, 'intervention:read', $this->serializer, 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function update(Intervention $intervention, Request $request): JsonResponse
    {
        $dto = CreateInterventionDto::fromRequest($this->data($request));
        if ($r = $this->validateDto($dto, $this->validator)) {
            return $r;
        }

        $vehicule = $this->vehicules->find($dto->vehiculeId);
        if (!$vehicule) {
            return new JsonResponse(['error' => 'Véhicule introuvable'], 404);
        }

        $intervention
            ->setDateIntervention(new \DateTimeImmutable($dto->dateIntervention))
            ->setDescription($dto->description)
            ->setKilometrageReleve($dto->kilometrageReleve)
            ->setVehicule($vehicule);

        $this->em->flush();

        return $this->jsonRead($intervention, 'intervention:read', $this->serializer);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function delete(Intervention $intervention): JsonResponse
    {
        $this->em->remove($intervention);
        $this->em->flush();
        return new JsonResponse(null, 204);
    }

    #[Route('/{id}/facture', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function genererFacture(Intervention $intervention): JsonResponse
    {
        if ($intervention->getFacture() !== null) {
            return new JsonResponse(['error' => 'Une facture existe déjà pour cette intervention'], 422);
        }

        $facture = (new Facture())
            ->setNumeroFacture('FACT-' . date('Ymd') . '-' . $intervention->getId())
            ->setDateEmission(new \DateTimeImmutable())
            ->setStatut(FactureStatut::EN_ATTENTE)
            ->setIntervention($intervention);

        $montantTotal = 0.0;
        foreach ($intervention->getInterventionServices() as $is) {
            $sousTotal = $is->getQuantite() * (float) $is->getPrixApplique();
            $ligne = (new LigneFacture())
                ->setFacture($facture)
                ->setDescription($is->getService()->getNom())
                ->setQuantite($is->getQuantite())
                ->setPrixUnitaire($is->getPrixApplique())
                ->setSousTotal((string) $sousTotal);

            $this->em->persist($ligne);
            $montantTotal += $sousTotal;
        }

        $facture->setMontantTotal((string) $montantTotal);

        $this->em->persist($facture);
        $this->em->flush();

        return $this->jsonRead($facture, 'facture:read', $this->serializer, 201);
    }
}