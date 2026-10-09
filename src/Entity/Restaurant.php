<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use App\Dto\Restaurant\RestaurantListOutput;
use App\Entity\Enum\Campus;
use App\Entity\Impl\AbstractEntity;
use App\Repository\RestaurantRepository;
use App\State\Restaurant\RestaurantCollectionProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        // GET /api/restaurants
        new GetCollection(
            uriTemplate: '/restaurants',
            provider: RestaurantCollectionProvider::class,
            output: RestaurantListOutput::class,
            paginationEnabled: false,
            openapi: new OpenApiOperation(security: []),
            parameters: [
                'q' => new QueryParameter(
                    description: 'Filtre textuel sur le nom du restaurant ou sur son campus (orleans, dijon, avignon). Insensible à la casse.',
                    schema: [
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 255,
                    ],
                ),
                'limit' => new QueryParameter(
                    description: 'Nombre maximum de restaurants retournés.',
                    schema: [
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => 100,
                        'default' => 20,
                    ],
                ),
            ]
        ),
    ]
)]
#[ORM\Entity(repositoryClass: RestaurantRepository::class)]
class Restaurant extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING, length: 32, enumType: Campus::class)]
    private ?Campus $campus = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCampus(): ?Campus
    {
        return $this->campus;
    }

    public function setCampus(Campus $campus): static
    {
        $this->campus = $campus;

        return $this;
    }
}
