<?php

namespace App\Tests\Api;

use App\Entity\Dish;
use App\Entity\Restaurant;
use App\Tests\ApiTestCaseBase;
use Symfony\Component\Uid\Uuid;

class DishTest extends ApiTestCaseBase
{
    public function testSearchingDishesOfARestaurant(): void
    {
        $response = static::createClient()->request('POST', '/api/dishes/search', [
            'json' => ['restaurantId' => $this->refectoireId()],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $prices = array_column($response->toArray(), 'price');
        $this->assertCount(8, $prices);
        $sorted = $prices;
        sort($sorted);
        self::assertJsonSame($sorted, $prices, 'les prix des plats, dans l\'ordre reçu');
    }

    public function testSearchingDishesByCategory(): void
    {
        $response = static::createClient()->request('POST', '/api/dishes/search', [
            'json' => ['restaurantId' => $this->refectoireId(), 'category' => 'main'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertCount(3, $data);
        self::assertJsonSame(['main'], array_values(array_unique(array_column($data, 'category'))), 'les catégories des plats reçus');
    }

    public function testSearchingDishesByMinimumEcoScore(): void
    {
        $response = static::createClient()->request('POST', '/api/dishes/search', [
            'json' => ['restaurantId' => $this->refectoireId(), 'minEcoScore' => 'B'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertCount(4, $data);
        foreach ($data as $dish) {
            $this->assertContains($dish['ecoScore'], ['A', 'B']);
        }
    }

    public function testSearchingDishesByMaximumPrice(): void
    {
        $response = static::createClient()->request('POST', '/api/dishes/search', [
            'json' => ['restaurantId' => $this->refectoireId(), 'maxPrice' => 600],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertCount(4, $data);
        foreach ($data as $dish) {
            $this->assertLessThanOrEqual(600, $dish['price']);
        }
    }

    public function testSearchingDishesWithoutRestaurantIsUnprocessable(): void
    {
        static::createClient()->request('POST', '/api/dishes/search', ['json' => []]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['violations' => [['propertyPath' => 'restaurantId']]]);
    }

    public function testSearchingDishesOfAnUnknownRestaurantIsNotFound(): void
    {
        static::createClient()->request('POST', '/api/dishes/search', [
            'json' => ['restaurantId' => Uuid::v7()->toRfc4122()],
        ]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains(['detail' => 'Restaurant non trouvé']);
    }

    public function testShowingADishReturnsItsDetails(): void
    {
        $dish = $this->repository(Dish::class)->findOneBy(['name' => 'Dahl de pois chiches']);

        $response = static::createClient()->request('GET', '/api/dishes/'.$dish->getId()->toRfc4122());

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertNotEmpty($data['description']);
        $this->assertSame('Le Réfectoire', $data['restaurant']['name']);
    }

    public function testShowingAnUnknownDishIsNotFound(): void
    {
        static::createClient()->request('GET', '/api/dishes/'.Uuid::v7()->toRfc4122());

        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains(['detail' => 'Plat non trouvé']);
    }

    /**
     * Identifiant du restaurant figé dont la carte est écrite à la main.
     */
    private function refectoireId(): string
    {
        return $this->repository(Restaurant::class)
            ->findOneBy(['name' => 'Le Réfectoire'])
            ->getId()
            ->toRfc4122();
    }
}
