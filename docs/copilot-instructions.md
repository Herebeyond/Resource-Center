# Instructions pour les agents IA du projet de réservation de ressources

## Contexte du projet

Ce dépôt correspond à un projet de gestion de réservation de ressources partagées pour toute entreprise. Le système doit permettre de gérer des salles, véhicules, ordinateurs portables, vidéoprojecteurs et autres équipements avec des réservations sécurisées, traçables et cohérentes.

Les documents principaux à consulter en priorité sont :
- [sujet-projet-cda.md](sujet-projet-cda.md)
- [journal-problemes-solutions.md](journal-problemes-solutions.md)
- [TODO.md](TODO.md)

## Règles de travail

- Travailler en français dans les libellés, commentaires, messages utilisateur, documentation et choix de conception.
- Respecter les conventions métier du projet : réservation, disponibilité, droits d’accès, historique, calendrier, notification.
- Faire preuve de clarté, de simplicité et de robustesse ; éviter les solutions sur-complexes si la demande peut être satisfaite de manière plus directe.
- Ne pas inventer de fonctionnalités non demandées ni de piles technologiques inexistantes dans le dépôt sans justification claire.
- Lorsqu’un choix technique est nécessaire, documenter brièvement la raison et la contrainte associée.
- Favoriser un code lisible, maintenable et testable, avec une séparation claire entre logique métier, API et interface.

## Règle d'arbitrage des choix techniques

- Pour chaque demande, évaluer si la prémisse technique choisie reste la plus efficace pour le besoin réel.
- Si une approche hybride est plus pertinente (exemple : SQL pour le transactionnel et NoSQL pour des logs volumineux), prévenir l'utilisateur avant d'agir.
- Expliquer brièvement le raisonnement : bénéfices attendus, compromis, coût de complexité, impact maintenance.
- Ne pas appliquer un changement de pile technique sans validation explicite de l'utilisateur.

## Recherche de solutions existantes

- Lorsqu'une demande concerne un visuel spécifique, une interaction avancée ou une commande peu courante, vérifier d'abord sur Internet si une bibliothèque, un composant ou une implémentation existante peut répondre au besoin.
- Rechercher plusieurs solutions adaptées au contexte technique du projet avant de développer une solution personnalisée.
- Pour un visuel de données de type graphe relationnel ou graph view, étudier par exemple D3.js, Force-Graph, 3D-Force-Graph, React-Force-Graph, Cytoscape.js, Vis.js ou toute autre solution plus adaptée au besoin réel.
- Ne pas choisir une bibliothèque uniquement parce qu'elle est connue : comparer son adéquation fonctionnelle, sa compatibilité avec la pile utilisée, sa facilité d'intégration, ses performances, son accessibilité, sa documentation, sa maintenance et sa licence.
- Si une solution est clairement meilleure, la recommander avant d'agir et expliquer brièvement pourquoi, en la comparant aux alternatives pertinentes.
- Si aucune solution existante ne convient suffisamment, expliquer la limite constatée avant de proposer une implémentation personnalisée.
- Vérifier les informations importantes sur des sources fiables et actuelles, notamment la documentation officielle, le dépôt du projet, la licence et la compatibilité avec les versions utilisées.
- Ne pas copier de code dont la licence ou les conditions de réutilisation ne sont pas claires. Préférer l'utilisation documentée d'une dépendance et respecter sa licence.

## Règles spécifiques pour les accents et le texte en français

- Toujours conserver les accents dans les chaînes écrites en français : é, è, à, ç, ô, û, î, ï, ù, œ, etc.
- Ne pas supprimer, normaliser, tronquer ou translittérer les caractères accentués dans les libellés, messages, données de test, seeds, migrations, SQL, JSON, formulaires, noms de rôles, noms de ressources, commentaires ou documentation.
- Définir et conserver une encodage UTF-8 fiable dans le code, la base de données, les fichiers de données et les exports.
- Vérifier que les données issues de la base ou des fichiers ne sortent pas cassées lorsque les accents sont inclus directement dans les valeurs.
- Si un script de base de données, un modèle, une migration, un seed ou une fixture est créé, veiller à ce qu’il supporte correctement les caractères Unicode et les textes français.
- Ne pas utiliser de remplacements “ASCII-only” pour des libellés métier : par exemple, ne pas transformer “réservation” en “reservation”, ni “équipe” en “equipe” dans des utilisations fonctionnelles.

## Conventions applicables au projet

### Domaine fonctionnel
- Gérer les ressources avec leur type, leur état et leur disponibilité.
- Empêcher les conflits de réservation sur un même créneau.
- Séparer les profils utilisateur selon les droits et permissions.
- Prévoir une vue calendrier et un historique d’actions.
- Considérer la sécurité, la traçabilité et la fiabilité des données.

### API et données
- Préférer des réponses structurées, explicites et cohérentes.
- Respecter le principe de sécurité sur les accès et les données sensibles.
- Pour les développements liés à la base, privilégier des modèles lisibles et des données cohérentes, avec des valeurs textuelles en français correctes et complètes.

### Qualité de code
- Écrire des tests ciblés lorsque des règles métier importantes sont implémentées.
- Vérifier les cas limites : chevauchement de réservation, droits insuffisants, ressources indisponibles, erreurs de validation.
- Garder les messages utilisateur compréhensibles et correctement accentués.

### Templates et composants réutilisables
- Factoriser les éléments d'interface communs dans des templates Twig partagés plutôt que de dupliquer leur HTML et leur comportement dans chaque page.
- Réutiliser notamment l'en-tête, le pied de page, le bouton de retour en haut, les menus, les contrôles de langue et les autres éléments récurrents sur l'ensemble des pages concernées.
- Lorsqu'un composant partagé est modifié, vérifier qu'il reste cohérent et fonctionnel sur toutes les pages qui l'incluent, y compris pour les traductions et les interactions JavaScript.
- Pour une nouvelle interface réutilisée à plusieurs endroits, privilégier un partial Twig dans `app/templates/shared/` et lui transmettre explicitement les données propres à la page.

## À retenir pour les agents

L’objectif principal de ce dépôt est de produire une application de réservation de ressources fiable et utilisable, avec des textes en français correctement accentués et une base de données qui conserve ces caractères sans corruption. Les agents doivent donc traiter les accents comme un élément fonctionnel important, pas comme un détail cosmétique.
