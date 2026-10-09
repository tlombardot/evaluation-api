<?php

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\User\UserDetailsOutput;
use App\Dto\User\UserRegisterInput;
use App\Service\UserService;

/**
 * @implements ProcessorInterface<UserRegisterInput, UserDetailsOutput>
 */
final class UserRegisterProcessor implements ProcessorInterface
{
    public function __construct(private readonly UserService $userService) {}

    /**
     * Inscrit le compte envoyé et renvoie sa représentation détaillée.
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): UserDetailsOutput {
        // 1. Inscrire le user
        // 2. transformer le user qu'on vient de créer en UserDetailsOutput
        // 3. Retourner le UserDetailsOutput
        //
        // /!\ NE PAS TRANSFORMER LE USER ICI MAIS DANS LE SERVICE

        return $this->userService->toDetails(
            $this->userService->register($data),
        );
    }
}
