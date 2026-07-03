<?php

declare(strict_types=1);

namespace OCA\BrStunden\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000001Date202607030001 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('brs_hour_entries')) {
            $table = $schema->createTable('brs_hour_entries');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('user_id', Types::STRING, ['notnull' => true, 'length' => 64]);
            $table->addColumn('entry_year', Types::INTEGER, ['notnull' => true]);
            $table->addColumn('entry_month', Types::INTEGER, ['notnull' => true]);
            $table->addColumn('minutes', Types::INTEGER, ['notnull' => true, 'default' => 0]);
            $table->addColumn('note', Types::TEXT, ['notnull' => false]);
            $table->addColumn('created_at', Types::DATETIME, ['notnull' => true]);
            $table->addColumn('updated_at', Types::DATETIME, ['notnull' => true]);
            $table->addColumn('updated_by_uid', Types::STRING, ['notnull' => false, 'length' => 64]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['user_id', 'entry_year', 'entry_month'], 'brs_user_month_unique');
            $table->addIndex(['entry_year', 'entry_month'], 'brs_year_month');
        }

        return $schema;
    }
}
