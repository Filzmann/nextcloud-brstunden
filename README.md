# brstunden 0.1.0

Nextcloud-App-Prototyp fuer monatliche BR-Stunden und Jahresuebersichten.

## Status

Development-Prototyp. Nicht produktiv und nicht rechtssicher.

Enthalten:

- BR-Mitglieder aus der Nextcloud-Gruppe `Betriebsrat`.
- Monatliche Stundeneintraege pro BR-Mitglied.
- Jahresuebersicht mit Monaten als Spalten und BR-Mitgliedern als Zeilen.
- Fehlende Monate je Mitglied.
- Background-Job, der am letzten Tag des Monats Erinnerungs-E-Mails an Mitglieder mit fehlenden Monaten vorbereitet und versendet.

## Lokale URL

```text
https://nextcloud-dev.ddev.site/apps/brstunden/
```

## DDEV

```bash
cd ~/projects/br-nextcloud-apps/nextcloud-dev
ddev exec -d /var/www/html/html php occ app:enable brstunden
ddev exec -d /var/www/html/html php occ status
```

Diese lokale Nextcloud-Version hat keinen occ migrations:migrate-Befehl. Die Migration wird beim Aktivieren der App ausgefuehrt; occ status sollte danach needsDbUpgrade: false melden.
