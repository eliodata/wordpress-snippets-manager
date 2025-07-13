# Guide de vérification - Plugin WordPress non fonctionnel

## 🚨 Problème persistant

Malgré la mise à jour du plugin, l'erreur 404 persiste :
- ❌ FluentSnippets toggle : `HTTP 404: Not Found`
- ❌ Code Snippets toggle : Pas de logs, pas de fonctionnement

## 🔍 Vérifications à effectuer

### 1. Vérifier l'installation du plugin

**Dans WordPress Admin :**
1. Aller dans `Extensions` > `Extensions installées`
2. Chercher `IDE Snippets Bridge`
3. Vérifier qu'il est **activé**
4. Noter la **version** affichée

**Via FTP/cPanel :**
1. Aller dans `wp-content/plugins/ide-snippets-bridge/`
2. Vérifier que le fichier `includes/class-ide-snippets-api.php` existe
3. Ouvrir le fichier et chercher la ligne :
   ```php
   register_rest_route($this->namespace, '/fluent-snippets/(?P<id>\d+)/toggle',
   ```
4. Si cette ligne n'existe pas, le plugin n'a pas été correctement mis à jour

### 2. Vérifier les routes API disponibles

**Test manuel avec curl :**
```bash
# Remplacer par vos vraies valeurs
SITE_URL="http://votre-site.com"
USERNAME="votre-username"
APP_PASSWORD="votre-app-password"

# Test de base
curl -u "$USERNAME:$APP_PASSWORD" "$SITE_URL/wp-json/ide/v1/"

# Test FluentSnippets
curl -u "$USERNAME:$APP_PASSWORD" "$SITE_URL/wp-json/ide/v1/fluent-snippets"

# Test endpoint toggle (devrait retourner 404 si manquant)
curl -X PUT -u "$USERNAME:$APP_PASSWORD" \
  -H "Content-Type: application/json" \
  -d '{"active": false}' \
  "$SITE_URL/wp-json/ide/v1/fluent-snippets/1/toggle"
```

**Avec le script Node.js :**
1. Modifier `test-endpoints.js` avec vos vraies valeurs
2. Exécuter : `node test-endpoints.js`

### 3. Vérifier les logs WordPress

**Activer les logs :**
1. Éditer `wp-config.php`
2. Ajouter :
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```
3. Consulter `wp-content/debug.log`

### 4. Vérifications spécifiques

**A. Vérifier que le plugin se charge :**
```php
// Ajouter temporairement dans wp-config.php pour debug
if (function_exists('add_action')) {
    add_action('init', function() {
        error_log('IDE Snippets Bridge: Plugin loaded');
        if (class_exists('IDE_Snippets_API')) {
            error_log('IDE Snippets Bridge: API class exists');
        } else {
            error_log('IDE Snippets Bridge: API class NOT found');
        }
    });
}
```

**B. Vérifier les hooks REST API :**
```php
// Ajouter temporairement dans functions.php du thème
add_action('rest_api_init', function() {
    error_log('REST API Init - IDE Snippets routes should be registered');
});
```

## 🛠️ Solutions possibles

### Solution 1: Réinstallation complète

1. **Sauvegarder la base de données**
2. **Désactiver le plugin**
3. **Supprimer complètement le dossier** `wp-content/plugins/ide-snippets-bridge/`
4. **Vider le cache** (si plugin de cache actif)
5. **Extraire `ide-snippets-bridge-fixed.zip`** dans `wp-content/plugins/`
6. **Réactiver le plugin**
7. **Tester immédiatement**

### Solution 2: Vérification des permissions

1. Vérifier que les fichiers ont les bonnes permissions :
   ```bash
   chmod 644 wp-content/plugins/ide-snippets-bridge/*.php
   chmod 644 wp-content/plugins/ide-snippets-bridge/includes/*.php
   ```

### Solution 3: Conflit de plugins

1. **Désactiver tous les autres plugins**
2. **Tester le toggle**
3. **Réactiver un par un** pour identifier le conflit

### Solution 4: Cache WordPress

1. **Vider tous les caches** :
   - Cache de plugin (WP Rocket, W3 Total Cache, etc.)
   - Cache d'objet (Redis, Memcached)
   - Cache de base de données
2. **Redémarrer le serveur web** si possible

## 🔧 Debug avancé

### Ajouter des logs dans le plugin

**Modifier temporairement `class-ide-snippets-api.php` :**
```php
// Ajouter au début de register_routes()
public function register_routes() {
    error_log('IDE Snippets: Registering routes');
    
    // ... code existant ...
    
    // Après chaque register_rest_route, ajouter :
    error_log('IDE Snippets: Route registered - fluent-snippets toggle');
}
```

### Vérifier les routes enregistrées

**Ajouter dans functions.php :**
```php
add_action('wp_loaded', function() {
    global $wp_rest_server;
    if ($wp_rest_server) {
        $routes = $wp_rest_server->get_routes();
        foreach ($routes as $route => $handlers) {
            if (strpos($route, 'ide/v1') !== false) {
                error_log('IDE Route found: ' . $route);
            }
        }
    }
});
```

## 📋 Checklist de diagnostic

- [ ] Plugin activé dans WordPress Admin
- [ ] Fichier `class-ide-snippets-api.php` contient la route toggle
- [ ] Logs WordPress activés et consultés
- [ ] Test manuel avec curl effectué
- [ ] Cache vidé
- [ ] Autres plugins désactivés temporairement
- [ ] Permissions des fichiers vérifiées
- [ ] Réinstallation complète effectuée

## 🆘 Si rien ne fonctionne

**Partager ces informations :**
1. Version de WordPress
2. Version de PHP
3. Liste des plugins actifs
4. Contenu de `wp-content/debug.log`
5. Résultat du test `curl` ou `test-endpoints.js`
6. Capture d'écran de la liste des plugins WordPress