<?php

namespace App\Service;

use App\Dto\KitchenTicket\KitchenTicketListOutput;
use App\Entity\KitchenTicket;
use App\Entity\Order;
use App\Entity\User;
use App\Repository\KitchenTicketRepository;
use App\Service\Utils\AuditService;

class KitchenTicketService
{
    public function __construct(
        private readonly DishService $dishService,
        private readonly KitchenTicketRepository $kitchenTicketRepository,
        private readonly AuditService $audit,
    ) {
    }

    /**
     * Fabrique et enregistre un bon de cuisine par ligne vivante de cette commande.
     *
     * @return KitchenTicket[]
     */
    public function issue(Order $order): array
    {
        $issued = [];

        foreach ($order->getAliveLines() as $line) {
            // un bon vaut une ligne : le plat et sa quantité, copiée maintenant
            $kitchenTicket = new KitchenTicket()
                ->setDish($line->getDish())
                ->setOrder($order)
                ->setQuantity($line->getQuantity());

            $this->audit->stampCreation($kitchenTicket);
            $this->kitchenTicketRepository->persist($kitchenTicket);

            $issued[] = $kitchenTicket;
        }

        $this->kitchenTicketRepository->flush();

        return $issued;
    }

    /**
     * Renvoie les bons de cuisine de cet utilisateur, du plus récent au plus ancien.
     *
     * @return KitchenTicket[]
     */
    public function findFor(User $user): array
    {
        return $this->kitchenTicketRepository->findFor($user);
    }

    public function toList(KitchenTicket $kitchenTicket): KitchenTicketListOutput
    {
        return new KitchenTicketListOutput(
            id: $kitchenTicket->getId(),
            // la transformation d'un plat reste au domaine des plats
            dish: $this->dishService->toList($kitchenTicket->getDish()),
            quantity: $kitchenTicket->getQuantity(),
            createdAt: $kitchenTicket->getCreatedAt(),
        );
    }
}
