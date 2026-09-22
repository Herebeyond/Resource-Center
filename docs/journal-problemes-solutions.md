# Journal des problèmes et solutions

Ce document sert à noter tous les problèmes rencontrés pendant le projet, ainsi que la façon dont ils ont été résolus, contournés, ou remplacés.

## Mode d'utilisation
- Ajouter une entrée à chaque blocage important.
- Être concret : cause, impact, solution, résultat.
- Noter aussi les alternatives testées et les décisions prises.

---

## Template d'entrée (à copier/coller)

### [AAAA-MM-JJ] Titre du problème
- Contexte :
- Problème observé :
- Cause probable :
- Impact sur le projet :
- Solution appliquée :
- Contournement (si pas de correction complete) :
- Alternative retenue / remplacement :
- Résultat :
- Leçon apprise :
- Actions a faire ensuite :

---

## Historique

### [AAAA-MM-JJ] Exemple - Conflit de réservation non bloqué
- Contexte : test de réservation simultanée d'une salle.
- Problème observé : deux utilisateurs ont pu réserver le même créneau.
- Cause probable : vérification faite uniquement côté front-end.
- Impact sur le projet : incohérence des données et perte de confiance.
- Solution appliquée : ajout d'une vérification en base + contrainte métier côté back-end.
- Contournement (si pas de correction complète) : blocage temporaire par règle applicative stricte.
- Alternative retenue / remplacement : utilisation d'une transaction pour sécuriser l'écriture.
- Résultat : conflit correctement refusé.
- Leçon apprise : les règles critiques doivent être garanties côté serveur.
- Actions à faire ensuite : ajouter un test automatisé de concurrence.
