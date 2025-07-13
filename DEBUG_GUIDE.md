# Guide de débogage - Problèmes de toggle FluentSnippets et Code Snippets

## Problèmes identifiés

### 1. Erreur 404 pour FluentSnippets
**Symptôme**: `AxiosError: Request failed with status code 404`

**Causes possibles**:
- Le plugin WordPress n'a pas été mis à jour avec les nouvelles corrections
- L'endpoint `/fluent-snippets/{id}/toggle` n'est pas accessible
- Problème de format d'ID (FS1 vs 1)

### 2. Code Snippets toggle ne fonctionne pas
**Symptôme**: Pas de message d'erreur mais le toggle ne fonctionne pas

**Cause probable**: Le plugin WordPress n'a pas été mis à jour

## Solutions de test

### Étape 1: Vérifier l'installation du plugin
1. Aller dans le dossier WordPress
2. Remplacer le plugin existant par `ide-snippets-bridge-fixed.zip`
3. Réactiver le plugin si nécessaire

### Étape 2: Tester les endpoints manuellement
```bash
# Test endpoint FluentSnippets
curl -X GET "http://votre-site/wp-json/ide/v1/fluent-snippets" \
  -H "Authorization: Basic [votre-auth]"

# Test toggle FluentSnippets
curl -X PUT "http://votre-site/wp-json/ide/v1/fluent-snippets/1/toggle" \
  -H "Authorization: Basic [votre-auth]" \
  -H "Content-Type: application/json" \
  -d '{"active": false}'
```

### Étape 3: Vérifier les logs
1. Activer les logs WordPress
2. Vérifier les erreurs dans wp-content/debug.log

## Corrections apportées

### Dans le plugin WordPress:
- ✅ Ajout de l'endpoint `/fluent-snippets/{id}/toggle`
- ✅ Implémentation de `toggle_fluent_snippet()`
- ✅ Correction de la table pour Code Snippets
- ✅ Détection du statut basée sur l'emplacement des fichiers

### Dans l'extension VS Code:
- ✅ Méthode `toggleFluentSnippet()` dans ApiConnector
- ✅ Méthode `toggleSnippet()` dans FluentSnippetProvider
- ✅ Logique conditionnelle dans SnippetController

## Fichiers modifiés
- `wordpress-plugin/includes/class-ide-snippets-api.php`
- `src/core/ApiConnector.ts`
- `src/providers/FluentSnippetProvider.ts`
- `src/controllers/SnippetController.ts`

## Test recommandé
1. Installer `ide-snippets-bridge-fixed.zip` dans WordPress
2. Installer `wordpress-snippets-manager-2.2.0.vsix` dans VS Code
3. Tester le toggle sur les deux types de snippets