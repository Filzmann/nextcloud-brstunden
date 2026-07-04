<?php

declare(strict_types=1);

namespace OCA\BrStunden\Repository;

use DateTimeImmutable;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class ReminderRunRepository {
    private const TABLE = 'brs_reminder_runs';

    public function __construct(
        private IDBConnection $db
    ) {
    }

    public function findForMonth(int $year, int $month): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from(self::TABLE)
            ->where($qb->expr()->eq('run_year', $qb->createNamedParameter($year, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('run_month', $qb->createNamedParameter($month, IQueryBuilder::PARAM_INT)));

        $row = $qb->executeQuery()->fetch();

        return $row === false ? null : $row;
    }

    public function createStarted(int $year, int $month, DateTimeImmutable $now): void {
        $qb = $this->db->getQueryBuilder();
        $qb->insert(self::TABLE)
            ->values([
                'run_year' => $qb->createNamedParameter($year, IQueryBuilder::PARAM_INT),
                'run_month' => $qb->createNamedParameter($month, IQueryBuilder::PARAM_INT),
                'status' => $qb->createNamedParameter('started'),
                'sent_count' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
                'skipped_count' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
                'failed_count' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
                'created_at' => $qb->createNamedParameter($now, IQueryBuilder::PARAM_DATE),
                'updated_at' => $qb->createNamedParameter($now, IQueryBuilder::PARAM_DATE),
            ]);
        $qb->executeStatement();
    }

    public function markFinished(int $year, int $month, int $sent, int $skipped, int $failed, DateTimeImmutable $now): void {
        $qb = $this->db->getQueryBuilder();
        $qb->update(self::TABLE)
            ->set('status', $qb->createNamedParameter('finished'))
            ->set('sent_count', $qb->createNamedParameter($sent, IQueryBuilder::PARAM_INT))
            ->set('skipped_count', $qb->createNamedParameter($skipped, IQueryBuilder::PARAM_INT))
            ->set('failed_count', $qb->createNamedParameter($failed, IQueryBuilder::PARAM_INT))
            ->set('updated_at', $qb->createNamedParameter($now, IQueryBuilder::PARAM_DATE))
            ->where($qb->expr()->eq('run_year', $qb->createNamedParameter($year, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('run_month', $qb->createNamedParameter($month, IQueryBuilder::PARAM_INT)));
        $qb->executeStatement();
    }
}
