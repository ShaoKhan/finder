// Mehrfach-Löschfunktion für Bilder
export function initializeBulkDelete() {
    const dateGroupSelectAllCheckboxes = document.querySelectorAll('.date-group-select-all');
    const dateGroupDeleteBtns = document.querySelectorAll('.date-group-delete-btn');
    const imageCheckboxes = document.querySelectorAll('.image-checkbox');

    if (dateGroupSelectAllCheckboxes.length === 0) {
        return; // Elemente nicht gefunden, Funktion nicht verfügbar
    }

    // "Alle auswählen" Checkboxen für jede Datumsgruppe
    dateGroupSelectAllCheckboxes.forEach(selectAllCheckbox => {
        const dateGroup = selectAllCheckbox.getAttribute('data-date-group');
        const dateGroupImages = document.querySelectorAll(`.found-item[data-date-group="${dateGroup}"] .image-checkbox`);
        const dateGroupDeleteBtn = document.querySelector(`.date-group-delete-btn[data-date-group="${dateGroup}"]`);

        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            dateGroupImages.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            updateDateGroupDeleteButton(dateGroup);
        });
    });

    // Einzelne Checkboxen
    imageCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const dateGroup = this.closest('.found-item').getAttribute('data-date-group');
            updateDateGroupSelectAllCheckbox(dateGroup);
            updateDateGroupDeleteButton(dateGroup);
        });
    });

    // Bulk Delete Buttons für jede Datumsgruppe
    dateGroupDeleteBtns.forEach(deleteBtn => {
        const dateGroup = deleteBtn.getAttribute('data-date-group');
        
        deleteBtn.addEventListener('click', function() {
            const selectedIds = getSelectedImageIdsInDateGroup(dateGroup);
            if (selectedIds.length === 0) {
                alert('Bitte wählen Sie mindestens ein Bild aus.');
                return;
            }

            showBulkDeleteModal(selectedIds);
        });
    });

    // Bulk Delete Modal Event Listener
    const confirmBulkDeleteBtn = document.getElementById('confirmBulkDelete');
    if (confirmBulkDeleteBtn) {
        confirmBulkDeleteBtn.addEventListener('click', function() {
            const selectedIds = this.getAttribute('data-selected-ids');
            if (selectedIds) {
                performBulkDelete(selectedIds.split(','));
            }
            
            // Modal schließen
            const bulkDeleteModal = document.getElementById('bulkDeleteModal');
            const modal = window.bootstrap.Modal.getInstance(bulkDeleteModal);
            modal.hide();
        });
    }

    // Hilfsfunktionen
    function updateDateGroupSelectAllCheckbox(dateGroup) {
        const dateGroupImages = document.querySelectorAll(`.found-item[data-date-group="${dateGroup}"] .image-checkbox`);
        const selectAllCheckbox = document.querySelector(`.date-group-select-all[data-date-group="${dateGroup}"]`);
        const checkedCount = document.querySelectorAll(`.found-item[data-date-group="${dateGroup}"] .image-checkbox:checked`).length;
        const totalCount = dateGroupImages.length;
        
        if (checkedCount === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedCount === totalCount) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }
    }

    function updateDateGroupDeleteButton(dateGroup) {
        const selectedCount = getSelectedImageIdsInDateGroup(dateGroup).length;
        const deleteBtn = document.querySelector(`.date-group-delete-btn[data-date-group="${dateGroup}"]`);
        deleteBtn.disabled = selectedCount === 0;
        deleteBtn.innerHTML = `<i class="bi bi-trash3"></i> ${selectedCount} ausgewählte löschen`;
    }

    function getSelectedImageIdsInDateGroup(dateGroup) {
        const selectedCheckboxes = document.querySelectorAll(`.found-item[data-date-group="${dateGroup}"] .image-checkbox:checked`);
        return Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
    }

    function getSelectedImageIds() {
        const selectedCheckboxes = document.querySelectorAll('.image-checkbox:checked');
        return Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
    }

    function performBulkDelete(ids) {
        // CSRF-Token generieren
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                         document.querySelector('input[name="_token"]')?.value;

        if (!csrfToken) {
            alert('CSRF-Token nicht gefunden. Bitte laden Sie die Seite neu.');
            return;
        }

        // Button deaktivieren während des Requests
        dateGroupDeleteBtns.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Löschen...';
        });

        // FormData für den Request erstellen
        const formData = new FormData();
        ids.forEach(id => formData.append('ids[]', id));
        formData.append('_token', csrfToken);

        // AJAX-Request
        fetch('/found/bulk-delete', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Erfolgreich gelöscht - ausgewählte Elemente entfernen
                ids.forEach(id => {
                    const checkbox = document.querySelector(`.image-checkbox[value="${id}"]`);
                    if (checkbox) {
                        const foundItem = checkbox.closest('.found-item');
                        if (foundItem) {
                            foundItem.remove();
                        }
                    }
                });

                // Leere Datumsgruppen entfernen
                removeEmptyDateGroups();

                // Erfolgsmeldung anzeigen
                showAlert('success', data.message);

                // Prüfen ob noch Bilder vorhanden sind
                const remainingItems = document.querySelectorAll('.found-item');
                if (remainingItems.length === 0) {
                    // Keine Bilder mehr vorhanden, verstecke Filter und Sortierung
                    const filterContainer = document.querySelector('.d-flex.justify-content-between.mb-4');
                    const searchForm = document.querySelector('form[action*="image_list"]');
                    const pagination = document.querySelector('.d-flex.justify-content-center.mt-4');
                    
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

                // Checkboxen zurücksetzen
                dateGroupSelectAllCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                    checkbox.indeterminate = false;
                });
                
                // Alle Delete-Buttons zurücksetzen
                dateGroupDeleteBtns.forEach(btn => {
                    btn.disabled = true;
                    btn.innerHTML = `<i class="bi bi-trash3"></i> 0 ausgewählte löschen`;
                });
            } else {
                showAlert('danger', data.message || 'Ein Fehler ist beim Löschen aufgetreten');
            }
        })
        .catch(error => {
            console.error('Fehler beim Löschen:', error);
            showAlert('danger', 'Ein Fehler ist beim Löschen aufgetreten');
        })
        .finally(() => {
            // Button-Status zurücksetzen
            dateGroupDeleteBtns.forEach(btn => {
                const dateGroup = btn.getAttribute('data-date-group');
                updateDateGroupDeleteButton(dateGroup);
            });
        });
    }

    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        
        // Finde den Filter-Container
        const filterContainer = document.querySelector('.d-flex.justify-content-between.mb-4');
        if (!filterContainer) {
            return;
        }
        
        // Füge das Alert vor dem Filter-Container ein
        filterContainer.insertAdjacentElement('beforebegin', alertDiv);
        
        // Alert nach 5 Sekunden ausblenden
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

    function showBulkDeleteModal(selectedIds) {
        const bulkDeleteModal = document.getElementById('bulkDeleteModal');
        const bulkDeleteCount = document.getElementById('bulkDeleteCount');
        const confirmBulkDeleteBtn = document.getElementById('confirmBulkDelete');

        if (!bulkDeleteModal || !bulkDeleteCount || !confirmBulkDeleteBtn) {
            return;
        }

        // Anzahl der ausgewählten Bilder anzeigen
        bulkDeleteCount.textContent = selectedIds.length;

        // Selected IDs für den Confirm Button setzen
        confirmBulkDeleteBtn.setAttribute('data-selected-ids', selectedIds.join(','));

        // Modal anzeigen
        const modal = new window.bootstrap.Modal(bulkDeleteModal);
        modal.show();
    }

    function removeEmptyDateGroups() {
        // Alle Datumsgruppen durchgehen
        const dateGroups = document.querySelectorAll('.date-group');
        
        dateGroups.forEach(dateGroup => {
            // Prüfe, ob noch Bilder in dieser Gruppe vorhanden sind
            const remainingImages = dateGroup.querySelectorAll('.found-item');
            
            if (remainingImages.length === 0) {
                // Gruppe ist leer, entferne sie
                dateGroup.remove();
            }
        });
    }
}