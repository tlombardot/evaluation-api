<?php

namespace App\Dto\Dish;

use ApiPlatform\Metadata\ApiProperty;
use App\Dto\Restaurant\RestaurantListOutput;
use App\Entity\Enum\DishCategory;
use App\Entity\Enum\EcoScore;
use Symfony\Component\Uid\Uuid;

class DishListOutput
{
    public function __construct(
        #[ApiProperty(schema: [
            'type' => 'string',
            'format' => 'uuid',
            'description' => 'Identifiant unique du plat.',
        ])]
        public readonly Uuid $id,

        #[ApiProperty(description: 'Nom du plat.')]
        public readonly string $name,

        #[ApiProperty(description: 'Catégorie du plat.')]
        public readonly DishCategory $category,

        #[ApiProperty(description: 'Éco-score du plat, de A à E.')]
        public readonly EcoScore $ecoScore,

        #[ApiProperty(schema: [
            'type' => 'integer',
            'description' => 'Prix du plat en centimes.',
        ])]
        public readonly int $price,

        #[ApiProperty(description: 'Restaurant qui sert le plat.')]
        public readonly RestaurantListOutput $restaurant,
    ) {
    }
}
