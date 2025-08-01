import axios from 'axios';

export class ApiConnector {
    private apiUrl: string;
    private username: string;
    private applicationPassword: string;

    constructor(apiUrl: string, username: string, applicationPassword: string) {
        this.apiUrl = apiUrl.endsWith('/') ? apiUrl : apiUrl + '/';
        this.username = username;
        this.applicationPassword = applicationPassword;
    }

    private getAuthHeaders() {
        return {
            'Authorization': 'Basic ' + Buffer.from(this.username + ':' + this.applicationPassword).toString('base64'),
            'Content-Type': 'application/json'
        };
    }

    public async getSnippets(status: 'all' | 'active' | 'inactive' = 'all') {
        let url = `${this.apiUrl}wp-json/ide/v1/snippets`;
        if (status !== 'all') {
            url += `?status=${status}`;
        }
        const response = await axios.get(url, {
            headers: this.getAuthHeaders()
        });
        return response.data;
    }

    public async getSnippet(id: number) {
        const response = await axios.get(`${this.apiUrl}wp-json/ide/v1/snippets/${id}`, {
            headers: this.getAuthHeaders()
        });
        return response.data;
    }

    public async createSnippet(data: any) {
        const response = await axios.post(`${this.apiUrl}wp-json/ide/v1/snippets`, data, {
            headers: this.getAuthHeaders()
        });
        return response.data;
    }

    public async updateSnippet(id: number, data: any) {
        const response = await axios.put(`${this.apiUrl}wp-json/ide/v1/snippets/${id}`, data, {
            headers: this.getAuthHeaders()
        });
        return response.data;
    }

    public async deleteSnippet(id: number) {
        const response = await axios.delete(`${this.apiUrl}wp-json/ide/v1/snippets/${id}`, {
            headers: this.getAuthHeaders()
        });
        return response.data;
    }

    public async getStatus() {
        const response = await axios.get(`${this.apiUrl}wp-json/ide/v1/status`, {
            headers: this.getAuthHeaders()
        });
        return response.data;
    }

    public async getFluentSnippets() {
        const response = await axios.get(`${this.apiUrl}wp-json/ide/v1/fluent-snippets`, {
            headers: this.getAuthHeaders()
        });
        return response.data;
    }

    public async updateFluentSnippet(id: string | number, data: { content: string }) {
        const response = await axios.put(`${this.apiUrl}wp-json/ide/v1/fluent-snippets/${id}`, data, {
            headers: this.getAuthHeaders()
        });
        return response.data;
    }

    public async toggleFluentSnippet(id: string | number, active: boolean) {
        const url = `${this.apiUrl}wp-json/ide/v1/fluent-snippets/${id}/toggle`;
        console.log(`[DEBUG] Making API call to: ${url}`);
        console.log(`[DEBUG] Payload:`, { active: active });
        
        const response = await axios.put(url, 
            { active: active }, 
            {
                headers: this.getAuthHeaders()
            }
        );
        console.log(`[DEBUG] API response status: ${response.status}`);
        console.log(`[DEBUG] API response data:`, response.data);
        return response.data;
    }
}