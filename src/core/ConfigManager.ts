import * as vscode from 'vscode';
import { WordPressConnectionConfig } from '../types/Snippet';
import { ApiConnector } from './ApiConnector';

export class ConfigManager {
    private static readonly CONFIG_KEY = 'wordpressSnippets.connection';
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