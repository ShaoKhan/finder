/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// Import Bootstrap JS first
import * as bootstrap from 'bootstrap';

// Make bootstrap globally available
window.bootstrap = bootstrap;

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';

// start the Stimulus application
import './bootstrap';

// Import delete modal functionality
import { initializeDeleteModal } from './js/delete-modal';

// Initialize delete modal when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('deleteModal')) {
        initializeDeleteModal();
    }
});

