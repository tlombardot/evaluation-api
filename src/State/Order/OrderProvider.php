<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Order;
use App\Service\OrderService;

/**
 * @implements ProviderInterface<Order>
 */
final class OrderProvider implements ProviderInterface
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {
    }

    /**
     * Résout la commande portée par l'URL vers son entité, pour que l'expression de sécurité
     * de l'opération ait un propriétaire à comparer. L'entité ne sort pas : le processor
     * la consomme et renvoie le DTO.
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Order
    {
        // aucune conversion : Order::$id étant typé Uuid, API Platform livre déjà un Uuid
        return $this->orderService->findOneById($uriVariables['id']);
    }
}
