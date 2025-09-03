/**
 * Live-Validierung für Projektnamen
 */
export class ProjectNameValidator {
    constructor() {
        this.nameInput = document.getElementById('project_name');
        this.feedbackElement = null;
        this.debounceTimer = null;
        this.minLength = 3;
        this.maxLength = 255;
        
        if (this.nameInput) {
            this.init();
        }
    }

    init() {
        // Feedback-Element erstellen
        this.createFeedbackElement();
        
        // Event-Listener hinzufügen
        this.nameInput.addEventListener('input', () => this.handleInput());
        this.nameInput.addEventListener('blur', () => this.validateName());
        this.nameInput.addEventListener('focus', () => this.clearFeedback());
        
        // CSRF-Token für AJAX-Requests
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                        document.querySelector('input[name="_token"]')?.value;
    }

    createFeedbackElement() {
        // Bestehendes Feedback-Element entfernen, falls vorhanden
        const existingFeedback = this.nameInput.parentNode.querySelector('.project-name-feedback');
        if (existingFeedback) {
            existingFeedback.remove();
        }

        // Neues Feedback-Element erstellen
        this.feedbackElement = document.createElement('div');
        this.feedbackElement.className = 'project-name-feedback mt-2';
        this.feedbackElement.style.display = 'none';
        
        // Nach dem Input-Feld einfügen
        this.nameInput.parentNode.appendChild(this.feedbackElement);
    }

    handleInput() {
        const value = this.nameInput.value.trim();
        
        // Sofortige lokale Validierung
        this.validateLocally(value);
        
        // Debounced AJAX-Validierung
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            if (value.length >= this.minLength) {
                this.validateName();
            }
        }, 500); // 500ms Verzögerung
    }

    validateLocally(value) {
        let isValid = true;
        let message = '';
        let type = 'success';

        if (value.length === 0) {
            // Leerer Wert - kein Feedback anzeigen
            this.clearFeedback();
            return;
        }

        if (value.length < this.minLength) {
            isValid = false;
            message = `Mindestens ${this.minLength} Zeichen erforderlich.`;
            type = 'warning';
        } else if (value.length > this.maxLength) {
            isValid = false;
            message = `Maximal ${this.maxLength} Zeichen erlaubt.`;
            type = 'danger';
        } else {
            message = 'Länge OK';
            type = 'success';
        }

        this.showFeedback(message, type, isValid);
    }

    async validateName() {
        const value = this.nameInput.value.trim();
        
        if (value.length < this.minLength) {
            return; // Zu kurz für AJAX-Validierung
        }

        try {
            const excludeId = this.nameInput.dataset.excludeId;
            const body = new URLSearchParams({
                'name': value,
                '_token': this.csrfToken
            });
            
            if (excludeId) {
                body.append('excludeId', excludeId);
            }

            const response = await fetch('/project-validation/check-name', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body
            });

            if (response.ok) {
                const data = await response.json();
                this.showFeedback(data.message, data.valid ? 'success' : 'danger', data.valid);
                
                // Submit-Button aktivieren/deaktivieren
                this.toggleSubmitButton(data.valid);
            } else {
                this.showFeedback('Validierung fehlgeschlagen.', 'danger', false);
                this.toggleSubmitButton(false);
            }
        } catch (error) {
            console.error('Validierungsfehler:', error);
            this.showFeedback('Netzwerkfehler bei der Validierung.', 'danger', false);
            this.toggleSubmitButton(false);
        }
    }

    showFeedback(message, type, isValid) {
        if (!this.feedbackElement) return;

        // Bootstrap-Klassen für Feedback
        const alertClass = `alert alert-${type} alert-dismissible fade show`;
        const iconClass = this.getIconClass(type);
        
        this.feedbackElement.className = `project-name-feedback mt-2 ${alertClass}`;
        this.feedbackElement.innerHTML = `
            <i class="bi ${iconClass} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        this.feedbackElement.style.display = 'block';

        // Input-Feld styling
        this.nameInput.classList.remove('is-valid', 'is-invalid');
        this.nameInput.classList.add(isValid ? 'is-valid' : 'is-invalid');
    }

    clearFeedback() {
        if (this.feedbackElement) {
            this.feedbackElement.style.display = 'none';
        }
        this.nameInput.classList.remove('is-valid', 'is-invalid');
    }

    getIconClass(type) {
        switch (type) {
            case 'success': return 'bi-check-circle-fill';
            case 'warning': return 'bi-exclamation-triangle-fill';
            case 'danger': return 'bi-x-circle-fill';
            default: return 'bi-info-circle-fill';
        }
    }

    toggleSubmitButton(isValid) {
        const submitButton = this.nameInput.closest('form')?.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = !isValid;
        }
    }
}

// Initialisierung
document.addEventListener('DOMContentLoaded', () => {
    new ProjectNameValidator();
});
