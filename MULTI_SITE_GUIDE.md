# Guide de Gestion Multi-Sites WordPress

Cette extension supporte maintenant la gestion de connexions multiples à différents sites WordPress, vous permettant de basculer facilement entre vos différents projets.

## 🚀 Fonctionnalités

### Gestion des Connexions
- **Connexions multiples** : Configurez et gérez plusieurs sites WordPress
- **Basculement rapide** : Changez de site actif en un clic
- **Noms personnalisés** : Donnez des noms explicites à vos connexions
- **Suppression sécurisée** : Supprimez les connexions inutiles

## 📋 Utilisation

### Première Configuration

1. **Ouvrez la palette de commandes** : `Cmd/Ctrl + Shift + P`
2. **Tapez** : `WordPress Snippets: Configure Connection`
3. **Suivez les étapes** pour configurer votre première connexion

### Ajouter une Nouvelle Connexion

1. **Cliquez sur l'icône** 🌐 dans la barre d'outils des snippets
2. **Ou utilisez la commande** : `WordPress Snippets: Manage Connections`
3. **Sélectionnez** : `➕ Ajouter une nouvelle connexion`
4. **Entrez** :
   - Nom de la connexion (optionnel)
   - URL du site WordPress
   - Nom d'utilisateur
   - Mot de passe d'application
   - Plugin de snippets à utiliser

### Basculer entre les Connexions

#### Méthode 1 : Menu Rapide
1. **Cliquez sur l'icône** 🌍 dans la barre d'outils
2. **Ou utilisez la commande** : `WordPress Snippets: Switch Connection`
3. **Sélectionnez** la connexion désirée

#### Méthode 2 : Gestionnaire Complet
1. **Cliquez sur l'icône** 🖥️ dans la barre d'outils
2. **Ou utilisez la commande** : `WordPress Snippets: Manage Connections`
3. **Sélectionnez** une connexion existante pour l'activer

### Supprimer une Connexion

1. **Ouvrez le gestionnaire** : `WordPress Snippets: Manage Connections`
2. **Sélectionnez** : `🗑️ Supprimer une connexion`
3. **Choisissez** la connexion à supprimer
4. **Confirmez** la suppression

## 🔧 Interface Utilisateur

### Icônes de la Barre d'Outils

- **🖥️** : Gestionnaire de connexions complet
- **🌍** : Basculement rapide entre connexions
- **🔄** : Changer de plugin de snippets (pour la connexion active)

### Indicateurs Visuels

- **🟢** : Connexion actuellement active
- **⚪** : Connexions disponibles mais inactives

## 💡 Conseils d'Utilisation

### Nommage des Connexions
- Utilisez des noms descriptifs : "Site Production", "Site de Test", "Client ABC"
- Le nom par défaut sera l'URL du site si aucun nom n'est fourni

### Organisation
- Gardez vos connexions organisées en supprimant celles qui ne sont plus utilisées
- Utilisez des noms cohérents pour faciliter l'identification

### Sécurité
- Utilisez toujours des mots de passe d'application WordPress
- Ne partagez jamais vos informations de connexion
- Supprimez les connexions des sites auxquels vous n'avez plus accès

## 🔄 Migration depuis l'Ancien Système

Si vous utilisiez déjà l'extension avec une seule connexion :

1. **Votre connexion existante** sera automatiquement conservée
2. **Ajoutez de nouvelles connexions** via le gestionnaire
3. **L'ancien système** reste compatible en arrière-plan

## ❓ Dépannage

### Problèmes de Connexion
- Vérifiez que le plugin WordPress "IDE Code Snippets Bridge" est installé et activé
- Assurez-vous que vos identifiants sont corrects
- Vérifiez que l'URL du site est accessible

### Connexions Manquantes
- Utilisez `WordPress Snippets: Manage Connections` pour voir toutes les connexions
- Ajoutez à nouveau la connexion si nécessaire

### Performance
- L'extension ne se connecte qu'au site actif
- Le changement de connexion est instantané
- Les données sont stockées de manière sécurisée dans VS Code

## 🆘 Support

Pour toute question ou problème :
- Consultez la documentation complète
- Ouvrez une issue sur le repository GitHub
- Contactez le support via [eliodata.com](https://eliodata.com)