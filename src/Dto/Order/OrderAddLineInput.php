<?php

namespace App\Dto\Order;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Validator\Constraints as Assert;

final class OrderAddLineInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        #[ApiProperty(schema: [
            'type' => 'string',
            'format' => 'uuid',
            'description' => 'Identifiant du plat à ajouter.',
        ], required: true)]
        public string $dishId,

        #[Assert\NotBlank]
        #[Assert\Positive]
        #[ApiProperty(schema: [
            'type' => 'integer',
            'description' => 'Nombre de portions du plat.',
            'minimum' => 0,
        ], required: true)]
        public int $quantity,
    ) {
    }
}
