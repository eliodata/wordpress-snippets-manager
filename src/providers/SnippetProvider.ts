import * as vscode from 'vscode';
import * as fs from 'fs/promises';
import * as path from 'path';
import { ApiConnector } from '../core/ApiConnector';
import { ConfigManager } from '../core/ConfigManager';
import { Snippet, SnippetCreateData, SnippetUpdateData } from '../types/Snippet';
import { SnippetPluginProvider } from './SnippetPluginProvider';

export class SnippetProvider implements vscode.Disposable, SnippetPluginProvider {
    private _onDidChangeSnippets: vscode.EventEmitter<void> = new vscode.EventEmitter<void>();
    public readonly onDidChangeSnippets: vscode.Event<void> = this._onDidChangeSnippets.event;

    private apiConnector: ApiConnector | null = null;
    private configManager: ConfigManager;
    private cachePath: string;
    private backupPath: string;

    constructor(context: vscode.ExtensionContext) {
        this.configManager = new ConfigManager(context);
        const workspaceFolders = vscode.workspace.workspaceFolders;
        if (workspaceFolders && workspaceFolders.length > 0) {
            this.cachePath = path.join(workspaceFolders[0].uri.fsPath, '.snippet_cache');
            this.backupPath = path.join(workspaceFolders[0].uri.fsPath, '.snippet_backups');
        } else {
            // Fallback to global storage if no workspace is open
            this.cachePath = path.join(context.globalStorageUri.fsPath, 'snippet_cache');
            this.backupPath = path.join(context.globalStorageUri.fsPath, 'snippet_backups');
            vscode.window.showWarningMessage('No workspace folder is open. Snippet cache and backups will be stored globally.');
        }
    }

    private async _ensureBackupDir(): Promise<void> {
        try {
            await fs.mkdir(this.backupPath, { recursive: true });
        } catch (error) {
            console.error("Failed to create snippet backup directory", error);
            vscode.window.showErrorMessage("Failed to create snippet backup directory.");
        }
    }

    private async _ensureCacheDir(): Promise<void> {
        try {
            await fs.mkdir(this.cachePath, { recursive: true });
        } catch (error) {
            console.error("Failed to create snippet cache directory", error);
            vscode.window.showErrorMessage("Failed to create snippet cache directory.");
        }
    }

    public getSnippetCachePath(id: string | number): string {
        return path.join(this.cachePath, `snippet-${id}.php`);
    }

    public isSnippetFile(filePath: string): boolean {
        return path.dirname(filePath) === this.cachePath && path.basename(filePath).startsWith('snippet-');
    }

    public async initialize(): Promise<boolean> {
        await this._ensureCacheDir();
        await this._ensureBackupDir();
        const config = await this.configManager.getConfig();
        if (!config) {
            const newConfig = await this.configManager.promptForConfig();
            if (!newConfig) return false;
        }

        const currentConfig = await this.configManager.getConfig();
        if (!currentConfig) return false;

        this.apiConnector = new ApiConnector(
            currentConfig.siteUrl,
            currentConfig.username,
            currentConfig.applicationPassword
        );

        return true;
    }

    public async getSnippets(status: 'all' | 'active' | 'inactive' = 'all'): Promise<Snippet[]> {
        if (!this.apiConnector) {
            throw new Error('Le fournisseur n\'est pas initialisé');
        }

        try {
            const snippets = await this.apiConnector.getSnippets(status);
            for (const snippet of snippets) {
                const filePath = this.getSnippetCachePath(snippet.id);
                const content = `<?php
/**
 * Snippet ID: ${snippet.id}
 * Name: ${snippet.name}
 * Description: ${snippet.description}
 * @active ${snippet.active}
 */\n\n${snippet.code}`;
                await fs.writeFile(filePath, content);
            }
            return snippets;
        } catch (error: any) {
            vscode.window.showErrorMessage('Erreur lors de la récupération des snippets: ' + (error?.message || 'Erreur inconnue'));
            return [];
        }
    }

    public async getSnippet(id: string | number): Promise<Snippet | null> {
        if (!this.apiConnector) {
            throw new Error('Le fournisseur n\'est pas initialisé');
        }

        // Skip FluentSnippets (with FS prefix) as they are not handled by CodeSnippets API
        if (typeof id === 'string' && id.startsWith('FS')) {
            return null;
        }

        try {
            const snippet = await this.apiConnector.getSnippet(id as number);
            if (snippet) {
                const filePath = this.getSnippetCachePath(snippet.id);
                const content = `<?php
/**
 * Snippet ID: ${snippet.id}
 * Name: ${snippet.name}
 * Description: ${snippet.description}
 * @active ${snippet.active}
 */

${snippet.code}`;
                await fs.writeFile(filePath, content);
            }
            return snippet;
        } catch (error: any) {
            vscode.window.showErrorMessage('Erreur lors de la récupération du snippet: ' + (error?.message || 'Erreur inconnue'));
            return null;
        }
    }

    public async createSnippet(data: SnippetCreateData): Promise<Snippet | null> {
        if (!this.apiConnector) {
            throw new Error('Le fournisseur n\'est pas initialisé');
        }

        try {
            const result = await this.apiConnector.createSnippet(data);
            this._onDidChangeSnippets.fire();
            return result;
        } catch (error: any) {
            vscode.window.showErrorMessage('Erreur lors de la création du snippet: ' + (error?.message || 'Erreur inconnue'));
            return null;
        }
    }

    public async updateSnippet(data: SnippetUpdateData): Promise<boolean> {
        if (!this.apiConnector) {
            throw new Error('Le fournisseur n\'est pas initialisé');
        }

        // Skip FluentSnippets (with FS prefix) as they are not handled by CodeSnippets API
        if (typeof data.id === 'string' && data.id.startsWith('FS')) {
            return false;
        }

        try {
            await this.apiConnector.updateSnippet(data.id as number, data);
            this._onDidChangeSnippets.fire();
            return true;
        } catch (error: any) {
            vscode.window.showErrorMessage('Erreur lors de la mise à jour du snippet: ' + (error?.message || 'Erreur inconnue'));
            return false;
        }
    }

    public async deleteSnippet(id: string | number): Promise<boolean> {
        if (!this.apiConnector) {
            throw new Error('Le fournisseur n\'est pas initialisé');
        }

        // Skip FluentSnippets (with FS prefix) as they are not handled by CodeSnippets API
        if (typeof id === 'string' && id.startsWith('FS')) {
            return false;
        }

        try {
            await this.apiConnector.deleteSnippet(id as number);
            const filePath = this.getSnippetCachePath(id);
            try {
                await fs.unlink(filePath);
            } catch (e: any) {
                if (e.code !== 'ENOENT') {
                    console.error(`Failed to delete cached snippet file: ${filePath}`, e);
                }
            }
            this._onDidChangeSnippets.fire();
            return true;
        } catch (error: any) {
            vscode.window.showErrorMessage('Erreur lors de la suppression du snippet: ' + (error?.message || 'Erreur inconnue'));
            return false;
        }
    }



    public async updateSnippetFromFile(filePath: string): Promise<void> {
        if (!this.isSnippetFile(filePath)) {
            return;
        }

        if (!this.apiConnector) {
            vscode.window.showErrorMessage('Cannot update snippet, API is not connected.');
            return;
        }

        try {
            const content = await fs.readFile(filePath, 'utf-8');

            const idMatch = content.match(/Snippet ID: (\d+)/);
            if (!idMatch || !idMatch[1]) {
                vscode.window.showWarningMessage(`Could not determine Snippet ID for ${path.basename(filePath)}. Update failed.`);
                return;
            }
            const id = parseInt(idMatch[1], 10);

            const originalSnippet = await this.apiConnector.getSnippet(id);
            if (!originalSnippet) {
                vscode.window.showErrorMessage(`Snippet with ID ${id} no longer exists. Cannot update.`);
                return;
            }

            // Backup before syncing
            const backupDir = path.join(this.backupPath, `snippet-${id}`);
            await fs.mkdir(backupDir, { recursive: true });

            const now = new Date();
            const timestamp = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}_${String(now.getHours()).padStart(2, '0')}-${String(now.getMinutes()).padStart(2, '0')}-${String(now.getSeconds()).padStart(2, '0')}`;
            const backupFile = path.join(backupDir, `backup-${timestamp}-pre-sync.json`);
            await fs.writeFile(backupFile, JSON.stringify(originalSnippet, null, 2));

            // Limit the number of backups
            const maxBackups = 20;
            const backups = await fs.readdir(backupDir);
            const sortedBackups = backups.filter(f => f.endsWith('.json')).sort().reverse();

            if (sortedBackups.length > maxBackups) {
                const backupsToDelete = sortedBackups.slice(maxBackups);
                for (const fileToDelete of backupsToDelete) {
                    await fs.unlink(path.join(backupDir, fileToDelete));
                }
            }

            if (!originalSnippet) {
                vscode.window.showErrorMessage(`Snippet with ID ${id} no longer exists. Cannot update.`);
                return;
            }

            const headerMatch = content.match(/\/\*\*([\s\S]*?)\*\//);
            if (!headerMatch) {
                vscode.window.showErrorMessage("Invalid snippet format. Could not find header comment.");
                return;
            }
            const header = headerMatch[1];

            const descriptionMatch = header.match(/\*\s*Description:\s*(.*?)(?=\n\s*\*\s*@|\n\s*\*\s*Version:|\n\s*\*\s*Author:|\n\s*\*\s*Tags:|\n\s*\*\/|$)/);
            const tagsMatch = header.match(/\*\s*Tags:\s*(.*)/);

            const headerEndIndex = content.indexOf('*/');
            const newCode = content.substring(headerEndIndex + 2).trim();

            const updateData: SnippetUpdateData = {
                id: originalSnippet.id,
                name: originalSnippet.name,
                description: originalSnippet.description,
                code: newCode,
                active: originalSnippet.active,
                tags: originalSnippet.tags,
            };

            if (descriptionMatch && descriptionMatch[1]) {
                updateData.description = descriptionMatch[1].replace(/\n\s*\*/g, ' ').trim();
            }

            if (tagsMatch && tagsMatch[1]) {
                updateData.tags = tagsMatch[1].trim();
            }

            await this.updateSnippet(updateData);
            vscode.window.setStatusBarMessage(`Snippet "${originalSnippet.name}" saved and synced!`, 3000);

        } catch (error: any) {
            console.error(`Failed to update snippet from file ${filePath}`, error);
            vscode.window.showErrorMessage(`Failed to sync snippet: ${error.message}`);
        }
    }

    public async getBackups(snippetId: string | number): Promise<string[]> {
        const backupDir = path.join(this.backupPath, `snippet-${snippetId}`);
        try {
            const files = await fs.readdir(backupDir);
            return files.filter(f => f.endsWith('.json')).sort().reverse();
        } catch (error: any) {
            if (error.code === 'ENOENT') {
                return []; // No backups yet
            }
            console.error(`Failed to read backups for snippet ${snippetId}`, error);
            vscode.window.showErrorMessage(`Failed to read backups: ${error.message}`);
            return [];
        }
    }

    public async restoreBackup(snippetId: string | number, backupFile: string): Promise<boolean> {
        const backupPath = path.join(this.backupPath, `snippet-${snippetId}`, backupFile);
        try {
            const backupContent = await fs.readFile(backupPath, 'utf-8');
            const snippetData = JSON.parse(backupContent) as Snippet;

            // The backup contains the full snippet object, we need to pass update data
            const updateData: SnippetUpdateData = {
                id: snippetData.id,
                name: snippetData.name,
                description: snippetData.description,
                code: snippetData.code,
                active: snippetData.active,
                tags: snippetData.tags,
            };

            return await this.updateSnippet(updateData);
        } catch (error: any) {
            console.error(`Failed to restore backup ${backupFile}`, error);
            vscode.window.showErrorMessage(`Failed to restore backup: ${error.message}`);
            return false;
        }
    }

    dispose() {
        this._onDidChangeSnippets.dispose();
    }
}