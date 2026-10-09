<?php

namespace App\Service;

use App\Dto\User\UserDetailsOutput;
use App\Dto\User\UserRegisterInput;
use App\Entity\User;
use App\Exception\User\EmailAlreadyUsedException;
use App\Repository\UserRepository;
use App\Service\Utils\AuditService;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly AuditService $audit,
        private readonly LoggerInterface $domainLogger,
    )
    {

    }

    public function toDetails(User $user): UserDetailsOutput
    {
        return new UserDetailsOutput(
            id: $user->getId(),
            email: $user->getEmail(),
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            createdAt: $user->getCreatedAt(),
        );
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->userRepository->findOneByEmail($email);
    }

    public function register(UserRegisterInput $input): User
    {
        $existingUser = $this->findOneByEmail($input->email);

        if ($existingUser) {
            $this->domainLogger->error("User registration - conflict : email already used");
            throw new EmailAlreadyUsedException();
        }

        $user = new User()
            ->setEmail($input->email)
            ->setFirstName($input->firstName)
            ->setLastName($input->lastName);

        $user->setPassword($this->passwordHasher->hashPassword($user, $input->password));

        $this->audit->stampCreation($user);

        $this->userRepository->persist($user);
        $this->userRepository->flush();

        $this->domainLogger->info('User registered', ['user_id' => $user->getId()->toRfc4122()]);

        return $user;
    }
}
