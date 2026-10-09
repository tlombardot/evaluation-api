<?php

namespace App\Dto\Order;

use ApiPlatform\Metadata\ApiProperty;
use App\Dto\KitchenTicket\KitchenTicketListOutput;

final class OrderPayOutput
{
    /**
     * @param KitchenTicketListOutput[] $kitchenTickets
     */
    public function __construct(
        #[ApiProperty(schema: [
            'type' => 'string',
            'description' => 'Référence de retrait, à présenter au comptoir.',
            'example' => 'CE-2026-4F2A9C',
        ])]
        public readonly string $reference,

        /**
         * @var KitchenTicketListOutput[] $kitchenTickets
         */
        #[ApiProperty(description: 'Bons de cuisine émis, un par ligne de la commande.')]
        public readonly array $kitchenTickets,
    ) {
    }
}
