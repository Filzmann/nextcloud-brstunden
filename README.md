# BRStunden

Nextcloud-App-Prototyp fuer monatliche BR-Stunden und Jahresuebersichten.

## Status

Development-Prototyp. Nicht produktiv und nicht rechtssicher.

Enthalten:

- BR-Mitglieder sowie Vorsitz und Stellvertretung aus dem gemeinsamen,
  versionierten LocalBase-BR-Rollenvertrag.
- Monatliche Stundeneintraege pro BR-Mitglied, getrennt nach BR-Stunden und FoBi-Stunden.
- Jahresuebersicht mit Monaten als Spalten und BR-Mitgliedern als Zeilen.
- Fehlende Monate je Mitglied.
- Background-Job, der am letzten Tag des Monats Erinnerungs-E-Mails an Mitglieder mit fehlenden Monaten vorbereitet und versendet.
- Reminder-Laeufe werden pro Jahr/Monat markiert, damit Monatsend-Mails nicht mehrfach versendet werden.
- BR-Mitglieder verwalten standardmäßig nur eigene Einträge; das getrennte
  Recht zur Fremdkorrektur ist vorhanden und standardmäßig deaktiviert.
- BR-Mitglieder exportieren die eigene PDF-Abrechnung; Vorsitz und
  Stellvertretung dürfen standardmäßig zusätzlich Abrechnungen anderer
  BR-Mitglieder erzeugen und die Reminder-Vorschau lesen.
- Die sechs Fachberechtigungen werden im nativen Nextcloud-Adminbereich über
  Auswahlfelder den semantischen BR-Rollen zugeordnet. Technische Gruppen-IDs
  werden nicht als Berechtigung gespeichert.

Die App besitzt keinen fachlichen Nextcloud-Admin-Bypass: Auch native
Administrierende erhalten ohne fachliche Rolle keine Stundenrechte. Daher ist
ein app-lokaler Vollzugriffsschalter weiterhin nicht anwendbar. Der
PermissionProvider veröffentlicht die sechs getrennten Rechte mit ihrem
Eigen- oder Fremdscope; ein Negativvertrag erzwingt eine Neubewertung, sobald
ein Admin-Vollzugriffspfad ergänzt wird.

Die zustandslosen Browserbausteine aus `flz_shared` 0.1.0 werden reproduzierbar
im app-lokalen `js/shared`-Bundle unter `window.OCA.BrStunden.Shared`
ausgeliefert. BRStunden benötigt dafür weder eine installierte `flz_shared`-
Laufzeit-App noch LocalBase-Browserassets. Änderungen am gemeinsamen Paket
werden mit einer neuen BRStunden-Appversion ausgeliefert; ein Rückbau auf die
vorherige Appversion stellt deren festgeschriebene Bundleversion wieder her.

## Datenschutz

Der app-eigene `PersonalDataProvider` liefert eigene Monatswerte,
Fortbildungszeiten und Notizen subjectgebunden an das Datenschutz-Center;
fremde Bearbeitungsreferenzen werden neutralisiert. Der versionierte
Processing-Metadata-Provider beschreibt ergänzend die drei Verarbeitungen
Stundenverwaltung, Erinnerungskommunikation und transiente PDF-Erzeugung aus
`resources/privacy-processing.json`, ohne personenbezogene Laufzeitdaten in
den Katalog zu übernehmen. Offene fachliche und rechtliche Entscheidungen
bleiben dort sichtbar als `PRIVACY-DECISION-REQUIRED`.

BR-Stunden speichert erzeugte Abrechnungs-PDFs nicht app-seitig. Die
Erinnerungsläufe speichern keine Empfängerlisten oder Nachrichtenkopien,
sondern ausschließlich aggregierte Laufwerte.

Organisationsname, Organisationsanschrift, Ausstellungsort sowie optionale
Einleitungs- und Fußtexte der PDF werden versioniert im nativen
Nextcloud-Adminbereich gepflegt. Entwürfe verändern laufende Exporte nicht;
erst eine ausdrückliche Freigabe aktiviert eine neue unveränderliche Version.
Die PDF hält ihre Vorlagenversion und die derzeit feste Ausgabelocale `de-DE`
selbst fest. Berechnungen, Mitglieds-/Monatsbezug, Stundenfelder, Gesamtsumme
und Unterschriftssemantik bleiben nicht editierbar.

## Lokale URL

```text
https://nextcloud-dev.ddev.site/apps/brstunden/
```

## DDEV

Aus dem dokumentierten `nextcloud-dev`-Root:

```bash
ddev exec -d /var/www/html/html php occ app:enable brstunden
ddev exec -d /var/www/html/html php occ status
```

Diese lokale Nextcloud-Version hat keinen occ migrations:migrate-Befehl. Die Migration wird beim Aktivieren der App ausgefuehrt; occ status sollte danach needsDbUpgrade: false melden.

## Abnahme und Roadmap

Für die fachliche, visuelle und datenschutzbezogene Staging-Prüfung steht ein
ausfüllbares [manuelles Abnahmeformular](docs/manual-acceptance.md) bereit.
Es bezieht sich ausdrücklich auf den aktuellen Entwicklungsstand und erteilt
keine Produktiv- oder Rechtssicherheitsfreigabe.

Geplante Erweiterungen und offene Fachentscheidungen stehen in der
[`ROADMAP.md`](ROADMAP.md).

## Dokumentation

- [Architektur](docs/architecture.md)
- [Lokalisierungsinventar](docs/l10n-inventory.md)
- [Manuelle Abnahme](docs/manual-acceptance.md)
- [Roadmap](ROADMAP.md)
- [Changelog](CHANGELOG.md)
- [Arbeitsregeln](AGENTS.md)
