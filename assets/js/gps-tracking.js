/**
 * GPS Tracking JavaScript für Finder
 * Ermöglicht das Starten/Stoppen von Begehungen und Live-Tracking
 */

class GpsTracking {
    constructor() {
        this.isTracking = false;
        this.watchId = null;
        this.currentBegehung = null;
        this.trackInterval = null;
        
        this.init();
    }

    init() {
        // Buttons standardmäßig deaktivieren
        this.disableButtons();
        
        // Prüfe GPS-Verfügbarkeit beim Laden der Seite
        this.checkGpsAvailability();
        
        // Prüfe ob bereits eine aktive Begehung läuft
        this.checkActiveBegehung();
        
        // Event Listener für Buttons
        this.setupEventListeners();
    }

    setupEventListeners() {
        // GPS-Verfügbarkeit prüfen bei Seitenwechsel
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.checkGpsAvailability();
            }
        });

        // Event Listener für GPS-Buttons
        document.addEventListener('click', (event) => {
            if (event.target.matches('#gps-start-btn')) {
                this.startGpsTracking();
            } else if (event.target.matches('#gps-stop-btn')) {
                this.stopGpsTracking();
            }
        });
    }

    async checkGpsAvailability() {
        if (!navigator.geolocation) {
            this.updateGpsStatus('GPS nicht unterstützt', 'danger');
            this.disableButtons(); // Buttons deaktivieren wenn kein GPS
            return false;
        }

        try {
            // Prüfe GPS-Verfügbarkeit
            const position = await this.getCurrentPosition();
            this.updateGpsStatus('GPS verfügbar', 'success');
            this.enableButtons();
            return true;
        } catch (error) {
            this.updateGpsStatus('GPS nicht verfügbar', 'warning');
            this.disableButtons(); // Buttons deaktivieren wenn kein GPS
            return false;
        }
    }

    getCurrentPosition() {
        return new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(
                resolve,
                reject,
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000
                }
            );
        });
    }

    async checkActiveBegehung() {
        try {
            const response = await fetch('/gps-tracking/status');
            const data = await response.json();
            
            if (data.active) {
                this.currentBegehung = data.begehung;
                this.isTracking = true;
                this.updateButtons();
                this.updateGpsStatus('Begehung aktiv', 'success');
                this.startTrackInterval();
            } else {
                // Keine aktive Begehung - Buttons basierend auf GPS-Status setzen
                this.updateButtons();
            }
        } catch (error) {
            console.error('Fehler beim Prüfen der aktiven Begehung:', error);
            // Bei Fehler Buttons basierend auf GPS-Status setzen
            this.updateButtons();
        }
    }

    async startGpsTracking() {
        if (this.isTracking) {
            return;
        }

        try {
            // Aktuelle Position abrufen
            const position = await this.getCurrentPosition();
            const { latitude, longitude } = position.coords;

            // Begehung starten
            const response = await fetch('/gps-tracking/start', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    latitude: latitude,
                    longitude: longitude
                })
            });

            const data = await response.json();

            if (data.success) {
                this.currentBegehung = data.begehung;
                this.isTracking = true;
                this.updateButtons();
                this.updateGpsStatus('Begehung gestartet', 'success');
                this.startTrackInterval();
                
                // Benachrichtigung anzeigen
                this.showNotification('Begehung gestartet!', 'success');
            } else {
                throw new Error(data.error || 'Fehler beim Starten der Begehung');
            }
        } catch (error) {
            console.error('Fehler beim Starten der GPS-Verfolgung:', error);
            this.showNotification('Fehler: ' + error.message, 'danger');
        }
    }

    async stopGpsTracking() {
        if (!this.isTracking) {
            return;
        }

        try {
            // Aktuelle Position abrufen
            const position = await this.getCurrentPosition();
            const { latitude, longitude } = position.coords;

            // Begehung beenden
            const response = await fetch('/gps-tracking/stop', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    latitude: latitude,
                    longitude: longitude
                })
            });

            const data = await response.json();

            if (data.success) {
                this.isTracking = false;
                this.currentBegehung = null;
                this.updateButtons();
                this.updateGpsStatus('Begehung beendet', 'info');
                this.stopTrackInterval();
                
                // Benachrichtigung anzeigen
                this.showNotification('Begehung beendet!', 'success');
                
                // Optional: Zur Kartenansicht weiterleiten
                if (data.begehung && data.begehung.id) {
                    setTimeout(() => {
                        window.location.href = `/gps-tracking/map/${data.begehung.id}`;
                    }, 2000);
                }
            } else {
                throw new Error(data.error || 'Fehler beim Beenden der Begehung');
            }
        } catch (error) {
            console.error('Fehler beim Beenden der GPS-Verfolgung:', error);
            this.showNotification('Fehler: ' + error.message, 'danger');
        }
    }

    startTrackInterval() {
        // Alle 30 Sekunden einen GPS-Punkt hinzufügen
        this.trackInterval = setInterval(async () => {
            if (this.isTracking) {
                try {
                    const position = await this.getCurrentPosition();
                    const { latitude, longitude } = position.coords;

                    await fetch('/gps-tracking/track-point', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            latitude: latitude,
                            longitude: longitude
                        })
                    });
                } catch (error) {
                    console.error('Fehler beim Hinzufügen des GPS-Punkts:', error);
                }
            }
        }, 30000); // 30 Sekunden
    }

    stopTrackInterval() {
        if (this.trackInterval) {
            clearInterval(this.trackInterval);
            this.trackInterval = null;
        }
    }

    updateButtons() {
        const startBtn = document.getElementById('gps-start-btn');
        const stopBtn = document.getElementById('gps-stop-btn');

        if (startBtn && stopBtn) {
            if (this.isTracking) {
                // Begehung läuft - Start deaktiviert, Stop aktiviert
                startBtn.disabled = true;
                stopBtn.disabled = false;
                startBtn.classList.remove('btn-success');
                startBtn.classList.add('btn-outline-success');
                stopBtn.classList.remove('btn-outline-danger');
                stopBtn.classList.add('btn-danger');
            } else {
                // Keine Begehung - Buttons basierend auf GPS-Status
                const gpsAvailable = this.isGpsAvailable();
                startBtn.disabled = !gpsAvailable;
                stopBtn.disabled = true;
                
                if (gpsAvailable) {
                    startBtn.classList.remove('btn-outline-success');
                    startBtn.classList.add('btn-success');
                } else {
                    startBtn.classList.remove('btn-success');
                    startBtn.classList.add('btn-outline-success');
                }
                
                stopBtn.classList.remove('btn-danger');
                stopBtn.classList.add('btn-outline-danger');
            }
        }
    }

    enableButtons() {
        const startBtn = document.getElementById('gps-start-btn');
        const stopBtn = document.getElementById('gps-stop-btn');
        
        // Start-Button nur aktivieren wenn GPS verfügbar und nicht bereits getrackt wird
        if (startBtn && !this.isTracking) {
            startBtn.disabled = false;
        }
        // Stop-Button nur aktivieren wenn bereits getrackt wird
        if (stopBtn && this.isTracking) {
            stopBtn.disabled = false;
        }
    }

    disableButtons() {
        const startBtn = document.getElementById('gps-start-btn');
        const stopBtn = document.getElementById('gps-stop-btn');
        
        if (startBtn) startBtn.disabled = true;
        if (stopBtn) stopBtn.disabled = true;
    }

    isGpsAvailable() {
        // Prüfe ob GPS verfügbar ist basierend auf dem Status-Element
        const statusElement = document.getElementById('gps-status');
        if (statusElement) {
            return statusElement.classList.contains('gps-available');
        }
        return false;
    }

    updateGpsStatus(message, type) {
        const statusElement = document.getElementById('gps-status');
        if (statusElement) {
            // Entferne alle Status-Klassen
            statusElement.classList.remove('gps-unavailable', 'gps-available');
            
            // Setze die entsprechende Icon-Klasse basierend auf dem Typ
            if (type === 'success') {
                statusElement.classList.add('gps-available');
            } else {
                statusElement.classList.add('gps-unavailable');
            }
            
            // Setze den Tooltip-Text
            statusElement.title = message;
            
            // Aktualisiere Buttons basierend auf GPS-Status
            this.updateButtons();
        }
    }

    showNotification(message, type) {
        // Einfache Benachrichtigung (kann durch Toast-Notification ersetzt werden)
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Nach 5 Sekunden automatisch entfernen
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
}

// Globale Funktionen für die Buttons
let gpsTracker;

document.addEventListener('DOMContentLoaded', function() {
    gpsTracker = new GpsTracking();
});

// Globale Funktionen im window-Objekt verfügbar machen
window.startGpsTracking = function() {
    if (gpsTracker) {
        gpsTracker.startGpsTracking();
    }
};

window.stopGpsTracking = function() {
    if (gpsTracker) {
        gpsTracker.stopGpsTracking();
    }
};
