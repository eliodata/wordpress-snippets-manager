import * as vscode from 'vscode';
import { WordPressConnectionConfig, MultiSiteConfig } from '../types/Snippet';
import { ApiConnector } from './ApiConnector';

export class ConfigManager {
    private static readonly CONFIG_KEY = 'wordpressSnippets.connection';
    private static readonly MULTI_SITE_CONFIG_KEY = 'wordpressSnippets.multiSiteConfig';
    private context: vscode.ExtensionContext;

    constructor(context: vscode.ExtensionContext) {
        this.context = context;
    }

    public async saveConfig(config: WordPressConnectionConfig): Promise<void> {
        await this.context.secrets.store(ConfigManager.CONFIG_KEY, JSON.stringify(config));
    }

    public async getConfig(): Promise<WordPressConnectionConfig | null> {
        const configStr = await this.context.secrets.get(ConfigManager.CONFIG_KEY);
        if (!configStr) {
            return null;
        }
        return JSON.parse(configStr);
    }

    public async clearConfig(): Promise<void> {
        await this.context.secrets.delete(ConfigManager.CONFIG_KEY);
    }

    // Nouvelles méthodes pour la gestion multi-sites
    public async getMultiSiteConfig(): Promise<MultiSiteConfig> {
        const configStr = await this.context.secrets.get(ConfigManager.MULTI_SITE_CONFIG_KEY);
        if (!configStr) {
            return { connections: [] };
        }
        return JSON.parse(configStr);
    }

    public async saveMultiSiteConfig(config: MultiSiteConfig): Promise<void> {
        await this.context.secrets.store(ConfigManager.MULTI_SITE_CONFIG_KEY, JSON.stringify(config));
    }

    public async addConnection(connection: WordPressConnectionConfig): Promise<void> {
        const multiConfig = await this.getMultiSiteConfig();
        
        // Générer un ID unique si pas fourni
        if (!connection.id) {
            connection.id = `wp_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        }
        
        // Vérifier si une connexion avec cette URL existe déjà
        const existingIndex = multiConfig.connections.findIndex(c => c.siteUrl === connection.siteUrl);
        if (existingIndex >= 0) {
            multiConfig.connections[existingIndex] = connection;
        } else {
            multiConfig.connections.push(connection);
        }
        
        // Si c'est la première connexion, la marquer comme active
        if (multiConfig.connections.length === 1) {
            multiConfig.activeConnectionId = connection.id;
        }
        
        await this.saveMultiSiteConfig(multiConfig);
    }

    public async removeConnection(connectionId: string): Promise<void> {
        const multiConfig = await this.getMultiSiteConfig();
        multiConfig.connections = multiConfig.connections.filter(c => c.id !== connectionId);
        
        // Si la connexion supprimée était active, choisir une nouvelle connexion active
        if (multiConfig.activeConnectionId === connectionId) {
            multiConfig.activeConnectionId = multiConfig.connections.length > 0 ? multiConfig.connections[0].id : undefined;
        }
        
        await this.saveMultiSiteConfig(multiConfig);
    }

    public async setActiveConnection(connectionId: string): Promise<WordPressConnectionConfig | null> {
        const multiConfig = await this.getMultiSiteConfig();
        const connection = multiConfig.connections.find(c => c.id === connectionId);
        
        if (connection) {
            multiConfig.activeConnectionId = connectionId;
            await this.saveMultiSiteConfig(multiConfig);
            
            // Maintenir la compatibilité avec l'ancien système
            await this.saveConfig(connection);
            return connection;
        }
        
        return null;
    }

    public async getActiveConnection(): Promise<WordPressConnectionConfig | null> {
        const multiConfig = await this.getMultiSiteConfig();
        
        if (multiConfig.activeConnectionId) {
            const connection = multiConfig.connections.find(c => c.id === multiConfig.activeConnectionId);
            if (connection) {
                return connection;
            }
        }
        
        // Fallback vers l'ancien système
        return await this.getConfig();
    }

    public async getAllConnections(): Promise<WordPressConnectionConfig[]> {
        const multiConfig = await this.getMultiSiteConfig();
        return multiConfig.connections;
    }

    public async switchPlugin(): Promise<WordPressConnectionConfig | null> {
        const currentConfig = await this.getConfig();
        if (!currentConfig) {
            vscode.window.showErrorMessage('Aucune configuration existante. Veuillez d\'abord vous connecter.');
            return this.promptForConfig();
        }

        const apiConnector = new ApiConnector(currentConfig.siteUrl, currentConfig.username, currentConfig.applicationPassword);
        try {
            const status = await apiConnector.getStatus();
            if (!status.active_plugins || status.active_plugins.length === 0) {
                vscode.window.showErrorMessage('Aucun plugin de snippet compatible n\'est actif sur votre site.');
                return currentConfig;
            }

            const newPlugin = await vscode.window.showQuickPick(status.active_plugins, {
                placeHolder: `Plugin actuel: ${currentConfig.plugin}. Choisissez un nouveau plugin.`,
            });

            if (!newPlugin || newPlugin === currentConfig.plugin) {
                return currentConfig;
            }

            const newConfig: WordPressConnectionConfig = {
                ...currentConfig,
                plugin: newPlugin as 'Code Snippets' | 'FluentSnippets',
                fluentSnippetsPath: newPlugin === 'FluentSnippets' ? status.fluent_snippets_path : undefined
            };

            await this.saveConfig(newConfig);
            vscode.window.showInformationMessage(`Passage à ${newPlugin} réussi.`);
            return newConfig;
        } catch (error: any) {
            vscode.window.showErrorMessage(`Échec du changement de plugin: ${error.message}`);
            return currentConfig;
        }
    }



    public async manageConnections(): Promise<WordPressConnectionConfig | null> {
        const connections = await this.getAllConnections();
        const activeConnection = await this.getActiveConnection();
        
        const options = [
            '➕ Ajouter une nouvelle connexion',
            ...connections.map(conn => {
                const isActive = activeConnection?.id === conn.id;
                return `${isActive ? '🟢' : '⚪'} ${conn.name || conn.siteUrl} (${conn.plugin})`;
            }),
            ...(connections.length > 0 ? ['🗑️ Supprimer une connexion'] : [])
        ];
        
        const selected = await vscode.window.showQuickPick(options, {
            placeHolder: 'Gérer les connexions WordPress'
        });
        
        if (!selected) return null;
        
        if (selected.startsWith('➕')) {
            return await this.promptForNewConnection();
        } else if (selected.startsWith('🗑️')) {
            return await this.promptForConnectionDeletion();
        } else {
            // Sélection d'une connexion existante
            const connectionIndex = options.indexOf(selected) - 1;
            const selectedConnection = connections[connectionIndex];
            if (selectedConnection) {
                await this.setActiveConnection(selectedConnection.id);
                vscode.window.showInformationMessage(`Connexion active: ${selectedConnection.name || selectedConnection.siteUrl}`);
                return selectedConnection;
            }
        }
        
        return null;
    }
    
    private async promptForConnectionDeletion(): Promise<WordPressConnectionConfig | null> {
        const connections = await this.getAllConnections();
        
        if (connections.length === 0) {
            vscode.window.showInformationMessage('Aucune connexion à supprimer.');
            return null;
        }
        
        const connectionOptions = connections.map(conn => ({
            label: conn.name || conn.siteUrl,
            description: `${conn.siteUrl} (${conn.plugin})`,
            connection: conn
        }));
        
        const selected = await vscode.window.showQuickPick(connectionOptions, {
            placeHolder: 'Sélectionner la connexion à supprimer'
        });
        
        if (selected) {
            const confirm = await vscode.window.showWarningMessage(
                `Êtes-vous sûr de vouloir supprimer la connexion "${selected.label}" ?`,
                { modal: true },
                'Oui'
            );
            
            if (confirm === 'Oui') {
                await this.removeConnection(selected.connection.id);
                vscode.window.showInformationMessage(`Connexion "${selected.label}" supprimée.`);
                
                // Retourner la nouvelle connexion active
                return await this.getActiveConnection();
            }
        }
        
        return null;
    }
    
    private async promptForNewConnection(): Promise<WordPressConnectionConfig | null> {
        const name = await vscode.window.showInputBox({
            prompt: 'Nom de la connexion (optionnel)',
            placeHolder: 'Mon site WordPress',
            ignoreFocusOut: true
        });
        
        const config = await this.promptForConfig();
        if (config) {
            config.name = name || config.siteUrl;
            config.id = `wp_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
            await this.addConnection(config);
            await this.setActiveConnection(config.id);
            return config;
        }
        
        return null;
    }

    public async promptForConfig(): Promise<WordPressConnectionConfig | null> {
        const siteUrl = await vscode.window.showInputBox({
            prompt: 'Entrez l\'URL de votre site WordPress',
            placeHolder: 'https://votresite.com',
            ignoreFocusOut: true,
            validateInput: (value) => {
                try {
                    new URL(value);
                    return null;
                } catch {
                    return 'Veuillez entrer une URL valide';
                }
            }
        });

        if (!siteUrl) return null;

        const username = await vscode.window.showInputBox({
            prompt: 'Entrez votre nom d\'utilisateur WordPress',
            placeHolder: 'admin',
            ignoreFocusOut: true
        });

        if (!username) return null;

        const applicationPassword = await vscode.window.showInputBox({
            prompt: 'Entrez votre mot de passe d\'application WordPress',
            password: true,
            ignoreFocusOut: true
        });

        if (!applicationPassword) return null;

        const apiConnector = new ApiConnector(siteUrl, username, applicationPassword);
        try {
            const status = await apiConnector.getStatus();

            if (!status.active_plugins || status.active_plugins.length === 0) {
                vscode.window.showErrorMessage(status.message);
                return null;
            }

            let selectedPlugin: string | undefined;
            if (status.active_plugins.length > 1) {
                selectedPlugin = await vscode.window.showQuickPick(status.active_plugins, {
                    placeHolder: 'Plusieurs plugins de snippets sont actifs. Veuillez en choisir un.',
                });
            } else {
                selectedPlugin = status.active_plugins[0];
            }

            if (!selectedPlugin) {
                return null;
            }

            const config: WordPressConnectionConfig = {
                id: `wp_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
                name: siteUrl,
                siteUrl,
                username,
                applicationPassword,
                plugin: selectedPlugin as 'Code Snippets' | 'FluentSnippets',
                fluentSnippetsPath: selectedPlugin === 'FluentSnippets' ? status.fluent_snippets_path : undefined
            };

            await this.saveConfig(config);
            vscode.window.showInformationMessage(`Connecté avec succès à ${siteUrl} en utilisant ${status.active_plugin}.`);
            return config;
        } catch (error: any) {
            vscode.window.showErrorMessage(`Échec de la connexion : ${error.message}`);
            return null;
        }
    }
}