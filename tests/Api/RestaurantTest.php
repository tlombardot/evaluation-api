<?php

namespace App\Tests\Api;

use App\Tests\ApiTestCaseBase;

class RestaurantTest extends ApiTestCaseBase
{
    public function testListingRestaurantsReturnsAllNine(): void
    {
        $response = static::createClient()->request('GET', '/api/restaurants');

        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(9, $response->toArray());
    }

    public function testListingRestaurantsFiltersByName(): void
    {
        $response = static::createClient()->request('GET', '/api/restaurants?q=moutarde');

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertCount(1, $data);
        $this->assertSame('La Moutarde', $data[0]['name']);
    }

    public function testListingRestaurantsIgnoresCase(): void
    {
        $data = static::createClient()->request('GET', '/api/restaurants?q=MOUTARDE')->toArray();

        $this->assertResponseStatusCodeSame(200);
        self::assertJsonSame(['La Moutarde'], array_column($data, 'name'), 'les restaurants trouvés');
    }

    public function testListingRestaurantsFiltersByCampus(): void
    {
        $response = static::createClient()->request('GET', '/api/restaurants?q=dijon');

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertCount(3, $data);
        self::assertJsonSame(['dijon'], array_values(array_unique(array_column($data, 'campus'))), 'les campus des restaurants trouvés');
    }

    public function testListingRestaurantsHonoursTheLimit(): void
    {
        $response = static::createClient()->request('GET', '/api/restaurants?limit=2');

        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(2, $response->toArray());
    }
}
