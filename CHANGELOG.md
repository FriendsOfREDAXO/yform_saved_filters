# Changelog

## 1.0.4 (2026-05-04)

- Feature: In der "Filter verwalten"-Ansicht können globale Filter jetzt direkt per Button gesetzt oder entfernt werden.
- UX: Globale Filterverwaltung ist damit ohne erneutes Speichern eines Filters möglich.
- Security: Globale Umschaltaktionen sind weiterhin auf Admins bzw. Benutzer mit `yform_saved_filters[global_default]` begrenzt.

## 1.0.3 (2026-05-04)

- Feature: Neben globalen Standardfiltern können jetzt auch normale globale Filter gespeichert werden (für alle Benutzer sichtbar/ladbar).
- Feature: Neue Save-Option „Für alle Benutzer freigeben (globaler Filter)" für Admins bzw. Benutzer mit `yform_saved_filters[global_default]`.
- Fix: Löschrechte für globale Filter abgesichert (nur Besitzer oder Benutzer mit Berechtigung).
- Tech: Datenbankschema um `is_global` erweitert (Install + Update-Migration).

## 1.0.2 (2026-05-04)

- Feature: Admins oder Benutzer mit `yform_saved_filters[global_default]` können beim Speichern eines Filters einen globalen Standard für alle Benutzer setzen.
- Feature: Beim Laden von Standardfiltern gilt jetzt: erst benutzerspezifischer Standard, sonst globaler Standard der Tabelle.
- Tech: Datenbankschema um `is_global_default` erweitert (Install + Update-Migration).

## 1.0.1 (2026-05-04)

- Fix: Redirect- und Aktions-URLs nutzen jetzt die aktuelle Backend-Seite statt harter `yform/manager/data_edit`-URLs.
- Fix: "Filter zurücksetzen" bleibt auch bei eingebundenen YForm-Tabellen auf der AddOn-Seite (z. B. `pfeifen_im_shitstorm/meldungen`).
- Fix: Aktionen in der Filter-Verwaltung (als Standard setzen/löschen) funktionieren jetzt im eingebundenen Tabellen-Kontext.

## 1.0.0

- Initial Release.
