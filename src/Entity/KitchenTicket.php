<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Dto\KitchenTicket\KitchenTicketListOutput;
use App\Entity\Impl\AbstractEntity;
use App\Repository\KitchenTicketRepository;
use App\State\KitchenTicket\KitchenTicketCollectionProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ApiResource(operations: [
    new GetCollection(
        uriTemplate: '/kitchen-tickets',
        paginationClientEnabled: false,
        output: KitchenTicketListOutput::class,
        provider: KitchenTicketCollectionProvider::class,
        security: "is_granted('ROLE_USER')",
    ),
])]
#[ORM\Entity(repositoryClass: KitchenTicketRepository::class)]
class KitchenTicket extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Dish $dish;

    // dans un seul sens : aucune opération ne demande à la commande ses bons, Order ne change pas
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Order $order;

    /**
     * Nombre de portions à préparer, copié de la ligne au moment du paiement.
     */
    #[ORM\Column]
    private int $quantity;

    /**
     * Construit un bon avec un UUID ordonné dans le temps, généré par l'entité.
     */
    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getDish(): Dish
    {
        return $this->dish;
    }

    public function setDish(Dish $dish): static
    {
        $this->dish = $dish;

        return $this;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function setOrder(Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }
}
