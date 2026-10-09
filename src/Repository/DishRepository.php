<?php

namespace App\Repository;

use App\Entity\Dish;
use App\Entity\Enum\DishCategory;
use App\Entity\Enum\EcoScore;
use App\Entity\Restaurant;
use App\Trait\EntityRepositorySaverTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dish>
 */
class DishRepository extends ServiceEntityRepository
{
    use EntityRepositorySaverTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dish::class);
    }

    /**
     * Cherche les plats d'un restaurant, du moins cher au plus cher.
     *
     * @return Dish[]
     */
    public function search(
        Restaurant $restaurant,
        ?DishCategory $category = null,
        ?EcoScore $minEcoScore = null,
        ?int $maxPrice = null,
    ): array {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.restaurant = :restaurant')
            ->setParameter('restaurant', $restaurant)
            ->orderBy('d.price', 'ASC')
            ->addOrderBy('d.name', 'ASC');

        if (null !== $category) {
            $qb->andWhere('d.category = :category')
                ->setParameter('category', $category);
        }

        if (null !== $minEcoScore) {
            // « B minimum » = B ou mieux : les lettres se comparent dans l'ordre alphabétique
            $qb->andWhere('d.ecoScore <= :minEcoScore')
                ->setParameter('minEcoScore', $minEcoScore->value);
        }

        if (null !== $maxPrice) {
            $qb->andWhere('d.price <= :maxPrice')
                ->setParameter('maxPrice', $maxPrice);
        }

        return $qb->getQuery()->getResult();
    }
}
