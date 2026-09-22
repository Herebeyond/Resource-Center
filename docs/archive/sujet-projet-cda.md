# Projet CDA - Site web de réservation de ressources

## 1. Contexte
Le site s'adresse à toute entreprise souhaitant gérer des ressources partagées pour ses collaborateurs :
- salles de réunion
- véhicules
- ordinateurs portables
- vidéoprojecteurs
- autres équipements

Le projet consiste à concevoir et développer une application web permettant de réserver ces ressources de façon fiable, sécurisée et traçable.

Le mode d'enregistrement des entreprises, des utilisateurs et leurs interactions détaillées sera défini dans une étape ultérieure.

## 2. Problème à résoudre
Sans système centralisé, les réservations peuvent être gérées de façon informelle (mails, messages, tableurs), ce qui génère :
- des conflits de réservation
- un manque de visibilité sur les disponibilités
- une gestion des droits insuffisante
- une absence d'historique fiable

L'application doit apporter une solution claire à ces contraintes métier.

## 3. Objectifs du projet
L'application doit permettre de :
- réserver une ressource sur un créneau donné
- empêcher les conflits de réservation
- consulter les disponibilités en temps réel
- gérer les droits selon les profils utilisateurs
- afficher un calendrier de réservations
- envoyer des notifications aux utilisateurs concernés
- permettre l'administration des ressources
- conserver un historique des actions et réservations

## 4. Fonctionnalités principales

### 4.1 Gestion des utilisateurs et des droits
- Authentification des utilisateurs
- Rôles et permissions (exemple : Employé, Gestionnaire, Administrateur)
- Restrictions d'accès selon le rôle

### 4.2 Gestion des ressources
- Création, modification, désactivation, suppression logique d'une ressource
- Typologie des ressources (salle, véhicule, matériel, etc.)
- Suivi de l'état (disponible, indisponible, en maintenance)

### 4.3 Gestion des réservations
- Création de réservation avec date/heure de début et de fin
- Vérification automatique des chevauchements
- Validation selon les règles métier
- Annulation ou modification de réservation

### 4.4 Calendrier et visualisation
- Vue calendrier (jour/semaine/mois)
- Filtres par type de ressource, service, utilisateur
- Affichage des créneaux libres/occupés

### 4.5 Notifications
- Notifications de confirmation
- Notifications de rappel
- Notifications de modification/annulation

### 4.6 Historique et traçabilité
- Journalisation des actions importantes
- Historique des réservations passées
- Consultation pour audit interne

## 5. API pour applications externes
Le système doit exposer une API permettant à d'autres applications de consulter les disponibilités.

### 5.1 Besoin
- Permettre l'interconnexion avec les outils internes de chaque entreprise (intranet, ERP, planning, etc.)

### 5.2 Fonctions minimales de l'API
- Consulter la liste des ressources
- Consulter les disponibilités par ressource et période
- (Optionnel) Créer une réservation via API selon droits et règles

### 5.3 Contraintes techniques
- API REST
- Authentification sécurisée (token/JWT ou clé API)
- Réponses structurées (JSON)
- Gestion des erreurs (codes HTTP et messages explicites)

## 6. Contraintes non fonctionnelles
- Sécurité des accès et des données
- Performance sur les consultations de disponibilités
- Fiabilité (cohérence des données en cas d'accès concurrents)
- Ergonomie (interface simple pour les employés)
- Maintenabilité (code structuré, tests, documentation)

## 7. Livrables attendus
- Application web fonctionnelle
- Base de données modélisée
- API documentée
- Documentation technique et fonctionnelle
- Démonstration de scénarios métier

## 8. Exemples de scénarios d'usage
1. Un employé réserve une salle pour une réunion de 14h à 15h.
2. Un second employé tente de réserver la même salle sur le même créneau et le système refuse la demande.
3. Un administrateur met un vidéoprojecteur en maintenance : il devient indisponible à la réservation.
4. Une application externe interroge l'API pour connaître les disponibilités d'un véhicule sur une période donnée.

## 9. Évolution possible
- Workflow de validation (manager)
- Synchronisation avec agendas externes (Outlook/Google)
- Tableaux de bord statistiques (taux d'utilisation, ressources les plus reservees)
- Gestion multi-sites / multi-entites
