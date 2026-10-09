<?php

namespace App\Dto\Order;

use ApiPlatform\Metadata\ApiProperty;
use App\Entity\Enum\OrderStatus;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class OrderDetailsOutput
{
    /**
     * @param OrderLineOutput[] $lines
     */
    public function __construct(
        #[ApiProperty(schema: [
            'type' => 'string',
            'format' => 'uuid',
            'description' => 'Identifiant de la commande.',
        ])]
        public readonly Uuid $id,

        #[ApiProperty(description: 'État de la commande : en attente ou payée.')]
        public readonly OrderStatus $status,

        /**
         * @var OrderLineOutput[] $lines
         */
        #[ApiProperty(description: 'Lignes de la commande.')]
        public readonly array $lines,

        #[ApiProperty(schema: [
            'type' => 'integer',
            'description' => 'Somme des sous-totaux des lignes, en centimes.',
        ])]
        public readonly int $total,

        #[ApiProperty(schema: [
            'type' => 'string',
            'format' => 'date-time',
            'description' => "Date d'ouverture de la commande.",
        ])]
        public readonly DateTimeImmutable $createdAt,
    ) {
    }
}
