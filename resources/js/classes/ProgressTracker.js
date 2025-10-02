export class ProgressTracker {
    constructor() {
        this.progressBar = null;
    }
    
    show() {
        console.log('Progress started');
        const html = '<div id="progress-overlay" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1060;display:flex;align-items:center;justify-content:center;"><div style="background:white;padding:20px;border-radius:8px;"><div>Loading...</div><div style="width:300px;background:#eee;border-radius:10px;overflow:hidden;margin-top:10px;"><div id="main-progress-bar" style="width:0%;height:20px;background:#007bff;transition:width 0.3s;"></div></div></div></div>';
        document.body.insertAdjacentHTML('beforeend', html);
        this.progressBar = document.getElementById('main-progress-bar');
    }
    
    update(percentage) {
        console.log(`Progress: ${percentage}%`);
        if (this.progressBar) {
            this.progressBar.style.width = percentage + '%';
        }
    }
    
    hide() {
        console.log('Progress completed');
        const overlay = document.getElementById('progress-overlay');
        if (overlay) overlay.remove();
    }
}
