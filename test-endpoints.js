/**
 * Script de test pour vérifier les endpoints WordPress
 * Utiliser avec Node.js pour tester directement les API
 */

const axios = require('axios');

// Configuration - REMPLACER PAR VOS VRAIES VALEURS
const config = {
    apiUrl: 'http://votre-site.com/', // Remplacer par votre URL
    username: 'votre-username',        // Remplacer par votre username
    applicationPassword: 'votre-app-password' // Remplacer par votre app password
};

// Fonction pour créer les headers d'authentification
function getAuthHeaders() {
    const auth = Buffer.from(config.username + ':' + config.applicationPassword).toString('base64');
    return {
        'Authorization': 'Basic ' + auth,
        'Content-Type': 'application/json'
    };
}

// Test des endpoints
async function testEndpoints() {
    console.log('🔍 Test des endpoints WordPress...');
    console.log('URL de base:', config.apiUrl);
    
    const tests = [
        {
            name: 'Status général',
            url: `${config.apiUrl}wp-json/ide/v1/status`,
            method: 'GET'
        },
        {
            name: 'Liste des Code Snippets',
            url: `${config.apiUrl}wp-json/ide/v1/snippets`,
            method: 'GET'
        },
        {
            name: 'Liste des FluentSnippets',
            url: `${config.apiUrl}wp-json/ide/v1/fluent-snippets`,
            method: 'GET'
        },
        {
            name: 'Toggle FluentSnippet (endpoint manquant)',
            url: `${config.apiUrl}wp-json/ide/v1/fluent-snippets/1/toggle`,
            method: 'PUT',
            data: { active: false }
        }
    ];
    
    for (const test of tests) {
        try {
            console.log(`\n📡 Test: ${test.name}`);
            console.log(`   URL: ${test.url}`);
            
            const response = await axios({
                method: test.method,
                url: test.url,
                headers: getAuthHeaders(),
                data: test.data || undefined
            });
            
            console.log(`   ✅ Succès: ${response.status} ${response.statusText}`);
            if (response.data) {
                console.log(`   📄 Données:`, JSON.stringify(response.data, null, 2).substring(0, 200) + '...');
            }
        } catch (error) {
            if (error.response) {
                console.log(`   ❌ Erreur: ${error.response.status} ${error.response.statusText}`);
                if (error.response.data) {
                    console.log(`   📄 Détails:`, error.response.data);
                }
            } else {
                console.log(`   ❌ Erreur réseau:`, error.message);
            }
        }
    }
}

// Test de découverte des routes disponibles
async function discoverRoutes() {
    try {
        console.log('\n🔍 Découverte des routes disponibles...');
        const response = await axios.get(`${config.apiUrl}wp-json/ide/v1/`, {
            headers: getAuthHeaders()
        });
        console.log('✅ Routes découvertes:', response.data);
    } catch (error) {
        console.log('❌ Impossible de découvrir les routes:', error.response?.data || error.message);
    }
}

// Exécution des tests
if (require.main === module) {
    console.log('🚀 Démarrage des tests d\'endpoints WordPress');
    console.log('⚠️  IMPORTANT: Modifiez les valeurs de configuration dans ce fichier avant d\'exécuter!');
    console.log('\n📝 Configuration actuelle:');
    console.log('   URL:', config.apiUrl);
    console.log('   Username:', config.username);
    console.log('   App Password:', config.applicationPassword.substring(0, 4) + '...');
    
    if (config.apiUrl === 'http://votre-site.com/' || config.username === 'votre-username') {
        console.log('\n❌ ERREUR: Veuillez modifier la configuration avant d\'exécuter ce script!');
        process.exit(1);
    }
    
    discoverRoutes()
        .then(() => testEndpoints())
        .then(() => {
            console.log('\n✅ Tests terminés!');
            console.log('\n📋 Actions recommandées:');
            console.log('1. Si l\'endpoint toggle retourne 404, le plugin n\'est pas correctement installé');
            console.log('2. Vérifiez que le fichier class-ide-snippets-api.php contient bien la route toggle');
            console.log('3. Vérifiez les logs WordPress dans wp-content/debug.log');
            console.log('4. Essayez de désactiver/réactiver le plugin');
        })
        .catch(error => {
            console.error('\n❌ Erreur lors des tests:', error.message);
        });
}

module.exports = { testEndpoints, discoverRoutes, getAuthHeaders };