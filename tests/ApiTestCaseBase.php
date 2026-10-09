<?php

namespace App\Tests;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Test\Client;
use Doctrine\ORM\EntityRepository;

/**
 * Socle des tests d'API : clients authentifiés et accès aux dépôts.
 */
abstract class ApiTestCaseBase extends ApiTestCase
{
    protected const ALICE = 'alice@example.fr';
    protected const BOB = 'bob@example.fr';
    protected const CAMILLE = 'camille.aubert@example.fr';

    private const PASSWORD = 'motdepasse';

    protected static ?bool $alwaysBootKernel = false;

    /**
     * L'API ne sert que du JSON : le client le demande par défaut
     * (API Platform annonce sinon du JSON-LD, refusé en 406).
     */
    protected static function createClient(array $kernelOptions = [], array $defaultOptions = []): Client
    {
        $defaultOptions['headers']['Accept'] ??= 'application/json';

        return parent::createClient($kernelOptions, $defaultOptions);
    }

    /**
     * Compare le statut de la dernière réponse. S'il diffère, l'échec tient en une ligne : le
     * statut reçu et le message de l'API, au lieu de recopier toute la réponse.
     */
    public static function assertResponseStatusCodeSame(int $expectedCode, string $message = '', ?bool $verbose = null): void
    {
        $response = static::getClient()?->getResponse();

        if (null === $response) {
            parent::assertResponseStatusCodeSame($expectedCode, $message, $verbose);

            return;
        }

        if ('' === $message) {
            $body = json_decode((string) $response->getContent(), true);
            $detail = \is_array($body) ? ($body['detail'] ?? $body['message'] ?? $body['title'] ?? null) : null;
            $message = \sprintf(
                'Statut %d attendu, %d reçu%s',
                $expectedCode,
                $response->getStatusCode(),
                \is_string($detail) ? ' : '.$detail : '',
            );
        }

        static::assertSame($expectedCode, $response->getStatusCode(), $message);
    }

    /**
     * Compare deux valeurs telles que l'API les sert, en JSON : si elles diffèrent, la différence
     * s'affiche en JSON, précédée de ce que la valeur représente.
     */
    protected static function assertJsonSame(mixed $expected, mixed $actual, string $what): void
    {
        $flags = \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES;

        static::assertSame(json_encode($expected, $flags), json_encode($actual, $flags), $what.' : JSON reçu différent de l\'attendu');
    }

    /**
     * Obtient un jeton JWT en passant par la vraie route de connexion.
     */
    protected function tokenFor(string $email): string
    {
        $response = static::createClient()->request('POST', '/api/auth/login', [
            'json' => ['email' => $email, 'password' => self::PASSWORD],
        ]);

        return $response->toArray()['token'];
    }

    /**
     * Client dont chaque requête porte le jeton de l'utilisateur.
     */
    protected function authed(string $email): Client
    {
        return static::createClient([], [
            'headers' => ['Authorization' => 'Bearer '.$this->tokenFor($email)],
        ]);
    }

    /**
     * @param class-string $entityClass
     */
    protected function repository(string $entityClass): EntityRepository
    {
        return static::getContainer()->get('doctrine')->getRepository($entityClass);
    }
}
