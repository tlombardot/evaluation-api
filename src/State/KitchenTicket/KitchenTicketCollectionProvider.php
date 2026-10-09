<?php

namespace App\State\KitchenTicket;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\KitchenTicket\KitchenTicketListOutput;
use App\Entity\User;
use App\Service\KitchenTicketService;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProviderInterface<KitchenTicketListOutput>
 */
final class KitchenTicketCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly KitchenTicketService $kitchenTicketService,
    ) {
    }

    /**
     * Sert les bons de cuisine de l'utilisateur connecté, du plus récent au plus ancien.
     *
     * @return KitchenTicketListOutput[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();

        // on ne protège pas une collection, on la filtre : sans porteur identifiable, elle est vide
        if (!$user instanceof User) {
            return [];
        }

        // un state ne fabrique aucun DTO : il appelle la méthode du service qui le fabrique
        return array_map($this->kitchenTicketService->toList(...), $this->kitchenTicketService->findFor($user));
    }
}
