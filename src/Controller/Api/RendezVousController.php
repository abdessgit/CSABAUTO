<?php

namespace App\Controller\Api;

use App\Dto\CreateRendezVousDto;
use App\Entity\RendezVous;
use App\Enum\RendezVousStatut;
use App\Repository\RendezVousRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\InterventionManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/rendez-vous')]
class RendezVousController extends AbstractApiController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private UtilisateurRepository $users,
        private VehiculeRepository $vehicules,
        private Security $security,
        private InterventionManager $interventionManager,
        #[Autowire(service: 'limiter.rendez_vous_attempts')]
        private RateLimiterFactory $rendezVousLimiter,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(RendezVousRepository $repo): JsonResponse
    {
        if ($this->security->isGranted('ROLE_MODERATEUR')) {
            $items = $repo->findBy(
                [],
                ['dateHeure' => 'ASC']
            );
        } else {
            $items = $repo->findBy(
                ['client' => $this->security->getUser()],
                ['dateHeure' => 'ASC']
            );
        }

        return $this->jsonRead(
            $items,
            'rendezvous:read',
            $this->serializer
        );
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(RendezVous $rendezVous): JsonResponse
    {
        if ($rendezVous->getClient() !== $this->security->getUser() && !$this->security->isGranted('ROLE_MODERATEUR')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }
        return $this->jsonRead($rendezVous, 'rendezvous:read', $this->serializer);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $currentUser = $this->security->getUser();
        $rateLimitKey = $currentUser ? (string) $currentUser->getUserIdentifier() : ($request->getClientIp() ?? 'unknown');
        $limiter = $this->rendezVousLimiter->create($rateLimitKey);
        if (!$limiter->consume(1)->isAccepted()) {
            return new JsonResponse(['error' => 'Trop de demandes de rendez-vous, veuillez patienter avant de réessayer.'], 429);
        }

        $data = $this->data($request);

        // Si l'utilisateur est un client (ou si non renseigné), on force automatiquement son propre ID
        if (!$this->security->isGranted('ROLE_MODERATEUR')) {
            $data['clientId'] = $currentUser?->getId();
            $data['moderateurId'] = null;
        } elseif (empty($data['clientId']) && $currentUser) {
            $data['clientId'] = $currentUser->getId();
        }

        $dto = CreateRendezVousDto::fromRequest($data);
        if ($r = $this->validateDto($dto, $this->validator)) return $r;

        if (!$this->security->isGranted('ROLE_MODERATEUR') && $dto->clientId !== $currentUser?->getId()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $client = $this->users->find($dto->clientId);
        if (!$client) return new JsonResponse(['error' => 'Client introuvable'], 404);

        $vehicule = $this->vehicules->find($dto->vehiculeId);
        if (!$vehicule) return new JsonResponse(['error' => 'Véhicule introuvable'], 404);

        // Sécurité : le véhicule doit appartenir au client si l'utilisateur n'est pas modérateur
        if (!$this->security->isGranted('ROLE_MODERATEUR') && $vehicule->getProprietaire() !== $client) {
            return new JsonResponse(['error' => 'Ce véhicule ne vous appartient pas'], 403);
        }

        $mod = $dto->moderateurId ? $this->users->find($dto->moderateurId) : null;
        if ($dto->moderateurId && !$mod) return new JsonResponse(['error' => 'Modérateur introuvable'], 404);

        $rv = (new RendezVous())
            ->setDateHeure(new \DateTimeImmutable($dto->dateHeure))
            ->setMotif($dto->motif)
            ->setClient($client)
            ->setVehicule($vehicule)
            ->setModerateur($mod)
            ->setStatut(RendezVousStatut::EN_ATTENTE)
            ->setDateCreation(new \DateTimeImmutable());

        $this->em->persist($rv);
        $this->em->flush();

        return $this->jsonRead($rv, 'rendezvous:read', $this->serializer, 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function update(RendezVous $rendezVous, Request $request): JsonResponse
    {
        $dto = CreateRendezVousDto::fromRequest($this->data($request));
        if ($r = $this->validateDto($dto, $this->validator)) return $r;
        $client = $this->users->find($dto->clientId);
        if (!$client) return new JsonResponse(['error' => 'Client introuvable'], 404);
        $vehicule = $this->vehicules->find($dto->vehiculeId);
        if (!$vehicule) return new JsonResponse(['error' => 'Véhicule introuvable'], 404);
        $mod = $dto->moderateurId ? $this->users->find($dto->moderateurId) : null;
        if ($dto->moderateurId && !$mod) return new JsonResponse(['error' => 'Modérateur introuvable'], 404);

        $rendezVous
            ->setDateHeure(new \DateTimeImmutable($dto->dateHeure))
            ->setMotif($dto->motif)
            ->setClient($client)
            ->setVehicule($vehicule)
            ->setModerateur($mod);

        $this->em->flush();
        return $this->jsonRead($rendezVous, 'rendezvous:read', $this->serializer);
    }

    #[Route('/{id}/statut', methods: ['PATCH'])]
    public function statut(RendezVous $rendezVous, Request $request): JsonResponse
    {
        $s = RendezVousStatut::tryFrom($this->data($request)['statut'] ?? '');
        if (!$s) return new JsonResponse(['error' => 'Statut invalide'], 400);

        if ($s === RendezVousStatut::ANNULE) {
            if ($rendezVous->getClient() !== $this->security->getUser() && !$this->security->isGranted('ROLE_MODERATEUR')) {
                return new JsonResponse(['error' => 'Accès refusé'], 403);
            }
        } elseif (!$this->security->isGranted('ROLE_MODERATEUR')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $rendezVous->setStatut($s);

        if ($s === RendezVousStatut::CONFIRME && $rendezVous->getIntervention() === null) {
            $this->interventionManager->createFromRendezVous($rendezVous);
        }

        $this->em->flush();
        return $this->jsonRead($rendezVous, 'rendezvous:read', $this->serializer);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function delete(RendezVous $rendezVous): JsonResponse
    {
        $this->em->remove($rendezVous);
        $this->em->flush();
        return new JsonResponse(null, 204);
    }
}
