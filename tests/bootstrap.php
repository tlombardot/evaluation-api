<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// Base de test recréée une fois par lancement : schéma migré, fixtures chargées.
// Ensuite DAMA annule chaque test dans une transaction.
foreach ([
    'doctrine:database:drop --force --if-exists',
    'doctrine:database:create',
    'doctrine:migrations:migrate -n',
    'doctrine:fixtures:load -n',
] as $commande) {
    passthru(sprintf(
        'APP_ENV=test %s %s/bin/console %s --env=test -q',
        escapeshellarg(PHP_BINARY),
        escapeshellarg(dirname(__DIR__)),
        $commande,
    ), $code);

    if (0 !== $code) {
        fwrite(STDERR, sprintf("Préparation de la base de test impossible : %s\n", $commande));
        exit(1);
    }
}
