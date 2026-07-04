<?php

declare(strict_types=1);

namespace OCA\BrStunden\Store;

use OCA\BrStunden\Model\HourEntry;
use OCA\BrStunden\Repository\HourEntryRepository;

class HourEntryStore {
    public function __construct(
        private HourEntryRepository $repository
    ) {
    }

    /**
     * @return HourEntry[]
     */
    public function forUsersInYear(array $userIds, int $year): array {
        return HourEntry::get_all($this->repository->findForUsersInYear($userIds, $year));
    }

    public function findForUserMonth(string $userId, int $year, int $month): ?HourEntry {
        $row = $this->repository->findForUserMonth($userId, $year, $month);

        return HourEntry::get($row);
    }

    public function save(string $userId, int $year, int $month, int $minutes, int $fobiMinutes, string $note, string $updatedByUid): void {
        $this->repository->upsert($userId, $year, $month, $minutes, $fobiMinutes, $note, $updatedByUid);
    }

    public function delete(string $userId, int $year, int $month): void {
        $this->repository->deleteForUserMonth($userId, $year, $month);
    }
}
