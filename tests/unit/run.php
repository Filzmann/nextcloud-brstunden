<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../../lib/Service/CalendarService.php';
require_once __DIR__ . '/../../lib/Model/HourAmount.php';
require_once __DIR__ . '/../../../localbase/lib/Model/ModelApiTrait.php';
require_once __DIR__ . '/../../lib/Model/HourEntry.php';
require_once __DIR__ . '/../../lib/Service/BrMemberService.php';
require_once __DIR__ . '/../../lib/Store/HourEntryStore.php';
require_once __DIR__ . '/../../lib/Service/PayrollPdfService.php';

use OCA\BrStunden\Model\HourAmount;
use OCA\BrStunden\Model\HourEntry;
use OCA\BrStunden\Service\BrMemberService;
use OCA\BrStunden\Service\CalendarService;
use OCA\BrStunden\Service\PayrollPdfService;
use OCA\BrStunden\Store\HourEntryStore;
use function OCA\BrStunden\Tests\assertSameValue;
use function OCA\BrStunden\Tests\assertThrows;

$calendar = new class extends CalendarService {
    public function today(): DateTimeImmutable {
        return new DateTimeImmutable('2026-07-03');
    }
};

assertSameValue(12, count($calendar->monthOptions()), 'Calendar should expose all months.');
assertSameValue('Maerz', $calendar->monthLabel(3), 'Calendar should expose German month labels.');
assertSameValue(7, $calendar->editableUntilMonth(2026), 'Current year should be editable until current month.');
assertSameValue(12, $calendar->editableUntilMonth(2025), 'Past years should be editable until December.');
assertSameValue(0, $calendar->editableUntilMonth(2027), 'Future years should not be editable.');
assertSameValue(false, $calendar->isFutureMonth(2026, 7), 'Current month should be editable.');
assertSameValue(true, $calendar->isFutureMonth(2026, 8), 'Future month should not be editable.');
assertSameValue(true, $calendar->isLastDayOfMonth(new DateTimeImmutable('2026-02-28')), 'February 28 2026 should be month end.');
assertSameValue(false, $calendar->isLastDayOfMonth(new DateTimeImmutable('2026-02-27')), 'February 27 2026 should not be month end.');

assertSameValue(0, HourAmount::fromInput('0')->minutes(), 'Zero hours should be valid.');
assertSameValue(75, HourAmount::fromInput('1,25')->minutes(), 'Comma decimals should be accepted.');
assertSameValue(90, HourAmount::fromInput('1.5')->minutes(), 'Dot decimals should be accepted.');
assertSameValue(44640, HourAmount::fromInput('744')->minutes(), 'Maximum plausible monthly hours should be accepted.');
assertSameValue(0, HourAmount::fromOptionalInput('')->minutes(), 'Empty optional hours should be zero.');
assertSameValue(0, HourAmount::fromOptionalInput(null)->minutes(), 'Null optional hours should be zero.');
assertSameValue(30, HourAmount::fromOptionalInput('0,5')->minutes(), 'Optional hours should parse non-empty values.');
assertThrows(static fn(): HourAmount => HourAmount::fromInput(''), 'Empty hours should be rejected.');
assertThrows(static fn(): HourAmount => HourAmount::fromInput('1.256'), 'More than two decimal places should be rejected.');
assertThrows(static fn(): HourAmount => HourAmount::fromInput('745'), 'Implausibly high hours should be rejected.');
assertThrows(static fn(): HourAmount => HourAmount::fromOptionalInput('x'), 'Invalid optional hours should be rejected.');

$entry = HourEntry::get([
    'id' => 9,
    'userId' => 'simon',
    'year' => 2026,
    'month' => 7,
    'brMinutes' => 90,
    'fobiMinutes' => 30,
    'note' => 'Test',
    'updatedByUid' => 'simon',
    'createdAt' => '2026-07-03',
    'updatedAt' => '2026-07-03',
]);
$entries = HourEntry::get_all([$entry->toArray()]);
assertSameValue(true, $entry instanceof HourEntry, 'HourEntry::get should hydrate API data.');
assertSameValue(1, count($entries), 'HourEntry::get_all should hydrate API lists.');
assertSameValue(120, $entry->toArray()['totalMinutes'], 'HourEntry::toArray should keep the API payload shape.');

$members = new class extends BrMemberService {
    public function __construct() {
    }

    public function currentUserId(): string {
        return 'simon';
    }

    public function assertCurrentUserIsMember(): void {
    }

    public function currentUserAddress(): string {
        return 'Teststr. 1, 12345 Berlin';
    }

    public function members(): array {
        return [
            ['uid' => 'simon', 'displayName' => 'Simon Test', 'email' => 'simon@example.invalid'],
        ];
    }
};

$entries = new class extends HourEntryStore {
    public function __construct() {
    }

    public function findForUserMonth(string $userId, int $year, int $month): ?HourEntry {
        return new HourEntry(1, $userId, $year, $month, 90, 30, 'Test', $userId, '2026-07-03', '2026-07-03');
    }
};

$payrollPdf = new PayrollPdfService($members, $entries, $calendar);
$pdf = $payrollPdf->createForCurrentUser(2026, 7);
assertSameValue('br-stunden-abrechnung-simon-2026-07.pdf', $pdf['filename'], 'Payroll PDF filename should include user and month.');
assertSameValue('application/pdf', $pdf['contentType'], 'Payroll PDF should use PDF content type.');
assertSameValue('%PDF-', substr($pdf['content'], 0, 5), 'Payroll PDF content should start with PDF header.');
assertSameValue(true, str_contains($pdf['content'], 'Teststr. 1'), 'Payroll PDF content should contain the profile address.');

echo "OK\n";
