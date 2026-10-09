<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Order\OrderDetailsOutput;
use App\Entity\User;
use App\Service\OrderService;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProviderInterface<OrderDetailsOutput>
 */
final class OrderCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly OrderService $orderService,
    ) {
    }

    /**
     * Sert la commande en attente de l'utilisateur connecté, en collection de zéro ou un élément.
     *
     * @return OrderDetailsOutput[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();

        // on ne protège pas une collection, on la filtre : sans porteur identifiable, elle est vide
        if (!$user instanceof User) {
            return [];
        }

        $order = $this->orderService->findActiveFor($user);

        if (null === $order) {
            return [];
        }

        // un state ne fabrique aucun DTO : il appelle la méthode du service qui le fabrique
        return array_map($this->orderService->toDetails(...), [$order]);
    }
}
