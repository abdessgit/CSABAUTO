<?php

namespace App\Repository;

use App\Entity\Facture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Facture>
 */
class FactureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Facture::class);
    }

    public function findLatestNumeroForYear(string $prefix): ?string
    {
        $result = $this->createQueryBuilder('f')
            ->select('f.numeroFacture')
            ->where('f.numeroFacture LIKE :prefix')
            ->setParameter('prefix', $prefix . '%')
            ->orderBy('f.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result['numeroFacture'] ?? null;
    }
}
