<?php

declare(strict_types=1);

namespace OCA\BrStunden\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000002Date202607030002 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('brs_reminder_runs')) {
            $table = $schema->createTable('brs_reminder_runs');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('run_year', Types::INTEGER, ['notnull' => true]);
            $table->addColumn('run_month', Types::INTEGER, ['notnull' => true]);
            $table->addColumn('status', Types::STRING, ['notnull' => true, 'length' => 32, 'default' => 'started']);
            $table->addColumn('sent_count', Types::INTEGER, ['notnull' => true, 'default' => 0]);
            $table->addColumn('skipped_count', Types::INTEGER, ['notnull' => true, 'default' => 0]);
            $table->addColumn('failed_count', Types::INTEGER, ['notnull' => true, 'default' => 0]);
            $table->addColumn('created_at', Types::DATETIME, ['notnull' => true]);
            $table->addColumn('updated_at', Types::DATETIME, ['notnull' => true]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['run_year', 'run_month'], 'brs_reminder_month_unique');
        }

        return $schema;
    }
}
