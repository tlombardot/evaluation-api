<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Order\OrderDetailsOutput;
use App\Entity\User;
use App\Service\OrderService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @implements ProcessorInterface<mixed, OrderDetailsOutput>
 */
final class OrderOpenProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly OrderService $orderService,
    ) {
    }

    /**
     * Sert la commande en attente de l'utilisateur connecté, en ouvrant une s'il n'en a pas.
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): OrderDetailsOutput
    {
        $user = $this->security->getUser();

        // l'opération exige déjà ROLE_USER : ce refus ne sert qu'à ramener le type au User du domaine
        if (!$user instanceof User) {
            throw new AccessDeniedException();
        }

        return $this->orderService->toDetails($this->orderService->open($user));
    }
}
