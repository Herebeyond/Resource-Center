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

## Connexion Symfony

Symfony utilise Doctrine DBAL et la variable `DATABASE_URL`. Dans Docker, elle pointe vers le service `database`. En lancement local depuis `app/`, elle pointe vers `127.0.0.1`.

## Réinitialisation de la base de démonstration

Les scripts d'initialisation PostgreSQL ne sont exécutés automatiquement que lorsque le volume est créé. Pour repartir d'une base vide, supprimer le volume Docker de développement puis relancer la stack. Cette opération supprime les données locales de démonstration.

## Vérifications

La connexion peut être vérifiée avec la commande Symfony Doctrine `doctrine:query:sql`. Les templates Twig et la configuration YAML peuvent être validés avec les commandes de lint Symfony.
