# Diagnostic

Une section par test en échec : renseignez ses quatre champs.

## testAddingALineToAnUnknownOrderIsNotFound

**Symptôme** : Au lieu de recevoir un 404 on reçoit un 500

**Cause** : Le manque de provider

**Règle du module en jeu** : On essaye de savoir si l'object qu'on ajoute a été fait par l'utilisateur avec le security mais si il y'a pas de provider impossible de savoir ou plutot symfony va le faire lui même et defois ça marche il arrive a trouvé defois non comme ce cas là donc c'est mieux de mettre notre propre provider.

**Correctif** : Rajouter un provider pour qu'il puisse récuperer la ressource de l'object pour la security

## testAddingALineToAPaidOrderIsAConflict

**Symptôme** :

**Cause** :

**Règle du module en jeu** :

**Correctif** :

## testAddingALineToMyOrder

**Symptôme** :

**Cause** :

**Règle du module en jeu** :

**Correctif** :

## testAddingALineWithAZeroQuantityIsUnprocessable

**Symptôme** :

**Cause** :

**Règle du module en jeu** :

**Correctif** :

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
