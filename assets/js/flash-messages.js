// Flash-Meldungen automatisch ausblenden
document.addEventListener('DOMContentLoaded', function() {
    // Alle Flash-Meldungen finden
    const flashMessages = document.querySelectorAll('.alert-success, .alert-danger, .alert-warning, .alert-info');
    
    flashMessages.forEach(function(message) {
        // Schließen-Button hinzufügen
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'btn-close';
        closeButton.setAttribute('data-bs-dismiss', 'alert');
        closeButton.setAttribute('aria-label', 'Schließen');
        message.appendChild(closeButton);
        
        // Nach 5 Sekunden ausblenden (länger als die JavaScript-Alerts)
        setTimeout(function() {
            // Smooth fade-out Effekt
            message.style.transition = 'opacity 0.5s ease-out';
            message.style.opacity = '0';
            
            // Nach dem Fade-out aus dem DOM entfernen
            setTimeout(function() {
                message.remove();
            }, 500);
        }, 5000);
    });
}); 