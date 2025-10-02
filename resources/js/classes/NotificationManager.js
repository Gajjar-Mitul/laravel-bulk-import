export class NotificationManager {
    constructor() {
        this.init();
    }
    
    init() {
        console.log('NotificationManager initialized');
    }
    
    success(message) { console.log('SUCCESS:', message); alert('Success: ' + message); }
    error(message) { console.log('ERROR:', message); alert('Error: ' + message); }
    warning(message) { console.log('WARNING:', message); alert('Warning: ' + message); }
    info(message) { console.log('INFO:', message); alert('Info: ' + message); }
    confirm(message, callback) { if (confirm(message)) callback(); }
}
