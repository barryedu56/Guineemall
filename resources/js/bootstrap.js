import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Importer Echo pour le temps réel
try {
    require('./echo');
} catch (e) {
    console.warn('Echo non disponible:', e);
}
