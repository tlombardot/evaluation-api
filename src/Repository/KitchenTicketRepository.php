<?php

namespace App\Repository;

use App\Entity\KitchenTicket;
use App\Entity\User;
use App\Trait\EntityRepositorySaverTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KitchenTicket>
 */
class KitchenTicketRepository extends ServiceEntityRepository
{
    use EntityRepositorySaverTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KitchenTicket::class);
    }

    /**
     * Renvoie les bons de cuisine, du plus récent au plus ancien.
     *
     * @return KitchenTicket[]
     */
    public function findFor(User $user): array
    {
        return $this->createQueryBuilder('k')
            // les bons d'un même paiement partagent leur date d'émission : l'identifiant v7,
            // ordonné dans le temps, les départage de façon stable
            ->andWhere('k.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('k.createdAt', 'DESC')
            ->addOrderBy('k.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
