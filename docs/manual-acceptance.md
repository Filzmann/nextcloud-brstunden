# Manuelles Abnahmeformular – BRStunden

Dieses Formular dokumentiert die fachliche, visuelle und datenschutzbezogene
Abnahme des aktuellen BRStunden-Entwicklungsstands auf einem realitätsnahen
Staging-System. Eine erfolgreiche Abnahme bestätigt weder Produktivreife noch
Rechtssicherheit. Pro Prüffall wird genau ein Ergebnis markiert und eine
Abweichung knapp begründet.

Keine personenbezogenen Echtdaten, realen Stundenabrechnungen, E-Mail-Adressen,
Dateipfade oder Zugangsdaten eintragen. Ausschließlich neutrale Testkonten und
synthetische Stundenwerte verwenden.

## Kopfdaten

| Feld | Eintrag |
|---|---|
| Datum und Uhrzeit | |
| Prüfer*in | |
| Umgebung und URL | |
| BRStunden-Version | |
| Nextcloud-Version | |
| Browser und Version | |
| Fenstergröße / Zoom | |
| Neutrale Testkonten und Gruppen | |
| Testjahr und Testmonate | |

Ergebniskennzeichnung: `[ ] erfolgreich` / `[ ] nicht erfolgreich` /
`[ ] nicht geprüft`. Bei „nicht erfolgreich“ oder „nicht geprüft“ ist eine
Begründung verpflichtend.

## A. Einstieg, Zugriff und Jahresübersicht

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| A1 | Zugriff als BR-Mitglied | Mit einem neutralen Mitglied der Gruppe `Betriebsrat` BRStunden öffnen. | Erfassung und Jahresübersicht werden geladen. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A2 | Zugriff ohne Mitgliedschaft | Mit einem angemeldeten Konto ohne BR-Mitgliedschaft App und direkten API-Aufruf versuchen. | Der Zugriff wird serverseitig verweigert; die Menüsichtbarkeit erteilt kein Recht. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A3 | BR-Suite-Navigation | BRStunden über den gemeinsamen BR-Einstieg öffnen und zwischen aktivierten BR-Apps wechseln. | BRStunden ist korrekt markiert und ohne eigenen doppelten Haupteinstieg erreichbar. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A4 | Jahreswechsel | Zwei unterschiedliche Testjahre öffnen, darunter eines ohne Einträge. | Monate und Einträge gehören eindeutig zum gewählten Jahr; ein leeres Jahr wird verständlich dargestellt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A5 | Übersicht und Notizhinweis | Mehrere Mitglieder mit unterschiedlichen Einträgen anzeigen und einen Eintrag mit Notiz prüfen. | Mitglieder stehen in Zeilen, Monate in Spalten; vorhandene Notizen werden zugänglich, aber nicht unnötig offenbart, gekennzeichnet. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A6 | Tastatur, Fokus und Scrollen | Jahr, Formular, Übersicht, Erinnerungsbereich und PDF-Aktion nur mit Tastatur bedienen; kleines Fenster verwenden. | Alle Aktionen sind erreichbar, Fokus ist sichtbar und breite Tabellen bleiben innerhalb der App scrollbar. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## B. Eigene Monatserfassung

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| B1 | Neuer Monatseintrag | Für einen fehlenden vergangenen Testmonat BR- und FoBi-Stunden sowie eine neutrale Notiz speichern. | Genau ein eigener Monatseintrag entsteht und erscheint nach Neuladen in Formular und Übersicht. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B2 | Gespeicherte Null | Für einen anderen Monat ausdrücklich `0` Stunden speichern. | Der Datensatz bleibt vorhanden und der Monat gilt nicht als fehlend. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B3 | Änderung | Einen vorhandenen eigenen Eintrag ändern und neu laden. | Nur der gewählte Monat wird aktualisiert; andere Monate bleiben unverändert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B4 | Löschung | Einen entbehrlichen eigenen Testeintrag löschen und die Übersicht aktualisieren. | Der Eintrag verschwindet und der Monat gilt wieder als fehlend; andere Einträge bleiben erhalten. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B5 | Fremdeintrag | Als normales BR-Mitglied den Eintrag eines anderen Mitglieds über Oberfläche und direkten API-Aufruf zu ändern versuchen. | Beide Wege werden abgewiesen; der fremde Datensatz bleibt unverändert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B6 | Eingabegrenzen | Leere, negative, nicht numerische und übermäßig große Werte sowie einen unzulässigen Monat versuchen. | Ungültige Eingaben werden verständlich abgewiesen und überschreiben keinen gültigen Stand. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## C. Fehlende Monate und Erinnerungen

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| C1 | Ermittlung fehlender Monate | Testmitglied mit Lücke, Testmitglied mit vollständigen Einträgen und Testmonat mit gespeicherter Null vergleichen. | Nur tatsächlich fehlende Datensätze werden als fehlend gemeldet; `0` gilt als vorhanden. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C2 | Zeitliche Begrenzung | Laufendes Jahr prüfen, in dem zukünftige Monate noch nicht erreicht sind. | Zukünftige Monate werden nicht vorzeitig als erinnerungspflichtig behandelt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C3 | Erinnerungsprüfung | Den sichtbaren Erinnerungsstatus für mehrere neutrale Mitglieder laden. | Betroffene Mitglieder und Monate werden korrekt ermittelt, ohne unnötige Notizinhalte offenzulegen. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C4 | Monatsendversand | In einer dafür vorgesehenen Testumgebung einen fälligen Monatsendlauf mit Test-Mailboxen ausführen. | Nur Mitglieder mit fehlenden fälligen Monaten erhalten eine Nachricht; Inhalte sind datensparsam. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C5 | Wiederholungsschutz | Denselben fälligen Monatsendlauf erneut ausführen. | Für denselben Jahr-/Monatslauf wird keine zweite Erinnerungs-E-Mail versendet. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C6 | Fehlerisolation | Eine ausschließlich für Tests bestimmte nicht erreichbare Mailadresse neben einer gültigen Testadresse verwenden. | Ein Fehler wird nachvollziehbar behandelt und führt nicht zu falschen Erfolgsaussagen oder Datenverlust. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## D. PDF-Abrechnung und Datenschutz

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| D1 | PDF für vorhandenen Eintrag | Einen gespeicherten eigenen Testmonat wählen und PDF herunterladen. | Eine lesbare, vorausgefüllte Abrechnung mit genau den gespeicherten synthetischen Werten wird erzeugt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D2 | Kein PDF ohne Eintrag | Einen Monat ohne eigenen Datensatz wählen und PDF-Aktion prüfen. | Die Aktion bleibt deaktiviert oder wird serverseitig verständlich abgewiesen. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D3 | Fremdes PDF | Direkten Download für den Datensatz eines anderen Mitglieds versuchen. | Der Zugriff wird serverseitig verweigert; es werden keine fremden Stunden offengelegt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D4 | Inhalt und Dateiname | PDF-Inhalt, Metadaten und Dateinamen auf unnötige Personen- oder Systemdaten prüfen. | Nur die für die Testabrechnung vorgesehenen Angaben sind enthalten; keine technischen Pfade oder Secrets werden sichtbar. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D5 | Datensparsame Abnahme | Formular, Screenshots, Testmails und PDFs vor Ablage oder Weitergabe prüfen. | Es wurden ausschließlich synthetische Daten verwendet und keine Zugangsdaten dokumentiert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## Abschlussentscheidung

| Feld | Eintrag |
|---|---|
| Anzahl erfolgreich | |
| Anzahl nicht erfolgreich | |
| Anzahl nicht geprüft | |
| Kritische Abweichungen / Ticketreferenzen | |
| Erneute Prüfung erforderlich bis | |
| Entscheidung zum aktuellen Entwicklungsstand | [ ] abgenommen [ ] mit Auflagen abgenommen [ ] nicht abgenommen |
| Produktiv- oder Rechtssicherheitsfreigabe | nicht Bestandteil dieses Formulars |
| Begründung der Gesamtentscheidung | |
| Name / Datum | |
