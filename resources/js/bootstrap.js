import axios from 'axios';
window.axios = axios;

// Basic axios setup
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

// Get the base URL from the meta tag
const appUrlMeta = document.querySelector('meta[name="app-url"]');
const baseURL = appUrlMeta ? appUrlMeta.getAttribute('content') : window.location.origin;

console.log('Bootstrap.js initialized with base URL:', baseURL);

// Set the base URL for axios
window.axios.defaults.baseURL = baseURL;

// Add CSRF token to all requests
const token = document.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// We're handling module loading directly in the HTML, no need for complex patching
console.log('Using simple bootstrap without import patching');
