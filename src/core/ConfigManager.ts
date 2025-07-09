import * as vscode from 'vscode';
import { WordPressConnectionConfig } from '../types/Snippet';

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

        const config: WordPressConnectionConfig = {
            siteUrl,
            username,
            applicationPassword
        };

        await this.saveConfig(config);
        return config;
    }
}