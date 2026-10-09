<?php

namespace App\DataFixtures;

use App\Entity\Enum\Campus;
use App\Entity\Restaurant;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    /**
     * Les neuf restaurants des trois campus, par campus.
     */
    public const RESTAURANTS = [
        'orleans' => ['La Cantine de Loire', 'Le Comptoir des Halles', 'Le Martroi'],
        'dijon' => ['Le Réfectoire', 'La Moutarde', 'Chez Darcy'],
        'avignon' => ['Les Remparts', 'Le Palais', "La Cour d'Honneur"],
    ];

    private const PLAIN_PASSWORD = 'motdepasse';

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher    )
    {

    }

    public function load(ObjectManager $manager): void
    {
        #region Utilisateurs
        $aliceUser = new User()
            ->setEmail('alice@example.fr')
            ->setCreatedAt(new DateTimeImmutable());

        $password = $this->hasher->hashPassword($aliceUser, self::PLAIN_PASSWORD);
        $aliceUser->setPassword($password);

        $manager->persist($aliceUser);

        $bobUser = new User()
            ->setEmail('bob@example.fr')
            ->setCreatedAt(new DateTimeImmutable());

        $password = $this->hasher->hashPassword($bobUser, self::PLAIN_PASSWORD);
        $bobUser->setPassword($password);

        $manager->persist($bobUser);

        $camilleUser = new User()
            ->setFirstName("Camille")
            ->setLastName("Aubert")
            ->setEmail('camille.aubert@example.fr')
            ->setCreatedAt(new DateTimeImmutable("2026-02-04T09:00:00"));

        $password = $this->hasher->hashPassword($camilleUser, self::PLAIN_PASSWORD);
        $camilleUser->setPassword($password);

        $manager->persist($camilleUser);

        #endregion Utilisateurs

        #region Restaurants

        foreach (self::RESTAURANTS as $campus => $names) {
            foreach ($names as $name) {
                $restaurant = new Restaurant()
                    ->setName($name)
                    ->setCampus(Campus::from($campus))
                    ->setCreatedAt(new DateTimeImmutable());

                $manager->persist($restaurant);
            }
        }

        #endregion Restaurants

        $manager->flush();
    }
}
