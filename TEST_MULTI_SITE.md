# Test de la Fonctionnalité Multi-Sites

## ✅ Liste de Vérification

### Configuration Initiale
- [ ] L'extension se lance sans erreur
- [ ] La première connexion est automatiquement migrée vers le nouveau système
- [ ] Les anciennes configurations restent fonctionnelles

### Gestion des Connexions
- [ ] **Ajouter une connexion** : `WordPress Snippets: Manage Connections` → `➕ Ajouter une nouvelle connexion`
- [ ] **Nommer une connexion** : Le nom personnalisé s'affiche correctement
- [ ] **Connexion par défaut** : L'URL est utilisée comme nom si aucun nom n'est fourni
- [ ] **Validation des données** : Les champs obligatoires sont vérifiés

### Basculement entre Connexions
- [ ] **Menu rapide** : L'icône 🌍 affiche la liste des connexions
- [ ] **Indicateur actif** : La connexion active est marquée avec 🟢
- [ ] **Changement de connexion** : Le basculement fonctionne instantanément
- [ ] **Mise à jour de l'interface** : La liste des snippets se met à jour

### Suppression de Connexions
- [ ] **Menu de suppression** : `🗑️ Supprimer une connexion` est disponible
- [ ] **Confirmation** : Une confirmation est demandée avant suppression
- [ ] **Nouvelle connexion active** : Une autre connexion devient active automatiquement
- [ ] **Dernière connexion** : La suppression de la dernière connexion est gérée

### Interface Utilisateur
- [ ] **Icônes dans la barre d'outils** :
  - [ ] 🖥️ Gestionnaire de connexions
  - [ ] 🌍 Basculement rapide
  - [ ] 🔄 Changement de plugin (existant)
- [ ] **Menus contextuels** : Les nouvelles commandes apparaissent
- [ ] **Raccourcis clavier** : Les raccourcis existants fonctionnent toujours

### Compatibilité
- [ ] **Ancien système** : Les utilisateurs existants peuvent continuer à utiliser l'extension
- [ ] **Migration automatique** : L'ancienne configuration est préservée
- [ ] **Plugins supportés** : Code Snippets et FluentSnippets fonctionnent

### Stockage et Sécurité
- [ ] **Stockage sécurisé** : Les données sont dans VS Code Secret Storage
- [ ] **Persistance** : Les connexions sont conservées après redémarrage
- [ ] **Isolation** : Chaque connexion est indépendante

## 🧪 Scénarios de Test

### Scénario 1 : Premier Utilisateur
1. Installer l'extension
2. Configurer la première connexion
3. Vérifier que la connexion est active
4. Ajouter une deuxième connexion
5. Basculer entre les deux

### Scénario 2 : Utilisateur Existant
1. Avoir une configuration existante
2. Mettre à jour l'extension
3. Vérifier que l'ancienne connexion fonctionne
4. Ajouter une nouvelle connexion
5. Tester le basculement

### Scénario 3 : Gestion Avancée
1. Créer 3+ connexions avec des noms personnalisés
2. Tester le basculement rapide
3. Supprimer une connexion inactive
4. Supprimer la connexion active
5. Vérifier la gestion automatique

### Scénario 4 : Cas d'Erreur
1. Tenter de se connecter à un site inexistant
2. Utiliser des identifiants incorrects
3. Tester avec un site sans le plugin WordPress
4. Vérifier la gestion des erreurs

## 🐛 Points d'Attention

### Performance
- Le changement de connexion doit être instantané
- Pas de ralentissement avec plusieurs connexions
- Mémoire utilisée raisonnable

### Expérience Utilisateur
- Messages d'erreur clairs et en français
- Interface intuitive
- Pas de perte de données

### Robustesse
- Gestion des connexions réseau instables
- Récupération après erreur
- Validation des données d'entrée

## 📝 Rapport de Test

**Date** : ___________
**Testeur** : ___________
**Version** : 2.1.0

### Résultats
- [ ] ✅ Tous les tests passent
- [ ] ⚠️ Tests passent avec remarques mineures
- [ ] ❌ Échecs critiques détectés

### Notes
```
[Espace pour les commentaires du testeur]
```

### Bugs Identifiés
```
[Liste des bugs trouvés avec étapes de reproduction]
```

### Améliorations Suggérées
```
[Suggestions d'amélioration de l'UX/UI]
```