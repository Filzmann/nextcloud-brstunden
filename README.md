# BRStunden

Nextcloud-App-Prototyp fuer monatliche BR-Stunden und Jahresuebersichten.

## Status

Development-Prototyp. Nicht produktiv und nicht rechtssicher.

Enthalten:

- BR-Mitglieder aus der im gemeinsamen LocalBase-Vertrag konfigurierten
  Nextcloud-Gruppe; der Default lautet `Betriebsrat`.
- Monatliche Stundeneintraege pro BR-Mitglied, getrennt nach BR-Stunden und FoBi-Stunden.
- Jahresuebersicht mit Monaten als Spalten und BR-Mitgliedern als Zeilen.
- Fehlende Monate je Mitglied.
- Background-Job, der am letzten Tag des Monats Erinnerungs-E-Mails an Mitglieder mit fehlenden Monaten vorbereitet und versendet.
- Reminder-Laeufe werden pro Jahr/Monat markiert, damit Monatsend-Mails nicht mehrfach versendet werden.
- BR-Mitglieder koennen nur eigene Eintraege bearbeiten oder loeschen.
- Gespeicherte eigene Eintraege koennen als vorausgefuellte PDF-Abrechnung heruntergeladen werden.

Die App besitzt keinen fachlichen Nextcloud-Admin-Bypass: Auch native
Administrierende erhalten ohne Mitgliedschaft keine Stundenrechte. Daher ist
ein app-lokaler Vollzugriffsschalter derzeit nicht anwendbar. Der
PermissionProvider beschreibt ausschließlich Mitglieder- und Eigenrechte;
ein Negativvertrag erzwingt eine Neubewertung, sobald ein Adminpfad ergänzt
wird.

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
- [Manuelle Abnahme](docs/manual-acceptance.md)
- [Roadmap](ROADMAP.md)
- [Changelog](CHANGELOG.md)
- [Arbeitsregeln](AGENTS.md)
