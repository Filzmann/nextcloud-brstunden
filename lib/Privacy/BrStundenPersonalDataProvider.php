<?php

declare(strict_types=1);

namespace OCA\BrStunden\Privacy;

use InvalidArgumentException;
use OCA\BrStunden\Repository\HourEntryRepository;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataEntry;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataPage;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataProvider;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FilzmannDataProtection\PublicApi\V1\ProviderDescriptor;

final class BrStundenPersonalDataProvider implements PersonalDataProvider {
    private const MONTHS = [1=>'Januar', 2=>'Februar', 3=>'März', 4=>'April', 5=>'Mai', 6=>'Juni', 7=>'Juli', 8=>'August', 9=>'September', 10=>'Oktober', 11=>'November', 12=>'Dezember'];

    public function __construct(private HourEntryRepository $entries) {
    }

    public function descriptor(): ProviderDescriptor {
        return new ProviderDescriptor('brstunden', 'BR-Stunden', '1.0', ['nextcloud-user'], ['personal-data'], 500);
    }

    public function collect(PersonalDataRequest $request): PersonalDataPage {
        if ($request->subject()->subjectType() !== 'nextcloud-user') return new PersonalDataPage('not_applicable');
        if ($request->cursor() !== null) throw new InvalidArgumentException('BRStunden does not support cursor paging.');

        $uid = $request->subject()->subjectId();
        $rows = $this->entries->findPrivacyEntriesForSubject($uid, $request->pageLimit() + 1);
        $limited = count($rows) > $request->pageLimit();
        if ($limited) $rows = array_slice($rows, 0, $request->pageLimit());
        $items = array_map(fn(array $row): PersonalDataEntry => $this->item($row, $uid), $rows);
        if ($items === []) return new PersonalDataPage('not_applicable');

        return new PersonalDataPage(
            $limited ? 'partial' : 'complete',
            $items,
            $limited ? ['Ausgabelimit erreicht; weitere BR-Stundenbezüge können vorhanden sein.'] : [],
        );
    }

    /** @param array<string, mixed> $row */
    private function item(array $row, string $uid): PersonalDataEntry {
        $ownEntry = (string)$row['user_id'] === $uid;
        $period = (self::MONTHS[(int)$row['entry_month']] ?? ('Monat ' . (int)$row['entry_month'])) . ' ' . (int)$row['entry_year'];
        if (!$ownEntry) {
            return new PersonalDataEntry(
                categoryId: 'hour_entry_activity',
                categoryLabel: 'Bearbeitungsnachweis',
                reference: 'hour-entry-activity:' . (int)$row['id'],
                summary: 'Bearbeitung eines BR-Stundensatzes für ' . $period,
                purpose: 'Nachvollziehbarkeit der Erfassung von BR-Stunden',
                source: 'Automatisch beim Speichern durch die betroffene Person erzeugter Bearbeitungsnachweis',
                recipientCategories: ['Berechtigte Mitglieder und Funktionsträger*innen des Betriebsrats'],
                retention: 'Keine feste Löschfrist festgelegt; gespeichert zusammen mit dem zugehörigen Stundensatz.',
                thirdCountryTransfer: 'BR-Stunden sieht keine Drittlandübermittlung vor.',
                automatedDecision: 'Es findet keine automatisierte Entscheidung mit rechtlicher oder vergleichbar erheblicher Wirkung statt.',
                thirdPartyContentNotice: 'Der Nachweis gehört zu einem Stundensatz einer anderen Person. Deren Identität, Stundenwerte und Notiz werden nicht ausgegeben.',
                attributes: ['Zeitraum' => $period, 'Bearbeitet am' => self::dateValue($row['updated_at'] ?? '')],
            );
        }

        return new PersonalDataEntry(
            categoryId: 'hour_entry',
            categoryLabel: 'Monatlicher BR-Stundensatz',
            reference: 'hour-entry:' . (int)$row['id'],
            summary: $period . ': ' . self::hours((int)$row['minutes']) . ' BR-Arbeit und ' . self::hours((int)$row['fobi_minutes']) . ' Fortbildung',
            purpose: 'Erfassung, Jahresübersicht und Abrechnung geleisteter Betriebsrats- und Fortbildungszeiten',
            source: 'Eingabe der betroffenen Person; Zeitstempel und Bearbeitungsbezug entstehen beim Speichern',
            recipientCategories: ['Berechtigte Mitglieder und Funktionsträger*innen des Betriebsrats', 'Für die Abrechnung vorgesehene Empfänger*innen des von der betroffenen Person erzeugten PDF-Dokuments'],
            retention: 'Keine feste Löschfrist festgelegt; gespeichert bis zur fachlich oder gesetzlich veranlassten Löschung.',
            thirdCountryTransfer: 'BR-Stunden sieht keine Drittlandübermittlung vor.',
            automatedDecision: 'Fehlende Monate und Summen werden automatisch ermittelt; es findet keine Entscheidung mit rechtlicher oder vergleichbar erheblicher Wirkung statt.',
            thirdPartyContentNotice: 'Die freiwillige Notiz kann Angaben über andere Personen enthalten. Solche Angaben werden nicht als deren eigener Datensatz zugeordnet.',
            attributes: [
                'Zeitraum' => $period,
                'BR-Arbeit' => self::hours((int)$row['minutes']),
                'Fortbildung' => self::hours((int)$row['fobi_minutes']),
                'Notiz' => (string)($row['note'] ?? ''),
                'Erstellt am' => self::dateValue($row['created_at'] ?? ''),
                'Geändert am' => self::dateValue($row['updated_at'] ?? ''),
            ],
        );
    }

    private static function hours(int $minutes): string {
        $hours = rtrim(rtrim(number_format($minutes / 60, 2, ',', ''), '0'), ',');
        return $hours . ' Stunden';
    }

    private static function dateValue(mixed $value): string {
        return $value instanceof \DateTimeInterface ? $value->format('c') : (string)$value;
    }
}
