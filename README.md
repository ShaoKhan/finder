# Finder - Fundmeldungssystem

Ein modernes Web-basiertes System zur Verwaltung und Dokumentation archäologischer Funde mit GPS-Koordinaten und automatischer Standortbestimmung.

## 🎯 Projektbeschreibung

"Finder" ist eine spezialisierte Webanwendung für Archäologen, Sondengänger und Heimatforscher zur systematischen Erfassung und Verwaltung von Funden. Das System kombiniert moderne Webtechnologien mit präziser GPS-Standortbestimmung und automatischer Geodatenverarbeitung.

## ✨ Hauptfunktionen

### 📸 **Intelligenter Bild-Upload**
- **EXIF-Daten-Extraktion**: Automatische Auslesung von GPS-Koordinaten aus Fotos
- **Automatische Standortbestimmung**: Geocoding und UTM-Koordinaten-Berechnung
- **Gemarkung-Erkennung**: Automatische Zuordnung zu Gemarkungen und Flurstücken
- **Bildvalidierung**: Prüfung auf GPS-Daten und Bildqualität

### 🗺️ **Kartenintegration**
- **OpenStreetMap-Integration**: Interaktive Karten für alle Fundstellen
- **Standortvisualisierung**: Automatische Marker-Platzierung
- **Koordinaten-Konvertierung**: GPS zu UTM33-Koordinaten

### 📋 **Fundverwaltung**
- **Mehrfach-Löschfunktion**: Effiziente Verwaltung großer Bildmengen
- **Datumsgruppierung**: Automatische Gruppierung nach Aufnahmedatum
- **Suchfunktion**: Volltextsuche in allen Funddaten
- **Filterung**: Nach Datum, Ort, UTM-Koordinaten

### 📄 **Dokumentation**
- **PDF-Export**: Professionelle Fundmeldungen als PDF
- **Word-Export**: Bearbeitbare Dokumente im Word-Format
- **Automatische Berichte**: Mit Karten und Funddetails

### 👥 **Benutzerverwaltung**
- **Registrierung**: Einfache Benutzerregistrierung mit E-Mail-Bestätigung
- **Berechtigungssystem**: Rollenbasierte Zugriffsrechte
- **Admin-Panel**: Umfassende Verwaltung für Administratoren

## 🛠️ Technologie-Stack

### **Backend**
- **Symfony 6**: Modernes PHP-Framework
- **Doctrine ORM**: Datenbankabstraktion
- **MySQL/PostgreSQL**: Datenbank
- **Twig**: Template-Engine

### **Frontend**
- **Bootstrap 5**: Responsive UI-Framework
- **JavaScript ES6+**: Moderne Client-Side-Logik
- **Leaflet.js**: Interaktive Karten
- **Webpack Encore**: Asset-Management

### **Services**
- **GeoService**: Geocoding und Koordinatenverarbeitung
- **ImageService**: EXIF-Daten-Extraktion
- **MapService**: Karten-Generierung
- **PdfService**: PDF-Erstellung

## 🚀 Installation

### **Voraussetzungen**
- PHP 8.1+
- Composer
- Node.js 16+
- MySQL 8.0+ oder PostgreSQL 13+

### **Installation**
```bash
# Repository klonen
git clone https://github.com/your-username/finder.git
cd finder

# PHP-Dependencies installieren
composer install

# Node-Dependencies installieren
npm install

# Assets kompilieren
npm run build

# Umgebungsvariablen konfigurieren
cp .env.local.example .env.local
# .env.local bearbeiten mit Ihren Datenbankdaten

# Datenbank erstellen und Migrationen ausführen
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Cache leeren
php bin/console cache:clear
```

### **Konfiguration**
1. **Datenbank**: Konfigurieren Sie Ihre Datenbankverbindung in `.env.local`
2. **Upload-Verzeichnis**: Stellen Sie sicher, dass `public/fundbilder/` beschreibbar ist
3. **E-Mail**: Konfigurieren Sie SMTP-Einstellungen für Benutzerregistrierung

## 📖 Verwendung

### **Benutzerregistrierung**
1. Registrieren Sie sich mit Ihrer E-Mail-Adresse
2. Bestätigen Sie Ihre E-Mail-Adresse
3. Loggen Sie sich ein

### **Fund-Upload**
1. Gehen Sie zu "Bild hochladen"
2. Wählen Sie Fotos mit GPS-Daten aus
3. Das System extrahiert automatisch:
   - GPS-Koordinaten
   - Aufnahmedatum
   - UTM-Koordinaten
   - Gemarkung und Flurstück
4. Bilder werden automatisch verarbeitet und gespeichert

### **Fundverwaltung**
1. Gehen Sie zu "Funde anzeigen"
2. Nutzen Sie die Suchfunktion für spezifische Funde
3. Verwenden Sie die Mehrfach-Löschfunktion für effiziente Verwaltung
4. Exportieren Sie Fundmeldungen als PDF oder Word-Dokument

## 🔧 Entwicklung

### **Entwicklungsumgebung starten**
```bash
# Symfony-Server starten
symfony server:start

# Assets im Watch-Modus kompilieren
npm run watch
```

### **Tests ausführen**
```bash
# PHPUnit-Tests
php bin/phpunit

# JavaScript-Tests (falls konfiguriert)
npm test
```

## 📁 Projektstruktur

```
finder/
├── src/
│   ├── Controller/          # HTTP-Controller
│   ├── Entity/             # Datenbank-Entities
│   ├── Repository/         # Datenbank-Queries
│   ├── Service/            # Business-Logic
│   └── Form/              # Formulare
├── templates/              # Twig-Templates
├── assets/                # Frontend-Assets
│   ├── js/               # JavaScript-Module
│   └── styles/           # SCSS-Styles
├── public/               # Web-Root
├── migrations/           # Datenbank-Migrationen
└── translations/         # Übersetzungen
```

## 🤝 Beitragen

1. Fork das Repository
2. Erstellen Sie einen Feature-Branch (`git checkout -b feature/AmazingFeature`)
3. Committen Sie Ihre Änderungen (`git commit -m 'Add some AmazingFeature'`)
4. Pushen Sie zum Branch (`git push origin feature/AmazingFeature`)
5. Öffnen Sie einen Pull Request

## 📝 Changelog

### **Version 1.0.0**
- ✅ Mehrfach-Löschfunktion implementiert
- ✅ Modal-Bestätigung für Bulk-Operationen
- ✅ Automatische Entfernung leerer Datumsgruppen
- ✅ Verbesserte Benutzerfreundlichkeit
- ✅ Fix für user_uuid Constraint-Problem

## 📄 Lizenz

Dieses Projekt ist unter der MIT-Lizenz lizenziert - siehe [LICENSE.md](LICENSE.md) für Details.

## 📞 Support

Bei Fragen oder Problemen:
- Erstellen Sie ein Issue im GitHub-Repository
- Kontaktieren Sie das Entwicklungsteam

## 🙏 Danksagungen

- **OpenStreetMap** für Kartendaten
- **Bootstrap** für das UI-Framework
- **Symfony** für das PHP-Framework
- **Leaflet.js** für die Kartenintegration

---

**Entwickelt mit ❤️ für die archäologische Community**
