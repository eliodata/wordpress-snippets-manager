import * as vscode from 'vscode';
import { SnippetController } from './controllers/SnippetController';
import { SnippetTreeDataProvider } from './providers/SnippetTreeDataProvider';
import { createSnippetProvider } from './providers/SnippetProviderFactory';
import { ConfigManager } from './core/ConfigManager';

export async function activate(context: vscode.ExtensionContext) {
    const configManager = new ConfigManager(context);
    let config = await configManager.getConfig();

    if (!config) {
        config = await configManager.promptForConfig();
        if (!config) {
            return; // User cancelled the configuration
        }
    }

    let provider = await createSnippetProvider(context, config);
    if (!provider) {
        return; // Could not create a provider
    }

    await provider.initialize();

    const snippetTreeDataProvider = new SnippetTreeDataProvider(provider);
    vscode.window.registerTreeDataProvider('wordpress-snippets-view', snippetTreeDataProvider);

    const controller = new SnippetController(provider, context);

    context.subscriptions.push(
        vscode.commands.registerCommand('wordpressSnippets.refresh', () => snippetTreeDataProvider.refresh()),
        vscode.commands.registerCommand('wordpressSnippets.createSnippet', async () => {
            await controller.createSnippet();
            snippetTreeDataProvider.refresh();
        }),
        vscode.commands.registerCommand('wordpressSnippets.delete', async (item) => {
            await controller.deleteSnippet(item);
            snippetTreeDataProvider.refresh();
        }),
        vscode.commands.registerCommand('wordpressSnippets.configure', async () => {
            await controller.reconfigure();
            snippetTreeDataProvider.refresh();
        }),
        vscode.commands.registerCommand('wordpressSnippets.openSnippet', (item) => controller.openSnippet(item)),
        vscode.commands.registerCommand('wordpressSnippets.toggleSnippet', async (item) => {
            await controller.toggleSnippet(item);
            snippetTreeDataProvider.refresh();
        }),
        vscode.commands.registerCommand('wordpressSnippets.sortAsc', () => snippetTreeDataProvider.setSortOrder('asc')),
        vscode.commands.registerCommand('wordpressSnippets.sortDesc', () => snippetTreeDataProvider.setSortOrder('desc')),
        vscode.commands.registerCommand('wordpressSnippets.filterActive', () => snippetTreeDataProvider.setFilter('active')),
        vscode.commands.registerCommand('wordpressSnippets.filterInactive', () => snippetTreeDataProvider.setFilter('inactive')),
        vscode.commands.registerCommand('wordpressSnippets.filterAll', () => snippetTreeDataProvider.setFilter('all')),
        vscode.commands.registerCommand('wordpressSnippets.searchSnippets', async () => {
            const currentTerm = snippetTreeDataProvider.getSearchTerm();
            const searchTerm = await vscode.window.showInputBox({ 
                prompt: 'Rechercher des snippets (par nom, description, code ou ID)', 
                value: currentTerm,
                placeHolder: 'Tapez votre recherche ou un ID de snippet...'
            });
            if (searchTerm !== undefined) {
                snippetTreeDataProvider.setSearchTerm(searchTerm);
                
                // Attendre un peu pour que le tree view se mette à jour
                setTimeout(() => {
                    const statusMessage = snippetTreeDataProvider.getStatusMessage();
                    if (statusMessage) {
                        vscode.window.setStatusBarMessage(`🔍 ${statusMessage}`, 5000);
                        vscode.window.showInformationMessage(statusMessage);
                    } else if (searchTerm.trim() === '') {
                        const clearMessage = 'Recherche effacée';
                        vscode.window.setStatusBarMessage(`🔍 ${clearMessage}`, 2000);
                        vscode.window.showInformationMessage(clearMessage);
                    } else {
                        vscode.window.showInformationMessage('Aucun résultat trouvé');
                    }
                }, 1500);
            }
        }),
        vscode.commands.registerCommand('wordpressSnippets.clearSearch', () => {
            snippetTreeDataProvider.clearSearch();
            const clearMessage = 'Recherche effacée';
            vscode.window.setStatusBarMessage(`🔍 ${clearMessage}`, 2000);
            vscode.window.showInformationMessage(clearMessage);
        }),
        vscode.commands.registerCommand('wordpressSnippets.analyzeSnippet', () => controller.analyzeSnippet()),
        vscode.commands.registerCommand('wordpressSnippets.restoreBackup', (item) => controller.restoreBackup(item)),
        vscode.commands.registerCommand('wordpress-snippets.switchPlugin', async () => {
            const newConfig = await configManager.switchPlugin();
            if (newConfig) {
                const newProvider = await createSnippetProvider(context, newConfig);
                if (newProvider) {
                    await newProvider.initialize();
                    provider = newProvider;
                    snippetTreeDataProvider.updateProvider(provider);
                    snippetTreeDataProvider.refresh();
                }
            }
        })
    );

    context.subscriptions.push(
        vscode.workspace.onDidSaveTextDocument(async (document: vscode.TextDocument) => {
            if (provider && provider.isSnippetFile(document.uri.fsPath)) {
                await provider.updateSnippetFromFile(document.uri.fsPath);
            }
        })
    );


}

export function deactivate() {}