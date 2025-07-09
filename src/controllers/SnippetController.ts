import * as vscode from 'vscode';
import { SnippetProvider } from '../providers/SnippetProvider';
import { Snippet } from '../types/Snippet';

export class SnippetController {
    constructor(private snippetProvider: SnippetProvider) {}

    public async listSnippets() {
        // Cette méthode n'est plus nécessaire car la liste est affichée dans la vue arborescente
    }

    public async createSnippet() {
        const name = await vscode.window.showInputBox({ prompt: 'Nom du snippet' });
        if (!name) return;

        const description = await vscode.window.showInputBox({ prompt: 'Description du snippet' });
        if (description === undefined) return;

        const content = await vscode.window.showInputBox({ prompt: 'Contenu du snippet' });
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

        const confirm = await vscode.window.showWarningMessage(`Êtes-vous sûr de vouloir supprimer le snippet "${snippet.name}" ?`, { modal: true }, 'Oui');
        if (confirm === 'Oui') {
            await this.snippetProvider.deleteSnippet(snippet.id);
        }
    }

    public async reconfigure() {
        await this.snippetProvider.reconfigure();
    }

    public async openSnippet(id: number) {
        const snippet = await this.snippetProvider.getSnippet(id);
        if (!snippet) {
            vscode.window.showErrorMessage(`Snippet with ID ${id} not found.`);
            return;
        }

        const filePath = this.snippetProvider.getSnippetCachePath(id);
        const doc = await vscode.workspace.openTextDocument(filePath);
        await vscode.window.showTextDocument(doc, { preview: false });
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
        let snippetId: number;
        if (item && item.snippet) {
            snippetId = item.snippet.id;
        } else {
            const idStr = await vscode.window.showInputBox({ prompt: 'Enter the Snippet ID to restore' });
            if (!idStr) return;
            snippetId = parseInt(idStr, 10);
            if (isNaN(snippetId)) {
                vscode.window.showErrorMessage('Invalid ID.');
                return;
            }
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
                await this.openSnippet(snippetId);
            } else {
                vscode.window.showErrorMessage('Failed to restore backup.');
            }
        }
    }


    public async analyzeSnippet(id?: number) {
        let snippetId = id;

        if (!snippetId) {
            const editor = vscode.window.activeTextEditor;
            if (editor) {
                const text = editor.document.getText();
                const match = text.match(/\*\s*Snippet ID:\s*(\d+)/);
                if (match && match[1]) {
                    snippetId = parseInt(match[1], 10);
                }
            }
        }

        if (!snippetId) {
            const idStr = await vscode.window.showInputBox({ prompt: 'Enter the ID of the snippet to analyze' });
            if (idStr) {
                snippetId = parseInt(idStr, 10);
            } else {
                return;
            }
        }

        if (snippetId && !isNaN(snippetId)) {
            const snippet = await this.snippetProvider.getSnippet(snippetId);
            if (snippet) {
                const analysis = `ID: ${snippet.id}\nName: ${snippet.name}\nDescription: ${snippet.description}\nActive: ${snippet.active}\n\nCode:\n---\n${snippet.code}`;
                vscode.window.showInformationMessage(`Snippet Analysis: ${snippet.name}`, { modal: true, detail: analysis });
            } else {
                vscode.window.showErrorMessage(`Snippet with ID ${snippetId} not found.`);
            }
        } else if (snippetId) {
            vscode.window.showErrorMessage('Invalid ID entered.');
        }
    }
}