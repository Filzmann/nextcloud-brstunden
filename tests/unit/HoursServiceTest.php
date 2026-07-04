<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../../lib/Service/CalendarService.php';
require_once __DIR__ . '/../../lib/Service/BrMemberService.php';
require_once __DIR__ . '/../../lib/Model/HourAmount.php';
require_once __DIR__ . '/../../../localbase/lib/Model/ModelApiTrait.php';
require_once __DIR__ . '/../../lib/Model/HourEntry.php';
require_once __DIR__ . '/../../lib/Store/HourEntryStore.php';
require_once __DIR__ . '/../../lib/Service/HoursService.php';

use OCA\BrStunden\Model\HourEntry;
use OCA\BrStunden\Service\BrMemberService;
use OCA\BrStunden\Service\CalendarService;
use OCA\BrStunden\Service\HoursService;
use OCA\BrStunden\Store\HourEntryStore;
use function OCA\BrStunden\Tests\assertSameValue;
use function OCA\BrStunden\Tests\assertThrows;

$calendar = new class extends CalendarService {
    public function today(): DateTimeImmutable {
        return new DateTimeImmutable('2026-07-03');
    }
};

$members = new class extends BrMemberService {
    public function __construct() {
    }

    public function currentUserId(): string {
        return 'simon';
    }

    public function assertCurrentUserIsMember(): void {
    }

    public function members(): array {
        return [
            ['uid' => 'simon', 'displayName' => 'Simon Test', 'email' => 'simon@example.invalid'],
            ['uid' => 'alex', 'displayName' => 'Alex Test', 'email' => 'alex@example.invalid'],
        ];
    }
};

$entries = new class extends HourEntryStore {
    /** @var HourEntry[] */
    public array $entries = [];
    public array $saved = [];
    public array $deleted = [];

    public function __construct() {
        $this->entries = [
            new HourEntry(1, 'simon', 2026, 1, 0, 0, 'Null ist gueltig', 'simon', '2026-01-31', '2026-01-31'),
            new HourEntry(2, 'simon', 2026, 3, 120, 30, 'Sitzung', 'simon', '2026-03-31', '2026-03-31'),
            new HourEntry(3, 'alex', 2026, 2, 60, 0, '', 'alex', '2026-02-28', '2026-02-28'),
        ];
    }

    public function forUsersInYear(array $userIds, int $year): array {
        $allowed = array_flip($userIds);

        return array_values(array_filter(
            $this->entries,
            static fn(HourEntry $entry): bool => $entry->year === $year && isset($allowed[$entry->userId])
        ));
    }

    public function save(string $userId, int $year, int $month, int $minutes, int $fobiMinutes, string $note, string $updatedByUid): void {
        $this->saved[] = compact('userId', 'year', 'month', 'minutes', 'fobiMinutes', 'note', 'updatedByUid');
        $this->entries[] = new HourEntry(100 + count($this->saved), $userId, $year, $month, $minutes, $fobiMinutes, $note, $updatedByUid, '2026-07-03', '2026-07-03');
    }

    public function delete(string $userId, int $year, int $month): void {
        $this->deleted[] = compact('userId', 'year', 'month');
    }
};

$service = new HoursService($members, $entries, $calendar);

$missing = $service->missingMonthsForUser('simon', 2026, 3);
assertSameValue([2], array_column($missing, 'month'), 'Zero-hour entries should count as filled months.');
assertSameValue(['Februar'], array_column($missing, 'label'), 'Missing months should expose German labels.');

$overview = $service->yearOverview(2026);
$simonRow = $overview['rows'][0];
assertSameValue('simon', $simonRow['member']['uid'], 'Year overview should keep member rows.');
assertSameValue(0, $simonRow['months'][1]['totalMinutes'], 'Year overview should keep explicit zero-hour entries.');
assertSameValue(null, $simonRow['months'][2], 'Year overview should mark missing months as null.');
assertSameValue(120, $simonRow['brTotalMinutes'], 'Year overview should sum BR minutes.');
assertSameValue(30, $simonRow['fobiTotalMinutes'], 'Year overview should sum FoBi minutes.');
assertSameValue(150, $simonRow['totalMinutes'], 'Year overview should sum all minutes.');

$service->saveEntry(2026, 7, '1,5', '0,5', '  Mit Vorbereitung  ');
assertSameValue([
    'userId' => 'simon',
    'year' => 2026,
    'month' => 7,
    'minutes' => 90,
    'fobiMinutes' => 30,
    'note' => 'Mit Vorbereitung',
    'updatedByUid' => 'simon',
], $entries->saved[0], 'Saved entries should parse hours, trim notes and use the current user.');

assertThrows(
    static fn(): array => $service->saveEntry(2026, 8, '1', null, ''),
    'Future months should be rejected.'
);
assertThrows(
    static fn(): array => $service->saveEntry(2026, 7, '744', '0,25', ''),
    'Combined BR and FoBi hours should stay plausible.'
);

$service->deleteEntry(2026, 7);
assertSameValue(['userId' => 'simon', 'year' => 2026, 'month' => 7], $entries->deleted[0], 'Deletes should use the current user.');

echo 'HoursService tests passed' . PHP_EOL;
