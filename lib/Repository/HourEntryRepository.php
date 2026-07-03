<?php

declare(strict_types=1);

namespace OCA\BrStunden\Repository;

use DateTimeImmutable;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class HourEntryRepository {
    private const TABLE = 'brs_hour_entries';

    public function __construct(
        private IDBConnection $db
    ) {
    }

    public function findForUsersInYear(array $userIds, int $year): array {
        $userIds = array_values(array_unique(array_filter(array_map('strval', $userIds))));
        if ($userIds === []) {
            return [];
        }

        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from(self::TABLE)
            ->where($qb->expr()->eq('entry_year', $qb->createNamedParameter($year, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->in('user_id', $qb->createNamedParameter($userIds, IQueryBuilder::PARAM_STR_ARRAY)))
            ->orderBy('user_id', 'ASC')
            ->addOrderBy('entry_month', 'ASC');

        return $qb->executeQuery()->fetchAll();
    }

    public function findForUserMonth(string $userId, int $year, int $month): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from(self::TABLE)
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('entry_year', $qb->createNamedParameter($year, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('entry_month', $qb->createNamedParameter($month, IQueryBuilder::PARAM_INT)));

        $row = $qb->executeQuery()->fetch();

        return $row === false ? null : $row;
    }

    public function upsert(string $userId, int $year, int $month, int $minutes, string $note, string $updatedByUid): void {
        $existing = $this->findForUserMonth($userId, $year, $month);
        $now = new DateTimeImmutable();

        if ($existing === null) {
            $qb = $this->db->getQueryBuilder();
            $qb->insert(self::TABLE)
                ->values([
                    'user_id' => $qb->createNamedParameter($userId),
                    'entry_year' => $qb->createNamedParameter($year, IQueryBuilder::PARAM_INT),
                    'entry_month' => $qb->createNamedParameter($month, IQueryBuilder::PARAM_INT),
                    'minutes' => $qb->createNamedParameter($minutes, IQueryBuilder::PARAM_INT),
                    'note' => $qb->createNamedParameter($note),
                    'created_at' => $qb->createNamedParameter($now, IQueryBuilder::PARAM_DATE),
                    'updated_at' => $qb->createNamedParameter($now, IQueryBuilder::PARAM_DATE),
                    'updated_by_uid' => $qb->createNamedParameter($updatedByUid),
                ]);
            $qb->executeStatement();

            return;
        }

        $qb = $this->db->getQueryBuilder();
        $qb->update(self::TABLE)
            ->set('minutes', $qb->createNamedParameter($minutes, IQueryBuilder::PARAM_INT))
            ->set('note', $qb->createNamedParameter($note))
            ->set('updated_at', $qb->createNamedParameter($now, IQueryBuilder::PARAM_DATE))
            ->set('updated_by_uid', $qb->createNamedParameter($updatedByUid))
            ->where($qb->expr()->eq('id', $qb->createNamedParameter((int)$existing['id'], IQueryBuilder::PARAM_INT)));
        $qb->executeStatement();
    }
}
