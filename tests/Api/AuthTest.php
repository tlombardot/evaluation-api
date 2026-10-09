<?php

namespace App\Tests\Api;

use App\Tests\ApiTestCaseBase;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AuthTest extends ApiTestCaseBase
{
    public function testRegisterCreatesAnAccount(): void
    {
        $response = static::createClient()->request('POST', '/api/auth/register', [
            'json' => ['email' => 'nouveau@example.fr', 'password' => 'Zx9!kLm#Qw27vB'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame('nouveau@example.fr', $data['email']);
        $this->assertArrayNotHasKey('password', $data);
    }

    public function testRegisterWithATakenEmailIsAConflict(): void
    {
        static::createClient()->request('POST', '/api/auth/register', [
            'json' => ['email' => self::ALICE, 'password' => 'Zx9!kLm#Qw27vB'],
        ]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertJsonContains(['detail' => 'Adresse email déjà utilisée']);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json');
    }

    public function testRegisterWithAnInvalidEmailIsUnprocessable(): void
    {
        $response = static::createClient()->request('POST', '/api/auth/register', [
            'json' => ['email' => 'pas-un-email', 'password' => 'Zx9!kLm#Qw27vB'],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $propertyPaths = array_column($response->toArray(false)['violations'], 'propertyPath');
        self::assertJsonSame(['email'], $propertyPaths, 'les champs en violation');
    }

    public function testLoginReturnsATokenPair(): void
    {
        $data = $this->login(self::ALICE, 'motdepasse')->toArray();

        $this->assertResponseStatusCodeSame(200);
        $this->assertNotEmpty($data['token']);
        $this->assertNotEmpty($data['refreshToken']);
    }

    public function testLoginWithAWrongPasswordIsUnauthorized(): void
    {
        $this->login(self::ALICE, 'mauvais');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRefreshReturnsANewToken(): void
    {
        $refreshToken = $this->login(self::ALICE, 'motdepasse')->toArray()['refreshToken'];

        $data = static::createClient()->request('POST', '/api/auth/refresh', [
            'json' => ['refreshToken' => $refreshToken],
        ])->toArray();

        $this->assertResponseStatusCodeSame(200);
        $this->assertNotEmpty($data['token']);
    }

    public function testRefreshingTwiceWithTheSameTokenIsUnauthorized(): void
    {
        $refreshToken = $this->login(self::ALICE, 'motdepasse')->toArray()['refreshToken'];
        $client = static::createClient();

        $client->request('POST', '/api/auth/refresh', ['json' => ['refreshToken' => $refreshToken]]);
        $this->assertResponseStatusCodeSame(200);

        // un jeton de renouvellement ne sert qu'une fois : le second appel avec le même est refusé
        $client->request('POST', '/api/auth/refresh', ['json' => ['refreshToken' => $refreshToken]]);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testRefreshWithAnUnknownTokenIsUnauthorized(): void
    {
        static::createClient()->request('POST', '/api/auth/refresh', [
            'json' => ['refreshToken' => 'inconnu'],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeReturnsTheBearer(): void
    {
        $data = $this->authed(self::ALICE)->request('GET', '/api/users/me')->toArray();

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(self::ALICE, $data['email']);
    }

    public function testMeWithoutTokenIsUnauthorized(): void
    {
        static::createClient()->request('GET', '/api/users/me');

        $this->assertResponseStatusCodeSame(401);
    }

    private function login(string $email, string $password): ResponseInterface
    {
        return static::createClient()->request('POST', '/api/auth/login', [
            'json' => ['email' => $email, 'password' => $password],
        ]);
    }
}
