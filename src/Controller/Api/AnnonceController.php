<?php

namespace App\Controller\Api;

use App\Dto\CreateAnnonceDto;
use App\Entity\Annonce;
use App\Enum\AnnonceStatut;
use App\Repository\AnnonceRepository;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/annonces')]
class AnnonceController extends AbstractApiController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private VehiculeRepository $vehicules,
        private Security $security,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir
    ) {}

    // ==========================================
    // ENDPOINTS PUBLICS (statut PUBLIEE uniquement)
    // ==========================================

    #[Route('/publiques', methods: ['GET'])]
    public function listPubliques(AnnonceRepository $repo, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 12)));

        $qb = $repo->createQueryBuilder('a')
            ->where('a.statut IN (:statuts)')
            ->setParameter('statuts', [
                AnnonceStatut::PUBLIEE,
                AnnonceStatut::EN_VENTE,
                AnnonceStatut::VENDUE,
                AnnonceStatut::VENDU
            ]);

        // Filtre spécifique par statut si demandé (ex: seulement PUBLIEE)
        if ($statut = trim((string) $request->query->get('statut', ''))) {
            $qb->andWhere('a.statut = :statut')
               ->setParameter('statut', $statut);
        }

        // Recherche texte libre (titre, description, marque, modèle)
        $search = trim((string) ($request->query->get('q') ?? $request->query->get('search', '')));
        if ($search !== '') {
            $qb->andWhere('LOWER(a.titre) LIKE :search OR LOWER(a.description) LIKE :search OR LOWER(a.marque) LIKE :search OR LOWER(a.modele) LIKE :search')
               ->setParameter('search', '%' . strtolower($search) . '%');
        }

        if ($marque = trim((string) $request->query->get('marque', ''))) {
            $qb->andWhere('LOWER(a.marque) LIKE :marque')
               ->setParameter('marque', '%' . strtolower($marque) . '%');
        }

        if ($modele = trim((string) $request->query->get('modele', ''))) {
            $qb->andWhere('LOWER(a.modele) LIKE :modele')
               ->setParameter('modele', '%' . strtolower($modele) . '%');
        }

        if ($prixMin = $request->query->get('prixMin')) {
            $qb->andWhere('a.prix >= :prixMin')
               ->setParameter('prixMin', (float) $prixMin);
        }

        if ($prixMax = $request->query->get('prixMax')) {
            $qb->andWhere('a.prix <= :prixMax')
               ->setParameter('prixMax', (float) $prixMax);
        }

        if ($annee = $request->query->get('annee')) {
            $qb->andWhere('a.annee = :annee')
               ->setParameter('annee', (int) $annee);
        }

        if ($anneeMin = $request->query->get('anneeMin')) {
            $qb->andWhere('a.annee >= :anneeMin')
               ->setParameter('anneeMin', (int) $anneeMin);
        }

        if ($anneeMax = $request->query->get('anneeMax')) {
            $qb->andWhere('a.annee <= :anneeMax')
               ->setParameter('anneeMax', (int) $anneeMax);
        }

        if ($carburant = trim((string) $request->query->get('carburant', ''))) {
            $qb->andWhere('LOWER(a.carburant) = :carburant')
               ->setParameter('carburant', strtolower($carburant));
        }

        if ($boite = trim((string) $request->query->get('boite', ''))) {
            $qb->andWhere('LOWER(a.boite) = :boite')
               ->setParameter('boite', strtolower($boite));
        }

        // Tri
        $tri = (string) $request->query->get('tri', 'recent');
        switch ($tri) {
            case 'prix_asc':
                $qb->orderBy('a.prix', 'ASC');
                break;
            case 'prix_desc':
                $qb->orderBy('a.prix', 'DESC');
                break;
            case 'km_asc':
                $qb->orderBy('a.kilometrage', 'ASC');
                break;
            case 'km_desc':
                $qb->orderBy('a.kilometrage', 'DESC');
                break;
            case 'annee_desc':
                $qb->orderBy('a.annee', 'DESC');
                break;
            case 'recent':
            default:
                $qb->orderBy('a.datePublication', 'DESC')
                   ->addOrderBy('a.id', 'DESC');
                break;
        }

        $countQb = clone $qb;
        $total = (int) $countQb->resetDQLPart('orderBy')->select('COUNT(a.id)')->getQuery()->getSingleScalarResult();

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $items = $qb->getQuery()->getResult();

        return $this->jsonRead([
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => (int) ceil($total / $limit),
        ], 'annonce:read', $this->serializer);
    }

    #[Route('/publiques/{id}', methods: ['GET'])]
    public function showPublique(int $id, AnnonceRepository $repo): JsonResponse
    {
        $annonce = $repo->find($id);

        $allowedStatuts = [
            AnnonceStatut::PUBLIEE,
            AnnonceStatut::EN_VENTE,
            AnnonceStatut::VENDUE,
            AnnonceStatut::VENDU
        ];

        if (!$annonce || !in_array($annonce->getStatut(), $allowedStatuts, true)) {
            return new JsonResponse(['error' => 'Annonce introuvable ou non publiée'], 404);
        }

        return $this->jsonRead($annonce, 'annonce:read', $this->serializer);
    }

    // ==========================================
    // UPLOAD D'IMAGES (ROLE_MODERATEUR)
    // ==========================================

    #[Route('/upload-photo', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function uploadPhoto(Request $request): JsonResponse
    {
        $uploadedFiles = [];

        // Récupérer le ou les fichiers envoyés
        $allFiles = $request->files->all();
        foreach ($allFiles as $key => $fileOrArray) {
            if (is_array($fileOrArray)) {
                foreach ($fileOrArray as $f) {
                    if ($f instanceof UploadedFile) {
                        $uploadedFiles[] = $f;
                    }
                }
            } elseif ($fileOrArray instanceof UploadedFile) {
                $uploadedFiles[] = $fileOrArray;
            }
        }

        if (empty($uploadedFiles)) {
            return new JsonResponse(['error' => 'Aucun fichier fourni.'], 400);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $maxSize = 5 * 1024 * 1024; // 5 Mo

        $uploadDir = $this->projectDir . '/public/uploads/annonces';
        try {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Impossible de créer le dossier de destination pour les photos : ' . $e->getMessage(),
            ], 500);
        }

        $savedUrls = [];

        foreach ($uploadedFiles as $file) {
            if ($file->getSize() > $maxSize) {
                return new JsonResponse([
                    'error' => sprintf('L\'image "%s" dépasse la taille maximale autorisée de 5 Mo.', $file->getClientOriginalName()),
                ], 422);
            }

            $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
            $mime = $file->getMimeType();

            if (!in_array($ext, $allowedExtensions, true) || !in_array($mime, $allowedMimes, true)) {
                return new JsonResponse([
                    'error' => sprintf('Format invalide pour "%s". Formats acceptés : JPG, PNG, WEBP.', $file->getClientOriginalName()),
                ], 422);
            }

            try {
                $filename = bin2hex(random_bytes(16)) . '.' . $ext;
                $file->move($uploadDir, $filename);
                $savedUrls[] = '/uploads/annonces/' . $filename;
            } catch (\Throwable $e) {
                return new JsonResponse([
                    'error' => sprintf('Erreur lors de l\'enregistrement de l\'image "%s" : %s', $file->getClientOriginalName(), $e->getMessage()),
                ], 500);
            }
        }

        return new JsonResponse([
            'url' => $savedUrls[0] ?? null,
            'urls' => $savedUrls,
        ], 201);
    }

    // ==========================================
    // ENDPOINTS GESTION MODÉRATEUR
    // ==========================================

    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function list(AnnonceRepository $repo, Request $request): JsonResponse
    {
        $statutParam = $request->query->get('statut');
        $statut = $statutParam ? AnnonceStatut::tryFrom(strtoupper(trim($statutParam))) : null;

        $criteria = $statut ? ['statut' => $statut] : [];
        $items = $repo->findBy($criteria, ['dateCreation' => 'DESC']);

        return $this->jsonRead($items, 'annonce:read', $this->serializer);
    }

    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function show(Annonce $annonce): JsonResponse
    {
        return $this->jsonRead($annonce, 'annonce:read', $this->serializer);
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function create(Request $request): JsonResponse
    {
        $dto = CreateAnnonceDto::fromRequest($this->data($request));
        if ($r = $this->validateDto($dto, $this->validator)) {
            return $r;
        }

        $targetStatut = $dto->statut ? AnnonceStatut::tryFrom($dto->statut) : AnnonceStatut::BROUILLON;
        if (!$targetStatut) {
            $targetStatut = AnnonceStatut::BROUILLON;
        }

        if ($targetStatut === AnnonceStatut::PUBLIEE && empty($dto->photos)) {
            return new JsonResponse(['error' => 'Pour publier une annonce, au moins une photo est requise.'], 422);
        }

        $v = $dto->vehiculeId ? $this->vehicules->find($dto->vehiculeId) : null;

        $annonce = (new Annonce())
            ->setTitre($dto->titre)
            ->setMarque($dto->marque)
            ->setModele($dto->modele)
            ->setAnnee($dto->annee)
            ->setKilometrage($dto->kilometrage)
            ->setPrix($dto->prix)
            ->setCarburant($dto->carburant)
            ->setBoite($dto->boite)
            ->setPuissance($dto->puissance)
            ->setCouleur($dto->couleur)
            ->setNbPortes($dto->nbPortes)
            ->setNbPlaces($dto->nbPlaces)
            ->setDescription($dto->description)
            ->setPhotos($dto->photos)
            ->setStatut($targetStatut)
            ->setVehicule($v)
            ->setDateCreation(new \DateTimeImmutable());

        if ($targetStatut === AnnonceStatut::PUBLIEE) {
            $annonce->setDatePublication(new \DateTimeImmutable());
        }

        $this->em->persist($annonce);
        $this->em->flush();

        return $this->jsonRead($annonce, 'annonce:read', $this->serializer, 201);
    }

    #[Route('/{id}', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function update(Annonce $annonce, Request $request): JsonResponse
    {
        $dto = CreateAnnonceDto::fromRequest($this->data($request));
        if ($r = $this->validateDto($dto, $this->validator)) {
            return $r;
        }

        $targetStatut = $dto->statut ? AnnonceStatut::tryFrom($dto->statut) : $annonce->getStatut();
        if ($targetStatut === AnnonceStatut::PUBLIEE && empty($dto->photos)) {
            return new JsonResponse(['error' => 'Pour publier une annonce, au moins une photo est requise.'], 422);
        }

        $v = $dto->vehiculeId ? $this->vehicules->find($dto->vehiculeId) : $annonce->getVehicule();

        $annonce
            ->setTitre($dto->titre)
            ->setMarque($dto->marque)
            ->setModele($dto->modele)
            ->setAnnee($dto->annee)
            ->setKilometrage($dto->kilometrage)
            ->setPrix($dto->prix)
            ->setCarburant($dto->carburant)
            ->setBoite($dto->boite)
            ->setPuissance($dto->puissance)
            ->setCouleur($dto->couleur)
            ->setNbPortes($dto->nbPortes)
            ->setNbPlaces($dto->nbPlaces)
            ->setDescription($dto->description)
            ->setPhotos($dto->photos)
            ->setVehicule($v);

        if ($targetStatut) {
            $annonce->setStatut($targetStatut);
            if ($targetStatut === AnnonceStatut::PUBLIEE && !$annonce->getDatePublication()) {
                $annonce->setDatePublication(new \DateTimeImmutable());
            }
        }

        $this->em->flush();

        return $this->jsonRead($annonce, 'annonce:read', $this->serializer);
    }

    #[Route('/{id}/statut', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function updateStatut(Annonce $annonce, Request $request): JsonResponse
    {
        $data = $this->data($request);
        $statutStr = strtoupper(trim((string) ($data['statut'] ?? '')));

        $newStatut = AnnonceStatut::tryFrom($statutStr);
        if (!$newStatut || !in_array($newStatut, [AnnonceStatut::BROUILLON, AnnonceStatut::PUBLIEE, AnnonceStatut::VENDUE, AnnonceStatut::EN_VENTE, AnnonceStatut::VENDU], true)) {
            return new JsonResponse([
                'error' => 'Statut invalide. Choix possibles : BROUILLON, PUBLIEE, VENDUE.',
            ], 422);
        }

        // Si passage à PUBLIEE : vérification d'au moins 1 photo et des champs obligatoires
        if ($newStatut === AnnonceStatut::PUBLIEE || $newStatut === AnnonceStatut::EN_VENTE) {
            if (empty($annonce->getPhotos())) {
                return new JsonResponse([
                    'error' => 'Pour publier une annonce, au moins une photo est requise.',
                ], 422);
            }

            if (!$annonce->getTitre() || !$annonce->getMarque() || !$annonce->getModele() || !$annonce->getAnnee() || !$annonce->getKilometrage() || !$annonce->getPrix()) {
                return new JsonResponse([
                    'error' => 'Veuillez renseigner tous les champs obligatoires (titre, marque, modèle, année, kilométrage, prix) avant de publier.',
                ], 422);
            }

            if (!$annonce->getDatePublication()) {
                $annonce->setDatePublication(new \DateTimeImmutable());
            }
        }

        $annonce->setStatut($newStatut);
        $this->em->flush();

        return $this->jsonRead($annonce, 'annonce:read', $this->serializer);
    }

    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_MODERATEUR')]
    public function delete(Annonce $annonce): JsonResponse
    {
        // Détacher les conversations associées pour éviter les violations de clé étrangère
        foreach ($annonce->getConversations() as $conv) {
            $conv->setAnnonce(null);
        }

        $this->em->remove($annonce);
        $this->em->flush();

        return new JsonResponse(null, 204);
    }
}
