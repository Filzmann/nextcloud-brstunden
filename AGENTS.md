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

## Git- und Arbeitsregeln

- Dieses Verzeichnis ist ein eigenstaendiges Git-Repository fuer die BR-App `brstunden`.
- Andere eigene Nextcloud-Apps, zum Beispiel `brtop` oder `adplaner`, leben in eigenen Repositories.
- Keine Commits, kein Push und kein Deployment ohne ausdrueckliche Freigabe durch Simon.
- Vor Commits immer `git status --short`, `git diff --stat` und `git diff --name-only` zeigen.
- Nicht `git add .` verwenden; Dateien gezielt stagen.
- Aenderungen klein, pruefbar und rueckbaubar halten.

## DDEV

Die gemeinsame lokale Nextcloud-DDEV-Umgebung liegt ausserhalb dieses Repos:

    ~/projects/br-nextcloud-apps/nextcloud-dev

BRStunden nutzt gemeinsame Basisbausteine aus der Hilfsapp `localbase`. In der lokalen Nextcloud muss `localbase` aktiviert sein, bevor BRStunden vollstaendig lauffaehig ist.

Wichtige Pruefungen:

    ddev exec -d /var/www/html/html php occ app:list | grep -i localbase
    ddev exec -d /var/www/html/html php occ status
    ddev exec -d /var/www/html/html php occ app:list
    ddev exec -d /var/www/html/html php occ app:enable brstunden
    ddev exec -d /var/www/html/html php occ upgrade

Diese lokale Nextcloud-Version hat keinen occ migrations:migrate-Befehl. App-Migrationen laufen beim Aktivieren der App bzw. ueber occ upgrade, wenn Nextcloud einen DB-Upgrade-Bedarf meldet.

In Codex-Sessions koennen DDEV-Befehle im normalen Sandbox-Kontext nicht zuverlaessig auf Docker zugreifen. Wenn `ddev` mit Docker-/Stream-FD-Fehlern scheitert, den gleichen Befehl mit eskaliertem Zugriff erneut ausfuehren.

## Architekturregeln

- Controller bleiben duenn.
- Fachlogik, Datenzugriff, Darstellung und E-Mail-Versand werden getrennt.
- Persistente Kernobjekte bekommen Modelle/DTOs oder Value Objects.
- Modelle/DTOs werden bei Neu- und Weiterentwicklungen in PHP und JavaScript einheitlich angefasst: `get(...)` fuer ein einzelnes Payload/Row/Objekt, `get_all([...])` fuer Listen, `toArray()` fuer Serialisierung und `save()` nur fuer wirklich persistierbare, store-gebundene Modelle. Nicht persistierbare DTOs duerfen `save()` bewusst mit klarer Fehlermeldung blockieren.
- Modell-Hydration wird von aussen ueber `get(...)` und `get_all([...])` aufgerufen. Hilfsmethoden wie `fromArray` oder `fromRow` bleiben, falls noetig, interne/protected Implementierungsdetails und sind keine oeffentliche Modell-API.
- Neue Modellarbeit fuehrt keine neuen `fromApi`-/`toApi`-Kompatibilitaetsaliase ein. Bestehende PHP-`toApiArray()`-Call-sites duerfen schrittweise auf `toArray()` migriert werden, wenn die betroffene Schicht ohnehin angefasst wird.
- Datenzugriffe laufen ueber Repository-, Store- oder Service-Klassen.
- Services arbeiten bevorzugt mit Modellen/DTOs statt rohen Arrays.
- JavaScript wird gut gekapselt, wiederverwendbar und weitgehend objektorientiert strukturiert. API-Zugriffe gehoeren in Repositories/API-Adapter, Daten in Modelle/ViewModels, Workflows in kleine Services/Controller und Rendering/Eventbindung in Komponenten.
- DRY und KISS gelten gemeinsam: echte Duplizierung wird entfernt, aber einfache Lesbarkeit und klare BRStunden-Fachgrenzen bleiben wichtiger als fruehe generische Abstraktionen.
- Gemeinsame UI-Helfer oder Komponenten werden erst nach `localbase` verschoben, wenn sie in mindestens zwei Apps dieselbe Semantik, dieselben Zustaende, Events und Accessibility-Regeln haben.
- Fehler werden zentral protokolliert; Nutzer*innen erhalten sichere, knappe Meldungen ohne interne Details.
- Keine Architekturabstraktion wird vorsorglich gebaut.

## Learnings pflegen

- App-spezifische Learnings werden in dieser `AGENTS.md` gespeichert.
- App-uebergreifende Learnings werden im Parent-Workspace dokumentiert und bei Bedarf hier wiederholt.
- Ergaenzungen erfolgen erst nach ausdruecklicher Freigabe.

## Tests

Wichtige lokale Pruefungen:

    find js -name '*.js' -print0 | xargs -0 -n1 node --check
    node tests/js/model-smoke.js
    php tests/unit/run.php
