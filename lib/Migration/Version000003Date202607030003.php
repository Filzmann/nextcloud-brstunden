<?php

declare(strict_types=1);

namespace OCA\BrStunden\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000003Date202607030003 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('brs_hour_entries')) {
            $table = $schema->getTable('brs_hour_entries');
            if (!$table->hasColumn('fobi_minutes')) {
                $table->addColumn('fobi_minutes', Types::INTEGER, ['notnull' => true, 'default' => 0]);
            }
        }

        return $schema;
    }
}
