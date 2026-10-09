<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Order\OrderPayInput;
use App\Dto\Order\OrderPayOutput;
use App\Service\OrderService;

/**
 * @implements ProcessorInterface<OrderPayInput, OrderPayOutput>
 */
final class OrderPayProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {
    }

    /**
     * Paie la commande portée par l'URL, et sert sa référence de retrait avec les bons émis.
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): OrderPayOutput
    {
        // $data porte le moyen de paiement, déjà validé par ses contraintes : le règlement étant
        // simulé, plus personne ne le lit, et rien ne le conserve. La commande se retrouve par
        // l'URL, comme à l'ajout de ligne ; la propriété a déjà été contrôlée par l'opération
        $order = $this->orderService->findOneById($uriVariables['id']);

        return $this->orderService->toPayment($order, $this->orderService->pay($order));
    }
}
