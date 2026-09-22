# TODO - Projet réservation de ressources

## Priorité haute
- Implémenter la création vérifiée d'une entreprise par son administrateur.
- Implémenter l'invitation des utilisateurs par code et la configuration de première connexion.
- Implémenter l'activation et la désactivation de la permission de réservation par un administrateur.
- Définir et sécuriser le fonctionnement du super administrateur de support.
- Définir la politique de protection des données métier accessible au super administrateur technique.

## Priorité moyenne
- Implémenter les règles de gestion des conflits de réservation (cas limites inclus).
- Implémenter un avertissement non bloquant quand une réservation commence exactement à la fin d'une autre (ou finit exactement au début d'une autre) ou très proche (par exemple 5min), avec suggestion de décalage de quelques minutes côté application.
- Définir la stratégie de notifications (email, in-app, rappels).
- Définir les endpoints API minimum pour la consultation des disponibilités.
- Définir les contraintes de sécurité API (authentification, autorisation, limitation).
- Décider si les notifications et l'API appartiennent au MVP ou à une évolution future.
- Définir la règle permettant ou non au gestionnaire d'agir sur les réservations de ses supérieurs hiérarchiques.
- Installer et configurer les dépendances nécessaires sur la machine virtuelle de l'école.

## Priorité basse
- Définir les indicateurs de suivi (taux d'occupation, historique, usage par type de ressource).
- Lister les évolutions futures (workflow de validation, synchronisation calendrier, multi-sites).

## Problèmes potentiels
- Conflits de réservation en cas de demandes simultanées.
- Gestion complexe des droits selon les rôles et les entreprises.
- Risque de données incohérentes entre calendrier, réservations et disponibilités.
- Notifications non délivrées (emails bloqués, erreurs de file d'attente, etc.).
- Surcharge sur les recherches de disponibilités si le volume augmente.
- Risques de sécurité API (accès non autorisé, abus d'usage, fuite de données).

### Problème potentiel - Template rapide
- Problème :
- Impact :
- Probabilité : Faible / Moyenne / Haute
- Prévention :
- Plan de secours :

## Notes libres
-
