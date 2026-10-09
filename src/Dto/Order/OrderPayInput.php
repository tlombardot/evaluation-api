<?php

namespace App\Dto\Order;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Validator\Constraints as Assert;

final class OrderPayInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['card', 'voucher'])]
        #[ApiProperty(schema: [
            'type' => 'string',
            'enum' => ['card', 'voucher'],
            'description' => 'Moyen de paiement déclaré : card (carte) ou voucher (titre-restaurant). Il n\'est pas conservé.',
            'example' => 'card',
        ], required: true)]
        // valeur par défaut vide : sans elle, un corps sans `paymentMethod` échoue à la construction du DTO
        // (400) avant toute validation ; avec elle, NotBlank le rejette en 422, violation nommée à l'appui
        public string $paymentMethod = '',
    ) {
    }
}
