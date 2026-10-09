<?php

namespace App\Service;

use App\Dto\Order\OrderAddLineInput;
use App\Dto\Order\OrderDetailsOutput;
use App\Dto\Order\OrderLineOutput;
use App\Dto\Order\OrderPayOutput;
use App\Entity\Enum\OrderStatus;
use App\Entity\KitchenTicket;
use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\User;
use App\Exception\Dish\DishNotFoundException;
use App\Exception\Order\OrderAlreadyPaidException;
use App\Exception\Order\OrderEmptyException;
use App\Exception\Order\OrderLineNotFoundException;
use App\Exception\Order\OrderNotFoundException;
use App\Exception\Order\OrderRestaurantMismatchException;
use App\Repository\OrderRepository;
use App\Service\Utils\AuditService;
use Symfony\Component\Uid\Uuid;

class OrderService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly DishService $dishService,
        private readonly KitchenTicketService $kitchenTicketService,
        private readonly AuditService $audit,
    ) {
    }

    public function toLine(OrderLine $line): OrderLineOutput
    {
        $dish = $line->getDish();

        return new OrderLineOutput(
            id: $line->getId(),
            // la transformation d'un plat reste au domaine des plats
            dish: $this->dishService->toList($dish),
            quantity: $line->getQuantity(),
            // le sous-total ne vient d'aucune colonne : il se recalcule à chaque lecture
            subtotal: $dish->getPrice(),
        );
    }

    public function toDetails(Order $order): OrderDetailsOutput
    {
        // la boucle appartient au service, parce que la ligne appartient à la commande
        $lines = array_map($this->toLine(...), $order->getLines()->toArray());

        return new OrderDetailsOutput(
            id: $order->getId(),
            status: $order->getStatus(),
            lines: $lines,
            total: array_sum(array_column($lines, 'subtotal')),
            createdAt: $order->getCreatedAt(),
        );
    }

    /**
     * Renvoie la commande en attente de cet utilisateur, s'il en a une.
     */
    public function findActiveFor(User $user): ?Order
    {
        return $this->orderRepository->findActiveFor($user);
    }

    /**
     * Renvoie la commande en attente de cet utilisateur, et en ouvre une s'il n'en a pas.
     */
    public function open(User $user): Order
    {
        $existing = $this->findActiveFor($user);

        // une commande en cours existe déjà : le contrat interdit d'en ouvrir une seconde
        if (null !== $existing) {
            return $existing;
        }

        $order = new Order();

        // l'appel se fait dans une requête authentifiée : created_by_id est rempli
        $this->audit->stampCreation($order);

        $this->orderRepository->persist($order);
        $this->orderRepository->flush();

        return $order;
    }

    /**
     * Renvoie la commande portant cet identifiant.
     *
     * @throws OrderNotFoundException quand aucune commande ne porte cet identifiant
     */
    public function findOneById(Uuid $id): Order
    {
        $order = $this->orderRepository->find($id);

        if (null === $order) {
            throw new OrderNotFoundException();
        }

        return $order;
    }

    /**
     * Ajoute une ligne à cette commande et renvoie la commande elle-même.
     *
     * @throws DishNotFoundException            quand aucun plat ne porte l'identifiant envoyé
     * @throws OrderRestaurantMismatchException quand le plat vient d'un autre restaurant que les lignes déjà commandées
     */
    public function addLine(Order $order, OrderAddLineInput $input): Order
    {
        if ($order->getStatus() === OrderStatus::Paid){
            throw new OrderAlreadyPaidException;
        }
        // résoudre un plat appartient au domaine des plats : ce service passe par le sien
        $dish = $this->dishService->findOneById(Uuid::fromString($input->dishId));


        // une commande se retire dans un seul restaurant : le plat doit venir de celui des lignes
        // vivantes
        $restaurantId = $dish->getRestaurant()->getId();
        $foreignLine = $order->getAliveLines()->findFirst(
            static fn (int $key, OrderLine $line): bool => !$line->getDish()->getRestaurant()->getId()->equals($restaurantId),
        );

        if (null !== $foreignLine) {
            throw new OrderRestaurantMismatchException();
        }

        // aucune garde de doublon : le même plat peut figurer deux fois, une ligne vaut un plat
        // et une quantité
        $line = new OrderLine()
            ->setDish($dish)
            ->setQuantity($input->quantity);

        $order->addLine($line);

        $this->audit->stampCreation($line);

        // aucun persist explicite : Order::$lines porte cascade: ['persist']. La ligne s'écrit donc
        // par le repository de l'agrégat, pas par celui des lignes
        $this->orderRepository->flush();

        return $order;
    }

    /**
     * Supprime en douceur une ligne de cette commande, et la commande elle-même quand c'était la dernière.
     *
     * @throws OrderAlreadyPaidException  quand la commande n'est plus modifiable
     * @throws OrderLineNotFoundException quand cette commande ne porte aucune ligne vivante de cet identifiant
     */
    public function removeLine(Order $order, Uuid $lineId): void
    {
        // l'état se compare au cas d'enum, jamais à la chaîne
        if (OrderStatus::Paid === $order->getStatus()) {
            throw new OrderAlreadyPaidException();
        }

        // la ligne se cherche dans la collection de la commande, pas par le repository : une ligne
        // de la commande de quelqu'un d'autre ne doit tout simplement pas se trouver. Une ligne
        // déjà supprimée non plus : elle n'est pas vivante
        $line = $order->getAliveLines()->findFirst(
            static fn (int $key, OrderLine $line): bool => $line->getId()->equals($lineId),
        );

        if (null === $line) {
            throw new OrderLineNotFoundException();
        }

        // retirer une ligne l'estampille : elle reste en base, et lui seul sait qui porte la requête
        $this->audit->markDeleted($line);

        // la ligne qu'on vient d'estampiller est encore dans la collection, mais n'est plus
        // vivante : si c'était la dernière, la commande part avec elle, dans le même mouvement
        if ($order->getAliveLines()->isEmpty()) {
            $this->audit->markDeleted($order);
        }

        // un seul flush : la ligne et, le cas échéant, la commande s'écrivent ensemble
        $this->orderRepository->flush();
    }

    /**
     * Paie cette commande : émet ses bons de cuisine et la marque payée.
     *
     * @return KitchenTicket[] les bons émis
     *
     * @throws OrderAlreadyPaidException quand la commande a déjà été payée
     * @throws OrderEmptyException       quand la commande ne porte aucune ligne vivante
     */
    public function pay(Order $order): array
    {
        if (OrderStatus::Paid === $order->getStatus()) {
            throw new OrderAlreadyPaidException();
        }

        // une ligne supprimée en douceur ne compte pas : le décompte porte sur les lignes
        // vivantes, comme dans removeLine, et non sur la taille de la collection
        if ($order->getAliveLines()->isEmpty()) {
            throw new OrderEmptyException();
        }

        // fabriquer un bon appartient au domaine des bons : ce service passe par le sien
        $kitchenTickets = $this->kitchenTicketService->issue($order);

        $order->setStatus(OrderStatus::Paid);

        // ni une création ni une suppression : une mise à jour, estampillée comme telle
        $this->audit->stampUpdate($order);

        return $kitchenTickets;
    }

    /**
     * Renvoie la référence de retrait de cette commande, dérivée de son identité.
     */
    public function referenceOf(Order $order): string
    {
        // la fin de l'identifiant, pas son début : un UUID v7 commence par son horodatage
        $suffix = strtoupper(substr($order->getId()->toRfc4122(), -6));

        return sprintf('CE-%s-%s', $order->getCreatedAt()->format('Y'), $suffix);
    }

    /**
     * @param KitchenTicket[] $kitchenTickets
     */
    public function toPayment(Order $order, array $kitchenTickets): OrderPayOutput
    {
        return new OrderPayOutput(
            reference: $this->referenceOf($order),
            // la transformation d'un bon reste au domaine des bons
            kitchenTickets: array_map($this->kitchenTicketService->toList(...), $kitchenTickets),
        );
    }
}
