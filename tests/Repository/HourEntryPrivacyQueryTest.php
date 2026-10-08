<?php

declare(strict_types=1);

namespace OCP {
    interface IDBConnection { public function getQueryBuilder(); }
}

namespace OCP\DB\QueryBuilder {
    interface IQueryBuilder { public const PARAM_INT = 1; public const PARAM_STR_ARRAY = 102; }
}

namespace {
    use OCA\BrStunden\Repository\HourEntryRepository;
    use OCP\IDBConnection;

    final class PrivacyQueryResult {
        public function fetchAll(): array { return [['id'=>7, 'user_id'=>'self', 'updated_by_uid'=>'self']]; }
    }
    final class PrivacyExpression {
        public array $orConditions = [];
        public function eq(string $column, mixed $value): array { return ['eq', $column, $value]; }
        public function orX(mixed ...$conditions): array { $this->orConditions = $conditions; return ['or', $conditions]; }
    }
    final class PrivacyQueryBuilder {
        public array $bindings = [];
        public ?int $limit = null;
        public PrivacyExpression $expression;
        public function __construct() { $this->expression = new PrivacyExpression(); }
        public function expr(): PrivacyExpression { return $this->expression; }
        public function createNamedParameter(mixed $value, mixed $type = null): array { $this->bindings[] = $value; return ['value'=>$value]; }
        public function select(string ...$columns): self { return $this; }
        public function from(string $table): self { return $this; }
        public function where(mixed $condition): self { return $this; }
        public function orderBy(string $column, string $direction): self { return $this; }
        public function setMaxResults(int $limit): self { $this->limit = $limit; return $this; }
        public function executeQuery(): PrivacyQueryResult { return new PrivacyQueryResult(); }
    }
    final class PrivacyConnection implements IDBConnection {
        public PrivacyQueryBuilder $query;
        public function __construct() { $this->query = new PrivacyQueryBuilder(); }
        public function getQueryBuilder(): PrivacyQueryBuilder { return $this->query; }
    }

    $connection = new PrivacyConnection();
    $rows = (new HourEntryRepository($connection))->findPrivacyEntriesForSubject('self', 3);
    if ($rows[0]['id'] !== 7 || $connection->query->bindings !== ['self', 'self'] || $connection->query->limit !== 3) {
        throw new RuntimeException('Privacy-Abfrage bindet Subject oder Limit nicht korrekt.');
    }
    $conditions = $connection->query->expression->orConditions;
    if (array_column($conditions, 1) !== ['user_id', 'updated_by_uid']) {
        throw new RuntimeException('Privacy-Abfrage erfasst nicht ausschließlich Eigentums- und Bearbeitungsbezug.');
    }

    echo "BRStunden privacy query test passed\n";
}
