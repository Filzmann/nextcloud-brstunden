# AGENTS.md - BRStunden

## Projekt

Nextcloud-App `brstunden` fuer monatliche Erfassung und Jahresuebersicht von BR-Stunden.

Lokale App-URL in der gemeinsamen DDEV-Umgebung:

    https://nextcloud-dev.ddev.site/apps/brstunden/

Nextcloud-App-ID:

    brstunden

## Zielsetzung

BRStunden soll BR-Mitgliedern erlauben, fuer vergangene Monate ihre geleisteten BR-Stunden einzutragen.

Kernprozess:

- Ein BR-Mitglied ist ein Nextcloud-User in der Gruppe `Betriebsrat`.
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
