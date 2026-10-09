<?php

namespace App\Repository;

use App\Entity\Restaurant;
use App\Trait\EntityRepositorySaverTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Restaurant>
 */
class RestaurantRepository extends ServiceEntityRepository
{
    use EntityRepositorySaverTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Restaurant::class);
    }

    public function search(?string $query = null, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.campus', 'ASC')
            ->addOrderBy('r.name', 'ASC')
            ->setMaxResults($limit);

        if ($query) {
            // le terme cherché porte soit sur le nom, soit sur le campus (égalité exacte)
            $qb->where('LOWER(r.name) LIKE LOWER(:query) OR r.campus = LOWER(:campus)')
                ->setParameter('query', '%' . $query . '%')
                ->setParameter('campus', $query);
        }

        return $qb->getQuery()->getResult();
    }
}
