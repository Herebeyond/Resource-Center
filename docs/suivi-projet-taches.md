# Suivi des tâches - Projet de réservation de ressources

Ce fichier centralise les tâches du projet et leur état d'avancement.

## Convention

- `[ ]` Tâche à faire ou en cours
- `[x]` Tâche terminée
- Déplacer une tâche entre les sections lorsque son état change.
- Cocher la tâche uniquement lorsque son résultat est réellement vérifié.

---

## À faire

### Préparer le projet

- [ ] Initialiser la structure du projet
  - [x] Initialiser le projet Symfony
  - [x] Configurer les variables d'environnement
  - [x] Configurer PostgreSQL
  - [x] Configurer Git
  - [x] Créer la structure des dossiers
  - [x] Ajouter les dépendances nécessaires
  - [x] Vérifier que le projet démarre correctement

- [ ] Connecter l'application à la base de données
  - [x] Configurer la connexion PostgreSQL
  - [x] Importer le schéma SQL
  - [x] Vérifier les tables et les relations
  - [x] Vérifier les contraintes et les triggers
  - [x] Tester les fixtures de démonstration
  - [x] Documenter la procédure d'installation

- [x] Préparer le déploiement local avec Docker Desktop
  - [x] Créer la configuration Docker
  - [x] Créer le Dockerfile
  - [x] Créer la configuration Nginx
  - [ ] Vérifier le lancement local sur le navigateur

- [ ] Mettre en place l'interface multilingue de base
  - [ ] Ajouter le support FR / EN
  - [ ] Ajouter le bouton de changement de langue
  - [ ] Détecter la langue par défaut selon le pays / navigateur
  - [ ] Vérifier le comportement sur la homepage

### Finaliser la conception

- [ ] Finaliser le cahier des charges
  - [x] Définir le périmètre du MVP
  - [x] Décrire l'inscription et la connexion des utilisateurs
  - [x] Définir les rôles et les permissions
  - [x] Définir le fonctionnement d'une réservation
  - [x] Décrire les conflits de réservation
  - [x] Décrire les avertissements de réservations qui se chevauchent ou sont rapprochées
  - [ ] Définir les notifications nécessaires
  - [ ] Décider si les notifications entrent dans le MVP ou dans une évolution future
  - [ ] Décider si l'API entre dans le MVP ou dans une évolution future
  - [x] Vérifier la cohérence avec la base de données
  - [x] Ajouter les critères de validation

- [x] Finaliser les maquettes Figma de base
  - [x] Finaliser la page d'accueil HTML conforme à la maquette fournie
  - [ ] Finaliser la page de réservation
  - [ ] Ajouter la page de calendrier
  - [ ] Ajouter la page de connexion
  - [ ] Ajouter les pages de gestion des ressources
  - [ ] Ajouter la page de gestion des réservations
  - [ ] Ajouter les pages d'administration
  - [ ] Prévoir l'état sans résultat
  - [ ] Prévoir l'état de conflit
  - [ ] Prévoir l'état de succès
  - [ ] Prévoir l'état d'erreur
  - [ ] Vérifier la version mobile

- [ ] Ajouter les prérequis de langue du site
  - [ ] Support français et anglais
  - [ ] Bouton de changement de langue visible
  - [ ] Détection du pays pour la langue par défaut
  - [ ] Comportement de fallback si la détection échoue

- [x] Définir le fonctionnement multi-entreprise
  - [x] Définir le mode d'enregistrement des entreprises
  - [x] Définir le mode d'enregistrement des utilisateurs
  - [x] Définir les interactions entre entreprises et utilisateurs
  - [x] Définir les rôles et permissions par entreprise

### Développer le MVP

- [ ] Développer l'authentification
  - [x] Créer la page de connexion
  - [x] Gérer la déconnexion
  - [x] Créer les utilisateurs de test
  - [x] Vérifier les identifiants dans PostgreSQL
  - [x] Afficher le profil selon la session
  - [ ] Ajouter les rôles
  - [ ] Protéger les pages privées
  - [ ] Refuser les actions non autorisées
  - [ ] Afficher un message en cas d'accès interdit

- [ ] Développer la gestion des ressources
  - [ ] Afficher la liste des ressources
  - [ ] Ajouter une ressource
  - [ ] Modifier une ressource
  - [ ] Désactiver une ressource
  - [ ] Définir le type et la localisation
  - [ ] Définir la capacité
  - [ ] Gérer les états disponible, indisponible et maintenance
  - [x] Alimenter les statistiques de la homepage depuis PostgreSQL

- [ ] Développer la consultation des disponibilités
  - [ ] Sélectionner une date
  - [ ] Sélectionner une heure de début et de fin
  - [ ] Filtrer par type de ressource
  - [ ] Filtrer par capacité ou localisation
  - [ ] Afficher les créneaux occupés
  - [ ] Afficher les ressources indisponibles
  - [ ] Afficher un message lorsqu'aucun résultat n'est trouvé

- [ ] Développer la création d'une réservation
  - [ ] Sélectionner une ressource
  - [ ] Sélectionner un créneau
  - [ ] Saisir le motif de la réservation
  - [ ] Vérifier les données saisies
  - [ ] Vérifier la disponibilité côté serveur
  - [ ] Enregistrer la réservation
  - [ ] Afficher une confirmation
  - [ ] Empêcher la double réservation

- [ ] Gérer les conflits et les réservations rapprochées
  - [ ] Détecter les chevauchements de réservation
  - [ ] Bloquer une réservation qui chevauche une autre
  - [ ] Afficher un message d'erreur explicite en cas de conflit
  - [ ] Détecter les réservations directement adjacentes
  - [ ] Détecter un écart inférieur à cinq minutes
  - [ ] Afficher un avertissement non bloquant
  - [ ] Proposer un décalage du créneau
  - [ ] Permettre de confirmer malgré l'avertissement
  - [ ] Tester les chevauchements et les créneaux adjacents

- [ ] Gérer la modification et l'annulation
  - [ ] Afficher les réservations de l'utilisateur
  - [ ] Modifier une réservation
  - [ ] Vérifier les conflits après modification
  - [ ] Annuler une réservation
  - [ ] Conserver la réservation dans l'historique
  - [ ] Libérer le créneau après annulation

- [ ] Développer le calendrier
  - [ ] Créer une vue journalière
  - [ ] Créer une vue hebdomadaire
  - [ ] Afficher les ressources réservées
  - [ ] Afficher les créneaux libres
  - [ ] Filtrer par ressource
  - [ ] Filtrer par utilisateur
  - [ ] Adapter l'affichage aux petits écrans

- [ ] Ajouter l'historique et les notifications
  - [ ] Afficher l'historique des réservations
  - [ ] Afficher les annulations
  - [ ] Afficher les modifications
  - [ ] Afficher les changements de statut
  - [ ] Créer une notification après réservation
  - [ ] Créer une notification après annulation
  - [ ] Prévoir les rappels si nécessaire

- [ ] Développer l'API des disponibilités
  - [ ] Définir les endpoints minimum
  - [ ] Retourner la liste des ressources
  - [ ] Retourner les disponibilités
  - [ ] Ajouter l'authentification API
  - [ ] Vérifier les autorisations
  - [ ] Gérer les erreurs
  - [ ] Documenter les réponses JSON
  - [ ] Tester les endpoints

### Vérifier et livrer

- [ ] Écrire les tests principaux
  - [ ] Tester la création d'une réservation
  - [ ] Tester le refus d'un chevauchement
  - [ ] Tester l'annulation
  - [ ] Tester la modification
  - [ ] Tester les permissions
  - [ ] Tester les ressources en maintenance
  - [ ] Tester les réservations adjacentes
  - [ ] Tester l'API

- [ ] Préparer la démonstration finale
  - [ ] Créer les données de démonstration
  - [ ] Préparer un scénario utilisateur
  - [ ] Préparer un scénario administrateur
  - [ ] Préparer un scénario de conflit
  - [ ] Préparer un scénario d'annulation
  - [ ] Rédiger la documentation d'installation
  - [ ] Rédiger la documentation utilisateur
  - [ ] Préparer les captures d'écran
  - [ ] Vérifier l'installation depuis un environnement propre

---

## En cours

- [x] Finaliser le cahier des charges
- [x] Finaliser les maquettes Figma de base
- [x] Préparer le déploiement local avec Docker Desktop
- [ ] Ajouter la base d'internationalisation du site

---

## En pause / Après le MVP

- [ ] Ajouter les réservations récurrentes
- [ ] Ajouter un workflow de validation par un responsable
- [ ] Ajouter la gestion de plusieurs sites
- [ ] Synchroniser avec Google Calendar ou Outlook
- [ ] Ajouter des statistiques avancées
- [ ] Ajouter une application mobile
- [ ] Ajouter des intégrations externes avancées
- [ ] Ajouter les notifications SMS

---

## Terminé

- [x] Revoir les propositions techniques pour le site
- [x] Concevoir le modèle de données
- [x] Revoir le schéma de la base de données
- [x] Créer le schéma SQL
- [x] Ajouter les contraintes de réservation
- [x] Ajouter l'historisation des actions
- [x] Créer les fixtures de démonstration
- [x] Créer le sujet du projet
- [x] Créer le fichier TODO initial

---

## Problèmes et risques à surveiller

- [ ] Conflits de réservation en cas de demandes simultanées
- [ ] Gestion complexe des droits selon les rôles et les entreprises
- [ ] Risque de données incohérentes entre calendrier, réservations et disponibilités
- [ ] Notifications non délivrées
- [ ] Surcharge lors des recherches de disponibilités
- [ ] Risques de sécurité API

---

## Documents associés

- [Cahier des charges](cahier-des-charges.md)
- [Sujet du projet](sujet-projet-cda.md)
- [TODO technique](TODO.md)
- [Proposition technique temporaire](proposition-technique-temporaire.md)
- [Journal des problèmes et solutions](journal-problemes-solutions.md)
- [Schéma SQL](Reservations.sql)
- [Fixtures de démonstration](Reservations.demo.fixtures.sql)
