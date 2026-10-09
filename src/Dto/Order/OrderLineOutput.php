<?php

namespace App\Dto\Order;

use ApiPlatform\Metadata\ApiProperty;
use App\Dto\Dish\DishListOutput;
use Symfony\Component\Uid\Uuid;

final class OrderLineOutput
{
    public function __construct(
        #[ApiProperty(schema: [
            'type' => 'string',
            'format' => 'uuid',
            'description' => 'Identifiant de la ligne.',
        ])]
        public readonly Uuid $id,

        #[ApiProperty(description: 'Plat commandé.')]
        public readonly DishListOutput $dish,

        #[ApiProperty(schema: [
            'type' => 'integer',
            'description' => 'Nombre de portions commandées.',
        ])]
        public readonly int $quantity,

        #[ApiProperty(schema: [
            'type' => 'integer',
            'description' => 'Prix du plat multiplié par la quantité, en centimes.',
        ])]
        public readonly int $subtotal,
    ) {
    }
}
