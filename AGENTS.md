# AGENTS.md - BRStunden

## Projekt

Nextcloud-App `brstunden` fuer monatliche Erfassung und Jahresuebersicht von BR-Stunden.

Lokale App-URL in der gemeinsamen DDEV-Umgebung:

    https://nextcloud-dev.ddev.site/apps/brstunden/

Nextcloud-App-ID:

    brstunden

Die priorisierte Produktplanung und offene Entscheidungen stehen in
`ROADMAP.md`; verbindliche Fach-, Sicherheits- und Architekturregeln bleiben
in dieser Datei.

## Zielsetzung

BRStunden soll BR-Mitgliedern erlauben, fuer vergangene Monate ihre geleisteten BR-Stunden einzutragen.

Kernprozess:

- Ein BR-Mitglied ist ein Nextcloud-User in der semantischen
  Mitgliedergruppe des gemeinsamen LocalBase-BR-Gruppenvertrags.
- Jedes BR-Mitglied traegt pro Monat die eigenen BR-Stunden ein.
- Pro Kalenderjahr wird eine tabellarische Uebersicht ueber alle BR-Mitglieder und Monate erzeugt.
- Fehlende Monate werden pro BR-Mitglied ermittelt.
- Am letzten Tag des Monats sollen alle BR-Mitglieder per E-Mail erinnert werden, wenn fuer sie Monate im laufenden Jahr bis einschliesslich des aktuellen Monats fehlen.
- Eine eingetragene `0` ist ein gueltiger Eintrag und gilt nicht als fehlend.

## Repository und gemeinsamer Arbeitsablauf

- Dieses Verzeichnis ist ein eigenstaendiges Git-Repository fuer die BR-App `brstunden`.
- Andere eigene Nextcloud-Apps, zum Beispiel `brtop` oder `adplaner`, leben in eigenen Repositories.
- Diese Datei und lokal referenzierte Skills bilden bei einem direkten Start in diesem Repository die vollständige Repository-Steuerung.
- Fuer Git-, Sandbox-, DDEV-/`occ`-Sicherheit, Verifikation und Learning Candidates gilt der lokal mitgefuehrte Skill `work-in-nextcloud-app`; die folgenden BRStunden-Regeln und Pruefungen ergaenzen ihn.

## DDEV

Die gemeinsame Nextcloud-DDEV-Umgebung wird aus dem dokumentierten
Parent-Unterverzeichnis `nextcloud-dev` gesteuert. Bei einem eigenständigen
Checkout ist der lokale DDEV-Pfad zuerst anhand der realen Umgebung zu
ermitteln.

BRStunden nutzt gemeinsame Basisbausteine aus der Hilfsapp `localbase`. In der lokalen Nextcloud muss `localbase` aktiviert sein, bevor BRStunden vollstaendig lauffaehig ist.

Der versionierte LocalBase-Vertrag ist die einzige Laufzeitquelle für
Mitglieder-, Vorsitz- und Stellvertretungsgruppe. Vorsitzende und
Stellvertretungen müssen zugleich Mitglieder der konfigurierten
Mitgliedergruppe sein. Fehlende, beschädigte oder widersprüchliche Verträge
werden serverseitig abgewiesen; BRStunden benennt keine Gruppen um und
verändert keine Mitgliedschaften.

Wichtige Pruefungen:

    ddev exec -d /var/www/html/html php occ app:list | grep -i localbase
    ddev exec -d /var/www/html/html php occ status
    ddev exec -d /var/www/html/html php occ app:list
    ddev exec -d /var/www/html/html php occ app:enable brstunden
    ddev exec -d /var/www/html/html php occ upgrade

## Architekturregeln

- Der lokale Skill `work-in-nextcloud-app` ist die kanonische Quelle für
  gemeinsame Schichtungs-, Modell-, Sicherheits-, UI- und Testregeln.
- Monatserfassung, Jahresübersicht, Ermittlung fehlender Monate,
  Reminderplanung, PDF-Erzeugung und E-Mail-Versand bleiben getrennte
  fachliche Verantwortungen.
- Eine gespeicherte `0` bleibt ein vorhandener Fachdatensatz und darf weder
  durch Wahrheitswertprüfung noch durch Reminderlogik als fehlend gelten.
- App-spezifische Fachlogik bleibt in BRStunden; gemeinsame Bausteine wandern
  erst bei mindestens zwei semantisch gleichen, testbaren Nutzungen nach
  LocalBase.
- Personenbezogene Stunden- und Bearbeitungsbezüge werden ausschließlich über
  den öffentlichen V1-Vertrag von `filzmann_data_protection` bereitgestellt.
  Fremde Stundensätze, Notizen und UIDs bleiben aus der subjectgebundenen
  Auskunft ausgeschlossen.
- BRStunden besitzt aktuell keinen nativen Admin-Bypass und deshalb keinen
  wirkungslosen app-lokalen Vollzugriffsschalter. Fachrechte entstehen nur
  aus dem BR-Mitgliedervertrag und `self`. PermissionProvider und Negativtest
  sichern diese begründete Nichtanwendbarkeit. Sobald ein nativer Adminpfad
  oder eine neue Fachberechtigung hinzukommt, muss die Bewertung im selben
  Änderungssatz erneut erfolgen.

## Verbindliche Suite-Navigation

- BRStunden besitzt keinen eigenen Nextcloud-Hauptnavigationseintrag. `orgsuite` stellt den gemeinsamen Einstieg `BR` bereit.
- Das Template bindet das zentrale OrgSuite-Menue mit `data-suite="br"` und `data-current-app="brstunden"` ein.
- Stunden- und Uebersichtsrechte bleiben ausschliesslich serverseitig in BRStunden; Menuesichtbarkeit ist keine Berechtigung.


## Tests

Vor groesseren Refactorings zuerst Charakterisierungstests fuer das bestehende gewuenschte Verhalten schreiben oder aktualisieren.

- Tests sind Teil der Architekturarbeit und kein optionaler Nachtrag. Neue oder refaktorierte BRStunden-Fachlogik bekommt passende Charakterisierungs-, Unit-, Contract- oder Smoke-Tests, bevor darauf weiter aufgebaut wird.
- Schnelle PHP-Suite: `php tests/run.php`
- Schnelle JavaScript-Suite: `node tests/run-js.mjs`
- Nach LocalBase-Aenderungen mindestens die betroffenen BRStunden-Smoke-/Contract-Tests laufen lassen.
- Gemeinsame LocalBase-Test-Helper nutzen, wenn dadurch echte Setup-Duplizierung verschwindet, ohne die Lesbarkeit des einzelnen Tests zu verschlechtern.
- Bei Controller-, DI-, Migrations-, Background-Job- oder Nextcloud-Container-Aenderungen zusaetzlich gezielte DDEV-/`occ`-Checks ausfuehren.

Wichtige lokale Pruefungen:

    php tests/run.php
    node tests/run-js.mjs

Einzelne Checks, die durch die Testlaeufer gebuendelt werden:

    find js -name '*.js' -print0 | xargs -0 -n1 node --check
    node tests/js/model-smoke.js
    node tests/js/hour-repository-smoke.js
    php tests/unit/run.php

## Dokumentenverantwortung

- `README.md` beschreibt ausschließlich den aktuellen nutzbaren Stand,
  Installation, Betrieb, Tests und den Dokumentationsindex.
- `ROADMAP.md` enthält ausschließlich offene, zurückgestellte oder
  freigabepflichtige Arbeit und Entscheidungen.
- `CHANGELOG.md` dokumentiert erledigte Änderungen releasebezogen; erledigte
  Checklisten verbleiben nicht in der Roadmap.
- `docs/architecture.md` ist die ausführliche Quelle für geltende fachliche
  und technische Architekturverträge.
- `docs/manual-acceptance.md` enthält wiederholbare manuelle Prüfungen und
  keine Produktplanung.
- `AGENTS.md` enthält ausschließlich verbindliche Arbeits-, Sicherheits-,
  Architektur- und Prüfregeln. Zusätzliche Dokumente werden in `README.md`
  mit eindeutiger Zuständigkeit eingeordnet.

## Parent-Governance-Vertrag: 2

- Die für dieses Subrepository anwendbaren Regeln des Parent-Workspaces sind
  verbindlich. Dazu gehören insbesondere app-übergreifende ADRs und
  öffentliche Verträge, Repositorygrenzen sowie Workspace-, Delivery- und
  Release-Gates.
- Diese lokale `AGENTS.md` und die lokalen Skills bleiben die vollständige,
  ohne Parent-Checkout arbeitsfähige Repository-Steuerung. Die anwendbaren
  Parent-Regeln werden dafür hier oder in den lokalen Skills mitgeführt.
- Repository-lokale Regeln dürfen Parent-Verträge konkretisieren und verschärfen,
  aber nicht abschwächen oder umgehen.
- Bei einem Widerspruch gilt bis zur Klärung die strengere Regel. Die Arbeit
  stoppt, bis die kanonische Quelle bestimmt, die Regelprojektionen
  synchronisiert und eine erforderliche Entscheidung dokumentiert ist.
- Ist der Parent-Workspace nicht verfügbar, bleibt die lokale Steuerung
  wirksam. Vor Cross-App-, Release- oder Delivery-Arbeit muss ein vermuteter
  neuerer Parent-Stand oder eine Regelungslücke zuerst gegen den Parent
  geprüft werden.

### Entwicklungsphase und Kompatibilitätsbedarf

Entscheidung vom 5. September 2026: Das Gesamtprojekt befindet sich vollständig
in der Entwicklung. Es gibt kein PROD, keinen produktiven Datenbestand und
keinen bereits betriebenen Bestand mit zu erhaltendem Upgradepfad. STAGING
ist eine wegwerfbare Entwicklungs- und Integrationsumgebung und darf im
konkret beauftragten Reinstall vollständig neu aufgebaut werden. Wenige
externe Testnutzer ändern diese Einordnung nicht.

Vor einer Datenmigration, Legacy-Unterstützung, Compatibility Layer,
Deprecated API, Dual-Read/Dual-Write, einem Altschema-Fallback, Übergangsformat
oder der Unterstützung historischer Entwicklungsstände wird geprüft:

1. Wurde der betroffene Zustand jemals produktiv eingesetzt?
2. Benötigen reale Daten oder Nutzer seine Erhaltung?
3. Gibt es einen anderen konkreten technischen Erhaltungsgrund, insbesondere
   einen geltenden Plattform- oder externen API-Vertrag?

Sind alle relevanten Antworten nein, ist die saubere Breaking-Change-/
Reinstall-Lösung der Standard. Frühere rein interne Entwicklungsstände
begründen weder Abwärtskompatibilität noch eine Deprecationfrist.
Entwicklungsschemata dürfen durch ein kanonisches Installationsschema ersetzt,
alte interne APIs und Konfigurationsformate samt ausschließlich dafür
benötigten Adaptern und Tests entfernt werden. Architekturqualität und der
saubere Zielzustand haben Vorrang. Nextclouds nötige Installationsmigrationen
bleiben erhalten; ein Verzeichnisname `Migration` beweist keine Altlast.

Breaking Changes werden im selben Änderungskontext vollständig durchgezogen:
betroffene Provider, Consumer, standardisierte APIs, Vertragsversionen,
Metadaten, Tests und Dokumentation müssen zusammenpassen. Unterstützte
Nextcloud-/openDesk-Plattformverträge, externe Standards, Autorisierung und
Datenschutz gelten unverändert. Fehlende oder inkompatible optionale Provider
bleiben kontrolliert sichtbar. Ein Reinstall erlaubt keine privaten
Fremdtabellenzugriffe oder parallel erfundenen Plattformmechanismen.

Vor destruktiver Arbeit werden die tatsächlich benötigten externen
Testidentitäten, Gruppen, Rollen und nicht reproduzierbaren Testdaten gezielt
gesichert oder über bestehende native Setup-Strukturen reproduzierbar gemacht.
Echte Personen- und Zugangsdaten bleiben außerhalb von Git. Diese begrenzte
Sicherung begründet keine allgemeine Legacy-Unterstützung. Ein Reinstall
bleibt ein normaler unterstützter Entwicklungsweg; der vorhandene
Compatibility-Workflow besitzt den Fresh-Install-Nachweis, dessen aktueller
Belegstatus in `docs/workspace.md` beschrieben ist.

Diese Phase endet ausschließlich durch einen ausdrücklich dokumentierten,
von Simon freigegebenen **Production-Readiness-/Production-Freeze-Entscheid**.
Ein Release Candidate, eine Versionsnummer, ein Staging-Deployment oder ein
externer Testzugang lösen den Wechsel nicht aus. Der Entscheid wird in dieser
kanonischen Lifecycle-Quelle mit Datum, Geltungsbereich und betroffenem
Versions-/Datenstand festgehalten und in die lokale Steuerung projiziert.
Dann werden Upgradepfade, Datenbankmigrationen, Persistenz, Backup/Restore,
Rollback, Release-/API-Kompatibilitätszusagen, Deployment-/Freigabeprozess und
PROD→STAGING/COPY-Strategie neu bewertet. Eine vollständige PROD-Governance
wird jetzt nicht vorweggenommen.

Diese Regel entscheidet den Kompatibilitätsbedarf, erweitert aber keinen
Repository-Schreibauftrag und ersetzt keine Freigabe für eine konkrete
destruktive Aktion. Lokale Regelprojektionen folgen dem bestehenden
`docs/parent-governance-contract.md`; ein unsynchronisierter Einzel-Checkout
darf keinen abweichenden Phasenstand stillschweigend annehmen.

### Prüfaufwand

- Vor einem neuen Test, Scan, Linter, Architektur- oder Systemcheck wird
  geprüft, welcher bestehende Check dieselbe Eigenschaft bereits nachweist.
  Diesen erweitern oder sein nachweislich passendes Ergebnis wiederverwenden;
  ein zusätzlicher Check braucht eine benannte zusätzliche Fehlerklasse oder
  Vertrauensgrenze. Gleicher Input, gleiche Prüfung, gleiche Fehlerklasse und
  gleiche Phase begründen keinen zweiten Lauf. Gestaffelte Unit-, Contract-
  und Runtime-Nachweise bleiben erhalten. Die dokumentierten lokalen
  Prüfeinstiege bestimmen Umfang und Ergebnisgültigkeit.
