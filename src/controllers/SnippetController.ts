import * as vscode from 'vscode';
import { ConfigManager } from '../core/ConfigManager';
import { SnippetPluginProvider } from '../providers/SnippetPluginProvider';
import { Snippet } from '../types/Snippet';

export class SnippetController {
    private configManager: ConfigManager;

    constructor(private snippetProvider: SnippetPluginProvider, context: vscode.ExtensionContext) {
        this.configManager = new ConfigManager(context);
    }

    public async listSnippets() {
        // Cette méthode n'est plus nécessaire car la liste est affichée dans la vue arborescente
    }

    public async createSnippet() {
        const name = await vscode.window.showInputBox({ prompt: 'Snippet Name' });
        if (!name) return;

        const description = await vscode.window.showInputBox({ prompt: 'Snippet Description' });
        if (description === undefined) return;

        const content = await vscode.window.showInputBox({ prompt: 'Snippet Content' });
        if (!content) return;

        await this.snippetProvider.createSnippet({ name, code: content, description });
    }

    public async deleteSnippet(snippet: Snippet) {
        if (!snippet) {
            const snippets = await this.snippetProvider.getSnippets();
            const snippetItems = snippets.map(s => ({ label: s.name, snippet: s }));
            const selected = await vscode.window.showQuickPick(snippetItems);
            if (selected) {
                snippet = selected.snippet;
            } else {
                return;
            }
        }

        const confirm = await vscode.window.showWarningMessage(`Are you sure you want to delete the snippet "${snippet.name}"?`, { modal: true }, 'Yes');
        if (confirm === 'Yes') {
            await this.snippetProvider.deleteSnippet(snippet.id);
        }
    }

    public async reconfigure() {
        const newConfig = await this.configManager.manageConnections();
        if (newConfig) {
            // The provider will be updated with the new config, so we just need to refresh the tree.
            vscode.commands.executeCommand('wordpress-snippets.refreshSnippets');
        } else {
            vscode.window.showInformationMessage('Configuration annulée.');
        }
    }
    
    public async switchPlugin() {
        const newConfig = await this.configManager.switchPlugin();
        if (newConfig) {
            vscode.commands.executeCommand('wordpress-snippets.refreshSnippets');
        } else {
            vscode.window.showInformationMessage('Changement de plugin annulé.');
        }
    }

    public async openSnippet(snippet: Snippet) {
        if (!snippet) {
            return;
        }
        const { id } = snippet;

        const filePath = this.snippetProvider.getSnippetCachePath(id);
        console.log(`Attempting to open snippet ${id} at path: ${filePath}`);
        
        try {
            // First check if file exists
            const fs = require('fs').promises;
            await fs.access(filePath);
            
            const doc = await vscode.workspace.openTextDocument(filePath);
            await vscode.window.showTextDocument(doc, { preview: false });
        } catch (error: any) {
            console.error(`Error opening snippet ${id}:`, error);
            
            // Try to fetch the snippet and cache it
            try {
                const fetchedSnippet = await this.snippetProvider.getSnippet(id);
                if (fetchedSnippet) {
                    // Try opening again after caching
                    const doc = await vscode.workspace.openTextDocument(filePath);
                    await vscode.window.showTextDocument(doc, { preview: false });
                } else {
                    vscode.window.showErrorMessage(`Snippet ${id} not found on server.`);
                }
            } catch (fetchError: any) {
                vscode.window.showErrorMessage(`Could not open file for snippet ${id}. Error: ${error.message}`);
            }
        }
    }

    public async toggleSnippet(snippet: Snippet) {
        if (!snippet) {
            return;
        }
        const { id, name, description, code, active } = snippet;
        const updatedSnippet = { id, name, description, code, active: !active };
        await this.snippetProvider.updateSnippet(updatedSnippet);
    }

    public async restoreBackup(item?: any) {
        let snippetId: string | number;
        if (item && item.snippet) {
            snippetId = item.snippet.id;
        } else {
            const idStr = await vscode.window.showInputBox({ prompt: 'Enter the Snippet ID to restore' });
            if (!idStr) return;
            // Try to parse as number first, if it fails, use as string
            const numericId = parseInt(idStr, 10);
            snippetId = isNaN(numericId) ? idStr : numericId;
        }

        const backups = await this.snippetProvider.getBackups(snippetId);
        if (backups.length === 0) {
            vscode.window.showInformationMessage('No backups found for this snippet.');
            return;
        }

        const selectedBackup = await vscode.window.showQuickPick(backups, {
            placeHolder: 'Select a backup to restore',
        });

        if (selectedBackup) {
            const success = await this.snippetProvider.restoreBackup(snippetId, selectedBackup);
            if (success) {
                vscode.window.showInformationMessage(`Snippet ${snippetId} restored from ${selectedBackup}.`);
                // Refresh the snippet view and file content
                const snippet = await this.snippetProvider.getSnippet(snippetId);
                if (snippet) {
                    await this.openSnippet(snippet);
                }
            } else {
                vscode.window.showErrorMessage('Failed to restore backup.');
            }
        }
    }


    public async analyzeSnippet(id?: string | number) {
        let snippetId = id;

        if (!snippetId) {
            const editor = vscode.window.activeTextEditor;
            if (editor) {
                const text = editor.document.getText();
                // Look for both numeric IDs and FS prefixed IDs
                const numericMatch = text.match(/\*\s*Snippet ID:\s*(\d+)/);
                const fsMatch = text.match(/\*\s*Snippet ID:\s*(FS\d+)/);
                if (fsMatch && fsMatch[1]) {
                    snippetId = fsMatch[1];
                } else if (numericMatch && numericMatch[1]) {
                    snippetId = parseInt(numericMatch[1], 10);
                }
            }
        }

        if (!snippetId) {
            const idStr = await vscode.window.showInputBox({ prompt: 'Enter the ID of the snippet to analyze' });
            if (idStr) {
                // Try to parse as number first, if it fails, use as string
                const numericId = parseInt(idStr, 10);
                snippetId = isNaN(numericId) ? idStr : numericId;
            } else {
                return;
            }
        }

        if (snippetId) {
            const snippet = await this.snippetProvider.getSnippet(snippetId);
            if (snippet) {
                const analysis = `ID: ${snippet.id}\nName: ${snippet.name}\nDescription: ${snippet.description}\nActive: ${snippet.active}\n\nCode:\n---\n${snippet.code}`;
                vscode.window.showInformationMessage(`Snippet Analysis: ${snippet.name}`, { modal: true, detail: analysis });
            } else {
                vscode.window.showErrorMessage(`Snippet with ID ${snippetId} not found.`);
            }
        }
    }
}