# Installation locale

## Prérequis

- Docker Desktop démarré
- Docker Compose disponible
- Ports `8000` et `5432` disponibles

## Démarrage

Depuis la racine du projet, renseigner les variables locales à partir de `.env.example`, puis démarrer la stack Docker. Le fichier `.env` reste local et n'est pas versionné. Le service `database` lance PostgreSQL 16 et le service `symfony_app` attend que PostgreSQL soit sain avant de démarrer.

Les fichiers `app/.env` et `app/.env.dev` ne sont pas versionnés. Pour lancer Symfony directement depuis `app/`, copier `app/.env.example` vers `app/.env.local` et remplacer les valeurs de développement localement. Dans Docker Compose, `APP_SECRET`, `DATABASE_URL` et `DEFAULT_URI` sont injectés par l'environnement Compose.

`DEFAULT_URI` sert à générer les URL absolues hors d'une requête HTTP. Compose utilise `http://127.0.0.1:8000` par défaut. Sur la machine du campus, définir dans le `.env` à la racine du dépôt l'adresse réellement accessible aux utilisateurs, par exemple `DEFAULT_URI=http://10.30.3.2:8000` si cette adresse et ce port sont ouverts sur le VPN.

Les scripts sont exécutés dans cet ordre lors de la première création du volume :

1. `database/Reservations.sql` : tables, contraintes, index, triggers et données de référence.
2. `database/Reservations.demo.fixtures.sql` : entreprise, utilisateurs, ressources et réservations de démonstration.
3. `database/Reservations.resources.fixtures.sql` : crée l'entreprise de démonstration si nécessaire et ajoute 40 salles et 200 véhicules sans créer de comptes ni modifier les rôles.
4. `database/Reservations.equipment.fixtures.sql` : 200 exemplaires d'équipements répartis sur 20 modèles, avec numéros de série uniques et réservations de test pour la date d'exécution.

Le fixture de ressources peut être exécuté seul après `Reservations.sql`, même si `Reservations.demo.fixtures.sql` n'est pas utilisé. Sur une base déjà initialisée, les scripts de `/docker-entrypoint-initdb.d/` ne sont pas rejoués automatiquement; pour ajouter les ressources de démonstration sans toucher aux utilisateurs, exécuter `docker compose exec -T database psql -U resource_center -d resource_center -v ON_ERROR_STOP=1 -f /docker-entrypoint-initdb.d/03-resources-fixtures.sql` (ou fournir le fichier à `psql` depuis l'hôte).

Le schéma de flotte ajoute `vehicle_details.vehicle_class` et `vehicle_usage` pour les classes de véhicules, prises en charge, retours et incidents. Les fixtures appliquent ces ajouts de façon idempotente aux volumes existants. Une migration autonome est également fournie dans `database/migrations/20261001_vehicle_class.sql` et `database/migrations/20261001_vehicle_usage.sql`.

Les équipements disposent aussi de `equipment_details.model`, ajouté aux bases existantes par `database/migrations/20261008_equipment_model.sql` ou par le script d'équipements. Ce script peut être réexécuté sans dupliquer les ressources ni les créneaux bloquants du jour et sans réinitialiser les rôles des utilisateurs. Les nouvelles réservations de test concernent un exemplaire par modèle entre 10 h et 13 h ; les autres exemplaires restent libres.

### Mettre à jour une base existante

Les scripts montés dans `/docker-entrypoint-initdb.d/` ne s'exécutent qu'à la première création du volume PostgreSQL. Un `git pull` ou un redémarrage de Docker ne met donc pas à jour un volume déjà initialisé. Après récupération des migrations sur le serveur, les appliquer dans l'ordre sans supprimer le volume :

```sh
cd ~/apps/Resource-Center
git pull origin dev
set -e
for migration in database/migrations/*.sql; do
	echo "Application de $migration"
	docker compose exec -T database psql -U resource_center -d resource_center -v ON_ERROR_STOP=1 < "$migration"
done
docker compose restart symfony_app
```

Les migrations actuelles utilisent `IF NOT EXISTS` et peuvent être rejouées. En cas d'erreur, la boucle s'arrête; corriger l'erreur avant de poursuivre. Ne pas supprimer `database_data` pour appliquer une migration, car cela effacerait les données.

La sélection d'un équipement suit le même parcours que les véhicules : modèle regroupé par type, marque et catégorie, puis exemplaire identifié par localisation et numéro de série (ou code de ressource si le numéro manque). Les anciens équipements sans modèle explicite restent des entrées séparées, sans tentative de regroupement à partir de leur nom numéroté.

L'application est disponible sur `http://127.0.0.1:8000`.

En développement, le code `app/` est monté dans le conteneur pour refléter les modifications immédiatement. Les dossiers `vendor/` et `var/` utilisent des volumes Docker dédiés afin d'éviter les lenteurs liées à un montage depuis OneDrive. Le projet n'utilise pas de police distante : les ressources de la homepage restent locales.

## Connexion Symfony

Symfony utilise Doctrine DBAL et la variable `DATABASE_URL`. Dans Docker, elle pointe vers le service `database`. En lancement local depuis `app/`, elle pointe vers `127.0.0.1`.

## Langue de l'interface

Le bouton de langue affiche la langue active (`FR` ou `EN`) avec son drapeau ; un clic bascule vers l'autre langue. Le choix manuel est mémorisé dans le navigateur et transmis à Symfony par le cookie `resource_center_lang`. Le subscriber `LocaleSubscriber` accepte uniquement `fr` et `en`, sinon utilise la langue préférée du navigateur, avec un repli vers l'anglais.

L'accueil, la réservation, le calendrier et le profil actualisent leurs libellés sans rechargement. Les pages rendues uniquement côté serveur, notamment administration, notifications et suivi de flotte, sont rechargées lors d'une bascule pour traduire aussi leur contenu. Le test autonome `app/tests/LocaleSubscriberTest.php` vérifie la priorité du choix manuel et le traitement des langues non prises en charge.

## Compte de démonstration

Les comptes sont créés par un administrateur via la base de données. Il n'y a pas d'inscription publique.

- Email : `alice.martin@demo-cda.local`
- Mot de passe de démonstration : `demo1234`

La session est utilisée pour afficher le profil connecté dans le menu et dans la page `/profil`. La homepage récupère ses statistiques depuis les ressources actives de PostgreSQL.

## Réinitialisation de la base de démonstration

Les scripts d'initialisation PostgreSQL ne sont exécutés automatiquement que lorsque le volume est créé. Pour repartir d'une base vide, supprimer le volume Docker de développement puis relancer la stack. Cette opération supprime les données locales de démonstration.

## Protection des pages privées

`PrivatePageAccessSubscriber` protège toutes les routes Symfony par défaut, y compris les nouvelles routes et les sous-requêtes. Il est enregistré automatiquement grâce à l'autoconfiguration des services. Il intervient après le routage, avant l'exécution du contrôleur.

- Seule la route `app_login` est publique. Une future exception publique doit être ajoutée explicitement à `PUBLIC_ROUTES` dans le subscriber et couverte par un test ; aucune exemption n'est accordée automatiquement à un contrôleur entier.
- Chaque accès privé vérifie en base l'existence d'un compte actif correspondant à la session. Une session devenue invalide est supprimée.
- Un visiteur est redirigé vers la connexion. Une requête JSON ou AJAX non authentifiée reçoit un statut `401` au lieu d'une page HTML de connexion.
- Les routes nommées `app_admin…` et les chemins `/administration` ou `/administration/…` exigent un rôle administrateur. Un accès non autorisé renvoie `404`, conformément à la politique des pages d'administration cachées.
- Le compte validé est disponible dans l'attribut de requête `_authenticated_user`. Les réponses privées portent `Cache-Control: private, no-store`.
- Les contrôleurs doivent toujours vérifier les permissions métier, la propriété des objets, l'appartenance à l'entreprise et les jetons CSRF des actions sensibles. Le garde global ne remplace pas ces vérifications.
- Les fichiers statiques du répertoire `public/` ne passent pas par ce garde : aucun secret ni document privé ne doit y être déposé.

Le test autonome `app/tests/PrivatePageAccessSubscriberTest.php` se lance avec PHP, sans base de données ni dépendance de test supplémentaire. Il vérifie notamment qu'une route future sans contrôle dans son contrôleur reste inaccessible à un visiteur.

## Vérifications

La connexion peut être vérifiée avec la commande Symfony Doctrine `doctrine:query:sql`. Les templates Twig et la configuration YAML peuvent être validés avec les commandes de lint Symfony.
