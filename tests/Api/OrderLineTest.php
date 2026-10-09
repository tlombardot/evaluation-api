<?php

namespace App\Tests\Api;

use App\DataFixtures\DishFixtures;
use App\DataFixtures\OrderFixtures;
use App\Entity\Dish;
use App\Entity\Enum\OrderStatus;
use App\Entity\Order;
use App\Entity\User;
use App\Tests\ApiTestCaseBase;
use Symfony\Component\Uid\Uuid;

class OrderLineTest extends ApiTestCaseBase
{
    public function testAddingALineToMyOrder(): void
    {
        $client = $this->authed(self::ALICE);
        $before = $client->request('POST', '/api/orders')->toArray();

        $response = $client->request('POST', '/api/orders/'.$before['id'].'/lines', [
            'json' => ['dishId' => $this->refectoireDishId(5), 'quantity' => 2],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertCount(count($before['lines']) + 1, $data['lines']);
        $this->assertSame($before['total'] + 2 * DishFixtures::SCENARIO_DISHES[5][3], $data['total']);
    }

    public function testAddingTheSameDishTwiceCreatesTwoLines(): void
    {
        $client = $this->authed(self::ALICE);
        $orderId = $this->pendingOrderIdOf(self::ALICE);
        $dishId = $this->refectoireDishId(5);

        $client->request('POST', '/api/orders/'.$orderId.'/lines', ['json' => ['dishId' => $dishId, 'quantity' => 1]]);
        $data = $client->request('POST', '/api/orders/'.$orderId.'/lines', ['json' => ['dishId' => $dishId, 'quantity' => 1]])->toArray();

        $this->assertResponseStatusCodeSame(201);
        $sameDish = array_filter($data['lines'], static fn (array $line): bool => $line['dish']['id'] === $dishId);
        $this->assertCount(2, $sameDish);
        $this->assertCount(2, array_unique(array_column($sameDish, 'id')));
    }

    public function testAddingALineToSomeoneElsesOrderIsForbidden(): void
    {
        $this->authed(self::BOB)->request('POST', '/api/orders/'.$this->pendingOrderIdOf(self::ALICE).'/lines', [
            'json' => ['dishId' => $this->refectoireDishId(5), 'quantity' => 1],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAddingALineToAnUnknownOrderIsNotFound(): void
    {
        $this->authed(self::ALICE)->request('POST', '/api/orders/'.Uuid::v7()->toRfc4122().'/lines', [
            'json' => ['dishId' => $this->refectoireDishId(5), 'quantity' => 1],
        ]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains(['detail' => 'Commande non trouvée']);
    }

    public function testAddingALineToAMalformedOrderIdIsNotFound(): void
    {
        $response = $this->authed(self::ALICE)->request('POST', '/api/orders/pas-un-uuid/lines', [
            'json' => ['dishId' => $this->refectoireDishId(5), 'quantity' => 1],
        ]);

        $this->assertResponseStatusCodeSame(404);
        // un identifiant qui n'a pas la forme d'un UUID ne désigne aucun chemin de l'API : la réponse
        // n'est pas celle d'une commande inconnue
        $this->assertStringStartsWith('No route found', $response->toArray(false)['detail']);
    }

    public function testAddingALineToAPaidOrderIsAConflict(): void
    {
        $order = $this->paidOrderOfBob();

        $this->authed(self::BOB)->request('POST', '/api/orders/'.$order->getId()->toRfc4122().'/lines', [
            'json' => ['dishId' => $order->getLines()->first()->getDish()->getId()->toRfc4122(), 'quantity' => 1],
        ]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertJsonContains(['detail' => 'Commande déjà payée']);
    }

    public function testAddingADishFromAnotherRestaurantIsAConflict(): void
    {
        $this->authed(self::ALICE)->request('POST', '/api/orders/'.$this->pendingOrderIdOf(self::ALICE).'/lines', [
            'json' => ['dishId' => $this->anyDishIdOf('La Moutarde'), 'quantity' => 1],
        ]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertJsonContains(['detail' => 'Une commande ne mélange pas les restaurants']);
    }

    public function testAddingALineWithAMalformedDishIdIsUnprocessable(): void
    {
        $this->authed(self::ALICE)->request('POST', '/api/orders/'.$this->pendingOrderIdOf(self::ALICE).'/lines', [
            'json' => ['dishId' => 'pas-un-uuid', 'quantity' => 1],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['violations' => [['propertyPath' => 'dishId']]]);
    }

    public function testAddingALineWithAZeroQuantityIsUnprocessable(): void
    {
        $this->authed(self::ALICE)->request('POST', '/api/orders/'.$this->pendingOrderIdOf(self::ALICE).'/lines', [
            'json' => ['dishId' => $this->refectoireDishId(5), 'quantity' => 0],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['violations' => [['propertyPath' => 'quantity']]]);
    }

    public function testRemovingALineFromMyOrder(): void
    {
        $client = $this->authed(self::ALICE);
        $before = $client->request('POST', '/api/orders')->toArray();

        $removed = $this->lineOf($before, DishFixtures::SCENARIO_DISHES[0][0]);

        $client->request('DELETE', '/api/orders/'.$before['id'].'/lines/'.$removed['id']);

        $this->assertResponseStatusCodeSame(204);
        $after = $client->request('GET', '/api/orders')->toArray();
        $this->assertCount(count($before['lines']) - 1, $after[0]['lines']);
        $this->assertNotContains($removed['id'], array_column($after[0]['lines'], 'id'));
    }

    public function testRemovingALineFromSomeoneElsesOrderIsForbidden(): void
    {
        $alice = $this->authed(self::ALICE)->request('POST', '/api/orders')->toArray();

        $line = $this->lineOf($alice, DishFixtures::SCENARIO_DISHES[0][0]);

        $this->authed(self::BOB)->request('DELETE', '/api/orders/'.$alice['id'].'/lines/'.$line['id']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRemovingAnUnknownLineIsNotFound(): void
    {
        $this->authed(self::ALICE)->request(
            'DELETE',
            '/api/orders/'.$this->pendingOrderIdOf(self::ALICE).'/lines/'.Uuid::v7()->toRfc4122(),
        );

        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains(['detail' => 'Ligne de commande non trouvée']);
    }

    public function testRemovingALineFromAPaidOrderIsAConflict(): void
    {
        $order = $this->paidOrderOfBob();

        $this->authed(self::BOB)->request(
            'DELETE',
            '/api/orders/'.$order->getId()->toRfc4122().'/lines/'.$order->getLines()->first()->getId()->toRfc4122(),
        );

        $this->assertResponseStatusCodeSame(409);
        $this->assertJsonContains(['detail' => 'Commande déjà payée']);
    }

    public function testSoftDeletedLinesStayHidden(): void
    {
        $client = $this->authed(self::ALICE);

        $orders = $client->request('GET', '/api/orders')->toArray();

        // trois lignes en base, dont une retirée : seules deux restent visibles
        $this->assertCount(2, $orders[0]['lines']);
        $this->assertNotContains(OrderFixtures::ALICE_DELETED_LINE_DISH, array_column(array_column($orders[0]['lines'], 'dish'), 'name'));

        $client->request('DELETE', '/api/orders/'.$orders[0]['id'].'/lines/'.$this->aliceDeletedLineId());

        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains(['detail' => 'Ligne de commande non trouvée']);
    }

    public function testRemovingTheLastLineClosesTheOrder(): void
    {
        $client = $this->authed(self::CAMILLE);
        $opened = $client->request('POST', '/api/orders')->toArray();
        $filled = $client->request('POST', '/api/orders/'.$opened['id'].'/lines', [
            'json' => ['dishId' => $this->refectoireDishId(0), 'quantity' => 1],
        ])->toArray();

        $client->request('DELETE', '/api/orders/'.$opened['id'].'/lines/'.$this->lineOf($filled, DishFixtures::SCENARIO_DISHES[0][0])['id']);
        $this->assertResponseStatusCodeSame(204);

        // la commande est partie avec sa dernière ligne : il n'y a plus de commande en cours
        self::assertJsonSame([], $client->request('GET', '/api/orders')->toArray(), 'les commandes en cours après le retrait de la dernière ligne');

        $reopened = $client->request('POST', '/api/orders')->toArray();
        $this->assertResponseStatusCodeSame(201);
        $this->assertNotSame($opened['id'], $reopened['id']);
        self::assertJsonSame([], $reopened['lines'], 'les lignes de la commande rouverte');
    }

    private function pendingOrderIdOf(string $email): string
    {
        $user = $this->repository(User::class)->findOneBy(['email' => $email]);

        return $this->repository(Order::class)
            ->findOneBy(['createdBy' => $user, 'status' => OrderStatus::Pending])
            ->getId()
            ->toRfc4122();
    }

    private function paidOrderOfBob(): Order
    {
        $bob = $this->repository(User::class)->findOneBy(['email' => self::BOB]);

        return $this->repository(Order::class)->findOneBy(['createdBy' => $bob, 'status' => OrderStatus::Paid]);
    }

    private function refectoireDishId(int $position): string
    {
        return $this->repository(Dish::class)
            ->findOneBy(['name' => DishFixtures::SCENARIO_DISHES[$position][0]])
            ->getId()
            ->toRfc4122();
    }

    private function anyDishIdOf(string $restaurantName): string
    {
        return $this->repository(Dish::class)->createQueryBuilder('d')
            ->join('d.restaurant', 'r')
            ->andWhere('r.name = :name')
            ->setParameter('name', $restaurantName)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult()
            ->getId()
            ->toRfc4122();
    }

    /**
     * Retrouve dans une commande rendue par l'API la ligne de ce plat : l'ordre des lignes ne fait
     * pas partie du contrat.
     *
     * @param array{lines: list<array{id: string, dish: array{name: string}}>} $order
     *
     * @return array{id: string, dish: array{name: string}}
     */
    private function lineOf(array $order, string $dishName): array
    {
        $matching = array_values(array_filter(
            $order['lines'],
            static fn (array $line): bool => $line['dish']['name'] === $dishName,
        ));
        $this->assertCount(1, $matching, 'une seule ligne attendue pour le plat '.$dishName);

        return $matching[0];
    }

    /**
     * Lit l'identifiant de la ligne retirée directement en SQL : l'ORM ne la sert pas, et la charger
     * malgré tout la laisserait dans l'unité de travail que la requête suivante partage avec le test.
     */
    private function aliceDeletedLineId(): string
    {
        return static::getContainer()->get('doctrine')->getConnection()->fetchOne(
            'SELECT l.id FROM order_line l JOIN dish d ON d.id = l.dish_id
             WHERE d.name = :name AND l.deleted_at IS NOT NULL',
            ['name' => OrderFixtures::ALICE_DELETED_LINE_DISH],
        );
    }
}
