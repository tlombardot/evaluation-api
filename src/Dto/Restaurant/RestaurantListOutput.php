<?php

namespace App\Dto\Restaurant;

use ApiPlatform\Metadata\ApiProperty;
use App\Entity\Enum\Campus;
use Symfony\Component\Uid\Uuid;

class RestaurantListOutput
{
    public function __construct(
        #[ApiProperty(schema: [
            'type' => 'string',
            'format' => 'uuid',
            'description' => 'Identifiant unique du restaurant.'
        ])]
        public readonly Uuid $id,
        #[ApiProperty(description: 'Nom du restaurant.')]
        public readonly string $name,
        #[ApiProperty(description: 'Campus où se trouve le restaurant.')]
        public readonly Campus $campus,
    ) {

    }
}
