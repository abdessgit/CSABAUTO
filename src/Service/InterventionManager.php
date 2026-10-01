<?php

namespace App\Service;

use App\Entity\Facture;
use App\Entity\Intervention;
use App\Entity\LigneFacture;
use App\Entity\RendezVous;
use App\Enum\FactureStatut;
use App\Enum\InterventionStatut;
use Doctrine\ORM\EntityManagerInterface;

class InterventionManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private FactureNumberGenerator $factureNumberGenerator,
    ) {}

    public function createFromRendezVous(RendezVous $rendezVous): ?Intervention
    {
        if ($rendezVous->getIntervention() !== null) {
            return $rendezVous->getIntervention();
        }

        $vehicule = $rendezVous->getVehicule();
        if (!$vehicule) {
            return null;
        }

        $description = $rendezVous->getMotif()
            ? 'Rendez-vous : ' . $rendezVous->getMotif()
            : 'Intervention atelier suite au rendez-vous du ' . ($rendezVous->getDateHeure()?->format('d/m/Y H:i') ?? 'N/A');

        $intervention = (new Intervention())
            ->setDateIntervention($rendezVous->getDateHeure() ?? new \DateTimeImmutable())
            ->setDescription($description)
            ->setKilometrageReleve($vehicule->getKilometrage() ?? 0)
            ->setVehicule($vehicule)
            ->setRendezVous($rendezVous)
            ->setStatut(InterventionStatut::EN_COURS)
            ->setCoutTotal('0.00');

        $this->em->persist($intervention);
        $this->em->flush();

        return $intervention;
    }

    public function cloturerAvecLignes(Intervention $intervention, array $lignes): Intervention
    {
        if ($intervention->getFacture() !== null) {
            throw new \LogicException(sprintf(
                'L\'intervention #%d a déjà été clôturée avec la facture %s. Une facture émise est immuable.',
                $intervention->getId(),
                $intervention->getFacture()->getNumeroFacture()
            ));
        }

        $montantHT = 0.0;
        $montantTVA = 0.0;
        $tauxTvaStandard = 20.00;

        foreach ($lignes as $l) {
            $lineHT = (float) ($l['montant'] ?? 0);
            $lineTva = round($lineHT * ($tauxTvaStandard / 100), 2);
            $montantHT += $lineHT;
            $montantTVA += $lineTva;
        }

        $montantTTC = round($montantHT + $montantTVA, 2);

        $montantHTFormatted = number_format($montantHT, 2, '.', '');
        $montantTVAFormatted = number_format($montantTVA, 2, '.', '');
        $montantTTCFormatted = number_format($montantTTC, 2, '.', '');

        $intervention->setCoutTotal($montantTTCFormatted);
        $intervention->setStatut(InterventionStatut::TERMINEE);

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
            $lineHT = (float) ($l['montant'] ?? 0);
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

        $this->em->flush();

        return $intervention;
    }
}
