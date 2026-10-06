# Roadmap – BRStunden

Diese Datei enthält ausschließlich offene Arbeit, zurückgestellte Vorhaben
und Freigabegates. Der aktuelle Funktionsumfang steht in `README.md`,
erledigte Änderungen in `CHANGELOG.md` und geltende Architektur in
`docs/architecture.md`.

## Nextcloud-Kompatibilitätsgate

### BRS-NC-COMPAT – RC-Kompatibilität und Zukunftsobergrenze nachweisen

Die `min-version` muss beim Release Candidate die aktuelle, autoritativ
ermittelte openDesk-Nextcloud-Hauptversion abdecken. Erst beim Erstellen eines
veröffentlichungsfähigen RC werden alle deklarierten Majors lückenlos geprüft:
Fresh Install/Upgrade, DI, Migrationen, Reminder-Job, Abrechnung/PDF, Assets
und sichtbare Oberfläche. `max-version` folgt ausschließlich der höchsten
lückenlos nachgewiesenen Major aus offiziellen, gepinnten Nextcloud-Git-Quellen;
eine offiziell benannte und testbare künftige Major (z. B. NC36) wird dabei
geprüft. Der regelmäßige Check der neuesten veröffentlichten Entwicklungsruntime
ist davon getrennt und ersetzt keinen RC-Nachweis.

## Freigegebene Umsetzungsaufgaben

### BRS-DOCUMENT-CONFIG – Abrechnungsstammdaten und Vorlage versionieren

Status: bereit nach fachlicher Trennung fester und editierbarer Inhalte

- Organisationsname, Anschrift, Ausstellungsort und organisationsspezifische
  Texte der Abrechnung in validierte App-Administration überführen.
- Fachlich feste Berechnungen und notwendige Formularsemantik getrennt und
  nicht frei entfernbar halten.
- Die PDF-Vorlage versionieren, nur validierte nicht ausführbare Platzhalter
  zulassen und Vorschau, Freigabestatus sowie Rückfall auf die letzte
  freigegebene Version anbieten.
- Jede erzeugte Abrechnung hält Vorlagenversion und Ausgabelocale fest;
  spätere Änderungen deuten bestehende Dokumente nicht um.
- Bestandsdefaults, Berechnungsgleichheit, Adress-/Textvalidierung,
  Platzhalterescaping, historische Reproduktion und Rückfall testen.

## Weitere geplante Arbeiten

- Die manuellen Prüfungen werden im ausfüllbaren
  [`docs/manual-acceptance.md`](docs/manual-acceptance.md) dokumentiert.
- Reminder, Jahresübersicht und Abrechnung auf einem realitätsnahen Staging
  fachlich und datenschutzbezogen abnehmen.
- Weitere Funktionen erst nach einem konkreten Fachbedarf und benanntem
  Rechtevertrag aufnehmen.

## Bewusst zurückgestellt – niedrigste Priorität

### BRS-L10N – Oberfläche, Reminder, E-Mail und PDF lokalisieren

Status seit 17. September 2026: Die Umsetzung beginnt erst nach allen höher
priorisierten Roadmap-Aufgaben und einer erneuten ausdrücklichen Freigabe des
Root-Vorhabens `ZM-06`. Neue Funktionen und Codeänderungen berücksichtigen
die spätere Lokalisierbarkeit an den jeweils berührten Stellen, lösen aber
keine flächige Umstellung oder Übersetzungsimplementierung aus.

Bei der späteren Umsetzung werden Datumsnamen locale-fähig; ISO-Daten,
Monatsnummern, Minutenwerte, API-Schlüssel und Fachwerte bleiben
sprachneutral. Die persönliche oder organisationsweite Ausgabelocale wird
app-lokal entschieden und für Abrechnungen reproduzierbar gespeichert.
