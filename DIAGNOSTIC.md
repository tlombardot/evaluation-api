# Diagnostic

Une section par test en échec : renseignez ses quatre champs.

## testAddingALineToAnUnknownOrderIsNotFound

**Symptôme** : Au lieu de recevoir un 404 on reçoit un 500

**Cause** : Le manque de provider

**Règle du module en jeu** : On essaye de savoir si l'object qu'on ajoute a été fait par l'utilisateur avec le security mais si il y'a pas de provider impossible de savoir ou plutot symfony va le faire lui même et defois ça marche il arrive a trouvé defois non comme ce cas là donc c'est mieux de mettre notre propre provider.

**Correctif** : Rajouter un provider pour qu'il puisse récuperer la ressource de l'object pour la security

## testAddingALineToAPaidOrderIsAConflict

**Symptôme** : Meme quand le la commande est deja payé on peut toujours mettre un plat dedans

**Cause** : Oublie de mettre une verification si la commande est déjà payé dans le service

**Règle du module en jeu** : Probleme d'exceptions la ressource est crée alors que la commande est deja payé

**Correctif** : Rajouter la verification du status de la commande pour savoir si elle est payé avant de rajouter un plat dessus

## testAddingALineToMyOrder

**Symptôme** : Le total qu'on devrait recevoir en prix n'était pas le bon

**Cause** : Oubli de calculer le plat lors de la commande selon la quantité

**Règle du module en jeu** :  Problème sur la véracité du prix, le plat peut etre commandé selon la quantité mais elle doit etre calculer dans le service le prix

**Correctif** : Rajout que la sortie subtotal de line mutiplié par la quantité

## testAddingALineWithAZeroQuantityIsUnprocessable

**Symptôme** : On peut ajouter zero quantité a un plat

**Cause** : La verification de surface laisse passer le zero quantité ou positive

**Règle du module en jeu** : Le prix va etre calculé sur 0 et peut poser des problèmes à l'insertion du plat

**Correctif** : dans le dto input changement de l'assert pour la quantité de postive or zero à postive

## testListingKitchenTicketsReturnsMine

**Symptôme** :

**Cause** :

**Règle du module en jeu** :

**Correctif** :

## testListingKitchenTicketsWithoutTokenIsUnauthorized

**Symptôme** : Le Refresh Token ne se renouvelle pas quand utilisé

**Cause** : Oublie de mettre single use sur le refresh token

**Règle du module en jeu** :  Question de sécurité on peut générer autant de token avec un refresh token surtout qu'il lui expire plus longtemps que ferai un token de base.

**Correctif** : single_use: true

## testOpeningAnOrderIgnoresAnAbandonedOne

**Symptôme** :

**Cause** :

**Règle du module en jeu** :

**Correctif** :

## testPayingMyOrderMarksItPaid

**Symptôme** :

**Cause** :

**Règle du module en jeu** :

**Correctif** :

## testRefreshingTwiceWithTheSameTokenIsUnauthorized

**Symptôme** :

**Cause** :

**Règle du module en jeu** :

**Correctif** :

## testRemovingALineFromSomeoneElsesOrderIsForbidden

**Symptôme** :

**Cause** :

**Règle du module en jeu** :

**Correctif** :
