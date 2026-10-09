<?php

namespace App\Dto\Dish;

use ApiPlatform\Metadata\ApiProperty;
use App\Dto\Restaurant\RestaurantListOutput;
use App\Entity\Enum\DishCategory;
use App\Entity\Enum\EcoScore;
use Symfony\Component\Uid\Uuid;

final class DishDetailsOutput extends DishListOutput
{
    public function __construct(
        Uuid $id,
        string $name,
        DishCategory $category,
        EcoScore $ecoScore,
        int $price,
        RestaurantListOutput $restaurant,

        #[ApiProperty(description: 'Description du plat.')]
        public readonly string $description,
    ) {
        // les six champs du résumé ne font que traverser : ils repartent tels quels au parent
        parent::__construct($id, $name, $category, $ecoScore, $price, $restaurant);
    }
}
