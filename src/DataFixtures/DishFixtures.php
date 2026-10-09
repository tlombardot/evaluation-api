<?php

namespace App\DataFixtures;

use App\Entity\Dish;
use App\Entity\Enum\DishCategory;
use App\Entity\Enum\EcoScore;
use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DishFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Graine du générateur pseudo-aléatoire, fixée pour que chaque poste charge les mêmes plats.
     */
    private const SEED = 20261005;

    /**
     * Nombre de plats générés pour chacun des huit restaurants sans carte écrite à la main.
     */
    private const DISHES_PER_RESTAURANT = 20;

    /**
     * Le restaurant dont la carte est écrite à la main : les tests visent ses plats.
     */
    public const SCENARIO_RESTAURANT = 'Le Réfectoire';

    /**
     * La carte du Réfectoire : nom, catégorie, éco-score, prix en centimes, description.
     * Huit plats aux prix tous distincts : trois plats principaux, quatre notés A ou B,
     * quatre à 6 € au plus.
     */
    public const SCENARIO_DISHES = [
        ['Velouté de potimarron', 'starter', 'A', 450, 'Potimarron de saison, crème d\'avoine et graines de courge grillées.'],
        ['Salade de lentilles du Berry', 'starter', 'B', 550, 'Lentilles tièdes, échalote, vinaigre de cidre et herbes fraîches.'],
        ['Dahl de pois chiches', 'main', 'A', 600, 'Pois chiches mijotés au lait de coco, riz complet et coriandre.'],
        ['Burger du Réfectoire', 'main', 'D', 1200, 'Steak de bœuf charolais, cheddar affiné, oignons confits et frites maison.'],
        ['Bœuf bourguignon', 'main', 'E', 1350, 'Joue de bœuf braisée au vin rouge, carottes fondantes et pommes de terre vapeur.'],
        ['Tarte aux pommes', 'dessert', 'C', 400, 'Pâte sablée, pommes du verger voisin et pointe de cannelle.'],
        ['Panna cotta aux fruits rouges', 'dessert', 'B', 750, 'Crème vanillée prise au point, coulis de fruits rouges de saison.'],
        ['Fondant au chocolat', 'dessert', 'D', 850, 'Cœur coulant au chocolat noir, crème anglaise à la vanille.'],
    ];

    /**
     * Noms de plats tirables pour les restaurants générés, par catégorie.
     */
    private const NAMES = [
        'starter' => [
            'Soupe à l\'oignon', 'Salade de chèvre chaud', 'Carottes râpées', 'Houmous et crudités',
            'Terrine de campagne', 'Œuf mayonnaise', 'Taboulé de quinoa', 'Gaspacho andalou',
            'Tartine de chèvre et miel', 'Salade de betteraves',
        ],
        'main' => [
            'Lasagnes au bœuf', 'Poulet rôti et gratin', 'Curry de légumes', 'Saumon à l\'oseille',
            'Risotto aux champignons', 'Cassoulet', 'Pâtes à la carbonara', 'Falafels et semoule',
            'Hachis parmentier', 'Quiche lorraine', 'Chili sin carne', 'Blanquette de veau',
        ],
        'dessert' => [
            'Crème brûlée', 'Mousse au chocolat', 'Salade de fruits', 'Île flottante',
            'Riz au lait', 'Clafoutis aux cerises', 'Tarte au citron', 'Far breton',
            'Yaourt et granola', 'Profiteroles',
        ],
    ];

    public function __construct(
        private readonly RestaurantRepository $restaurantRepository,
    ) {
    }

    public function getDependencies(): array
    {
        return [AppFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        // la graine est fixée : le générateur ne tire pas au hasard, il tire toujours le même hasard
        mt_srand(self::SEED);

        $now = new DateTimeImmutable();

        // l'ordre de lecture est fixé lui aussi : le tirage dépend de la place de chaque restaurant
        $restaurants = $this->restaurantRepository->findBy([], ['campus' => 'ASC', 'name' => 'ASC']);

        foreach ($restaurants as $restaurant) {
            if (self::SCENARIO_RESTAURANT === $restaurant->getName()) {
                $this->loadScenarioMenu($manager, $restaurant, $now);

                continue;
            }

            $this->loadGeneratedMenu($manager, $restaurant, $now);
        }

        $manager->flush();
    }

    /**
     * Écrit la carte figée du restaurant visé par les tests.
     */
    private function loadScenarioMenu(ObjectManager $manager, Restaurant $restaurant, DateTimeImmutable $now): void
    {
        foreach (self::SCENARIO_DISHES as [$name, $category, $ecoScore, $price, $description]) {
            $manager->persist($this->buildDish(
                $restaurant,
                $name,
                DishCategory::from($category),
                EcoScore::from($ecoScore),
                $price,
                $description,
                $now,
            ));
        }
    }

    /**
     * Tire une carte de vingt plats, sans doublon de nom au sein d'une même catégorie.
     */
    private function loadGeneratedMenu(ObjectManager $manager, Restaurant $restaurant, DateTimeImmutable $now): void
    {
        $categories = DishCategory::cases();
        $used = [];

        for ($i = 0; $i < self::DISHES_PER_RESTAURANT; ++$i) {
            $category = $categories[$i % count($categories)];
            $names = self::NAMES[$category->value];

            // on tire jusqu'à tomber sur un nom que la carte n'a pas encore
            do {
                $name = $names[mt_rand(0, count($names) - 1)];
            } while (isset($used[$name]));
            $used[$name] = true;

            // prix entre 3,50 € et 14,50 €, arrondi à 0,50 €
            $price = mt_rand(7, 29) * 50;
            $ecoScore = EcoScore::cases()[mt_rand(0, 4)];

            $manager->persist($this->buildDish(
                $restaurant,
                $name,
                $category,
                $ecoScore,
                $price,
                sprintf('%s, préparé chaque jour par les cuisines de %s.', $name, $restaurant->getName()),
                $now,
            ));
        }
    }

    /**
     * Construit un plat, date de création comprise.
     */
    private function buildDish(
        Restaurant $restaurant,
        string $name,
        DishCategory $category,
        EcoScore $ecoScore,
        int $price,
        string $description,
        DateTimeImmutable $now,
    ): Dish {
        return new Dish()
            ->setRestaurant($restaurant)
            ->setName($name)
            ->setCategory($category)
            ->setEcoScore($ecoScore)
            ->setPrice($price)
            ->setDescription($description)
            ->setCreatedAt($now);
    }
}
