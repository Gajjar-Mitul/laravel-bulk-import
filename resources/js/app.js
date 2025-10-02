import { ApiClient } from './utils/ApiClient';
import './utils/helpers';
import { NotificationManager } from './classes/NotificationManager';
import { ProgressTracker } from './classes/ProgressTracker';

class Application {
    constructor() {
        this.api = new ApiClient();
        this.notification = new NotificationManager();
        this.progressTracker = new ProgressTracker();
        this.init();
    }
    
    init() {
        this.setupCSRF();
        console.log('Application initialized successfully');
    }
    
    setupCSRF() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) window.csrfToken = token;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.app = new Application();
});
