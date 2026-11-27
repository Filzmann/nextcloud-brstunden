<?php

declare(strict_types=1);

namespace OCA\BrStunden\Privacy;

use InvalidArgumentException;
use OCA\BrStunden\Repository\HourEntryRepository;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataEntry;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataPage;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataProvider;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FlzDataProtection\PublicApi\V1\ProviderDescriptor;
use OCP\IL10N;

final class BrStundenPersonalDataProvider implements PersonalDataProvider {
    private const MONTHS = [1=>'Januar', 2=>'Februar', 3=>'März', 4=>'April', 5=>'Mai', 6=>'Juni', 7=>'Juli', 8=>'August', 9=>'September', 10=>'Oktober', 11=>'November', 12=>'Dezember'];

    public function __construct(private HourEntryRepository $entries, private ?IL10N $l10n = null) {
    }

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }

    public function descriptor(): ProviderDescriptor {
        return new ProviderDescriptor('brstunden', 'BR-Stunden', '1.0', ['nextcloud-user'], ['personal-data'], 500);
    }

    public function collect(PersonalDataRequest $request): PersonalDataPage {
        if ($request->subject()->subjectType() !== 'nextcloud-user') return new PersonalDataPage('not_applicable');
        if ($request->cursor() !== null) throw new InvalidArgumentException('BRStunden unterstützt kein Cursor-Paging.');

        $uid = $request->subject()->subjectId();
        $rows = $this->entries->findPrivacyEntriesForSubject($uid, $request->pageLimit() + 1);
        $limited = count($rows) > $request->pageLimit();
        if ($limited) $rows = array_slice($rows, 0, $request->pageLimit());
        $items = array_map(fn(array $row): PersonalDataEntry => $this->item($row, $uid), $rows);
        if ($items === []) return new PersonalDataPage('not_applicable');

        return new PersonalDataPage(
            $limited ? 'partial' : 'complete',
            $items,
            $limited ? [$this->t('Ausgabelimit erreicht; weitere BR-Stundenbezüge können vorhanden sein.')] : [],
        );
    }

    /** @param array<string, mixed> $row */
    private function item(array $row, string $uid): PersonalDataEntry {
        $ownEntry = (string)$row['user_id'] === $uid;
        $period = (self::MONTHS[(int)$row['entry_month']] ?? ($this->t('Monat ') . (int)$row['entry_month'])) . ' ' . (int)$row['entry_year'];
        if (!$ownEntry) {
            return new PersonalDataEntry(
                categoryId: 'hour_entry_activity',
                categoryLabel: 'Bearbeitungsnachweis',
                reference: 'hour-entry-activity:' . (int)$row['id'],
                summary: $this->t('Bearbeitung eines BR-Stundensatzes für ') . $period,
                purpose: $this->t('Nachvollziehbarkeit der Erfassung von BR-Stunden'),
                source: $this->t('Automatisch beim Speichern durch die betroffene Person erzeugter Bearbeitungsnachweis'),
                recipientCategories: ['Berechtigte Mitglieder und Funktionsträger*innen des Betriebsrats'],
                retention: $this->t('Keine feste Löschfrist festgelegt; gespeichert zusammen mit dem zugehörigen Stundensatz.'),
                thirdCountryTransfer: $this->t('BR-Stunden sieht keine Drittlandübermittlung vor.'),
                automatedDecision: $this->t('Es findet keine automatisierte Entscheidung mit rechtlicher oder vergleichbar erheblicher Wirkung statt.'),
                thirdPartyContentNotice: $this->t('Der Nachweis gehört zu einem Stundensatz einer anderen Person. Deren Identität, Stundenwerte und Notiz werden nicht ausgegeben.'),
                attributes: ['Zeitraum' => $period, $this->t('Bearbeitet am') => self::dateValue($row['updated_at'] ?? '')],
            );
        }

        return new PersonalDataEntry(
            categoryId: 'hour_entry',
            categoryLabel: $this->t('Monatlicher BR-Stundensatz'),
            reference: 'hour-entry:' . (int)$row['id'],
            summary: $period . ': ' . $this->hours((int)$row['minutes']) . $this->t(' BR-Arbeit und ') . $this->hours((int)$row['fobi_minutes']) . $this->t(' Fortbildung'),
            purpose: $this->t('Erfassung, Jahresübersicht und Abrechnung geleisteter Betriebsrats- und Fortbildungszeiten'),
            source: $this->t('Eingabe der betroffenen Person; Zeitstempel und Bearbeitungsbezug entstehen beim Speichern'),
            recipientCategories: ['Berechtigte Mitglieder und Funktionsträger*innen des Betriebsrats', 'Für die Abrechnung vorgesehene Empfänger*innen des von der betroffenen Person erzeugten PDF-Dokuments'],
            retention: $this->t('Keine feste Löschfrist festgelegt; gespeichert bis zur fachlich oder gesetzlich veranlassten Löschung.'),
            thirdCountryTransfer: $this->t('BR-Stunden sieht keine Drittlandübermittlung vor.'),
            automatedDecision: $this->t('Fehlende Monate und Summen werden automatisch ermittelt; es findet keine Entscheidung mit rechtlicher oder vergleichbar erheblicher Wirkung statt.'),
            thirdPartyContentNotice: $this->t('Die freiwillige Notiz kann Angaben über andere Personen enthalten. Solche Angaben werden nicht als deren eigener Datensatz zugeordnet.'),
            attributes: [
                'Zeitraum' => $period,
                'BR-Arbeit' => $this->hours((int)$row['minutes']),
                'Fortbildung' => $this->hours((int)$row['fobi_minutes']),
                $this->t('Notiz') => (string)($row['note'] ?? ''),
                $this->t('Erstellt am') => self::dateValue($row['created_at'] ?? ''),
                $this->t('Geändert am') => self::dateValue($row['updated_at'] ?? ''),
            ],
        );
    }

    private function hours(int $minutes): string {
        $hours = rtrim(rtrim(number_format($minutes / 60, 2, ',', ''), '0'), ',');
        return $hours . $this->t(' Stunden');
    }

    private static function dateValue(mixed $value): string {
        return $value instanceof \DateTimeInterface ? $value->format('c') : (string)$value;
    }
}
