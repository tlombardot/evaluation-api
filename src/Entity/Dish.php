<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\Response as OpenApiResponse;
use App\Dto\Dish\DishDetailsOutput;
use App\Dto\Dish\DishListOutput;
use App\Dto\Dish\DishSearchInput;
use App\Entity\Enum\DishCategory;
use App\Entity\Enum\EcoScore;
use App\Entity\Impl\AbstractEntity;
use App\Repository\DishRepository;
use App\State\Dish\DishItemProvider;
use App\State\Dish\DishSearchProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ApiResource(operations: [
    new Post(
        uriTemplate: '/dishes/search',
        // un Post répond 201 par défaut : cette recherche ne crée rien, le contrat n'y déclare qu'un 200
        status: 200,
        input: DishSearchInput::class,
        output: DishListOutput::class,
        processor: DishSearchProcessor::class,
        // on consulte la carte sans être connecté : le contrat déclare l'opération publique
        openapi: new OpenApiOperation(
            security: [],
            // le générateur déduit la réponse du `output:`, qui nomme une classe et non un tableau :
            // il annonce un objet unique là où l'API rend une liste. On corrige la documentation.
            responses: ['200' => new OpenApiResponse(
                description: 'Les plats de la carte du restaurant',
                content: new \ArrayObject([
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => ['$ref' => '#/components/schemas/Dish.DishListOutput'],
                        ],
                    ],
                ]),
            )],
        ),
    ),
    new Get(
        uriTemplate: '/dishes/{id}',
        output: DishDetailsOutput::class,
        provider: DishItemProvider::class,
        // on consulte un plat sans être connecté : le contrat déclare l'opération publique
        openapi: new OpenApiOperation(security: []),
    ),
])]
#[ORM\Entity(repositoryClass: DishRepository::class)]
class Dish extends AbstractEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Restaurant $restaurant = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 16, enumType: DishCategory::class)]
    private ?DishCategory $category = null;

    #[ORM\Column(type: Types::STRING, length: 1, enumType: EcoScore::class)]
    private ?EcoScore $ecoScore = null;

    /**
     * Prix du plat, en centimes.
     */
    #[ORM\Column]
    private ?int $price = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getRestaurant(): ?Restaurant
    {
        return $this->restaurant;
    }

    public function setRestaurant(Restaurant $restaurant): static
    {
        $this->restaurant = $restaurant;

        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCategory(): ?DishCategory
    {
        return $this->category;
    }

    public function setCategory(DishCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getEcoScore(): ?EcoScore
    {
        return $this->ecoScore;
    }

    public function setEcoScore(EcoScore $ecoScore): static
    {
        $this->ecoScore = $ecoScore;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }
}
