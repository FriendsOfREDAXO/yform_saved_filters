# YForm Saved Filters für REDAXO

![Screenshot](https://github.com/FriendsOfREDAXO/yform_saved_filters/blob/assets/screenshot.png)

Ermöglicht das Speichern und Wiederverwenden von Suchfiltern in YForm Manager Tabellen.

## Features

- ✅ **Benutzerspezifische Filter** - Jeder Benutzer hat seine eigenen gespeicherten Filter
- ✅ **Standard-Filter** - Automatisches Laden eines bevorzugten Filters
- ✅ **Filter-Vorschau** - Vor dem Speichern werden alle aktiven Filter angezeigt
- ✅ **Tabellenübergreifend** - Funktioniert mit allen YForm Manager Tabellen
- ✅ **Sortierung** - Filter speichern auch die aktuelle Sortierung
- ✅ **Einfache Integration** - Keine Anpassungen an YForm nötig
- ✅ **Mehrsprachig** - Deutsch und Englisch
- ✅ **Filter-Verwaltung** - Zentrale Verwaltung aller Filter in einem Modal

## Installation

1. AddOn über den Installer oder direkt aus GitHub installieren
2. AddOn aktivieren
3. Fertig! Die Filter-Buttons erscheinen automatisch in allen YForm Manager Tabellen

## Verwendung

### Filter speichern

1. Öffne eine YForm Manager Tabelle
2. Nutze die YForm-Suchfunktion und filtere die Daten
3. Klicke auf **"Filter speichern"**
4. Gib einen Namen für den Filter ein
5. Optional: Setze den Filter als Standard-Filter

### Filter laden

- Klicke auf einen gespeicherten Filter-Button in der Toolbar
- Der Standard-Filter wird automatisch beim Öffnen der Tabelle geladen

### Filter verwalten

- Klicke auf **"Filter verwalten"** um alle gespeicherten Filter anzuzeigen
- **Als Standard setzen**: Stern-Symbol in der Filter-Verwaltung
- **Löschen**: Löschen-Button in der Filter-Verwaltung

### Filter zurücksetzen

- Klicke auf **"Filter zurücksetzen"** um alle aktiven Filter zu entfernen

## Technische Details

### Datenbank

Das AddOn erstellt die Tabelle `rex_yform_saved_filters`:

```sql
- id              INT (Primary Key)
- user_id         INT (Benutzer-ID)
- table_name      VARCHAR(191) (YForm-Tabellen-Name)
- name            VARCHAR(255) (Filter-Name)
- filter_data     TEXT (JSON mit Filtereinstellungen)
- is_default      TINYINT (Standard-Filter: 0/1)
- createdate      DATETIME
- updatedate      DATETIME
```

### Extension Points

Das AddOn nutzt den Extension Point `YFORM_DATA_LIST_LINKS` um sich in die YForm Manager Toolbar einzuklinken.

### Filter-Daten (JSON)

Gespeichert werden:
- `rex_yform_filter` - Array mit Feldfiltern
- `rex_yform_search` - Suchbegriff
- `sort` - Sortier-Spalte
- `sorttype` - Sortier-Richtung (asc/desc)

### Service-Klasse

Die Klasse `YFormFilterService` bietet folgende Methoden:

```php
YFormFilterService::saveFilter($userId, $tableName, $name, $filterData, $isDefault)
YFormFilterService::getUserFilters($userId, $tableName)
YFormFilterService::getFilter($filterId, $userId)
YFormFilterService::getDefaultFilter($userId, $tableName)
YFormFilterService::deleteFilter($filterId, $userId)
YFormFilterService::setDefaultFilter($filterId, $userId)
```

## Lizenz

MIT License

## Autor

**Friends Of REDAXO**

* http://www.redaxo.org
* https://github.com/FriendsOfREDAXO

## Projektleitung

[Thomas Skerbis](https://github.com/skerbis)

## Credits

**Danksagungen:**

Inspiriert durch das Filter-System von [ForCal](https://github.com/FriendsOfREDAXO/forcal)

Code-Patterns und Integration basierend auf [Quick Navigation](https://github.com/FriendsOfREDAXO/quick_navigation), [yform_usability](https://github.com/FriendsOfREDAXO/yform_usability) und yform_export
