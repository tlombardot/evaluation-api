<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

#[AsDecorator('api_platform.openapi.factory', priority: -1)]
final class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        #[AutowireDecorated] private readonly OpenApiFactoryInterface $decorated,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $paths = $openApi->getPaths();

        $schemes = $openApi->getComponents()->getSecuritySchemes();
        unset($schemes['JWT']);

        $paths->addPath('/api/auth/login', new PathItem(post: new Operation(
            operationId: 'login',
            tags: ['Authentification'],
            summary: 'Se connecter',
            requestBody: new RequestBody(
                required: true,
                content: new ArrayObject([
                    'application/json' => ['schema' => [
                        'type' => 'object',
                        'required' => ['email', 'password'],
                        'properties' => [
                            'email' => ['type' => 'string', 'format' => 'email'],
                            'password' => ['type' => 'string', 'format' => 'password'],
                        ],
                    ]],
                ]),
            ),
            responses: [
                '200' => [
                    'description' => 'Jetons délivrés.',
                    'content' => new ArrayObject([
                        'application/json' => ['schema' => [
                            'type' => 'object',
                            'required' => ['token', 'refreshToken'],
                            'properties' => [
                                'token' => ['type' => 'string'],
                                'refreshToken' => ['type' => 'string'],
                            ],
                        ]],
                    ]),
                ],
                '401' => ['description' => 'Identifiants invalides.'],
            ],
            security: [],
        )));

        $paths->addPath('/api/auth/refresh', new PathItem(post: new Operation(
            operationId: 'refresh',
            tags: ['Authentification'],
            summary: 'Renouveler le jeton d\'accès',
            requestBody: new RequestBody(
                required: true,
                content: new ArrayObject([
                    'application/json' => ['schema' => [
                        'type' => 'object',
                        'required' => ['refreshToken'],
                        'properties' => [
                            'refreshToken' => ['type' => 'string'],
                        ],
                    ]],
                ]),
            ),
            responses: [
                '200' => [
                    'description' => 'Jeton d\'accès renouvelé.',
                    'content' => new ArrayObject([
                        'application/json' => ['schema' => [
                            'type' => 'object',
                            'required' => ['token', 'refreshToken'],
                            'properties' => [
                                'token' => ['type' => 'string'],
                                'refreshToken' => ['type' => 'string'],
                            ],
                        ]],
                    ]),
                ],
                '401' => ['description' => 'Jeton de renouvellement invalide ou expiré.'],
            ],
            security: [],
        )));

        return $openApi->withPaths($paths);
    }
}
