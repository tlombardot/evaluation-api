<?php

namespace App\State\Dish;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Dish\DishListOutput;
use App\Dto\Dish\DishSearchInput;
use App\Service\DishService;

/**
 * @implements ProcessorInterface<DishSearchInput, DishListOutput[]>
 */
final class DishSearchProcessor implements ProcessorInterface
{
    // le service n'est pas construit ici, il est demandé au conteneur
    public function __construct(
        private readonly DishService $dishService,
    ) {
    }

    /**
     * Sert les plats qui répondent à la recherche, convertis vers leur représentation de liste.
     *
     * @return DishListOutput[]
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $dishes = $this->dishService->search($data);

        return array_map($this->dishService->toList(...), $dishes);
    }
}
