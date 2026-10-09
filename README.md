# CodaEats

Les consignes de l'évaluation sont sur la page
[Évaluation du module](https://school.adriengras.fr/docs/coda/b2/api-developpement/evaluation).

## Installation

Installez [Docker Compose](https://docs.docker.com/compose/install/) (v2.10 ou plus), puis, à la
racine du dépôt :

```bash
make start
make sf c="doctrine:fixtures:load -n"
```

`make start` construit les images, démarre les conteneurs, applique les migrations et génère la
paire de clés JWT. La seconde commande charge les comptes de démonstration (`alice@example.fr`,
`bob@example.fr`, `camille.aubert@example.fr`, mot de passe `motdepasse`). L'API répond sur
`https://localhost` (certificat auto-signé à accepter), sa documentation sur
`https://localhost/api/docs`.

## Lancer les tests

```bash
make test
```

La base de test est recréée à chaque lancement. Pour cibler une classe :
`make test c="--filter OrderTest"`.
