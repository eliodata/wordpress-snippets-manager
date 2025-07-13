# Guide de résolution - Problèmes de toggle FluentSnippets et Code Snippets

## 🚨 Problèmes identifiés

### 1. Erreur 404 FluentSnippets
- **Erreur**: `AxiosError: Request failed with status code 404`
- **Cause**: Plugin WordPress pas mis à jour avec le nouvel endpoint

### 2. Code Snippets toggle non fonctionnel
- **Symptôme**: Pas d'erreur mais toggle ne fonctionne pas
- **Cause**: Plugin WordPress pas mis à jour avec la correction de table

## 🔧 Solution étape par étape

### Étape 1: Mettre à jour le plugin WordPress

1. **Sauvegarder votre site WordPress** (important !)

2. **Désactiver l'ancien plugin**:
   - Aller dans `Plugins` > `Plugins installés`
   - Désactiver `IDE Snippets Bridge`

3. **Supprimer l'ancien plugin**:
   - Supprimer le dossier `wp-content/plugins/ide-snippets-bridge/`

4. **Installer le nouveau plugin**:
   - Extraire `ide-snippets-bridge-fixed.zip`
   - Copier le contenu dans `wp-content/plugins/ide-snippets-bridge/`
   - Ou utiliser l'interface WordPress pour uploader le ZIP

5. **Réactiver le plugin**:
   - Aller dans `Plugins` > `Plugins installés`
   - Activer `IDE Snippets Bridge`

### Étape 2: Mettre à jour l'extension VS Code

1. **Désinstaller l'ancienne version**:
   - Dans VS Code: `Ctrl+Shift+X` > Rechercher "WordPress Snippets Manager"
   - Clic droit > "Uninstall"

2. **Installer la nouvelle version**:
   - `Ctrl+Shift+P` > "Extensions: Install from VSIX"
   - Sélectionner `wordpress-snippets-manager-2.2.0.vsix`

### Étape 3: Tester avec logs de débogage

1. **Ouvrir la console de développement**:
   - `Ctrl+Shift+P` > "Developer: Toggle Developer Tools"
   - Aller dans l'onglet "Console"

2. **Tester le toggle FluentSnippets**:
   - Essayer de toggle un FluentSnippet
   - Observer les logs `[DEBUG]` dans la console
   - Noter l'URL exacte appelée et la réponse

3. **Tester le toggle Code Snippets**:
   - Essayer de toggle un Code Snippet
   - Vérifier qu'il fonctionne sans erreur

## 🔍 Diagnostic des logs

### Logs attendus pour FluentSnippets:
```
[DEBUG] Toggling FluentSnippet with ID: FS1
[DEBUG] Found snippet: Mon Snippet, current status: true
[DEBUG] Using numeric ID for API call: 1
[DEBUG] Making API call to: http://votre-site/wp-json/ide/v1/fluent-snippets/1/toggle
[DEBUG] Payload: {active: false}
[DEBUG] API response status: 200
[DEBUG] API response data: {success: true, message: "FluentSnippet status toggled successfully", active: false, id: "1"}
```

### Si erreur 404:
- L'URL dans les logs devrait être: `http://votre-site/wp-json/ide/v1/fluent-snippets/1/toggle`
- Si l'URL est différente, vérifier la configuration de l'extension
- Si l'URL est correcte, le plugin WordPress n'est pas à jour

### Si erreur d'authentification:
- Vérifier les credentials dans les paramètres de l'extension
- Tester avec un autre endpoint (ex: `/wp-json/ide/v1/snippets`)

## 📋 Checklist de vérification

- [ ] Plugin WordPress `ide-snippets-bridge-fixed.zip` installé
- [ ] Plugin activé dans WordPress
- [ ] Extension VS Code `wordpress-snippets-manager-2.2.0.vsix` installée
- [ ] Configuration API correcte (URL, username, app password)
- [ ] Console de développement ouverte pour voir les logs
- [ ] Test toggle FluentSnippets avec observation des logs
- [ ] Test toggle Code Snippets

## 🆘 Si les problèmes persistent

1. **Vérifier l'endpoint manuellement**:
```bash
curl -X GET "http://votre-site/wp-json/ide/v1/fluent-snippets" \
  -u "username:app_password"
```

2. **Vérifier les logs WordPress**:
   - Activer `WP_DEBUG` dans `wp-config.php`
   - Consulter `wp-content/debug.log`

3. **Partager les logs de débogage**:
   - Copier tous les logs `[DEBUG]` de la console
   - Inclure l'URL exacte et la réponse d'erreur

## 📁 Fichiers fournis

- `ide-snippets-bridge-fixed.zip` - Plugin WordPress corrigé
- `wordpress-snippets-manager-2.2.0.vsix` - Extension VS Code avec logs de débogage
- `DEBUG_GUIDE.md` - Guide technique détaillé
- `SOLUTION_GUIDE.md` - Ce guide de résolution

## ✅ Résultat attendu

Après ces étapes:
- ✅ FluentSnippets toggle fonctionne sans erreur 404
- ✅ Code Snippets toggle fonctionne correctement
- ✅ Statut actif/inactif affiché correctement pour les deux types
- ✅ Logs de débogage visibles dans la console pour diagnostic