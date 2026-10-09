<?php

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\User\UserDetailsOutput;
use App\Entity\User;
use App\Service\UserService;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProviderInterface<UserDetailsOutput>
 */
final class UserMeProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly UserService $userService,
    ) {}

    /**
     * Résout le porteur authentifié vers sa propre représentation détaillée.
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): null|UserDetailsOutput {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return null;
        }

        return $this->userService->toDetails($user);
    }
}
