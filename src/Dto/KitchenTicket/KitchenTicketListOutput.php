<?php

namespace App\Dto\KitchenTicket;

use ApiPlatform\Metadata\ApiProperty;
use App\Dto\Dish\DishListOutput;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class KitchenTicketListOutput
{
    public function __construct(
        #[ApiProperty(schema: [
            'type' => 'string',
            'format' => 'uuid',
            'description' => 'Identifiant du bon de cuisine.',
        ])]
        public readonly Uuid $id,

        #[ApiProperty(description: 'Plat à préparer.')]
        public readonly DishListOutput $dish,

        #[ApiProperty(schema: [
            'type' => 'integer',
            'description' => 'Nombre de portions à préparer.',
        ])]
        public readonly int $quantity,

        #[ApiProperty(schema: [
            'type' => 'string',
            'format' => 'date-time',
            'description' => "Date d'émission du bon.",
        ])]
        public readonly DateTimeImmutable $createdAt,
    ) {
    }
}
