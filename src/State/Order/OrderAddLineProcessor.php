<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Order\OrderAddLineInput;
use App\Dto\Order\OrderDetailsOutput;
use App\Service\OrderService;

/**
 * @implements ProcessorInterface<OrderAddLineInput, OrderDetailsOutput>
 */
final class OrderAddLineProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {
    }

    /**
     * Ajoute la ligne envoyée à la commande portée par l'URL, et sert la commande qui la porte.
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): OrderDetailsOutput
    {
        // $data porte l'objet d'entrée désérialisé, pas la commande : celle-ci se retrouve par l'URL.
        // Aucun contrôle de propriété ici : la propriété a déjà été vérifiée par l'opération
        $order = $this->orderService->findOneById($uriVariables['id']);

        return $this->orderService->toDetails($this->orderService->addLine($order, $data));
    }
}
