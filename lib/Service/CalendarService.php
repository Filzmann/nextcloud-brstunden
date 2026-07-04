<?php

declare(strict_types=1);

namespace OCA\BrStunden\Service;

use DateTimeImmutable;

class CalendarService {
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

    public function today(): DateTimeImmutable {
        return new DateTimeImmutable('today');
    }

    public function currentYear(): int {
        return (int)$this->today()->format('Y');
    }

    public function currentMonth(): int {
        return (int)$this->today()->format('n');
    }

    public function monthOptions(): array {
        $months = [];
        foreach (self::MONTHS as $number => $label) {
            $months[] = ['number' => $number, 'label' => $label];
        }

        return $months;
    }

    public function monthLabel(int $month): string {
        $this->assertValidMonth($month);

        return self::MONTHS[$month];
    }

    public function boundedMonth(int $month): int {
        return max(1, min(12, $month));
    }

    public function editableUntilMonth(int $year): int {
        $currentYear = $this->currentYear();
        if ($year < $currentYear) {
            return 12;
        }

        if ($year > $currentYear) {
            return 0;
        }

        return $this->currentMonth();
    }

    public function isFutureMonth(int $year, int $month, ?DateTimeImmutable $today = null): bool {
        $this->assertValidYear($year);
        $this->assertValidMonth($month);

        $today ??= $this->today();
        $current = new DateTimeImmutable($today->format('Y-m-01'));
        $selected = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));

        return $selected > $current;
    }

    public function isLastDayOfMonth(?DateTimeImmutable $date = null): bool {
        $date ??= $this->today();

        return $date->format('j') === $date->format('t');
    }

    public function assertValidYear(int $year): void {
        if ($year < 2000 || $year > 2100) {
            throw new \InvalidArgumentException('Bitte ein gueltiges Jahr waehlen.');
        }
    }

    public function assertValidMonth(int $month): void {
        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException('Bitte einen gueltigen Monat waehlen.');
        }
    }
}
