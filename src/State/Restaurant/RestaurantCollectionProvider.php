<?php

namespace App\State\Restaurant;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Restaurant\RestaurantListOutput;
use App\Service\RestaurantService;

class RestaurantCollectionProvider implements ProviderInterface
{
    // le service n'est pas construit ici, il est demandé au conteneur
    public function __construct(private readonly RestaurantService $restaurantService) {}

    /**
     * Sert la collection des restaurants, déjà convertie vers sa représentation de sortie.
     *
     * @return RestaurantListOutput[]
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): array {
        // API Platform dépose les paramètres de requête dans le contexte, sous la clé « filters »
        $filters = $context["filters"] ?? [];

        $query = trim($filters["q"] ?? "");
        $limit = (int) ($filters["limit"] ?? $this->restaurantService::DEFAULT_LIMIT);

        $restaurants = $this->restaurantService->search($query, $limit);

        return array_map($this->restaurantService->toList(...), $restaurants);
    }
}
