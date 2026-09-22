# Proposition technique temporaire

## Objectif
Proposer une architecture réaliste pour un site de réservation de ressources destiné à toute entreprise, avec des choix justifiés et des alternatives.

## 1. Base de données : relationnelle ou NoSQL ?

### Recommandation principale
Utiliser une base relationnelle SQL comme socle, de préférence PostgreSQL.

### Pourquoi SQL (et pas NoSQL en base principale)
- Le cœur métier repose sur des relations fortes : entreprises, utilisateurs, rôles, ressources, réservations, historique.
- Les conflits de réservation exigent de la cohérence transactionnelle (ACID) pour garantir qu’un créneau ne soit pas doublement attribué.
- Les requêtes de planning, filtrage, audit et reporting sont naturellement relationnelles.
- Les contraintes d’intégrité (clés étrangères, contraintes uniques, checks) réduisent les erreurs métier.

### Pourquoi PostgreSQL
- Très bon support transactionnel et concurrence.
- Fonctions utiles pour les dates/périodes, indexation, JSONB si besoin hybride.
- Solide en production et bien supporté par les frameworks web.

### Quand NoSQL devient pertinent
NoSQL n’est pas nécessaire pour le socle métier au début, mais peut être utile plus tard pour :
- journalisation volumineuse orientée événements ;
- analytics non transactionnelles ;
- stockage de documents très flexibles.

Conclusion : SQL pour le cœur métier, NoSQL en complément uniquement si un besoin précis apparaît.

## 2. Framework backend : Symfony ou autre ?

### Recommandation principale
Symfony (PHP) est un excellent choix pour un projet CDA orienté architecture propre et robustesse.

### Pourquoi Symfony
- Structure claire (contrôleurs, services, entités, validation, sécurité).
- Écosystème mature pour API, authentification, rôles et permissions.
- Très bon cadre pour appliquer les bonnes pratiques attendues en soutenance (couches, tests, maintenabilité).
- Intégration naturelle avec Doctrine et PostgreSQL.

### Alternatives pertinentes
- Laravel : plus rapide à démarrer, DX très agréable ; bon choix si priorité à la vitesse de livraison.
- NestJS (Node.js) : intéressant si préférence TypeScript full stack.
- Spring Boot (Java) : très robuste, mais plus verbeux pour un projet de taille moyenne.

Conclusion : Symfony si priorité à la rigueur d’architecture et à la démonstration CDA.

## 3. Frontend : monolithique serveur ou SPA ?

### Option conseillée pour démarrer
Rendu serveur avec Twig (ou frontend léger) pour limiter la complexité initiale.

### Si besoin d’interface plus dynamique
Évoluer vers Vue.js ou React pour le calendrier et les interactions temps réel.

Conclusion : démarrer simple, complexifier seulement sur besoin prouvé.

## 4. Faut-il mélanger plusieurs technos dans le programme ?

Oui, mais de manière ciblée :
- SQL (PostgreSQL) pour le métier transactionnel.
- Redis pour cache/session/anti-surcharge (optionnel au départ).
- File de messages (RabbitMQ ou Symfony Messenger + transport) pour notifications asynchrones.

Important : éviter de multiplier les briques trop tôt. Chaque composant ajouté doit répondre à un problème mesuré.

## 5. Proposition d’architecture par étapes

### Étape 1 (MVP)
- Symfony + PostgreSQL
- API REST + interface web
- Gestion utilisateurs/rôles/ressources/réservations
- Prévention des conflits côté base et côté service métier

### Étape 2 (stabilisation)
- Historique/audit renforcé
- Notifications asynchrones
- Amélioration du calendrier et des filtres

### Étape 3 (montée en charge)
- Cache Redis
- Optimisation requêtes et index
- Éventuel composant NoSQL pour logs/analytiques si volumétrie élevée

## 6. Pourquoi ces choix et pas d’autres

- SQL plutôt que NoSQL en principal : meilleure cohérence métier pour les réservations et droits.
- Symfony plutôt qu’un framework minimaliste : meilleure démonstration d’architecture maintenable.
- Architecture hybride progressive plutôt que "tout dès le départ" : réduction de la complexité et des risques projet.

## 7. Règle de décision pratique
Avant d’ajouter une technologie, vérifier :
1. Problème concret identifié ?
2. Limite mesurable de la solution actuelle ?
3. Gain attendu supérieur au coût de complexité ?

Si l’une des réponses est non, ne pas ajouter la technologie.

## Statut
Document temporaire de travail. À valider puis intégrer dans la documentation de conception.
