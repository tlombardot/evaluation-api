<?php

namespace App\State\Dish;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Dish\DishDetailsOutput;
use App\Service\DishService;

/**
 * @implements ProviderInterface<DishDetailsOutput>
 */
final class DishItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly DishService $dishService,
    ) {
    }

    /**
     * Résout l'identifiant du chemin vers la représentation détaillée du plat.
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): ?DishDetailsOutput {
        $dish = $this->dishService->findOneById($uriVariables['id']);

        return $this->dishService->toDetails($dish);
    }
}
