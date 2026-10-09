<?php

namespace App\Dto\User;

use ApiPlatform\Metadata\ApiProperty;
use DateTimeImmutable;

class UserDetailsOutput
{
    public function __construct(
        #[ApiProperty(schema: [
            'type' => 'string',
            'description' => 'L\'identifiant de l\'utilisateur',
            'format' => 'uuid',
        ])]
        public string $id,

        #[ApiProperty(schema: [
            'type' => 'string',
            'description' => 'L\'email de l\'utilisateur',
            'format' => 'email',
        ])]
        public string $email,

        #[ApiProperty(schema: [
            'type' => 'string',
            'description' => 'Le prénom de l\'utilisateur',
            'nullable' => true,
        ])]
        public null|string $firstName,

        #[ApiProperty(schema: [
            'type' => 'string',
            'description' => 'Le nom de l\'utilisateur',
            'nullable' => true,
        ])]
        public null|string $lastName,

        #[ApiProperty(schema: [
            'type' => 'string',
            'description' => 'La date de création de l\'utilisateur',
            'format' => 'date-time',
        ])]
        public DateTimeImmutable $createdAt,
    ) {
    }
}
