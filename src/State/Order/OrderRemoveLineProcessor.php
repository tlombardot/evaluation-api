<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Order;
use App\Service\OrderService;

/**
 * @implements ProcessorInterface<Order, null>
 */
final class OrderRemoveLineProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {
    }

    /**
     * Retire de la commande portée par l'URL la ligne portée par l'URL, et ne sert aucun corps.
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $order = $this->orderService->findOneById($uriVariables['id']);

        $this->orderService->removeLine($order, $uriVariables['lineId']);

        // le 204 du contrat vient de ce null : l'opération ne fabrique aucun objet de sortie
        return null;
    }
}
