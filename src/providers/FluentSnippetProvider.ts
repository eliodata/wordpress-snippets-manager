import * as vscode from 'vscode';
import * as fs from 'fs/promises';
import * as path from 'path';
import { Snippet, SnippetCreateData, SnippetUpdateData } from '../types/Snippet';
import { SnippetPluginProvider } from './SnippetPluginProvider';
import { ConfigManager } from '../core/ConfigManager';
import { ApiConnector } from '../core/ApiConnector';

export class FluentSnippetProvider implements vscode.Disposable, SnippetPluginProvider {
    private _onDidChangeSnippets: vscode.EventEmitter<void> = new vscode.EventEmitter<void>();
    public readonly onDidChangeSnippets: vscode.Event<void> = this._onDidChangeSnippets.event;

    private configManager: ConfigManager;
    private apiConnector: ApiConnector | undefined;
    private snippetsPath: string | undefined;
    private cachePath: string;

    constructor(private context: vscode.ExtensionContext) {
        this.configManager = new ConfigManager(context);
        
        // Initialize cache path - use workspace folder like SnippetProvider
        const workspaceFolders = vscode.workspace.workspaceFolders;
        if (workspaceFolders && workspaceFolders.length > 0) {
            this.cachePath = path.join(workspaceFolders[0].uri.fsPath, '.snippet_cache');
        } else {
            // Fallback to global storage if no workspace is open
            this.cachePath = path.join(context.globalStorageUri.fsPath, 'snippet_cache');
            vscode.window.showWarningMessage('No workspace folder is open. Snippet cache will be stored globally.');
        }
        
        this._ensureCacheDir();
    }

    async initialize(): Promise<boolean> {
        const config = await this.configManager.getConfig();
        if (!config || !config.siteUrl) {
            vscode.window.showErrorMessage('WordPress site URL is not configured.');
            return false;
        }

        if (!config.username || !config.applicationPassword) {
            vscode.window.showErrorMessage('WordPress credentials are not configured.');
            return false;
        }

        this.apiConnector = new ApiConnector(config.siteUrl, config.username, config.applicationPassword);
        this.snippetsPath = config.fluentSnippetsPath;

        return true;
    }

    dispose() {
        this._onDidChangeSnippets.dispose();
    }

    // ... implementation of SnippetPluginProvider methods ...
    // These will be implemented in the next steps.

    async getSnippets(status: 'all' | 'active' | 'inactive' = 'all'): Promise<Snippet[]> {
        if (!this.apiConnector) {
            return [];
        }

        const snippets: Snippet[] = [];
        try {
            // Use API to get FluentSnippets data
            const response = await this.apiConnector.getFluentSnippets();
            
            if (response && response.snippets) {
                for (const snippetData of response.snippets) {
                    const snippet = this.parseFluentSnippetFromApi(snippetData);
                    if (snippet) {
                        const isActive = snippet.active;
                        if (status === 'all' || (status === 'active' && isActive) || (status === 'inactive' && !isActive)) {
                            snippets.push(snippet);
                            // Create cache file for each snippet
                            await this.createCacheFile(snippet);
                        }
                    }
                }
            }
        } catch (error) {
            vscode.window.showErrorMessage('Error fetching FluentSnippets: ' + error);
        }

        return snippets;
    }

    private parseIndexFile(content: string): any {
        try {
            // Remove PHP opening tag and comments
            let cleanContent = content.replace(/<\?php[\s\S]*?\*\//, '');
            cleanContent = cleanContent.replace(/if \(!defined\("ABSPATH"\)\) \{return;\}/, '');
            cleanContent = cleanContent.replace(/return\s+/, '');
            cleanContent = cleanContent.replace(/;\s*$/, '');
            
            // Convert PHP array syntax to JSON-like format
            cleanContent = cleanContent.replace(/array\s*\(/g, '{');
            cleanContent = cleanContent.replace(/\)/g, '}');
            cleanContent = cleanContent.replace(/'([^']+)'\s*=>/g, '"$1":');
            cleanContent = cleanContent.replace(/=>/g, ':');
            cleanContent = cleanContent.replace(/,\s*}/g, '}');
            
            // Try to parse as JSON (this is a simplified approach)
            // For a more robust solution, we would need a proper PHP parser
            return eval('(' + cleanContent + ')');
        } catch (error) {
            console.error('Error parsing index.php:', error);
            return null;
        }
    }

    private parseFluentSnippetFromApi(snippetData: any): Snippet | null {
        try {
            const numericId = snippetData.id || Math.floor(Math.random() * 10000);
            // Add FS prefix to avoid conflicts with CodeSnippets
            const id = `FS${numericId}`;
            
            // Extract the real name from the Internal Doc section in the code
            const nameFromDoc = this.extractNameFromInternalDoc(snippetData.code || '');
            const displayName = nameFromDoc || snippetData.name || 'Unnamed Snippet';
            
            return {
                id: id,
                name: displayName,
                description: snippetData.description || '',
                code: snippetData.code || '',
                active: snippetData.active === true || snippetData.status === 'published',
                scope: snippetData.scope || snippetData.run_at || 'backend',
                created: snippetData.created || snippetData.created_at || '',
                modified: snippetData.modified || snippetData.updated_at || '',
                tags: snippetData.tags || snippetData.group || ''
            };
        } catch (error) {
            console.error('Error parsing FluentSnippet from API:', error);
            return null;
        }
    }

    private extractNameFromInternalDoc(content: string): string | null {
        try {
            // Look for the @name: field in the Internal Doc section
            const nameMatch = content.match(/@name:\s*([^\n\r]+)/);
            if (nameMatch && nameMatch[1]) {
                return nameMatch[1].trim();
            }
            return null;
        } catch (error) {
            console.error('Error extracting name from Internal Doc:', error);
            return null;
        }
    }

    private parseSnippetFromCache(content: string, id: string | number): Snippet | null {
        try {
            // Extract metadata from cache file header
            const idMatch = content.match(/\* Snippet ID: (.+)/);
            const nameMatch = content.match(/\* Name: (.+)/);
            const descriptionMatch = content.match(/\* Description: (.+)/);
            const activeMatch = content.match(/@active (true|false)/);
            
            // Extract the actual code (everything after the header)
            const codeStartIndex = content.indexOf('*/\n\n');
            const code = codeStartIndex !== -1 ? content.substring(codeStartIndex + 4).trim() : '';
            
            // Try to get the real name from Internal Doc section if available
            const nameFromDoc = this.extractNameFromInternalDoc(content);
            const displayName = nameFromDoc || (nameMatch ? nameMatch[1].trim() : 'Unnamed Snippet');
            
            return {
                id: id,
                name: displayName,
                description: descriptionMatch ? descriptionMatch[1].trim() : '',
                code: code,
                active: activeMatch ? activeMatch[1] === 'true' : false,
                scope: 'backend',
                created: '',
                modified: '',
                tags: ''
            };
        } catch (error) {
            console.error('Error parsing snippet from cache:', error);
            return null;
        }
    }

    private parseFluentSnippet(metadata: any, content: string, fileName: string): Snippet | null {
        try {
            // Extract ID from filename (e.g., "1-pilotage-par-sessions-tableau.php" -> 1)
            const idMatch = fileName.match(/^(\d+)-/);
            const numericId = idMatch ? parseInt(idMatch[1], 10) : Math.floor(Math.random() * 10000);
            // Add FS prefix to avoid conflicts with CodeSnippets
            const id = `FS${numericId}`;
            
            // Extract the real name from the Internal Doc section
            const nameFromDoc = this.extractNameFromInternalDoc(content);
            const displayName = nameFromDoc || metadata.name || 'Unnamed Snippet';
            
            return {
                id: id,
                name: displayName,
                description: metadata.description || '',
                code: content,
                active: metadata.status === 'published',
                scope: metadata.run_at || 'backend',
                created: metadata.created_at || '',
                modified: metadata.updated_at || '',
                tags: metadata.tags || metadata.group || ''
            };
        } catch (error) {
            console.error('Error parsing FluentSnippet:', error);
            return null;
        }
    }

    private parseSnippet(content: string, filePath: string): Snippet | null {
        const idMatch = content.match(/@id\s+(\d+)/);
        const nameMatch = content.match(/@name\s+(.*)/);
        const descriptionMatch = content.match(/@description\s+(.*)/);
        const activeMatch = content.match(/@status\s+active/);

        const codeStartIndex = content.indexOf('?>');
        const code = codeStartIndex !== -1 ? content.substring(codeStartIndex + 2).trim() : '';

        if (nameMatch) {
            const numericId = idMatch ? parseInt(idMatch[1], 10) : 0;
            // Add FS prefix to avoid conflicts with CodeSnippets
            const id = `FS${numericId}`;
            
            return {
                id: id,
                name: nameMatch[1].trim(),
                description: descriptionMatch ? descriptionMatch[1].trim() : '',
                code: code,
                active: !!activeMatch,
                scope: '', // Scope is not a concept in FluentSnippets in the same way
                created: '', // Not available in file
                modified: '', // Not available in file
                tags: '' // Not available in file
            };
        }

        return null;
    }

    async getSnippet(id: string | number): Promise<Snippet | null> {
        // First try to find in cache
        const cacheFilePath = this.getSnippetCachePath(id);
        try {
            const content = await fs.readFile(cacheFilePath, 'utf8');
            return this.parseSnippetFromCache(content, id);
        } catch (error) {
            // Cache file doesn't exist, search in all snippets
            const snippets = await this.getSnippets();
            const snippet = snippets.find(snippet => snippet.id === id) || null;
            
            if (snippet) {
                await this.createCacheFile(snippet);
            }
            
            return snippet;
        }
    }

    async createSnippet(data: SnippetCreateData): Promise<Snippet | null> {
        if (!this.snippetsPath) {
            return null;
        }

        const numericId = Date.now(); // Generate a unique numeric ID
        const id = `FS${numericId}`; // Add FS prefix
        const fileName = `snippet-${id}.php`;
        const filePath = path.join(this.snippetsPath, fileName);

        const content = `<?php
/**
 * @name ${data.name}
 * @description ${data.description}
 * @status ${data.active ? 'active' : 'inactive'}
 * @id ${numericId}
 */

?>${data.code}`;

        try {
            await fs.writeFile(filePath, content);
            this._onDidChangeSnippets.fire();
            return this.parseSnippet(content, filePath);
        } catch (error) {
            vscode.window.showErrorMessage('Error creating FluentSnippet: ' + error);
            return null;
        }
    }

    async updateSnippet(data: SnippetUpdateData): Promise<boolean> {
        if (!this.snippetsPath) {
            return false;
        }

        // To update, we need to find the file associated with the snippet ID.
        // This is inefficient and a limitation of not having a proper mapping.
        const snippets = await this.getSnippets();
        const snippet = snippets.find(s => s.id === data.id);
        if (!snippet) {
            return false;
        }

        // Extract numeric ID from FS prefixed ID
        const numericId = typeof data.id === 'string' && data.id.startsWith('FS') 
            ? data.id.substring(2) 
            : data.id;
        
        const fileName = `snippet-${data.id}.php`;
        const filePath = path.join(this.snippetsPath, fileName);

        const content = `<?php
/**
 * @name ${data.name}
 * @description ${data.description}
 * @status ${data.active ? 'active' : 'inactive'}
 * @id ${numericId}
 */

?>${data.code}`;

        try {
            await fs.writeFile(filePath, content);
            this._onDidChangeSnippets.fire();
            return true;
        } catch (error) {
            vscode.window.showErrorMessage('Error updating FluentSnippet: ' + error);
            return false;
        }
    }

    async deleteSnippet(id: string | number): Promise<boolean> {
        if (!this.snippetsPath) {
            return false;
        }

        const fileName = `snippet-${id}.php`;
        const filePath = path.join(this.snippetsPath, fileName);

        try {
            await fs.unlink(filePath);
            this._onDidChangeSnippets.fire();
            return true;
        } catch (error) {
            vscode.window.showErrorMessage('Error deleting FluentSnippet: ' + error);
            return false;
        }
    }



    async updateSnippetFromFile(filePath: string): Promise<void> {
        // With FluentSnippets, saving the file is the update. We just need to refresh the view.
        this._onDidChangeSnippets.fire();
    }

    isSnippetFile(filePath: string): boolean {
        return this.snippetsPath ? filePath.startsWith(this.snippetsPath) : false;
    }

    getSnippetCachePath(id: string | number): string {
        return path.join(this.cachePath, `snippet-${id}.php`);
    }

    private async _ensureCacheDir(): Promise<void> {
        try {
            await fs.mkdir(this.cachePath, { recursive: true });
        } catch (error) {
            console.error("Failed to create snippet cache directory", error);
            vscode.window.showErrorMessage("Failed to create snippet cache directory.");
        }
    }

    private async createCacheFile(snippet: Snippet): Promise<void> {
        try {
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
        } catch (error) {
            console.error(`Failed to create cache file for snippet ${snippet.id}`, error);
        }
    }

    async getBackups(snippetId: string | number): Promise<string[]> {
        vscode.window.showInformationMessage('Backup and restore is not supported for FluentSnippets in this extension.');
        return [];
    }

    async restoreBackup(snippetId: string | number, backupFile: string): Promise<boolean> {
        vscode.window.showInformationMessage('Backup and restore is not supported for FluentSnippets in this extension.');
        return false;
    }
}