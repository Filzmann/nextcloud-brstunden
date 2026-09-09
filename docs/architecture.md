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

## Processing-Metadaten

`resources/privacy-processing.json` ist die einzige app-eigene Policyquelle
für `monthly_hours_management`, `monthly_reminder_communication` und
`payroll_pdf_generation`. Der öffentliche V1-Provider von
`filzmann_data_protection` lädt den Katalog lazy und veröffentlicht keine
personenbezogenen Laufzeitdaten.

Die Stundenverwaltung umfasst persistierte Monatswerte, freiwillige Notizen
und Bearbeitungsreferenzen. Der Reminder liest Mitgliedsprofil und E-Mail nur
für Vorschau und Versand; `brs_reminder_runs` enthält ausschließlich
aggregierte Laufwerte. Die Abrechnungs-PDF wird für das aktuelle Mitglied als
direkte Downloadantwort erzeugt und weder in AppData noch in Nextcloud Files
gespeichert. Rechtsgrundlagen, fachliche Verantwortlichkeit, Retention,
Backup- und Mailproviderentscheidungen werden nicht technisch erfunden,
sondern im Katalog als `PRIVACY-DECISION-REQUIRED` ausgewiesen.

Ein Processing `temporary_admin_full_access` wäre im heutigen Scope falsch:
BR-Stunden besitzt keinen nativen Admin-Bypass und keine app-lokale
Vollzugriffsfreigabe. Diese Nichtanwendbarkeit wird bei jeder Änderung der
Rechte- oder Administrationsgrenze neu bewertet.

## Rechte

Fachrechte folgen serverseitig dem versionierten BR-Mitgliedervertrag und dem
Self-Scope. Die App besitzt derzeit keinen nativen Admin-Bypass. Navigation
und UI-Sichtbarkeit erteilen keine Rechte.
