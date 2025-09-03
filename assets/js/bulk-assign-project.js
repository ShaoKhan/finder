// Mehrfach-Projektzuordnung für Bilder
export function initializeBulkAssignProject() {
    const dateGroupSelectAllCheckboxes = document.querySelectorAll('.date-group-select-all');
    const dateGroupAssignBtns = document.querySelectorAll('.date-group-assign-btn');
    const projectSelects = document.querySelectorAll('.project-select');
    const imageCheckboxes = document.querySelectorAll('.image-checkbox');

    if (dateGroupAssignBtns.length === 0) {
        return; // Elemente nicht gefunden, Funktion nicht verfügbar
    }

    // Projektauswahl-Event-Listener für jede Datumsgruppe
    projectSelects.forEach(projectSelect => {
        const dateGroup = projectSelect.getAttribute('data-date-group');
        const assignBtn = document.querySelector(`.date-group-assign-btn[data-date-group="${dateGroup}"]`);

        projectSelect.addEventListener('change', function() {
            updateDateGroupAssignButton(dateGroup);
        });
    });

    // Bulk Assign Buttons für jede Datumsgruppe
    dateGroupAssignBtns.forEach(assignBtn => {
        const dateGroup = assignBtn.getAttribute('data-date-group');
        
        assignBtn.addEventListener('click', function() {
            const selectedIds = getSelectedImageIdsInDateGroup(dateGroup);
            const projectSelect = document.querySelector(`.project-select[data-date-group="${dateGroup}"]`);
            const projectId = projectSelect.value;
            const projectName = projectSelect.options[projectSelect.selectedIndex].text;

            if (selectedIds.length === 0) {
                alert('Bitte wählen Sie mindestens ein Bild aus.');
                return;
            }

            if (!projectId) {
                alert('Bitte wählen Sie ein Projekt aus.');
                return;
            }

            showBulkAssignProjectModal(selectedIds, projectId, projectName);
        });
    });

    // Bulk Assign Modal Event Listener
    const confirmBulkAssignBtn = document.getElementById('confirmBulkAssign');
    if (confirmBulkAssignBtn) {
        confirmBulkAssignBtn.addEventListener('click', function() {
            const selectedIds = this.getAttribute('data-selected-ids');
            const projectId = this.getAttribute('data-project-id');
            
            if (selectedIds && projectId) {
                performBulkAssignProject(selectedIds.split(','), projectId);
            }
            
            // Modal schließen
            const bulkAssignProjectModal = document.getElementById('bulkAssignProjectModal');
            const modal = window.bootstrap.Modal.getInstance(bulkAssignProjectModal);
            modal.hide();
        });
    }

    // Hilfsfunktionen
    function updateDateGroupAssignButton(dateGroup) {
        const selectedCount = getSelectedImageIdsInDateGroup(dateGroup).length;
        const assignBtn = document.querySelector(`.date-group-assign-btn[data-date-group="${dateGroup}"]`);
        const projectSelect = document.querySelector(`.project-select[data-date-group="${dateGroup}"]`);
        
        const hasSelection = selectedCount > 0;
        const hasProject = projectSelect.value !== '';
        
        assignBtn.disabled = !hasSelection || !hasProject;
        
        if (hasSelection && hasProject) {
            assignBtn.innerHTML = `<i class="bi bi-folder-plus"></i> ${selectedCount} zuordnen`;
        } else {
            assignBtn.innerHTML = `<i class="bi bi-folder-plus"></i> Zu Projekt zuordnen`;
        }
    }

    function getSelectedImageIdsInDateGroup(dateGroup) {
        const selectedCheckboxes = document.querySelectorAll(`.found-item[data-date-group="${dateGroup}"] .image-checkbox:checked`);
        return Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
    }

    function performBulkAssignProject(ids, projectId) {
        // CSRF-Token generieren
        const csrfToken = document.querySelector('meta[name="assign-project-csrf-token"]')?.getAttribute('content') || 
                         document.querySelector('input[name="_token"]')?.value;

        if (!csrfToken) {
            alert('CSRF-Token nicht gefunden. Bitte laden Sie die Seite neu.');
            return;
        }

        // Button deaktivieren während des Requests
        dateGroupAssignBtns.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Zuordnen...';
        });

        // FormData für den Request erstellen
        const formData = new FormData();
        ids.forEach(id => formData.append('ids[]', id));
        formData.append('project_id', projectId);
        formData.append('_token', csrfToken);

        // AJAX-Request
        fetch('/found/bulk-assign-project', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Erfolgreich zugeordnet
                showAlert('success', data.message);

                // Checkboxen zurücksetzen
                dateGroupSelectAllCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                    checkbox.indeterminate = false;
                });
                
                // Alle Checkboxen in den Datumsgruppen zurücksetzen
                imageCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                
                // Alle Assign-Buttons zurücksetzen
                dateGroupAssignBtns.forEach(btn => {
                    btn.disabled = true;
                    btn.innerHTML = `<i class="bi bi-folder-plus"></i> Zu Projekt zuordnen`;
                });

                // Projektauswahl zurücksetzen
                projectSelects.forEach(select => {
                    select.value = '';
                });
            } else {
                showAlert('danger', data.message || 'Ein Fehler ist bei der Projektzuordnung aufgetreten');
            }
        })
        .catch(error => {
            console.error('Fehler bei der Projektzuordnung:', error);
            showAlert('danger', 'Ein Fehler ist bei der Projektzuordnung aufgetreten');
        })
        .finally(() => {
            // Button-Status zurücksetzen
            dateGroupAssignBtns.forEach(btn => {
                const dateGroup = btn.getAttribute('data-date-group');
                updateDateGroupAssignButton(dateGroup);
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

    function showBulkAssignProjectModal(selectedIds, projectId, projectName) {
        const bulkAssignProjectModal = document.getElementById('bulkAssignProjectModal');
        const bulkAssignCount = document.getElementById('bulkAssignCount');
        const bulkAssignProjectName = document.getElementById('bulkAssignProjectName');
        const confirmBulkAssignBtn = document.getElementById('confirmBulkAssign');

        if (!bulkAssignProjectModal || !bulkAssignCount || !bulkAssignProjectName || !confirmBulkAssignBtn) {
            return;
        }

        // Anzahl der ausgewählten Bilder und Projektname anzeigen
        bulkAssignCount.textContent = selectedIds.length;
        bulkAssignProjectName.textContent = projectName;

        // Selected IDs und Project ID für den Confirm Button setzen
        confirmBulkAssignBtn.setAttribute('data-selected-ids', selectedIds.join(','));
        confirmBulkAssignBtn.setAttribute('data-project-id', projectId);

        // Modal anzeigen
        const modal = new window.bootstrap.Modal(bulkAssignProjectModal);
        modal.show();
    }

    // Event-Listener für Checkbox-Änderungen hinzufügen (falls noch nicht vorhanden)
    imageCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const dateGroup = this.closest('.found-item').getAttribute('data-date-group');
            updateDateGroupAssignButton(dateGroup);
        });
    });

    // Event-Listener für "Alle auswählen" Checkboxen hinzufügen (falls noch nicht vorhanden)
    dateGroupSelectAllCheckboxes.forEach(selectAllCheckbox => {
        selectAllCheckbox.addEventListener('change', function() {
            const dateGroup = this.getAttribute('data-date-group');
            updateDateGroupAssignButton(dateGroup);
        });
    });
}
