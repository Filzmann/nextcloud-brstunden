<?php

declare(strict_types=1);

namespace OCA\BrStunden\Store;

use DateTimeImmutable;
use OCA\BrStunden\Repository\ReminderRunRepository;

class ReminderRunStore {
    public function __construct(
        private ReminderRunRepository $repository
    ) {
    }

    public function begin(int $year, int $month): bool {
        if ($this->repository->findForMonth($year, $month) !== null) {
            return false;
        }

        try {
            $this->repository->createStarted($year, $month, new DateTimeImmutable());
        } catch (\Throwable $e) {
            if ($this->repository->findForMonth($year, $month) !== null) {
                return false;
            }

            throw $e;
        }

        return true;
    }

    public function finish(int $year, int $month, int $sent, int $skipped, int $failed): void {
        $this->repository->markFinished($year, $month, $sent, $skipped, $failed, new DateTimeImmutable());
    }
}
