<?php

namespace App\Service;

use App\Dto\Restaurant\RestaurantListOutput;
use App\Entity\Restaurant;
use App\Exception\Restaurant\RestaurantNotFoundException;
use App\Repository\RestaurantRepository;
use Symfony\Component\Uid\Uuid;

class RestaurantService
{
    private const MAX_RESULTS = 100;
    public const DEFAULT_LIMIT = 20;

    public function __construct(
        private readonly RestaurantRepository $restaurantRepository
    )
    {

    }

    public function toList(Restaurant $restaurant): RestaurantListOutput
    {
        return new RestaurantListOutput(
            id: $restaurant->getId(),
            name: $restaurant->getName(),
            campus: $restaurant->getCampus(),
        );
    }

    public function search(?string $query = null, ?int $limit = null): array
    {
        $query = trim($query ?? '');

        if ($query === '') {
            $query = null;
        }

        // borne $limit à [1, MAX_RESULTS]
        $limit = min(self::MAX_RESULTS, max(1, $limit ?? self::DEFAULT_LIMIT));

        return $this->restaurantRepository->search($query, $limit);
    }

    /**
     * @throws RestaurantNotFoundException quand aucun restaurant ne porte cet identifiant
     */
    public function findOneById(Uuid $id): Restaurant
    {
        $found = $this->restaurantRepository->find($id);

        if (!$found) {
            throw new RestaurantNotFoundException();
        }

        return $found;
    }
}
