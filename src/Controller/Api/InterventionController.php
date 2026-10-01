<?php

namespace App\Controller\Api;

use App\Dto\CreateInterventionDto;
use App\Dto\UpdateInterventionStatutDto;
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
use App\Service\FactureNumberGenerator;
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
        private FactureNumberGenerator $factureNumberGenerator,
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
        if ($rendezVous && $rendezVous->getIntervention() !== null) {
            return new JsonResponse(['error' => 'Une intervention existe déjà pour ce rendez-vous'], 422);
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

    #[Route('/{id}/statut', methods: ['PATCH'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function statut(Intervention $intervention, Request $request): JsonResponse
    {
        $dto = UpdateInterventionStatutDto::fromRequest($this->data($request));
        if ($r = $this->validateDto($dto, $this->validator)) {
            return $r;
        }

        $s = InterventionStatut::from($dto->statut);
        $intervention->setStatut($s);

        if ($s === InterventionStatut::TERMINEE) {
            // IMMUABILITÉ : Si l'intervention possède déjà une facture, refus formel de toute modification
            if ($intervention->getFacture() !== null) {
                return new JsonResponse([
                    'error' => sprintf(
                        'L\'intervention #%d a déjà été validée et facturée (Facture N° %s). Une facture émise est un document légal immuable et ne peut pas être modifiée.',
                        $intervention->getId(),
                        $intervention->getFacture()->getNumeroFacture()
                    )
                ], 422);
            }

            $defaultDesc = $intervention->getDescription() ?: 'Intervention mécanique générale';
            $lignes = $dto->getNormalizedLignes($defaultDesc);

            $montantHT = 0.0;
            $montantTVA = 0.0;
            $tauxTvaStandard = 20.00;

            foreach ($lignes as $l) {
                $lineHT = (float) $l['montant'];
                $lineTva = round($lineHT * ($tauxTvaStandard / 100), 2);
                $montantHT += $lineHT;
                $montantTVA += $lineTva;
            }

            $montantTTC = round($montantHT + $montantTVA, 2);

            $montantHTFormatted = number_format($montantHT, 2, '.', '');
            $montantTVAFormatted = number_format($montantTVA, 2, '.', '');
            $montantTTCFormatted = number_format($montantTTC, 2, '.', '');

            // L'intervention stocke le montant TTC réel pour le client
            $intervention->setCoutTotal($montantTTCFormatted);

            // Génération de la Facture avec numéro séquentiel immuable FAC-YYYY-NNNN
            $dateEmission = new \DateTimeImmutable();
            $datePrestation = $intervention->getDateIntervention() ?? $dateEmission;
            $dateEcheance = $dateEmission->modify('+30 days');
            $numeroFacture = $this->factureNumberGenerator->generateNextNumber((int) $dateEmission->format('Y'));

            $facture = (new Facture())
                ->setNumeroFacture($numeroFacture)
                ->setDateEmission($dateEmission)
                ->setDatePrestation($datePrestation)
                ->setDateEcheance($dateEcheance)
                ->setTauxTva(number_format($tauxTvaStandard, 2, '.', ''))
                ->setMontantHT($montantHTFormatted)
                ->setMontantTVA($montantTVAFormatted)
                ->setMontantTTC($montantTTCFormatted)
                ->setMontantTotal($montantTTCFormatted)
                ->setStatut(FactureStatut::EN_ATTENTE)
                ->setIntervention($intervention);

            $this->em->persist($facture);

            $ordre = 1;
            foreach ($lignes as $l) {
                $lineHT = (float) $l['montant'];
                $ligne = (new LigneFacture())
                    ->setFacture($facture)
                    ->setDescription($l['designation'])
                    ->setQuantite(1)
                    ->setPrixUnitaire(number_format($lineHT, 2, '.', ''))
                    ->setSousTotal(number_format($lineHT, 2, '.', ''))
                    ->setTauxTva(number_format($tauxTvaStandard, 2, '.', ''))
                    ->setOrdre($ordre++);
                $this->em->persist($ligne);
                $facture->addLignesFacture($ligne);
            }
        } elseif ($dto->montant !== null && is_numeric($dto->montant) && (float) $dto->montant >= 0) {
            $montantFormatted = number_format((float) $dto->montant, 2, '.', '');
            $intervention->setCoutTotal($montantFormatted);
        }

        $this->em->flush();
        return $this->jsonRead($intervention, 'intervention:read', $this->serializer);
    }

    #[Route('/{id}/facture', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function genererFacture(Intervention $intervention): JsonResponse
    {
        if ($intervention->getFacture() !== null) {
            return new JsonResponse([
                'error' => sprintf(
                    'Une facture existe déjà pour cette intervention (N° %s)',
                    $intervention->getFacture()->getNumeroFacture()
                )
            ], 422);
        }

        $dateEmission = new \DateTimeImmutable();
        $datePrestation = $intervention->getDateIntervention() ?? $dateEmission;
        $dateEcheance = $dateEmission->modify('+30 days');
        $numeroFacture = $this->factureNumberGenerator->generateNextNumber((int) $dateEmission->format('Y'));

        $facture = (new Facture())
            ->setNumeroFacture($numeroFacture)
            ->setDateEmission($dateEmission)
            ->setDatePrestation($datePrestation)
            ->setDateEcheance($dateEcheance)
            ->setTauxTva('20.00')
            ->setStatut(FactureStatut::EN_ATTENTE)
            ->setIntervention($intervention);

        $montantHT = 0.0;
        $montantTVA = 0.0;
        $tauxTvaStandard = 20.00;

        if ($intervention->getInterventionServices()->count() > 0) {
            $ordre = 1;
            foreach ($intervention->getInterventionServices() as $is) {
                $sousTotalHT = $is->getQuantite() * (float) $is->getPrixApplique();
                $lineTva = round($sousTotalHT * ($tauxTvaStandard / 100), 2);
                $montantHT += $sousTotalHT;
                $montantTVA += $lineTva;

                $ligne = (new LigneFacture())
                    ->setFacture($facture)
                    ->setDescription($is->getService()->getNom())
                    ->setQuantite($is->getQuantite())
                    ->setPrixUnitaire($is->getPrixApplique())
                    ->setSousTotal((string) $sousTotalHT)
                    ->setTauxTva('20.00')
                    ->setOrdre($ordre++);

                $this->em->persist($ligne);
            }
        } else {
            $montantValHT = (float) ($intervention->getCoutTotal() ?: '0.00');
            // Si coutTotal était déjà en TTC ou HT, on le prend comme HT de départ
            $lineTva = round($montantValHT * ($tauxTvaStandard / 100), 2);
            $montantHT = $montantValHT;
            $montantTVA = $lineTva;

            $ligne = (new LigneFacture())
                ->setFacture($facture)
                ->setDescription($intervention->getDescription() ?: 'Intervention mécanique générale')
                ->setQuantite(1)
                ->setPrixUnitaire(number_format($montantValHT, 2, '.', ''))
                ->setSousTotal(number_format($montantValHT, 2, '.', ''))
                ->setTauxTva('20.00')
                ->setOrdre(1);

            $this->em->persist($ligne);
        }

        $montantTTC = round($montantHT + $montantTVA, 2);

        $facture->setMontantHT(number_format($montantHT, 2, '.', ''));
        $facture->setMontantTVA(number_format($montantTVA, 2, '.', ''));
        $facture->setMontantTTC(number_format($montantTTC, 2, '.', ''));
        $facture->setMontantTotal(number_format($montantTTC, 2, '.', ''));

        $intervention->setCoutTotal(number_format($montantTTC, 2, '.', ''));

        $this->em->persist($facture);
        $this->em->flush();

        return $this->jsonRead($facture, 'facture:read', $this->serializer, 201);
    }
}