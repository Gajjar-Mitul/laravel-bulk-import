<footer class="bg-white border-top mt-5 py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="text-muted mb-0">
                    &copy; {{ date('Y') }} Laravel Bulk Import System.
                    Built with ❤️ using Laravel {{ app()->version() }}
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <small class="text-muted">
                    <i class="bi bi-speedometer me-1"></i>
                    Page loaded in <span id="page-load-time">--</span>ms
                </small>
            </div>
        </div>
    </div>
</footer>

<script>
    // Show page load time
    window.addEventListener('load', () => {
        const loadTime = performance.now();
        document.getElementById('page-load-time').textContent = Math.round(loadTime);
    });
</script>
