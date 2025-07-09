export interface Snippet {
    id: number;
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
    id: number;
}

export interface WordPressConnectionConfig {
    siteUrl: string;
    username: string;
    applicationPassword: string;
}