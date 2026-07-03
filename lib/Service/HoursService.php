<?php

declare(strict_types=1);

namespace OCA\BrStunden\Service;

use DateTimeImmutable;
use OCA\BrStunden\Model\HourEntry;
use OCA\BrStunden\Store\HourEntryStore;

class HoursService {
    private const MONTHS = [
        1 => 'Januar',
        2 => 'Februar',
        3 => 'Maerz',
        4 => 'April',
        5 => 'Mai',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'August',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Dezember',
    ];

    public function __construct(
        private BrMemberService $members,
        private HourEntryStore $entries
    ) {
    }

    public function state(): array {
        $this->members->assertCurrentUserIsMember();

        return [
            'currentUser' => ['uid' => $this->members->currentUserId()],
            'members' => $this->members->members(),
            'months' => $this->monthOptions(),
            'defaultYear' => (int)date('Y'),
            'defaultMonth' => (int)date('n'),
            'groupName' => BrMemberService::GROUP_NAME,
        ];
    }

    public function yearOverview(int $year): array {
        $this->members->assertCurrentUserIsMember();
        $this->assertValidYear($year);

        return $this->buildYearOverview($year);
    }

    public function saveEntry(int $year, int $month, string|int|float $hours, string $note): array {
        $this->members->assertCurrentUserIsMember();
        $this->assertValidYear($year);
        $this->assertValidMonth($month);
        $this->assertNotFutureMonth($year, $month);

        $uid = $this->members->currentUserId();
        $minutes = $this->parseHoursToMinutes($hours);
        $this->entries->save($uid, $year, $month, $minutes, trim($note), $uid);

        return $this->buildYearOverview($year);
    }

    public function missingMonthsForUser(string $userId, int $year, int $untilMonth): array {
        $this->assertValidYear($year);
        $untilMonth = max(1, min(12, $untilMonth));
        $entries = $this->entries->forUsersInYear([$userId], $year);
        $filled = [];
        foreach ($entries as $entry) {
            $filled[$entry->month] = true;
        }

        $missing = [];
        for ($month = 1; $month <= $untilMonth; $month++) {
            if (!isset($filled[$month])) {
                $missing[] = ['month' => $month, 'label' => self::MONTHS[$month]];
            }
        }

        return $missing;
    }

    private function buildYearOverview(int $year): array {
        $members = $this->members->members();
        $uids = array_map(static fn(array $member): string => $member['uid'], $members);
        $entries = $this->entries->forUsersInYear($uids, $year);
        $entryMap = [];

        foreach ($entries as $entry) {
            $entryMap[$entry->userId][$entry->month] = $entry;
        }

        $rows = [];
        foreach ($members as $member) {
            $uid = $member['uid'];
            $months = [];
            $totalMinutes = 0;

            for ($month = 1; $month <= 12; $month++) {
                $entry = $entryMap[$uid][$month] ?? null;
                if ($entry instanceof HourEntry) {
                    $totalMinutes += $entry->minutes;
                    $months[$month] = $entry->toApiArray();
                } else {
                    $months[$month] = null;
                }
            }

            $rows[] = [
                'member' => $member,
                'months' => $months,
                'totalMinutes' => $totalMinutes,
                'totalHours' => round($totalMinutes / 60, 2),
            ];
        }

        return [
            'year' => $year,
            'months' => $this->monthOptions(),
            'currentUser' => ['uid' => $this->members->currentUserId()],
            'rows' => $rows,
        ];
    }

    private function monthOptions(): array {
        $months = [];
        foreach (self::MONTHS as $number => $label) {
            $months[] = ['number' => $number, 'label' => $label];
        }

        return $months;
    }

    private function parseHoursToMinutes(string|int|float $hours): int {
        $normalized = str_replace(',', '.', trim((string)$hours));
        if ($normalized === '' || !preg_match('/^\d+(\.\d{1,2})?$/', $normalized)) {
            throw new \InvalidArgumentException('Bitte Stunden als Zahl eintragen.');
        }

        $value = (float)$normalized;
        if ($value < 0 || $value > 744) {
            throw new \InvalidArgumentException('Bitte eine plausible Stundenzahl eintragen.');
        }

        return (int)round($value * 60);
    }

    private function assertValidYear(int $year): void {
        if ($year < 2000 || $year > 2100) {
            throw new \InvalidArgumentException('Bitte ein gueltiges Jahr waehlen.');
        }
    }

    private function assertValidMonth(int $month): void {
        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException('Bitte einen gueltigen Monat waehlen.');
        }
    }

    private function assertNotFutureMonth(int $year, int $month): void {
        $now = new DateTimeImmutable('first day of this month');
        $selected = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        if ($selected > $now) {
            throw new \InvalidArgumentException('Zukuenftige Monate koennen noch nicht eingetragen werden.');
        }
    }
}
