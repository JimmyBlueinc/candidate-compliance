import axios from 'axios';
window.axios = axios;

// Use API domain for all axios requests in production
// Any agenchq.com host (including tenant subdomains like jimmy.agenchq.com) uses api.agenchq.com
const host = window.location.hostname;
const API_BASE = (host === 'agenchq.com' || host.endsWith('.agenchq.com'))
    ? 'https://api.agenchq.com'
    : '';
if (API_BASE) {
    window.axios.defaults.baseURL = API_BASE;
}

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.axios.defaults.withCredentials = false;

window.axios.defaults.xsrfCookieName = '';
window.axios.defaults.xsrfHeaderName = '';

window.axios.defaults.timeout = 20000;

try {
    window.axios.defaults.headers.common['X-Org-Host'] = window.location.host;
} catch {
    // no-op
}
