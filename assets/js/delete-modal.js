// Globale Variablen
let currentItemId = null;
let currentCsrfToken = null;
let currentImageElement = null;

export function initializeDeleteModal() {
    // Warten bis Bootstrap verfügbar ist
    if (typeof window.bootstrap === 'undefined') {
        setTimeout(initializeDeleteModal, 100);
        return;
    }
    
    const deleteModal = document.getElementById('deleteModal');
    const modalItemName = document.getElementById('modalItemName');
    const confirmDeleteButton = document.getElementById('confirmDelete');
    
    if (!deleteModal) {
        return;
    }

    try {
        // Bootstrap Modal initialisieren
        const modal = new window.bootstrap.Modal(deleteModal);
        
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            
            if (button) {
                const buttonData = {
                    id: button.getAttribute('data-id'),
                    name: button.getAttribute('data-name'),
                    csrf: button.getAttribute('data-csrf')
                };
                
                currentItemId = buttonData.id;
                currentCsrfToken = buttonData.csrf;
                currentImageElement = button.closest('.row');
                
                if (modalItemName) {
                    modalItemName.textContent = buttonData.name || 'Unbekannt';
                }
            }
        });
        
        if (confirmDeleteButton) {
            confirmDeleteButton.addEventListener('click', handleDelete);
        }
    } catch (e) {
        console.error('Fehler beim Initialisieren des Delete Modals:', e);
    }
}

function handleDelete() {
    if (!currentItemId || !currentCsrfToken) {
        return;
    }

    // Loading-Status anzeigen
    const confirmButton = document.getElementById('confirmDelete');
    const originalText = confirmButton.textContent;
    confirmButton.disabled = true;
    confirmButton.textContent = 'Wird gelöscht...';

    // URL für den Delete-Request erstellen
    const metaTag = document.querySelector('meta[name="delete-path"]');
    if (!metaTag) {
        showAlert('danger', 'Konfigurationsfehler: Delete-Path nicht gefunden');
        confirmButton.disabled = false;
        confirmButton.textContent = originalText;
        return;
    }

    const urlPath = metaTag.getAttribute('content');
    if (!urlPath) {
        showAlert('danger', 'Konfigurationsfehler: Delete-Path ist leer');
        confirmButton.disabled = false;
        confirmButton.textContent = originalText;
        return;
    }

    const url = urlPath.replace('PLACEHOLDER', currentItemId);

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: '_token=' + encodeURIComponent(currentCsrfToken)
    })
    .then(response => {
        return response.text().then(text => {
            let json;
            try {
                json = JSON.parse(text);
            } catch (e) {
                console.error('Server response:', text);
                throw new Error('Ungültige Server-Antwort: ' + text);
            }
            
            if (!response.ok) {
                throw new Error(json.message || 'HTTP Fehler: ' + response.status);
            }
            
            return json;
        });
    })
    .then(data => {
        // Erfolgreich gelöscht
        const deleteModal = document.getElementById('deleteModal');
        const modal = window.bootstrap.Modal.getInstance(deleteModal);
        modal.hide();
        
        // Element aus dem DOM entfernen
        if (currentImageElement) {
            currentImageElement.remove();
            
            // Prüfen ob dies das letzte Bild in der Datumsgruppe war
            const dateGroup = currentImageElement.closest('.date-group');
            if (dateGroup && !dateGroup.querySelector('.found-item')) {
                dateGroup.remove();
            }

            // Prüfen ob noch Bilder vorhanden sind
            const remainingItems = document.querySelectorAll('.found-item');
            if (remainingItems.length === 0) {
                // Keine Bilder mehr vorhanden, verstecke Filter und Sortierung
                const filterContainer = document.querySelector('.d-flex.justify-content-between.mb-4');
                const searchForm = document.querySelector('form[action*="image_list"]');
                const pagination = document.querySelector('.d-flex.justify-content-center.mt-4');
                
                // Verstecke alle Filter-Elemente
                [filterContainer, searchForm, pagination].forEach(element => {
                    if (element) {
                        element.style.display = 'none';
                    }
                });
                
                // Zeige "Keine Einträge" Nachricht
                const container = document.querySelector('.container .row .col-12.mt-3');
                if (container) {
                    const noEntriesMessage = document.createElement('p');
                    noEntriesMessage.className = 'text-center mt-4';
                    noEntriesMessage.textContent = 'Keine Einträge vorhanden';
                    container.appendChild(noEntriesMessage);
                }
            }
        }

        // Erfolgsmeldung anzeigen
        showAlert('success', data.message || 'Erfolgreich gelöscht');
    })
    .catch(error => {
        console.error('Fehler beim Löschen:', error);
        showAlert('danger', error.message || 'Ein Fehler ist beim Löschen aufgetreten');
        
        // Modal nicht schließen bei Fehler
        const deleteModal = document.getElementById('deleteModal');
        const modal = window.bootstrap.Modal.getInstance(deleteModal);
        modal.show();
    })
    .finally(() => {
        // Reset Button-Status
        confirmButton.disabled = false;
        confirmButton.textContent = originalText;
    });
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    // Finde den Filter-Container (der Bereich unter der Suchleiste)
    const filterContainer = document.querySelector('.d-flex.justify-content-between.mb-4');
    if (!filterContainer) {
        return;
    }
    
    // Füge das Alert vor dem Filter-Container ein
    filterContainer.insertAdjacentElement('beforebegin', alertDiv);
    
    // Alert nach 3 Sekunden ausblenden
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
} 