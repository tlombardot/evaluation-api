<?php

namespace App\Tests\Api;

use App\Entity\Enum\OrderStatus;
use App\Entity\Order;
use App\Entity\User;
use App\Tests\ApiTestCaseBase;

class OrderTest extends ApiTestCaseBase
{
    public function testOpeningAnOrderWithoutTokenIsUnauthorized(): void
    {
        static::createClient()->request('POST', '/api/orders');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testOpeningAnOrderCreatesAPendingOne(): void
    {
        $response = $this->authed(self::CAMILLE)->request('POST', '/api/orders');

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame('pending', $data['status']);
        self::assertJsonSame([], $data['lines'], 'les lignes de la commande ouverte');
        $this->assertSame(0, $data['total']);
    }

    public function testOpeningAnOrderTwiceReturnsTheSameOne(): void
    {
        $client = $this->authed(self::CAMILLE);

        $first = $client->request('POST', '/api/orders')->toArray();
        $second = $client->request('POST', '/api/orders')->toArray();

        $this->assertSame($first['id'], $second['id']);
    }

    public function testOpeningAnOrderReturnsThePendingOneWhenItExists(): void
    {
        $response = $this->authed(self::ALICE)->request('POST', '/api/orders');

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame($this->alicePendingOrderId(), $response->toArray()['id']);
    }

    public function testOpeningAnOrderIgnoresAnAbandonedOne(): void
    {
        $data = $this->authed(self::BOB)->request('POST', '/api/orders')->toArray();

        $this->assertResponseStatusCodeSame(201);
        // la commande abandonnée de Bob ne revient pas : il en ouvre une neuve, vide
        self::assertJsonSame([], $data['lines'], 'les lignes de la commande ouverte par Bob');
        $this->assertSame(0, $data['total']);
    }

    public function testListingOrdersReturnsThePendingOne(): void
    {
        $response = $this->authed(self::ALICE)->request('GET', '/api/orders');

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertCount(1, $data);
        $this->assertSame('pending', $data[0]['status']);
        $this->assertSame($this->alicePendingOrderId(), $data[0]['id']);
    }

    public function testListingOrdersIsEmptyWithoutPendingOrder(): void
    {
        $response = $this->authed(self::CAMILLE)->request('GET', '/api/orders');

        $this->assertResponseStatusCodeSame(200);
        self::assertJsonSame([], $response->toArray(), 'les commandes en cours de Camille');
    }

    private function alicePendingOrderId(): string
    {
        $alice = $this->repository(User::class)->findOneBy(['email' => self::ALICE]);
        $order = $this->repository(Order::class)->findOneBy(['createdBy' => $alice, 'status' => OrderStatus::Pending]);

        return $order->getId()->toRfc4122();
    }
}
