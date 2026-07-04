<?php

declare(strict_types=1);

namespace OCA\BrStunden\Model;

class HourEntry {
    use ModelApiTrait;

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

    public static function fromArray(array $data): self {
        return new self(
            (int)($data['id'] ?? 0),
            (string)($data['userId'] ?? $data['user_id'] ?? ''),
            (int)($data['year'] ?? $data['entry_year'] ?? 0),
            (int)($data['month'] ?? $data['entry_month'] ?? 0),
            (int)($data['brMinutes'] ?? $data['minutes'] ?? 0),
            (int)($data['fobiMinutes'] ?? $data['fobi_minutes'] ?? 0),
            (string)($data['note'] ?? ''),
            isset($data['updatedByUid']) || isset($data['updated_by_uid'])
                ? (string)($data['updatedByUid'] ?? $data['updated_by_uid'])
                : null,
            self::dateToString($data['createdAt'] ?? $data['created_at'] ?? ''),
            self::dateToString($data['updatedAt'] ?? $data['updated_at'] ?? '')
        );
    }

    public static function fromRow(array $row): self {
        return self::fromArray($row);
    }

    public function toArray(): array {
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
