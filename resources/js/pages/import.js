import { ProductImporter } from '../classes/ProductImporter';

// Only initialize once
document.addEventListener('DOMContentLoaded', () => {
    if (!window.importerInitialized) {
        new ProductImporter();
        window.importerInitialized = true;
    }
});
