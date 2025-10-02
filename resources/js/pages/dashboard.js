/**
 * Dashboard Page JavaScript
 * Handles dashboard statistics, charts, and real-time updates
 */

class Dashboard {
    constructor() {
        this.statsEndpoint = '/api/dashboard/stats';
        this.charts = {};

        this.init();
    }

    async init() {
        try {
            // Load initial data
            await this.loadDashboardStats();

            // Initialize charts
            this.initializeCharts();

            // Set up auto-refresh every 30 seconds
            setInterval(() => {
                this.loadDashboardStats();
            }, 30000);

            console.log('Dashboard initialized successfully');
        } catch (error) {
            console.error('Dashboard initialization failed:', error);
        }
    }

    async loadDashboardStats() {
        try {
            const response = await fetch(this.statsEndpoint);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            // Update stat cards
            this.updateStatCards(data);

            // Update recent imports table
            this.updateRecentImports(data.recent_imports);

            // Update charts with new data
            this.updateCharts(data);

        } catch (error) {
            console.error('Failed to load dashboard stats:', error);
            this.showErrorMessage('Failed to load dashboard statistics');
        }
    }

    updateStatCards(data) {
        // Update total products
        const totalProducts = document.getElementById('total-products');
        if (totalProducts) {
            totalProducts.textContent = this.formatNumber(data.total_products || 0);
        }

        // Update successful imports
        const successfulImports = document.getElementById('successful-imports');
        if (successfulImports) {
            successfulImports.textContent = this.formatNumber(data.successful_imports || 0);
        }

        // Update total images
        const totalImages = document.getElementById('total-images');
        if (totalImages) {
            totalImages.textContent = this.formatNumber(data.total_images || 0);
        }

        // Update pending uploads
        const pendingUploads = document.getElementById('pending-uploads');
        if (pendingUploads) {
            pendingUploads.textContent = this.formatNumber(data.pending_uploads || 0);
        }
    }

    updateRecentImports(imports) {
        const tbody = document.getElementById('recent-imports');
        if (!tbody || !imports) return;

        if (imports.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                        No recent imports found
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = imports.map(importItem => {
            const statusBadge = this.getStatusBadge(importItem.status);
            const successRate = importItem.success_rate;
            const successClass = successRate >= 90 ? 'text-success' : successRate >= 70 ? 'text-warning' : 'text-danger';

            return `
                <tr>
                    <td>
                        <span class="badge bg-light text-dark">#${importItem.id}</span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-file-earmark-text me-2 text-muted"></i>
                            <span class="text-truncate" style="max-width: 200px;" title="${importItem.filename}">
                                ${importItem.filename}
                            </span>
                        </div>
                    </td>
                    <td>${statusBadge}</td>
                    <td>
                        <span class="fw-medium">${this.formatNumber(importItem.total_rows || 0)}</span>
                        <small class="text-muted">rows</small>
                    </td>
                    <td>
                        <span class="fw-medium ${successClass}">
                            ${successRate}%
                        </span>
                    </td>
                    <td>
                        <small class="text-muted">${importItem.date}</small>
                    </td>
                </tr>
            `;
        }).join('');
    }

    getStatusBadge(status) {
        const badges = {
            'completed': '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Completed</span>',
            'processing': '<span class="badge bg-primary"><i class="bi bi-hourglass-split me-1"></i>Processing</span>',
            'queued': '<span class="badge bg-info"><i class="bi bi-clock me-1"></i>Queued</span>',
            'failed': '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Failed</span>'
        };

        return badges[status] || `<span class="badge bg-secondary">${status}</span>`;
    }

    initializeCharts() {
        // Import Activity Chart (Line Chart)
        const importChartCtx = document.getElementById('importChart');
        if (importChartCtx) {
            this.charts.importChart = new Chart(importChartCtx, {
                type: 'line',
                data: {
                    labels: this.getLast30Days(),
                    datasets: [{
                        label: 'Successful Imports',
                        data: this.generateSampleData(30),
                        borderColor: 'rgb(13, 110, 253)',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.1,
                        fill: true
                    }, {
                        label: 'Failed Imports',
                        data: this.generateSampleData(30, 0, 3),
                        borderColor: 'rgb(220, 53, 69)',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Success Rate Chart (Doughnut Chart)
        const successChartCtx = document.getElementById('successChart');
        if (successChartCtx) {
            this.charts.successChart = new Chart(successChartCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Successful', 'Failed', 'Processing'],
                    datasets: [{
                        data: [85, 12, 3],
                        backgroundColor: [
                            'rgb(25, 135, 84)',
                            'rgb(220, 53, 69)',
                            'rgb(13, 110, 253)'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }

    updateCharts(data) {
        // In a real implementation, you would update charts with actual data
        // For now, we'll keep the sample data
    }

    getLast30Days() {
        const days = [];
        for (let i = 29; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            days.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        }
        return days;
    }

    generateSampleData(count, min = 0, max = 10) {
        return Array.from({ length: count }, () =>
            Math.floor(Math.random() * (max - min + 1)) + min
        );
    }

    formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }

    showErrorMessage(message) {
        // You can implement a toast notification system here
        console.error(message);
    }
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Check if Chart.js is available
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js not loaded, charts will not be available');
        // Load Chart.js dynamically
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        script.onload = () => {
            new Dashboard();
        };
        document.head.appendChild(script);
    } else {
        new Dashboard();
    }
});
