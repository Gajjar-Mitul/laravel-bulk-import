export class ApiClient {
    constructor() {
        this.baseURL = window.location.origin;
        this.defaultHeaders = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.getCSRFToken(),
            'Accept': 'application/json'
        };
    }
    
    getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }
    
    async request(url, options = {}) {
        const config = {
            method: 'GET',
            headers: { ...this.defaultHeaders },
            ...options
        };
        
        if (options.body instanceof FormData) {
            delete config.headers['Content-Type'];
        }
        
        try {
            const response = await fetch(`${this.baseURL}${url}`, config);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }
    
    async get(url) { return this.request(url); }
    async post(url, data) { return this.request(url, { method: 'POST', body: data instanceof FormData ? data : JSON.stringify(data) }); }
    async put(url, data) { return this.request(url, { method: 'PUT', body: JSON.stringify(data) }); }
    async delete(url) { return this.request(url, { method: 'DELETE' }); }
}
window.ApiClient = ApiClient;
