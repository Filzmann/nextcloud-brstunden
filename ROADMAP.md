# Roadmap – BRStunden

Diese Datei enthält ausschließlich zukünftige Ziele und freigegebene
Umsetzungsaufgaben. Geltende Fach-, Rechte-, Sicherheits- und
Architekturregeln stehen in `AGENTS.md`.

## Freigegebene Umsetzungsaufgaben

### BRS-BR-GROUPS – Gemeinsamen BR-Gruppenvertrag konsumieren

Status: bereit nach `LB-BR-GROUPS` und Klärung der
Mitgliedschaftsinvariante

- Mitgliedsprüfung, Mitgliederliste, Reminder und Administration auf den
  gemeinsamen semantischen BR-Gruppenvertrag umstellen.
- Die festen Bestandsgruppennamen additiv übernehmen; keine Gruppe oder
  Mitgliedschaft automatisch umbenennen, löschen oder verändern.
- App-spezifische serverseitige Rechteentscheidungen behalten und bei
  fehlendem oder widersprüchlichem Vertrag sicher verweigern.
- Fresh Install, Bestandsmigration, Mitglieder, Vorsitz, Stellvertretung,
  Nichtmitglieder, Admins, Reminderempfänger und direkte API-Denies testen.

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

### BRS-L10N – BRStunden vollständig lokalisieren

Status: bereit nach Entscheidung über die organisationsweite Dokumentsprache

- Monats- und Datumsnamen locale-fähig erzeugen und Oberfläche, Reminder,
  E-Mails sowie Fehlermeldungen auf Nextcloud-l10n umstellen.
- ISO-Daten, Monatsnummern, Minutenwerte, API-Schlüssel und persistierte
  Fachwerte sprachneutral lassen; Abkürzungen nicht durch Abschneiden bilden.
- Für Abrechnungen und gemeinsame Reminder vorab persönliche oder
  organisationsweite Ausgabelocale festlegen und reproduzierbar speichern.
- Deutsche Ausgabe, eine weitere Locale, Fallback, Jahresgrenzen,
  Pluralformen, Platzhalter, Escaping, E-Mail- und PDF-Ausgabe testen.

## Weitere geplante Arbeiten

- Reminder, Jahresübersicht und Abrechnung auf einem realitätsnahen Staging
  fachlich und datenschutzbezogen abnehmen.
- Weitere Funktionen erst nach einem konkreten Fachbedarf und benanntem
  Rechtevertrag aufnehmen.
