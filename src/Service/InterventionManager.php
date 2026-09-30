<?php

namespace App\Service;

use App\Entity\Intervention;
use App\Entity\RendezVous;
use App\Enum\InterventionStatut;
use Doctrine\ORM\EntityManagerInterface;

class InterventionManager
{
    public function __construct(private EntityManagerInterface $em) {}

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
}
