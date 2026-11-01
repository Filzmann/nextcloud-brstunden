# Architektur – BRStunden

## Verantwortung

BRStunden verwaltet monatliche BR-Stunden, Jahresübersichten, fehlende Monate,
Erinnerungen und Abrechnungsdokumente. LocalBase liefert ausschließlich den
gemeinsamen BR-Gruppenvertrag; OrgSuite stellt die Navigation bereit.
Der Betriebsrat ist fachlicher Owner; `Datenschutzbeauftragte` verantwortet
Retention-Policies und zweckgebundene Sperren.

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
`flz_data_protection` lädt den Katalog lazy und veröffentlicht keine
personenbezogenen Laufzeitdaten.

Die Stundenverwaltung umfasst persistierte Monatswerte, freiwillige Notizen
und Bearbeitungsreferenzen. Der Reminder liest Mitgliedsprofil und E-Mail nur
für Vorschau und Versand; `brs_reminder_runs` enthält ausschließlich
aggregierte Laufwerte. Die Abrechnungs-PDF wird für das betroffene Mitglied
auf eigene Anforderung oder durch Vorsitz beziehungsweise Stellvertretung mit
gesonderter Fachberechtigung als direkte Downloadantwort erzeugt und weder in
AppData noch in Nextcloud Files gespeichert. Der Katalog hält drei volle Kalenderjahre nach Ende des
Bezugsjahrs für Stunden-/Fortbildungswerte, zwölf Monate für freiwillige
Notizen und 90 Tage für aggregierte Reminder-Laufwerte fest; es verbleiben
keine anonymisierten Einzel- oder Jahresaggregate. PDFs bleiben transiente
persönliche Downloads ohne App-Kopie. Rechtsgrundlagen, betriebliche Backup-
und Mailproviderentscheidungen sowie der getestete app-lokale
Ausführungs-/Restorevertrag bleiben ausdrücklich offen.

Ein Processing `temporary_admin_full_access` wäre im heutigen Scope falsch:
BR-Stunden besitzt keinen nativen Admin-Bypass und keine app-lokale
Vollzugriffsfreigabe. Diese Nichtanwendbarkeit wird bei jeder Änderung der
Rechte- oder Administrationsgrenze neu bewertet.

## Rechte

LocalBase ist ausschließlich die kanonische Quelle für die semantischen
Rollen `member`, `chair` und `deputy`. BRStunden speichert davon getrennt je
Fachberechtigung die zugeordneten Rollen; beliebige Gruppen-IDs und
Freitexteingaben sind unzulässig. Der Standard ist:

- Jahresübersicht lesen, eigene Stunden verwalten und eigene Abrechnung
  exportieren: alle BR-Mitglieder;
- fremde Abrechnung exportieren und Reminder-Vorschau lesen: Vorsitz und
  Stellvertretung;
- fremde Stunden korrigieren: keine Rolle, also standardmäßig deaktiviert.

Eigenrechte werden zusätzlich durch den Self-Scope begrenzt. Fremdzugriffe
prüfen Zielmitgliedschaft und konkretes Fachrecht vor jedem Datenzugriff oder
jeder Änderung. Die App besitzt keinen nativen Admin-Bypass; die Verwaltung
der Rollen-Rechte-Zuordnung im Nextcloud-Adminbereich erteilt dem technischen
Admin selbst kein Fachrecht. Navigation und UI-Sichtbarkeit erteilen keine
Rechte. Ein fehlender, ungültiger oder inkompatibler LocalBase-Vertrag sperrt
Fachzugriffe und macht die Permission-Provider-Ausgabe sichtbar unvollständig.
