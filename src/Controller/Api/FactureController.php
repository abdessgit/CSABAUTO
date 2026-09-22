<?php

namespace App\Controller\Api;

use App\Entity\Facture;
use App\Enum\FactureStatut;
use App\Repository\FactureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/factures')]
class FactureController extends AbstractApiController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private Security $security,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(FactureRepository $repo): JsonResponse
    {
        $items = $repo->findAll();
        if (!$this->security->isGranted('ROLE_MODERATEUR')) {
            $user = $this->security->getUser();
            $items = array_values(array_filter(
                $items,
                fn(Facture $f) => $f->getIntervention()->getVehicule()->getProprietaire() === $user
            ));
        }
        return $this->jsonRead($items, 'facture:read', $this->serializer);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Facture $facture): JsonResponse
    {
        if ($facture->getIntervention()->getVehicule()->getProprietaire() !== $this->security->getUser()
            && !$this->security->isGranted('ROLE_MODERATEUR')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }
        return $this->jsonRead($facture, 'facture:read', $this->serializer);
    }

    #[Route('/{id}/statut', methods: ['PATCH'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function statut(Facture $facture, Request $request): JsonResponse
    {
        $statut = FactureStatut::tryFrom($this->data($request)['statut'] ?? '');
        if (!$statut) {
            return new JsonResponse(['error' => 'Statut invalide'], 400);
        }
        $facture->setStatut($statut);
        $this->em->flush();
        return $this->jsonRead($facture, 'facture:read', $this->serializer);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Facture $facture): JsonResponse
    {
        $this->em->remove($facture);
        $this->em->flush();
        return new JsonResponse(null, 204);
    }
}