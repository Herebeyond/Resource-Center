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
  - [x] Ajouter le support FR / EN
  - [x] Ajouter le bouton de changement de langue
  - [x] Détecter la langue par défaut selon le pays / navigateur
  - [x] Vérifier le comportement sur la homepage et la page de connexion

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
  - [x] Ajouter la page de connexion
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
  - [x] Rediriger les utilisateurs non connectés vers la connexion
  - [x] Ajouter la protection CSRF
  - [x] Régénérer la session après connexion
  - [x] Limiter les statistiques aux ressources de l'entreprise connectée
  - [x] Ajouter les rôles
  - [x] Protéger les pages privées
  - [x] Refuser les actions non autorisées
  - [x] Afficher un message en cas d'accès interdit

- [x] Créer les pages d'erreur
  - [x] Créer une page 404 pour les pages ou ressources introuvables
  - [x] Créer une page « Accès refusé » pour les ressources dont l'existence peut être révélée
  - [x] Utiliser une réponse 404 à la place d'une réponse 403 pour les pages d'administration cachées
  - [x] Vérifier que les pages d'erreur ne divulguent aucune donnée ni information technique sensible

- [ ] Développer la gestion des ressources
  - [x] Afficher la liste des ressources dans l'espace administrateur
  - [x] Ajouter une ressource
  - [ ] Modifier une ressource
  - [x] Désactiver une ressource
  - [x] Définir le type et la localisation lors de la création
  - [x] Définir la capacité lors de la création
  - [ ] Gérer les états disponible, indisponible et maintenance
  - [x] Alimenter les statistiques de la homepage depuis PostgreSQL
  - [x] Afficher le total et la disponibilité des salles depuis PostgreSQL

- [x] Créer l'espace d'administration de l'entreprise
  - [x] Afficher un tableau de bord réservé aux administrateurs
  - [x] Afficher la liste des utilisateurs de l'entreprise
  - [x] Gérer les rôles des utilisateurs de l'entreprise
  - [x] Activer et désactiver les comptes utilisateurs
  - [x] Empêcher la désactivation ou la rétrogradation du dernier administrateur
  - [x] Créer, lister et désactiver les ressources de l'entreprise
  - [x] Limiter les opérations administrateur à l'entreprise de l'administrateur

- [x] Organiser les styles frontend
  - [x] Extraire les styles inline des templates Twig
  - [x] Créer la feuille `app/public/css/app.css`

- [ ] Développer la consultation des disponibilités
  - [x] Sélectionner une date
  - [x] Sélectionner une heure de début et de fin
  - [x] Filtrer par type de ressource
  - [x] Filtrer par capacité ou localisation
  - [x] Afficher les créneaux occupés
  - [ ] Afficher les ressources indisponibles
  - [ ] Afficher un message lorsqu'aucun résultat n'est trouvé
  - [x] Filtrer les équipements par marque, catégorie et localisation

- [ ] Développer la création d'une réservation
  - [x] Sélectionner une ressource
  - [x] Sélectionner un créneau
  - [x] Saisir le motif de la réservation
  - [x] Vérifier les données saisies
  - [x] Vérifier la disponibilité côté serveur
  - [x] Enregistrer la réservation
  - [x] Afficher une confirmation
  - [x] Empêcher la double réservation

- [x] Créer les pages de réservation par type de ressource
  - [x] Page réutilisable pour salles, véhicules et équipements
  - [x] Ressources chargées depuis PostgreSQL
  - [x] Réservations existantes affichées dans la timeline
  - [x] Icônes réelles utilisées selon le type de ressource
  - [x] Ajouter le changement de langue
  - [x] Rendre les filtres fonctionnels
  - [x] Ajouter les modes heures de travail et journée complète
  - [x] Afficher la plage saisie en orange avec bordure en pointillés
  - [x] Synchroniser le résumé avec les champs en temps réel
  - [x] Empêcher la soumission avec la touche Entrée
  - [x] Ajouter plusieurs salles de démonstration
  - [x] Sélectionner une salle sans recharger la page
  - [x] Actualiser la timeline et le résumé lors du changement de salle

- [ ] Gérer les conflits et les réservations rapprochées
  - [x] Détecter les chevauchements de réservation
  - [x] Bloquer une réservation qui chevauche une autre
  - [x] Afficher un message d'erreur explicite en cas de conflit
  - [x] Détecter les réservations directement adjacentes
  - [x] Détecter un écart inférieur à cinq minutes
  - [x] Afficher un avertissement non bloquant
  - [ ] Proposer un décalage du créneau
  - [x] Permettre de confirmer malgré l'avertissement
  - [ ] Tester les chevauchements et les créneaux adjacents

- [ ] Gérer la modification et l'annulation
  - [x] Afficher les réservations de l'utilisateur
  - [ ] Modifier une réservation
  - [ ] Vérifier les conflits après modification
  - [x] Annuler une réservation depuis son détail dans le calendrier, après confirmation temporisée de trois secondes
  - [x] Conserver la réservation annulée dans la base et l'historique d'audit
  - [x] Libérer le créneau après annulation

- [ ] Développer le calendrier
  - [x] Créer une vue mensuelle avec navigation entre les mois
  - [x] Afficher l'agenda du jour sélectionné
  - [x] Créer une vue hebdomadaire
  - [x] Afficher les réservations de l'utilisateur connecté et les ressources associées
  - [x] Afficher le détail d'une réservation sélectionnée
  - [x] Naviguer entre les jours sans recharger la page
  - [x] Afficher les chevauchements côte à côte avec une largeur adaptée
  - [x] Distinguer visuellement salles, véhicules et autres équipements
  - [x] Permettre l'annulation sécurisée d'une réservation depuis son détail
  - [ ] Afficher les créneaux libres
  - [ ] Filtrer par ressource
  - [x] Filtrer les réservations par utilisateur connecté
  - [x] Adapter l'affichage aux petits écrans

- [ ] Ajouter l'historique et les notifications
  - [x] Afficher les notifications personnelles
  - [x] Marquer une notification comme lue
  - [x] Ajouter la page Notifications à la navigation principale
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

- [x] Maintenir les dépendances Symfony
  - [x] Mettre Symfony à jour vers la version 7.4 maintenue
  - [x] Vérifier l'audit Composer

- [ ] Préparer la démonstration finale
  - [x] Créer les données de démonstration, dont de nombreux matériels et des réservations qui se chevauchent
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
- [ ] Définir dans les conditions d'utilisation que le super administrateur peut, selon ses droits, accéder aux informations de toutes les entreprises
- [ ] Ajouter dans les paramètres de l'entreprise une option activée par défaut permettant à son administrateur d'empêcher l'accès du super administrateur aux informations de cette entreprise
- [ ] Synchroniser avec Google Calendar ou Outlook
- [ ] Ajouter des statistiques avancées
- [ ] Ajouter une application mobile
- [ ] Ajouter des intégrations externes avancées
- [ ] Ajouter les notifications SMS

- [ ] Concevoir la gestion avancée des flottes de véhicules
  - [x] Regrouper dans la première liste les véhicules strictement identiques afin de n'afficher qu'une seule entrée par marque et modèle
  - [x] Calculer la disponibilité agrégée d'un groupe depuis les créneaux de ses véhicules physiques, sans les afficher dans la première liste
  - [x] Au clic sur un groupe, afficher ses véhicules physiques et leur disponibilité sur le créneau sélectionné
  - [x] Trier les véhicules physiques disponibles en premier pour le créneau sélectionné
  - [x] Conserver le filtre de localisation de la première recherche dans la seconde liste et le reprendre comme valeur initiale
  - [x] Masquer les véhicules physiques situés hors de la localisation sélectionnée
  - [x] Afficher les créneaux de réservation et la place de parking du véhicule physique
  - [x] Vérifier les conflits sur l'identifiant de chaque véhicule physique
  - [x] Classer les véhicules par carrosserie et générer les options de filtre depuis les classes présentes en base
  - [x] Ajouter 200 véhicules de démonstration couvrant citadines, SUV, pick-up, camionnettes, autobus, semi-remorques et autres catégories
  - [x] Fournir une interface web responsive pour signaler la prise en charge et le retour d'un véhicule
  - [x] Enregistrer et afficher les retards de retour
  - [x] Déclarer un problème technique avec une description
  - [x] Retirer des choix de réservation les véhicules en maintenance
  - [ ] Étudier une application mobile native pour les opérations de flotte
  - [ ] Alerter toutes les personnes dont une réservation est affectée par l'indisponibilité technique d'un véhicule
  - [ ] Lorsqu'un véhicule est rendu en retard et qu'une réservation suivante approche, prévenir la personne concernée
  - [x] Proposer un autre véhicule de même marque et modèle lorsqu'un véhicule compatible est disponible sur le créneau

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
