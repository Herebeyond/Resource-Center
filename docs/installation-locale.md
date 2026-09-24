# Installation locale

## Prérequis

- Docker Desktop démarré
- Docker Compose disponible
- Ports `8000` et `5432` disponibles

## Démarrage

Depuis la racine du projet, démarrer la stack Docker. Le service `database` lance PostgreSQL 16 et le service `symfony_app` attend que PostgreSQL soit sain avant de démarrer.

Les scripts sont exécutés dans cet ordre lors de la première création du volume :

1. `database/Reservations.sql` : tables, contraintes, index, triggers et données de référence.
2. `database/Reservations.demo.fixtures.sql` : entreprise, utilisateurs, ressources et réservations de démonstration.

L'application est disponible sur `http://127.0.0.1:8000`.

En développement, le code `app/` est monté dans le conteneur pour refléter les modifications immédiatement. Les dossiers `vendor/` et `var/` utilisent des volumes Docker dédiés afin d'éviter les lenteurs liées à un montage depuis OneDrive. Le projet n'utilise pas de police distante : les ressources de la homepage restent locales.

## Connexion Symfony

Symfony utilise Doctrine DBAL et la variable `DATABASE_URL`. Dans Docker, elle pointe vers le service `database`. En lancement local depuis `app/`, elle pointe vers `127.0.0.1`.

## Compte de démonstration

Les comptes sont créés par un administrateur via la base de données. Il n'y a pas d'inscription publique.

- Email : `alice.martin@demo-cda.local`
- Mot de passe de démonstration : `demo1234`

La session est utilisée pour afficher le profil connecté dans le menu et dans la page `/profil`. La homepage récupère ses statistiques depuis les ressources actives de PostgreSQL.

## Réinitialisation de la base de démonstration

Les scripts d'initialisation PostgreSQL ne sont exécutés automatiquement que lorsque le volume est créé. Pour repartir d'une base vide, supprimer le volume Docker de développement puis relancer la stack. Cette opération supprime les données locales de démonstration.

## Vérifications

La connexion peut être vérifiée avec la commande Symfony Doctrine `doctrine:query:sql`. Les templates Twig et la configuration YAML peuvent être validés avec les commandes de lint Symfony.
