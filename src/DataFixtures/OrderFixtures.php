<?php

namespace App\DataFixtures;

use App\Entity\Dish;
use App\Entity\Enum\OrderStatus;
use App\Entity\KitchenTicket;
use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\User;
use App\Repository\DishRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Le plat de la ligne d'Alice supprimée en douceur : les tests de paiement et de suppression le visent.
     */
    public const ALICE_DELETED_LINE_DISH = DishFixtures::SCENARIO_DISHES[2][0];

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly DishRepository $dishRepository,
    ) {
    }

    public function getDependencies(): array
    {
        return [DishFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $now = new DateTimeImmutable();

        $alice = $this->userRepository->findOneBy(['email' => 'alice@example.fr']);
        $bob = $this->userRepository->findOneBy(['email' => 'bob@example.fr']);

        // Alice : une commande en attente au Réfectoire, trois lignes dont une supprimée en douceur
        $pending = $this->buildOrder($alice, OrderStatus::Pending, $now);

        foreach ([0, 2, 3] as $position) {
            $dish = $this->dishRepository->findOneBy(['name' => DishFixtures::SCENARIO_DISHES[$position][0]]);
            $line = $this->buildLine($alice, $dish, $position + 1, $now);

            if (self::ALICE_DELETED_LINE_DISH === $dish->getName()) {
                // pas d'AuditService ici : en CLI, il n'y a pas d'utilisateur connecté à estampiller
                $line->setDeletedAt($now)->setDeletedBy($alice);
            }

            $pending->addLine($line);
        }

        $manager->persist($pending);

        // Bob : une commande payée à La Cantine de Loire, deux lignes sur ses deux premiers plats par nom
        $paid = $this->buildOrder($bob, OrderStatus::Paid, $now);

        foreach ($this->firstDishesOf('La Cantine de Loire', 2) as $position => $dish) {
            $paid->addLine($this->buildLine($bob, $dish, $position + 1, $now));
        }

        $manager->persist($paid);

        // ses bons de cuisine, un par ligne, quantité copiée comme le ferait le paiement
        foreach ($paid->getLines() as $line) {
            $manager->persist(
                new KitchenTicket()
                    ->setDish($line->getDish())
                    ->setOrder($paid)
                    ->setQuantity($line->getQuantity())
                    ->setCreatedAt($now)
                    // Security::getUser() est nul en CLI : le propriétaire se pose explicitement
                    ->setCreatedBy($bob),
            );
        }

        // Bob a aussi une commande abandonnée : jamais payée, fermée par un traitement hors API
        $abandoned = $this->buildOrder($bob, OrderStatus::Pending, $now)->setDeletedAt($now);
        $abandoned->addLine($this->buildLine($bob, $this->firstDishesOf('La Cantine de Loire', 1)[0], 1, $now));

        $manager->persist($abandoned);

        // Camille n'a aucune commande
        $manager->flush();
    }

    /**
     * @return Dish[]
     */
    private function firstDishesOf(string $restaurantName, int $count): array
    {
        return $this->dishRepository->createQueryBuilder('d')
            ->join('d.restaurant', 'r')
            ->andWhere('r.name = :name')
            ->setParameter('name', $restaurantName)
            ->orderBy('d.name', 'ASC')
            ->setMaxResults($count)
            ->getQuery()
            ->getResult();
    }

    private function buildOrder(User $owner, OrderStatus $status, DateTimeImmutable $now): Order
    {
        return new Order()
            ->setStatus($status)
            ->setCreatedAt($now)
            // Security::getUser() est nul en CLI : le propriétaire se pose explicitement
            ->setCreatedBy($owner);
    }

    private function buildLine(User $owner, Dish $dish, int $quantity, DateTimeImmutable $now): OrderLine
    {
        return new OrderLine()
            ->setDish($dish)
            ->setQuantity($quantity)
            ->setCreatedAt($now)
            ->setCreatedBy($owner);
    }
}
