import * as vscode from 'vscode';
import { SnippetProvider } from './providers/SnippetProvider';
import { SnippetController } from './controllers/SnippetController';
import { SnippetTreeDataProvider } from './providers/SnippetTreeDataProvider';

export async function activate(context: vscode.ExtensionContext) {
    const provider = new SnippetProvider(context);
    await provider.initialize();

    const snippetTreeDataProvider = new SnippetTreeDataProvider(provider);
    vscode.window.registerTreeDataProvider('wordpress-snippets-view', snippetTreeDataProvider);

    const controller = new SnippetController(provider);

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
        vscode.commands.registerCommand('wordpressSnippets.openSnippet', (item) => controller.openSnippet(item.snippet.id)),
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
            const searchTerm = await vscode.window.showInputBox({ prompt: 'Rechercher des snippets' });
            if (searchTerm !== undefined) {
                snippetTreeDataProvider.setSearchTerm(searchTerm);
            }
        }),
        vscode.commands.registerCommand('wordpressSnippets.analyzeSnippet', () => controller.analyzeSnippet()),
        vscode.commands.registerCommand('wordpressSnippets.restoreBackup', (item) => controller.restoreBackup(item))
    );

    context.subscriptions.push(
        vscode.workspace.onDidSaveTextDocument(async (document: vscode.TextDocument) => {
            if (provider.isSnippetFile(document.uri.fsPath)) {
                await provider.updateSnippetFromFile(document.uri.fsPath);
            }
        })
    );


}

export function deactivate() {}