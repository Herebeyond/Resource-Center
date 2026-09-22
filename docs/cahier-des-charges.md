# Cahier des charges - Site web de réservation de ressources

## 1. Objet du projet

Le projet consiste à concevoir et développer un site web de gestion des réservations de ressources partagées pour une entreprise ou toute organisation utilisant des équipements et espaces communs.

Le système doit permettre à des utilisateurs autorisés de consulter la disponibilité des ressources, réserver un matériel ou un espace, gérer les droits d’accès, consulter l’historique et administrer les ressources selon les règles métier définies.

## 2. Contexte

Les entreprises disposent souvent de ressources partagées, telles que :
- salles de réunion ;
- véhicules de service ;
- ordinateurs portables ;
- vidéoprojecteurs ;
- imprimantes ;
- autres équipements spécifiques.

Sans système centralisé, la gestion des réservations est souvent faite par messagerie, tableur ou appel téléphonique. Cela entraîne :
- des conflits de réservation ;
- des informations incomplètes ou incohérentes ;
- un manque de suivi des demandes ;
- des difficultés pour gérer les droits et les historiques ;
- une mauvaise visibilité sur les disponibilités.

Le site web doit donc centraliser la gestion des réservations et sécuriser les accès.

## 3. Objectif général

Mettre à disposition une application web permettant de :
- gérer les ressources de manière centralisée ;
- consulter les disponibilités en temps réel ;
- réserver une ressource selon un créneau ;
- empêcher les conflits de réservation ;
- administrer les droits d’accès ;
- afficher les réservations dans un calendrier ;
- conserver un historique des actions et réservations ;
- offrir une interface disponible en français et en anglais ;
- permettre au visiteur de basculer facilement entre les deux langues ;
- détecter automatiquement la langue par défaut selon le pays de l’utilisateur lorsque cela est possible ;
- prévoir un système de notifications, dont le périmètre sera défini ultérieurement ;
- prévoir une API de consultation pour les applications externes, dont le périmètre sera défini ultérieurement.

## 3.1 Périmètre du MVP

La première version (MVP) comprendra :
- l'authentification et la configuration initiale des utilisateurs ;
- la gestion des rôles et des droits ;
- la gestion des ressources ;
- la consultation des disponibilités ;
- la création, la modification et l'annulation des réservations ;
- la détection des conflits ;
- les avertissements pour les réservations adjacentes ou très rapprochées ;
- le calendrier journalier et hebdomadaire ;
- l'historique des actions et des réservations.

Les fonctionnalités indiquées comme évolutions futures pourront être modifiées ou supprimées selon le temps disponible et les besoins validés.

## 4. Public cible et utilisateurs

### 4.1 Utilisateurs
Les utilisateurs du système peuvent être classés en plusieurs profils :
- employé / collaborateur ;
- gestionnaire / responsable de service ;
- administrateur d'entreprise ;
- super administrateur technique du site ;
- utilisateur externe ou application partenaire (via API).

### 4.2 Rôles attendus
- Employé : consulter les ressources et, uniquement après activation par un administrateur, créer des réservations. Il peut modifier, déplacer ou annuler uniquement ses propres réservations, dans le délai autorisé.
- Gestionnaire : gérer les ressources de son périmètre, notamment ajouter, modifier, désactiver ou supprimer des véhicules, salles et autres ressources. Il peut également valider ou refuser certaines demandes de modification ou d'annulation, avec justification.
- Administrateur d’entreprise : gérer uniquement les utilisateurs, ressources, paramètres et réservations de sa propre entreprise. Il active ou désactive le droit de réservation des utilisateurs.
- Super administrateur du site : assurer le support technique global sans accès fonctionnel au contenu métier non déchiffré des entreprises. Ses interventions doivent être limitées, protégées et journalisées.
- Application partenaire : consulter les disponibilités via API.

La règle concernant la capacité d'un gestionnaire à agir sur les réservations de ses supérieurs hiérarchiques est mise de côté et devra être précisée ultérieurement.

## 5. Fonctionnalités attendues

### 5.1 Gestion des ressources
Le système devra permettre :
- l’ajout d’une ressource ;
- la modification des caractéristiques de la ressource ;
- la désactivation ou la mise hors service d’une ressource ;
- la gestion de son état (disponible, réservé, indisponible, en maintenance) ;
- la catégorisation par type (salle, véhicule, informatique, audiovisuel, etc.) ;
- la gestion des informations associées à chaque ressource (code, libellé, localisation, capacité, disponibilité, règles de réservation, etc.).

### 5.2 Gestion des utilisateurs et des droits
Le système devra permettre :
- la création d'une entreprise par son administrateur ;
- l'envoi d'un email de vérification lors de la création de l'entreprise ;
- l'invitation d'un utilisateur au moyen d'un code à usage contrôlé ;
- l'initialisation du compte lors de la première connexion avec le code et un mot de passe ;
- la possibilité d'utiliser le code directement dans la page « Sign in / Register », sans rendre obligatoire l'utilisation du lien présent dans l'email ;
- la désactivation d'un compte par un administrateur ;
- la gestion des rôles et permissions ;
- la restriction des accès selon le profil ;
- la protection des actions sensibles par contrôle d’autorisation ;
- l’affectation d’un utilisateur à un service, un site ou une zone donnée selon la logique métier.

Par défaut, un utilisateur ne peut pas créer de réservation. Un administrateur doit explicitement activer cette permission.

### 5.3 Gestion des réservations
Le système devra permettre :
- la création d’une réservation avec date, heure de début et heure de fin ;
- la sélection d’une ressource disponible ;
- la validation automatique des conflits ;
- la modification d’une réservation existante ;
- l’annulation d’une réservation ;
- la gestion des réservations récurrentes si besoin plus tard ;
- la consultation du statut d’une réservation (en attente, validée, refusée, annulée, terminée).

Une réservation future peut être créée uniquement par un utilisateur dont la permission de réservation est active. Par défaut, la durée minimale est de 15 minutes. Une modification, un déplacement ou une annulation sont autorisés jusqu'à une heure avant le début de la réservation. Ce délai est configurable par un administrateur.

Un gestionnaire peut valider ou refuser une demande de modification ou d'annulation lorsqu'elle nécessite une validation. Tout refus ou toute décision nécessitant une justification doit être accompagné d'un motif conservé dans l'historique.

### 5.4 Gestion des disponibilités
Le système devra :
- afficher les ressources disponibles pour une période donnée ;
- afficher les créneaux déjà réservés ;
- indiquer les ressources indisponibles ou en maintenance ;
- prévenir l’utilisateur si une réservation est impossible en raison d’un conflit ;
- filtrer les ressources par type, lieu, capacité ou service.

### 5.5 Calendrier
Le système devra proposer :
- une vue par jour ;
- une vue par semaine ;
- une vue par mois pourra être ajoutée ultérieurement ;
- une vue filtrée par ressource ;
- une vue filtrée par utilisateur ou service ;
- une visualisation claire des créneaux libres et occupés.

L'utilisateur pourra sélectionner directement un créneau libre dans le calendrier horaire.

### 5.6 Internationalisation et langue
Le site web devra être disponible en français et en anglais.

Le système devra permettre :
- un accès complet au site en français ;
- un accès complet au site en anglais ;
- un bouton de bascule clair pour passer d’une langue à l’autre ;
- la détection du pays de l’utilisateur pour déterminer la langue par défaut lorsque cela est possible ;
- la possibilité pour l’utilisateur de conserver un choix manuel ou de réinitialiser la langue par défaut à tout moment ;
- une interface cohérente et entièrement traduite dans les deux langues, sans texte non localisé.

La logique de langue devra être prise en charge côté application et, si nécessaire, conservée dans le navigateur ou la session utilisateur pour éviter un changement de langue aléatoire lors de la navigation.

### 5.7 Notifications
La stratégie de notifications n'est pas encore arrêtée. Elle sera définie après le périmètre fonctionnel principal. Les notifications par email, les notifications dans l'application et les rappels restent des options à étudier.

### 5.8 Historique et traçabilité
Le système devra conserver :
- les réservations passées et présentes ;
- les actions réalisées par les utilisateurs ;
- les changements de statut d’une ressource ;
- les modifications et suppressions liées aux réservations ;
- des logs pour le suivi et l’audit.

### 5.9 API de consultation
Une API de consultation est prévue, mais ses endpoints, son authentification et son périmètre exact restent à définir. Elle pourra notamment permettre de consulter les ressources et leurs disponibilités sur une période donnée.

## 6. Règles métier prioritaires

Le système devra respecter les règles suivantes :
- une ressource ne peut pas être réservée deux fois sur le même créneau ;
- une ressource indisponible ou en maintenance ne peut pas être réservée ;
- un utilisateur ne peut effectuer que les actions autorisées par son rôle et ses permissions activées ;
- un utilisateur ne peut pas réserver tant qu'un administrateur n'a pas activé sa permission de réservation ;
- une réservation annulée doit rester dans l’historique ;
- les conflits de réservation doivent être détectés avant validation ;
- un chevauchement réel est bloquant ;
- une réservation adjacente ou séparée de moins de cinq minutes déclenche un avertissement non bloquant ;
- le délai d'une heure pour modifier, déplacer ou annuler une réservation est configurable par un administrateur ;
- la langue active du site doit être cohérente sur toutes les pages et rester stable lors de la session ;
- le choix manuel de langue doit avoir priorité sur la détection automatique si un utilisateur a déjà sélectionné une langue ;
- chaque action importante doit être journalisée ;
- les réservations doivent être consultables selon le rôle de l’utilisateur ;
- une entreprise ne peut consulter que ses propres données métier.

## 7. Cas d’utilisation principaux

### Cas d’utilisation 1 : consultation des disponibilités
Un utilisateur consulte la disponibilité d’une ressource pour une date donnée. Il visualise les créneaux libres et occupés.

### Cas d’utilisation 2 : réservation d’une ressource
Un utilisateur dont la permission est activée sélectionne une ressource et un créneau libre. Le système vérifie la disponibilité et enregistre la réservation si la règle métier est respectée.

### Cas d’utilisation 3 : refus de réservation
Un utilisateur tente de réserver une ressource déjà réservée sur le même créneau. Le système bloque la demande et affiche un message explicite.

### Cas d’utilisation 4 : annulation
Un utilisateur annule une réservation. Le créneau devient de nouveau disponible et l’événement est conservé dans l’historique.

### Cas d’utilisation 5 : gestion d’une ressource
Un gestionnaire ou un administrateur d'entreprise modifie les caractéristiques d'une ressource, la désactive ou la remet en service selon ses permissions.

### Cas d’utilisation 6 : consultation de l’historique
Un gestionnaire consulte les réservations passées et les actions effectuées sur une ressource.

### Cas d’utilisation 7 : appel API
Une application externe pourra demander les disponibilités d’une ressource sur une période lorsque le périmètre et la sécurité de l’API auront été définis.

## 8. Exigences fonctionnelles

Le système doit :
- permettre la création vérifiée d'une entreprise par son administrateur ;
- permettre l'invitation d'utilisateurs par code et la configuration de leur compte lors de la première connexion ;
- sécuriser les opérations d’écriture ;
- centraliser les informations de réservation ;
- afficher les données de manière claire et lisible ;
- gérer les droits selon les profils ;
- garantir la cohérence des données pour les réservation et les disponibilités ;
- permettre la récupération de l’historique et des logs ;
- préparer une future intégration avec des applications externes, sans imposer l'API au périmètre initial.

## 9. Exigences non fonctionnelles

### 9.1 Performance
- les pages et les requêtes doivent rester réactives même avec un nombre important de ressources et de réservations ;
- les recherches de disponibilité doivent être optimisées.

### 9.2 Sécurité
- authentification des utilisateurs ;
- contrôle d’accès strict selon les rôles ;
- protection des données sensibles ;
- sécurisation des échanges API ;
- gestion des erreurs sans fuite d’informations.

### 9.3 Confiance et intégrité
- les réservations doivent rester cohérentes ;
- les conflits doivent être gérés côté serveur ;
- les journaux d’audit doivent conserver les informations utiles.

### 9.4 Maintenabilité
- code structuré et lisible ;
- séparation entre logique métier, interface et données ;
- tests unitaires et fonctionnels sur les règles métier critiques.

### 9.5 Ergonomie
- interface claire et compréhensible ;
- navigation simple pour la réservation et la consultation ;
- calendrier lisible et adapté aux utilisateurs ;
- bouton de changement de langue visible et accessible sur toutes les pages ;
- expérience fluide pour un utilisateur francophone ou anglophone ;
- détection de pays et choix de langue par défaut sans interruption de l’usage.

## 10. Contraintes techniques

- backend principal : Symfony ;
- base de données principale : PostgreSQL relationnelle ;
- interface initiale : rendu serveur et composants simples, avec enrichissement dynamique si nécessaire pour le calendrier ;
- architecture progressive : ne pas ajouter de service complémentaire tant qu'un besoin concret ne le justifie pas ;
- utilisation d’une base de données fiable et cohérente ;
- application web avec interface utilisateur et API ;
- stockage de l’historique des actions ;
- support d’un nombre suffisant de ressources et d’utilisateurs ;
- gestion des droits au niveau applicatif ;
- sécurisation des accès utilisateur et API ;
- possibilité d’évoluer vers une architecture multi-entreprise ou multi-site.
- déploiement sur une machine virtuelle fournie par l'école ; les services et dépendances nécessaires devront être installés et configurés par le projet via l'accès disponible à la machine virtuelle et à la ligne de commande.

## 11. Contraintes fonctionnelles à traiter plus tard

Les questions suivantes restent à préciser dans une phase ultérieure du projet :
- stratégie exacte des notifications ;
- endpoints, authentification et périmètre de l'API ;
- règle permettant ou non à un gestionnaire d'agir sur les réservations de ses supérieurs hiérarchiques ;
- politique de protection des données métier accessible au super administrateur technique ;
- stratégie de détection de pays précise en cas d’absence de données géolocalisées ou de blocage du navigateur ;
- synchronisation avec des outils externes ;
- fonctionnalités futures qui pourront être modifiées ou supprimées.

## 12. Livrables attendus

Le projet devra livrer :
- une application web fonctionnelle ;
- une base de données modélisée ;
- un système de réservation avec gestion des conflits ;
- un calendrier de visualisation ;
- une gestion des droits et des ressources ;
- un historique des actions et réservations ;
- la conception d'un futur module de notifications ;
- la conception d'une future API de consultation ;
- une documentation technique et fonctionnelle ;
- une démonstration de scénarios métier.

## 13. Critères de validation

Le projet sera considéré comme satisfaisant si :
- les ressources ne peuvent être réservées en conflit ;
- les droits d’accès sont correctement appliqués ;
- le calendrier affiche les disponibilités de façon fiable ;
- l’historique est exploitable ;
- les cas de non-conformité sont clairement signalés à l’utilisateur.

## 14. Périmètre du projet

### Inclus
- réservation de ressources ;
- gestion des disponibilités ;
- droits d’accès et rôles ;
- calendrier ;
- notifications ;
- historique ;
- API de consultation ;
- administration des ressources.

### Exclu
- gestion comptable complexe ;
- intégration avancée avec des outils tiers non demandés ;
- gestion de facturation automatique ;
- synchronisation avancée avec plusieurs systèmes externes sans besoin métier clairement défini.

## 15. Conclusion

Ce projet vise à fournir une solution simple, robuste et fiable pour la gestion de ressources partagées au sein d’une organisation. L’objectif principal est de permettre à un utilisateur de réserver une ressource en toute sécurité, sans conflit, tout en offrant une visibilité claire sur les disponibilités et un historique exploitable pour l’administration et l’audit.
