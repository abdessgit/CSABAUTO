<?php

namespace App\Repository;

use App\Entity\MessageContact;
use App\Enum\MessageContactStatut;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MessageContact>
 */
class MessageContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageContact::class);
    }

    public function countNouveaux(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.statut = :statut')
            ->setParameter('statut', MessageContactStatut::NOUVEAU)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array{items: MessageContact[], total: int, nouveaux: int}
     */
    public function findPaginatedFiltered(?string $statut = null, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.repondPar', 'u')
            ->addSelect('u')
            ->orderBy('m.dateEnvoi', 'DESC');

        if ($statut) {
            $enumStatut = MessageContactStatut::tryFrom(strtoupper($statut));
            if ($enumStatut) {
                $qb->andWhere('m.statut = :statut')
                   ->setParameter('statut', $enumStatut);
            }
        }

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(m.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $nouveaux = $this->countNouveaux();

        return [
            'items' => $items,
            'total' => $total,
            'nouveaux' => $nouveaux,
        ];
    }
}
