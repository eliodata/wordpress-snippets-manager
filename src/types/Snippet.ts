export interface Snippet {
    id: string | number;
    name: string;
    description: string;
    code: string;
    scope: string;
    active: boolean;
    created: string;
    modified: string;
    tags: string;
}

export interface SnippetCreateData {
    name: string;
    description: string;
    code: string;
    active?: boolean;
    tags?: string;
}

export interface SnippetUpdateData extends SnippetCreateData {
    id: string | number;
}

export interface WordPressConnectionConfig {
    id: string;
    name: string;
    siteUrl: string;
    username: string;
    applicationPassword: string;
    plugin: 'Code Snippets' | 'FluentSnippets';
    fluentSnippetsPath?: string;
    isActive?: boolean;
}

export interface MultiSiteConfig {
    connections: WordPressConnectionConfig[];
    activeConnectionId?: string;
}