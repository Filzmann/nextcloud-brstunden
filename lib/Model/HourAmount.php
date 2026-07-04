<?php

declare(strict_types=1);

namespace OCA\BrStunden\Model;

class HourAmount {
    private function __construct(
        private int $minutes
    ) {
        if ($minutes < 0 || $minutes > 744 * 60) {
            throw new \InvalidArgumentException('Bitte eine plausible Stundenzahl eintragen.');
        }
    }

    public static function fromInput(string|int|float $hours): self {
        $normalized = str_replace(',', '.', trim((string)$hours));
        if ($normalized === '' || !preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new \InvalidArgumentException('Bitte Stunden als Zahl eintragen.');
        }

        return new self((int)round(((float)$normalized) * 60));
    }

    public static function fromOptionalInput(string|int|float|null $hours): self {
        if (trim((string)($hours ?? '')) === '') {
            return new self(0);
        }

        return self::fromInput($hours);
    }

    public function minutes(): int {
        return $this->minutes;
    }

    public function hours(): float {
        return round($this->minutes / 60, 2);
    }
}
