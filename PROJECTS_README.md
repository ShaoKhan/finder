# Projekt-Verwaltung für Finder

## Übersicht

Die neue Projekt-Funktionalität ermöglicht es Benutzern, Projekte zu erstellen und zu verwalten, die verschiedene Fundmeldungen enthalten können. Projekte können mehrere Benutzer haben und bieten eine strukturierte Organisation der Funddaten.

## Funktionen

### 🆕 Neue Features
- **Projekte erstellen**: Benutzer können neue Projekte mit eindeutigen Namen anlegen
- **Projektmitglieder verwalten**: Mehrere Benutzer können einem Projekt zugeordnet werden
- **Fundmeldungen organisieren**: Fundmeldungen können Projekten zugeordnet werden
- **Projektübersicht**: Übersichtliche Darstellung aller Projekte eines Benutzers
- **Projektbearbeitung**: Projekte können nachträglich bearbeitet und gelöscht werden

### 🔧 Technische Details
- **UUID-basierte Identifikation**: Jedes Projekt erhält eine eindeutige UUID
- **Many-to-Many Beziehungen**: Benutzer können mehreren Projekten angehören
- **One-to-Many Beziehungen**: Projekte können mehrere Fundmeldungen enthalten
- **Berechtigungssystem**: Nur Projektmitglieder haben Zugriff auf ihre Projekte

## Datenbankstruktur

### Project Entity
```php
- id: Integer (Primary Key)
- uuid: String (Unique, RFC 4122)
- name: String (Unique, 3-255 Zeichen)
- description: Text (Optional)
- createdAt: DateTime
- updatedAt: DateTime (Optional)
- users: ManyToMany mit User
- foundsImages: OneToMany mit FoundsImage
```

### Neue Beziehungen
- **User ↔ Project**: ManyToMany über `project_user` Tabelle
- **Project ↔ FoundsImage**: OneToMany (FoundsImage kann einem Projekt zugeordnet werden)

## Verwendung

### 1. Projekt erstellen
1. Gehen Sie zu "Projekte" → "Projekt anlegen"
2. Geben Sie einen eindeutigen Projektnamen ein
3. Fügen Sie eine optionale Beschreibung hinzu
4. Wählen Sie Projektmitglieder aus
5. Klicken Sie auf "Projekt erstellen"

### 2. Projekte verwalten
- **Übersicht**: Alle Projekte werden in der Projektliste angezeigt
- **Bearbeiten**: Klicken Sie auf "Bearbeiten" um Projektdetails zu ändern
- **Löschen**: Projekte können über den "Löschen"-Button entfernt werden

### 3. Projektansicht
- **Details**: Projektdetails und Beschreibung
- **Mitglieder**: Liste aller Projektmitglieder
- **Fundmeldungen**: Übersicht der zugeordneten Fundmeldungen
- **Aktionen**: Schnellzugriff auf wichtige Funktionen

## Menüstruktur

```
Projekte (Dropdown)
├── Projekt anlegen
└── Projekte anzeigen
```

## Sicherheit

- **Zugriffskontrolle**: Nur Projektmitglieder können ihre Projekte einsehen/bearbeiten
- **CSRF-Schutz**: Alle Formulare sind mit CSRF-Token geschützt
- **Validierung**: Projektnamen müssen eindeutig und 3-255 Zeichen lang sein

## Übersetzungen

Alle Texte sind in der Datei `translations/projects.de.yaml` verfügbar und können einfach angepasst werden.

## Technische Implementierung

### Controller
- `ProjectController`: Vollständige CRUD-Operationen für Projekte

### Repository
- `ProjectRepository`: Datenbankabfragen und benutzerdefinierte Methoden

### Formulare
- `ProjectType`: Formular für Projekterstellung und -bearbeitung

### Templates
- `project/index.html.twig`: Projektübersicht
- `project/create.html.twig`: Projekterstellung
- `project/show.html.twig`: Projektansicht
- `project/edit.html.twig`: Projektbearbeitung

## Nächste Schritte

### Geplante Erweiterungen
- [ ] Fundmeldungen direkt Projekten zuordnen
- [ ] Projekt-Export (PDF/Word)
- [ ] Projekt-Statistiken
- [ ] Projekt-Tags und Kategorien
- [ ] Projekt-Archivierung

### Integration mit bestehenden Features
- [ ] Fundmeldungen-Upload mit Projektauswahl
- [ ] Projektfilter in der Fundmeldungen-Übersicht
- [ ] Projekt-basierte Berichte

## Support

Bei Fragen oder Problemen wenden Sie sich an das Entwicklungsteam oder erstellen Sie ein Issue im Projekt-Repository.
