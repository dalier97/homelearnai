import './bootstrap';
import 'htmx.org';

import Alpine from 'alpinejs';

// Ensure Alpine is available on window before starting
window.Alpine = Alpine;

// Start Alpine and ensure it's ready
Alpine.start();

// Add HTMX configuration for Laravel CSRF
document.addEventListener('DOMContentLoaded', function() {
    // Configure HTMX for Laravel
    document.body.addEventListener('htmx:configRequest', function(evt) {
        evt.detail.headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    });
    
    // Add debugging for HTMX requests
    document.body.addEventListener('htmx:beforeRequest', function(evt) {
        console.log('HTMX: Starting request to', evt.detail.requestConfig.url);
    });
    
    document.body.addEventListener('htmx:responseError', function(evt) {
        console.error('HTMX: Response error', evt.detail);
        // Handle authentication redirects
        if (evt.detail.xhr.status === 401 || evt.detail.xhr.responseURL?.includes('/login')) {
            console.warn('HTMX: Authentication required, redirecting to login');
            window.location.href = '/login';
        }
    });
    
    document.body.addEventListener('htmx:sendError', function(evt) {
        console.error('HTMX: Send error', evt.detail);
    });
    
    document.body.addEventListener('htmx:afterRequest', function(evt) {
        console.log('HTMX: Request completed', evt.detail.requestConfig.url, evt.detail.xhr.status);
    });
});
