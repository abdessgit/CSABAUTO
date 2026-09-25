<?php

namespace App\Controller\Api;

use App\Entity\Facture;
use App\Enum\FactureStatut;
use App\Repository\FactureRepository;
use App\Service\FacturePdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
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
        private FacturePdfGenerator $pdfGenerator,
        private MailerInterface $mailer,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(FactureRepository $repo, Request $request): JsonResponse
    {
        $items = $repo->findAll();

        if (!$this->security->isGranted('ROLE_MODERATEUR')) {
            $user = $this->security->getUser();
            $items = array_values(array_filter(
                $items,
                fn(Facture $f) => $f->getIntervention()->getVehicule()->getProprietaire() === $user
            ));
        }

        $mois = $request->query->get('mois');
        $annee = $request->query->get('annee');

        if ($annee) {
            $items = array_values(array_filter(
                $items,
                fn(Facture $f) => $f->getDateEmission()->format('Y') === (string) $annee
            ));
        }

        if ($mois) {
            $items = array_values(array_filter(
                $items,
                fn(Facture $f) => (int) $f->getDateEmission()->format('n') === (int) $mois
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

    #[Route('/{id}/pdf', methods: ['GET'])]
    public function pdf(Facture $facture, FacturePdfGenerator $pdfGenerator): Response
    {
        if ($facture->getIntervention()->getVehicule()->getProprietaire() !== $this->security->getUser()
            && !$this->security->isGranted('ROLE_MODERATEUR')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $pdfContent = $pdfGenerator->generate($facture);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $facture->getNumeroFacture() . '.pdf"',
        ]);
    }

    #[Route('/{id}/envoyer-email', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function envoyerEmail(Facture $facture, FacturePdfGenerator $pdfGenerator, MailerInterface $mailer): JsonResponse
    {
        $client = $facture->getIntervention()->getVehicule()->getProprietaire();
        $pdfContent = $pdfGenerator->generate($facture);

        $email = (new Email())
            ->from('contact@csabauto.com')
            ->to($client->getEmail())
            ->subject('Votre facture ' . $facture->getNumeroFacture() . ' - CSAB AUTO')
            ->text('Bonjour ' . $client->getPrenom() . ",\n\nVeuillez trouver ci-joint votre facture.\n\nCordialement,\nCSAB AUTO")
            ->attach($pdfContent, $facture->getNumeroFacture() . '.pdf', 'application/pdf');

        try {
            $mailer->send($email);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Échec de l\'envoi de l\'email : ' . $e->getMessage()], 500);
        }

        return new JsonResponse(['message' => 'Facture envoyée par email avec succès.']);
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