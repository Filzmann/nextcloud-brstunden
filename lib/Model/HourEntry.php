<?php

declare(strict_types=1);

namespace OCA\BrStunden\Model;

class HourEntry {
    public function __construct(
        public readonly int $id,
        public readonly string $userId,
        public readonly int $year,
        public readonly int $month,
        public readonly int $minutes,
        public readonly int $fobiMinutes,
        public readonly string $note,
        public readonly ?string $updatedByUid,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {
    }

    public static function fromRow(array $row): self {
        return new self(
            (int)$row['id'],
            (string)$row['user_id'],
            (int)$row['entry_year'],
            (int)$row['entry_month'],
            (int)$row['minutes'],
            (int)($row['fobi_minutes'] ?? 0),
            (string)($row['note'] ?? ''),
            isset($row['updated_by_uid']) ? (string)$row['updated_by_uid'] : null,
            self::dateToString($row['created_at'] ?? ''),
            self::dateToString($row['updated_at'] ?? '')
        );
    }

    public function toApiArray(): array {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'year' => $this->year,
            'month' => $this->month,
            'minutes' => $this->minutes,
            'hours' => round($this->minutes / 60, 2),
            'brMinutes' => $this->minutes,
            'brHours' => round($this->minutes / 60, 2),
            'fobiMinutes' => $this->fobiMinutes,
            'fobiHours' => round($this->fobiMinutes / 60, 2),
            'totalMinutes' => $this->minutes + $this->fobiMinutes,
            'totalHours' => round(($this->minutes + $this->fobiMinutes) / 60, 2),
            'note' => $this->note,
            'updatedByUid' => $this->updatedByUid,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }

    private static function dateToString(mixed $value): string {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('c');
        }

        return (string)$value;
    }
}
