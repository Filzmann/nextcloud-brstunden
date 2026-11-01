<?php

declare(strict_types=1);

namespace OCA\BrStunden\Service;

use OCA\BrStunden\Model\HourAmount;
use OCA\BrStunden\Model\HourEntry;
use OCA\BrStunden\Store\HourEntryStore;

class HoursService {
    public function __construct(
        private BrMemberService $members,
        private HourEntryStore $entries,
        private CalendarService $calendar,
        private AuthorizationService $authorization,
    ) {
    }

    public function state(): array {
        $this->authorization->assertCan(CapabilitySettingsService::OVERVIEW_READ);
        $currentYear = $this->calendar->currentYear();
        $currentMonth = $this->calendar->currentMonth();

        return [
            'currentUser' => ['uid' => $this->members->currentUserId()],
            'members' => $this->members->members(),
            'months' => $this->calendar->monthOptions(),
            'defaultYear' => $currentYear,
            'defaultMonth' => $currentMonth,
            'currentYear' => $currentYear,
            'currentMonth' => $currentMonth,
            'groupName' => $this->members->memberGroupName(),
            'permissions' => $this->authorization->currentPermissions(),
        ];
    }

    public function yearOverview(int $year): array {
        $this->authorization->assertCan(CapabilitySettingsService::OVERVIEW_READ);
        $this->calendar->assertValidYear($year);

        return $this->buildYearOverview($year);
    }

    public function saveEntry(int $year, int $month, string|int|float $hours, string|int|float|null $fobiHours, string $note, ?string $userId = null): array {
        $this->calendar->assertValidYear($year);
        $this->calendar->assertValidMonth($month);
        $this->assertNotFutureMonth($year, $month);

        $actorUid = $this->members->currentUserId();
        $uid = trim((string)($userId ?? $actorUid));
        $this->authorization->assertCanManageEntryFor($uid);
        $minutes = HourAmount::fromInput($hours)->minutes();
        $fobiMinutes = HourAmount::fromOptionalInput($fobiHours)->minutes();
        $this->assertPlausibleMonthlyTotal($minutes, $fobiMinutes);
        $this->entries->save($uid, $year, $month, $minutes, $fobiMinutes, trim($note), $actorUid);

        return $this->buildYearOverview($year);
    }

    public function deleteEntry(int $year, int $month, ?string $userId = null): array {
        $this->calendar->assertValidYear($year);
        $this->calendar->assertValidMonth($month);
        $this->assertNotFutureMonth($year, $month);

        $uid = trim((string)($userId ?? $this->members->currentUserId()));
        $this->authorization->assertCanManageEntryFor($uid);
        $this->entries->delete($uid, $year, $month);

        return $this->buildYearOverview($year);
    }

    public function missingMonthsForUser(string $userId, int $year, int $untilMonth): array {
        $this->calendar->assertValidYear($year);
        $untilMonth = $this->calendar->boundedMonth($untilMonth);
        $entries = $this->entries->forUsersInYear([$userId], $year);
        $filled = [];
        foreach ($entries as $entry) {
            $filled[$entry->month] = true;
        }

        $missing = [];
        for ($month = 1; $month <= $untilMonth; $month++) {
            if (!isset($filled[$month])) {
                $missing[] = ['month' => $month, 'label' => $this->calendar->monthLabel($month)];
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
            $fobiTotalMinutes = 0;

            for ($month = 1; $month <= 12; $month++) {
                $entry = $entryMap[$uid][$month] ?? null;
                if ($entry instanceof HourEntry) {
                    $totalMinutes += $entry->minutes;
                    $fobiTotalMinutes += $entry->fobiMinutes;
                    $months[$month] = $entry->toArray();
                } else {
                    $months[$month] = null;
                }
            }

            $rows[] = [
                'member' => $member,
                'months' => $months,
                'brTotalMinutes' => $totalMinutes,
                'brTotalHours' => round($totalMinutes / 60, 2),
                'fobiTotalMinutes' => $fobiTotalMinutes,
                'fobiTotalHours' => round($fobiTotalMinutes / 60, 2),
                'totalMinutes' => $totalMinutes + $fobiTotalMinutes,
                'totalHours' => round(($totalMinutes + $fobiTotalMinutes) / 60, 2),
            ];
        }

        return [
            'year' => $year,
            'months' => $this->calendar->monthOptions(),
            'currentUser' => ['uid' => $this->members->currentUserId()],
            'currentYear' => $this->calendar->currentYear(),
            'currentMonth' => $this->calendar->currentMonth(),
            'editableUntilMonth' => $this->calendar->editableUntilMonth($year),
            'permissions' => $this->authorization->currentPermissions(),
            'rows' => $rows,
        ];
    }

    private function assertNotFutureMonth(int $year, int $month): void {
        if ($this->calendar->isFutureMonth($year, $month)) {
            throw new \InvalidArgumentException('Zukuenftige Monate koennen noch nicht eingetragen werden.');
        }
    }

    private function assertPlausibleMonthlyTotal(int $minutes, int $fobiMinutes): void {
        if ($minutes + $fobiMinutes > 744 * 60) {
            throw new \InvalidArgumentException('Bitte insgesamt eine plausible Stundenzahl eintragen.');
        }
    }
}
