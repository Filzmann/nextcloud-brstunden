# Changelog

## Unreleased

- Abrechnungsstammdaten und optionale Texte liegen nun versioniert in der
  nativen App-Administration. Validierte Entwürfe wirken erst nach Freigabe;
  die letzte gültige freigegebene Version bleibt der sichere Rückfall.
  Platzhalter sind auf eine nicht ausführbare Whitelist begrenzt.
- PDFs halten Vorlagenversion und die feste Ausgabelocale `de-DE` selbst fest.
  Berechnungen und notwendige Formularsemantik bleiben unveränderlich; eine
  zusätzliche personenbezogene Dokumentablage entsteht nicht.
- Organisationsrollen und Fachberechtigungen getrennt: sechs app-lokale
  Rechte verwenden die kanonischen LocalBase-Rollen Mitglied, Vorsitz und
  Stellvertretung. Vorsitz und Stellvertretung dürfen standardmäßig fremde
  Abrechnungen exportieren und die Reminder-Vorschau lesen; Fremdkorrekturen
  sind als eigenes Recht vorhanden und standardmäßig deaktiviert. Die
  barrierearme Zuordnung liegt im nativen Nextcloud-Adminbereich, beliebige
  Gruppen-Freitexte werden weder angeboten noch akzeptiert.
- Fremde Stunden- und PDF-Ziele werden serverseitig auf aktuelle
  BR-Mitgliedschaft und konkretes Fachrecht geprüft. Abgewiesene Zugriffe
  lesen oder verändern keine Fachdaten; der Processing-Katalog weist den
  berechtigten Fremdexport aus.
- Nextcloud 35.0.1 durch Fresh Install, Upgrade 34→35 sowie Provider-,
  Berechtigungs-, Runtime-, UI-/API- und Asset-Smokes bestätigt. Der
  Permission-Provider-Listener erfüllt nun Nextclouds öffentlichen
  `IEventListener`-Vertrag; die Berechtigungssemantik bleibt unverändert.
  Die deklarierten Hauptversionen 29 bis 32 waren nicht Teil dieses Laufs.
- App-eigenen Processing-Metadata-Katalog für Stundenverwaltung,
  Erinnerungskommunikation und PDF-Erzeugung über den öffentlichen
  Datenschutz-V1-Vertrag veröffentlicht.
- Dokumentations- und Steuerungsstruktur vereinheitlicht.

## 0.4.4

- Bestehender BRStunden-Entwicklungsstand bei Einführung dieses Changelogs.
- Der aktuelle Funktionsumfang steht in `README.md`.
