<?php

namespace App\Service;

use App\Dto\Dish\DishDetailsOutput;
use App\Dto\Dish\DishListOutput;
use App\Dto\Dish\DishSearchInput;
use App\Entity\Dish;
use App\Entity\Enum\DishCategory;
use App\Entity\Enum\EcoScore;
use App\Exception\Dish\DishNotFoundException;
use App\Repository\DishRepository;
use Symfony\Component\Uid\Uuid;

class DishService
{
    public function __construct(
        private readonly DishRepository $dishRepository,
        private readonly RestaurantService $restaurantService,
    ) {
    }

    /**
     * @return Dish[]
     */
    public function search(DishSearchInput $input): array
    {
        $restaurant = $this->restaurantService->findOneById(
            Uuid::fromString($input->restaurantId),
        );

        return $this->dishRepository->search(
            $restaurant,
            null === $input->category ? null : DishCategory::from($input->category),
            null === $input->minEcoScore ? null : EcoScore::from($input->minEcoScore),
            $input->maxPrice,
        );
    }

    public function toList(Dish $dish): DishListOutput
    {
        return new DishListOutput(
            id: $dish->getId(),
            name: $dish->getName(),
            category: $dish->getCategory(),
            ecoScore: $dish->getEcoScore(),
            price: $dish->getPrice(),
            restaurant: $this->restaurantService->toList($dish->getRestaurant()),
        );
    }

    public function toDetails(Dish $dish): DishDetailsOutput
    {
        return new DishDetailsOutput(
            id: $dish->getId(),
            name: $dish->getName(),
            category: $dish->getCategory(),
            ecoScore: $dish->getEcoScore(),
            price: $dish->getPrice(),
            restaurant: $this->restaurantService->toList($dish->getRestaurant()),
            description: $dish->getDescription(),
        );
    }

    /**
     * @throws DishNotFoundException quand aucun plat ne porte cet identifiant
     */
    public function findOneById(Uuid $id): Dish
    {
        $dish = $this->dishRepository->find($id);

        if (null === $dish) {
            throw new DishNotFoundException();
        }

        return $dish;
    }
}
