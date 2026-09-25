<?php

namespace App\Controller\Api;

use App\Enum\AnnonceStatut;
use App\Enum\FactureStatut;
use App\Enum\UtilisateurRole;
use App\Repository\AnnonceRepository;
use App\Repository\FactureRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\VehiculeRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/dashboard')]
class DashboardController extends AbstractApiController
{
    #[Route('/admin', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function admin(
        UtilisateurRepository $userRepo,
        VehiculeRepository $vehiculeRepo,
        AnnonceRepository $annonceRepo,
        FactureRepository $factureRepo
    ): JsonResponse {
        $users = $userRepo->findAll();
        $repartition = ['CLIENT' => 0, 'MODERATEUR' => 0, 'ADMIN' => 0];
        foreach ($users as $user) {
            foreach ($user->getRoleEnums() as $roleEnum) {
                if (isset($repartition[$roleEnum->value])) {
                    $repartition[$roleEnum->value]++;
                }
            }
        }

        $totalVehicules = $vehiculeRepo->count([]);
        $totalAnnoncesEnVente = $annonceRepo->count(['statut' => AnnonceStatut::EN_VENTE]);
        $facturesEnAttente = $factureRepo->count(['statut' => FactureStatut::EN_ATTENTE]);

        $startOfMonth = new \DateTimeImmutable('first day of this month 00:00:00');
        $endOfMonth = new \DateTimeImmutable('last day of this month 23:59:59');

        $facturesPayees = $factureRepo->findBy(['statut' => FactureStatut::PAYEE]);
        $caMois = 0.0;
        foreach ($facturesPayees as $f) {
            if ($f->getDateEmission() >= $startOfMonth && $f->getDateEmission() <= $endOfMonth) {
                $caMois += (float) $f->getMontantTotal();
            }
        }

        return new JsonResponse([
            'repartitionUtilisateurs' => $repartition,
            'totalVehicules' => $totalVehicules,
            'totalAnnoncesEnVente' => $totalAnnoncesEnVente,
            'chiffreAffaireMoisEnCours' => round($caMois, 2),
            'facturesEnAttente' => $facturesEnAttente,
        ]);
    }
}
