# Architektur – BRStunden

## Verantwortung

BRStunden verwaltet monatliche BR-Stunden, Jahresübersichten, fehlende Monate,
Erinnerungen und Abrechnungsdokumente. LocalBase liefert ausschließlich den
gemeinsamen BR-Gruppenvertrag; OrgSuite stellt die Navigation bereit.

## Fach- und Datenvertrag

- Monatserfassung, Jahresaggregation, Fehlmengenermittlung,
  Reminderplanung, PDF-Erzeugung und E-Mail-Versand bleiben getrennte
  Verantwortungen.
- Eine gespeicherte `0` ist ein vorhandener Fachdatensatz und niemals ein
  fehlender Monat.
- Personenbezogene Stunden- und Bearbeitungsbezüge werden ausschließlich über
  den öffentlichen Datenschutzprovider projiziert.

## Rechte

Fachrechte folgen serverseitig dem versionierten BR-Mitgliedervertrag und dem
Self-Scope. Die App besitzt derzeit keinen nativen Admin-Bypass. Navigation
und UI-Sichtbarkeit erteilen keine Rechte.
