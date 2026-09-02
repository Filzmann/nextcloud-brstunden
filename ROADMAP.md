# Roadmap – BRStunden

Diese Datei enthält ausschließlich offene Arbeit, zurückgestellte Vorhaben
und Freigabegates. Der aktuelle Funktionsumfang steht in `README.md`,
erledigte Änderungen in `CHANGELOG.md` und geltende Architektur in
`docs/architecture.md`.

## Nextcloud-Kompatibilitätsgate

### BRS-NC-COMPAT – deklarierten Bereich 29–35 und künftige Majors belegen

`info.xml` umfasst Nextcloud 33 bereits. Vor dem nächsten Release werden alle
deklarierten Majors lückenlos mit Fresh Install/Upgrade, DI, Migrationen,
Reminder-Job, Abrechnung/PDF, Assets und sichtbarer Oberfläche geprüft. Eine
weitere Obergrenze folgt ausschließlich aus dem app-lokalen
`verify-nextcloud-future-compatibility`-Nachweis; fehlende oder rote Majors
begrenzen den ehrlichen Bereich.

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

## Systemweit gegatete app-lokale Aufgabe

### BRS-L10N – Oberfläche, Reminder, E-Mail und PDF lokalisieren

Aktivierung ausschließlich nach Freigabe des Root-Vorhabens `ZM-06`.
Datumsnamen werden locale-fähig; ISO-Daten, Monatsnummern, Minutenwerte,
API-Schlüssel und Fachwerte bleiben sprachneutral. Die persönliche oder
organisationsweite Ausgabelocale wird app-lokal entschieden und für
Abrechnungen reproduzierbar gespeichert.

## Weitere geplante Arbeiten

- Die manuellen Prüfungen werden im ausfüllbaren
  [`docs/manual-acceptance.md`](docs/manual-acceptance.md) dokumentiert.
- Reminder, Jahresübersicht und Abrechnung auf einem realitätsnahen Staging
  fachlich und datenschutzbezogen abnehmen.
- Weitere Funktionen erst nach einem konkreten Fachbedarf und benanntem
  Rechtevertrag aufnehmen.
