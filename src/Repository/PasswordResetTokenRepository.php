<?php

namespace App\Repository;

use App\Entity\PasswordResetToken;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetToken>
 */
class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    /**
     * Invalide (marque comme utilisés) tous les anciens tokens non consommés d'un utilisateur.
     */
    public function invalidateTokensForUser(Utilisateur $user): void
    {
        $this->createQueryBuilder('t')
            ->update()
            ->set('t.usedAt', ':now')
            ->where('t.user = :user')
            ->andWhere('t.usedAt IS NULL')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Supprime tous les autres tokens d'un utilisateur après une réinitialisation réussie.
     */
    public function deleteTokensForUser(Utilisateur $user, ?PasswordResetToken $except = null): void
    {
        $qb = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.user = :user')
            ->setParameter('user', $user);

        if ($except !== null && $except->getId() !== null) {
            $qb->andWhere('t.id != :exceptId')
               ->setParameter('exceptId', $except->getId());
        }

        $qb->getQuery()->execute();
    }
}
