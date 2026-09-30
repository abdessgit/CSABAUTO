<?php

namespace App\Repository;

use App\Entity\Vehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vehicule>
 */
class VehiculeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicule::class);
    }

    public function findOneByImmatriculationInsensitive(?string $immatriculation): ?Vehicule
    {
        if (!$immatriculation) {
            return null;
        }

        return $this->createQueryBuilder('v')
            ->where('UPPER(TRIM(v.immatriculation)) = :immat')
            ->setParameter('immat', strtoupper(trim($immatriculation)))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
