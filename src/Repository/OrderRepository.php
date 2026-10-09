<?php

namespace App\Repository;

use App\Entity\Enum\OrderStatus;
use App\Entity\Order;
use App\Entity\User;
use App\Trait\EntityRepositorySaverTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    use EntityRepositorySaverTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Cherche la commande en cours d'un utilisateur : celle qu'il a créée et qui est encore en attente.
     */
    public function findActiveFor(User $user): ?Order
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.status = :status')
            ->andWhere('o.createdBy = :user')
            ->setParameter('status', OrderStatus::Pending)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
