<?php

namespace App\Tests\Api;

use App\DataFixtures\DishFixtures;
use App\Entity\Enum\OrderStatus;
use App\Entity\Order;
use App\Entity\User;
use App\Tests\ApiTestCaseBase;

class PaymentTest extends ApiTestCaseBase
{
    public function testPayingMyOrderMarksItPaid(): void
    {
        $client = $this->authed(self::ALICE);
        $orderId = $this->orderIdOf(self::ALICE, OrderStatus::Pending);

        $response = $client->request('POST', '/api/orders/'.$orderId.'/pay', [
            'json' => ['paymentMethod' => 'card'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertMatchesRegularExpression('/^CE-\d{4}-[0-9A-F]{6}$/', $response->toArray()['reference']);

        // la commande payée n'est plus en cours : la collection est vide
        self::assertJsonSame([], $client->request('GET', '/api/orders')->toArray(), 'les commandes en cours après le paiement');

        // une commande supprimée viderait aussi la collection : le statut se lit en base, et la
        // commande n'a pas été supprimée en douceur
        self::assertJsonSame(['status' => 'paid', 'deleted_at' => null], $this->orderRow($orderId), 'la commande en base après le paiement');

        // la réponse annonce des bons : ils doivent aussi être enregistrés, un par ligne visible
        $this->assertSame(2, $this->kitchenTicketCount($orderId));
    }

    public function testPayingGivesAPickupReferenceDerivedFromTheOrder(): void
    {
        $orderId = $this->orderIdOf(self::ALICE, OrderStatus::Pending);

        $data = $this->authed(self::ALICE)->request('POST', '/api/orders/'.$orderId.'/pay', [
            'json' => ['paymentMethod' => 'card'],
        ])->toArray();

        // la référence se lit au comptoir : l'année d'ouverture, puis la fin de l'identifiant
        $this->assertSame(sprintf('CE-%s-%s', date('Y'), strtoupper(substr($orderId, -6))), $data['reference']);
    }

    public function testPayingEmitsOneKitchenTicketPerLine(): void
    {
        $orderId = $this->orderIdOf(self::ALICE, OrderStatus::Pending);

        $data = $this->authed(self::ALICE)->request('POST', '/api/orders/'.$orderId.'/pay', [
            'json' => ['paymentMethod' => 'voucher'],
        ])->toArray();

        // la commande d'Alice en fixture : trois lignes, dont une retirée, qui n'appelle aucun bon ;
        // restent ses deux plats visibles, en une et quatre portions
        $this->assertCount(2, $data['kitchenTickets']);
        $expected = [
            [DishFixtures::SCENARIO_DISHES[0][0], 1],
            [DishFixtures::SCENARIO_DISHES[3][0], 4],
        ];
        $issued = array_map(
            static fn (array $kitchenTicket): array => [$kitchenTicket['dish']['name'], $kitchenTicket['quantity']],
            $data['kitchenTickets'],
        );
        sort($expected);
        sort($issued);
        self::assertJsonSame($expected, $issued, 'les bons émis (plat, quantité)');
    }

    public function testPayingSomeoneElsesOrderIsForbidden(): void
    {
        $this->authed(self::BOB)->request('POST', '/api/orders/'.$this->orderIdOf(self::ALICE, OrderStatus::Pending).'/pay', [
            'json' => ['paymentMethod' => 'card'],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPayingAPaidOrderIsAConflict(): void
    {
        $this->authed(self::BOB)->request('POST', '/api/orders/'.$this->orderIdOf(self::BOB, OrderStatus::Paid).'/pay', [
            'json' => ['paymentMethod' => 'card'],
        ]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertJsonContains(['detail' => 'Commande déjà payée']);
    }

    public function testPayingAnEmptyOrderIsAConflict(): void
    {
        $client = $this->authed(self::CAMILLE);
        $opened = $client->request('POST', '/api/orders')->toArray();

        $client->request('POST', '/api/orders/'.$opened['id'].'/pay', [
            'json' => ['paymentMethod' => 'card'],
        ]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertJsonContains(['detail' => 'Commande vide']);
    }

    public function testPayingWithAnUnknownMethodIsUnprocessable(): void
    {
        $this->authed(self::ALICE)->request('POST', '/api/orders/'.$this->orderIdOf(self::ALICE, OrderStatus::Pending).'/pay', [
            'json' => ['paymentMethod' => 'bitcoin'],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['violations' => [['propertyPath' => 'paymentMethod']]]);
    }

    public function testListingKitchenTicketsReturnsMine(): void
    {
        $kitchenTickets = $this->authed(self::BOB)->request('GET', '/api/kitchen-tickets')->toArray();

        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(2, $kitchenTickets);

        // seul Bob a des bons en fixture : Alice n'en voit aucun, chacun ne voit que les siens
        self::assertJsonSame([], $this->authed(self::ALICE)->request('GET', '/api/kitchen-tickets')->toArray(), 'les bons de cuisine d\'Alice');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testListingKitchenTicketsWithoutTokenIsUnauthorized(): void
    {
        static::createClient()->request('GET', '/api/kitchen-tickets');

        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * Lit l'état de la commande directement en SQL : la connexion est celle de la requête, sous la
     * même transaction de test, sans passer par une unité de travail qui pourrait servir un état périmé.
     *
     * @return array{status: string, deleted_at: ?string}
     */
    private function orderRow(string $orderId): array
    {
        return static::getContainer()->get('doctrine')->getConnection()->fetchAssociative(
            'SELECT status, deleted_at FROM "order" WHERE id = :id',
            ['id' => $orderId],
        );
    }

    /**
     * Compte en SQL les bons enregistrés pour cette commande, sous la même transaction de test : ce
     * que la réponse annonce ne suffit pas à prouver qu'ils ont été écrits.
     */
    private function kitchenTicketCount(string $orderId): int
    {
        return (int) static::getContainer()->get('doctrine')->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM kitchen_ticket WHERE order_id = :id',
            ['id' => $orderId],
        );
    }

    private function orderIdOf(string $email, OrderStatus $status): string
    {
        $user = $this->repository(User::class)->findOneBy(['email' => $email]);

        return $this->repository(Order::class)
            ->findOneBy(['createdBy' => $user, 'status' => $status])
            ->getId()
            ->toRfc4122();
    }
}
