<?php

namespace App\Command;

use App\Enum\RendezVousStatut;
use App\Repository\RendezVousRepository;
use App\Service\InterventionManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-rendezvous-interventions',
    description: 'Crée les interventions manquantes pour tous les rendez-vous confirmés'
)]
class SyncRendezVousInterventionsCommand extends Command
{
    public function __construct(
        private RendezVousRepository $rendezVousRepo,
        private InterventionManager $interventionManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rdvs = $this->rendezVousRepo->findBy(['statut' => RendezVousStatut::CONFIRME]);
        $count = 0;

        foreach ($rdvs as $rdv) {
            if ($rdv->getIntervention() === null) {
                $this->interventionManager->createFromRendezVous($rdv);
                $count++;
            }
        }

        $io->success(sprintf('%d intervention(s) créée(s) pour les rendez-vous confirmés.', $count));

        return Command::SUCCESS;
    }
}
