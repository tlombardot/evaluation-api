<?php

namespace App\Dto\Dish;

use ApiPlatform\Metadata\ApiProperty;
use App\Entity\Enum\DishCategory;
use App\Entity\Enum\EcoScore;
use Symfony\Component\Validator\Constraints as Assert;

class DishSearchInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        #[ApiProperty(schema: [
            'type' => 'string',
            'format' => 'uuid',
            'description' => 'UUID du restaurant dont on consulte la carte',
        ], required: true)]
        // valeur par défaut vide : sans elle, un corps sans `restaurantId` échoue à la construction du DTO
        // (400) avant toute validation ; avec elle, NotBlank le rejette en 422, violation nommée à l'appui
        public string $restaurantId = '',

        #[Assert\Choice(callback: [self::class, 'categories'])]
        #[ApiProperty(schema: [
            'type' => 'string',
            'enum' => ['starter', 'main', 'dessert'],
            'description' => 'Catégorie de plat recherchée',
        ])]
        public ?string $category = null,

        #[Assert\Choice(callback: [self::class, 'ecoScores'])]
        #[ApiProperty(schema: [
            'type' => 'string',
            'enum' => ['A', 'B', 'C', 'D', 'E'],
            'description' => 'Éco-score minimum : « B » garde les plats notés A ou B',
        ])]
        public ?string $minEcoScore = null,

        #[Assert\Positive]
        #[ApiProperty(schema: [
            'type' => 'integer',
            'description' => 'Prix maximum, en centimes',
        ])]
        public ?int $maxPrice = null,
    ) {
    }

    /**
     * @return string[]
     */
    public static function categories(): array
    {
        return array_column(DishCategory::cases(), 'value');
    }

    /**
     * @return string[]
     */
    public static function ecoScores(): array
    {
        return array_column(EcoScore::cases(), 'value');
    }
}
