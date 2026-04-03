<?php

declare(strict_types=1);

final class SecureSupabaseDB
{
    private PDO $pdo;

    private function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public static function connect(string $dsn, string $username = '', string $password = '', array $options = []): self
    {
        $pdo = new PDO($dsn, $username, $password, $options);

        return new self($pdo);
    }

    public static function fromPdo(PDO $pdo): self
    {
        return new self($pdo);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $bindings = []): QueryResult
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings);

        $data = [];

        if ($statement->columnCount() > 0) {
            $data = $statement->fetchAll();
        }

        return new QueryResult($data, $statement->rowCount(), $sql, $bindings);
    }

    public function from(string $table): QueryBuilder
    {
        return new QueryBuilder($this->pdo, $table);
    }

    public function table(string $table): QueryBuilder
    {
        return $this->from($table);
    }

}

final class QueryBuilder
{
    private PDO $pdo;
    private string $table;
    private string $operation = 'select';
    private array|string $columns = '*';
    private array $insertData = [];
    private array $upsertData = [];
    private array $updateData = [];
    private array $upsertConflictColumns = [];
    private ?array $upsertUpdateColumns = null;
    private array|string|null $returningColumns = null;
    private array $wheres = [];
    private array $bindings = [];
    private array $orders = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private int $placeholderCounter = 0;

    public function __construct(PDO $pdo, string $table)
    {
        $this->pdo = $pdo;
        $this->table = $this->guardIdentifier($table);
    }

    public function select(array|string $columns = '*'): self
    {
        if (in_array($this->operation, ['insert', 'update', 'delete'], true)) {
            $this->returningColumns = $columns;
            return $this;
        }

        $this->operation = 'select';
        $this->columns = $columns;

        return $this;
    }

    public function insert(array $data): self
    {
        $this->operation = 'insert';
        $this->insertData = $this->normalizeRows($data);

        return $this;
    }

    public function upsert(array $data, array $conflictColumns, ?array $updateColumns = null): self
    {
        if ($conflictColumns === []) {
            throw new InvalidArgumentException('Upsert conflict columns cannot be empty.');
        }

        $this->operation = 'upsert';
        $this->upsertData = $this->normalizeRows($data);
        $this->upsertConflictColumns = $conflictColumns;
        $this->upsertUpdateColumns = $updateColumns;

        return $this;
    }

    public function update(array $data): self
    {
        if ($data === []) {
            throw new InvalidArgumentException('Update data cannot be empty.');
        }

        $this->operation = 'update';
        $this->updateData = $data;

        return $this;
    }

    public function delete(): self
    {
        $this->operation = 'delete';

        return $this;
    }

    public function eq(string $column, mixed $value): self
    {
        return $this->where($column, '=', $value);
    }

    public function neq(string $column, mixed $value): self
    {
        return $this->where($column, '!=', $value);
    }

    public function gt(string $column, mixed $value): self
    {
        return $this->where($column, '>', $value);
    }

    public function gte(string $column, mixed $value): self
    {
        return $this->where($column, '>=', $value);
    }

    public function lt(string $column, mixed $value): self
    {
        return $this->where($column, '<', $value);
    }

    public function lte(string $column, mixed $value): self
    {
        return $this->where($column, '<=', $value);
    }

    public function like(string $column, string $value): self
    {
        return $this->where($column, 'LIKE', $value);
    }

    public function ilike(string $column, string $value): self
    {
        $column = $this->guardIdentifier($column);
        $placeholder = $this->bindValue($value);
        $this->wheres[] = sprintf('LOWER(%s) LIKE LOWER(%s)', $column, $placeholder);

        return $this;
    }

    public function in(string $column, array $values): self
    {
        if ($values === []) {
            throw new InvalidArgumentException('IN filter requires at least one value.');
        }

        $column = $this->guardIdentifier($column);
        $placeholders = [];

        foreach ($values as $value) {
            $placeholders[] = $this->bindValue($value);
        }

        $this->wheres[] = sprintf('%s IN (%s)', $column, implode(', ', $placeholders));

        return $this;
    }

    public function is(string $column, mixed $value): self
    {
        $column = $this->guardIdentifier($column);

        if ($value === null) {
            $this->wheres[] = sprintf('%s IS NULL', $column);
            return $this;
        }

        if (is_bool($value)) {
            $this->wheres[] = sprintf('%s IS %s', $column, $value ? 'TRUE' : 'FALSE');
            return $this;
        }

        throw new InvalidArgumentException('The is() filter only supports null and boolean values.');
    }

    public function between(string $column, mixed $from, mixed $to): self
    {
        $column = $this->guardIdentifier($column);
        $fromPlaceholder = $this->bindValue($from);
        $toPlaceholder = $this->bindValue($to);
        $this->wheres[] = sprintf('%s BETWEEN %s AND %s', $column, $fromPlaceholder, $toPlaceholder);

        return $this;
    }

    public function not(string $column, string $operator, mixed $value): self
    {
        $this->wheres[] = 'NOT (' . $this->compileCondition($column, $operator, $value) . ')';

        return $this;
    }

    public function or(array $conditions): self
    {
        if ($conditions === []) {
            throw new InvalidArgumentException('The or() method requires at least one condition.');
        }

        $parts = [];

        foreach ($conditions as $condition) {
            if (!is_array($condition) || count($condition) !== 3) {
                throw new InvalidArgumentException('Each or() condition must be [column, operator, value].');
            }

            [$column, $operator, $value] = array_values($condition);
            $parts[] = $this->compileCondition((string) $column, (string) $operator, $value);
        }

        $this->wheres[] = '(' . implode(' OR ', $parts) . ')';

        return $this;
    }

    public function order(string $column, bool $ascending = true): self
    {
        $direction = $ascending ? 'ASC' : 'DESC';
        $this->orders[] = sprintf('%s %s', $this->guardIdentifier($column), $direction);

        return $this;
    }

    public function limit(int $limit): self
    {
        if ($limit < 0) {
            throw new InvalidArgumentException('Limit cannot be negative.');
        }

        $this->limit = $limit;

        return $this;
    }

    public function range(int $from, int $to): self
    {
        if ($from < 0 || $to < $from) {
            throw new InvalidArgumentException('Invalid range values.');
        }

        $this->offset = $from;
        $this->limit = ($to - $from) + 1;

        return $this;
    }

    public function first(): array|null
    {
        $result = $this->maybeSingle();

        return $result->data;
    }

    public function pluck(string $column): array
    {
        $this->select($column);
        $result = $this->execute();

        if (!is_array($result->data)) {
            return [];
        }

        return array_map(
            static fn (array $row): mixed => $row[$column] ?? null,
            $result->data
        );
    }

    public function exists(): bool
    {
        $sql = sprintf('SELECT 1 FROM %s', $this->table);
        $sql .= $this->buildWhereClause();
        $sql .= ' LIMIT 1';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($this->bindings);

        return $statement->fetchColumn() !== false;
    }

    public function count(string $column = '*'): int
    {
        $countTarget = $column === '*'
            ? '*'
            : $this->guardIdentifier($column);

        $sql = sprintf('SELECT COUNT(%s) AS aggregate_count FROM %s', $countTarget, $this->table);
        $sql .= $this->buildWhereClause();

        $statement = $this->pdo->prepare($sql);
        $statement->execute($this->bindings);
        $value = $statement->fetchColumn();

        return (int) $value;
    }

    public function single(): QueryResult
    {
        $this->limit = 1;
        $result = $this->execute();

        if (!is_array($result->data) || count($result->data) !== 1) {
            throw new RuntimeException('Expected exactly one row.');
        }

        return new QueryResult($result->data[0], $result->rowCount, $result->sql, $result->bindings);
    }

    public function maybeSingle(): QueryResult
    {
        $this->limit = 1;
        $result = $this->execute();

        if ($result->rowCount === 0) {
            return new QueryResult(null, 0, $result->sql, $result->bindings);
        }

        return new QueryResult($result->data[0], $result->rowCount, $result->sql, $result->bindings);
    }

    public function execute(): QueryResult
    {
        return match ($this->operation) {
            'select' => $this->executeSelect(),
            'insert' => $this->executeInsert(),
            'upsert' => $this->executeUpsert(),
            'update' => $this->executeUpdate(),
            'delete' => $this->executeDelete(),
            default => throw new RuntimeException('Unsupported query operation.'),
        };
    }

    private function executeSelect(): QueryResult
    {
        $columns = $this->buildColumnList($this->columns);
        $sql = sprintf('SELECT %s FROM %s', $columns, $this->table);
        $sql .= $this->buildWhereClause();
        $sql .= $this->buildOrderClause();
        $sql .= $this->buildLimitOffsetClause();

        $statement = $this->pdo->prepare($sql);
        $statement->execute($this->bindings);
        $rows = $statement->fetchAll();

        return new QueryResult($rows, count($rows), $sql, $this->bindings);
    }

    private function executeInsert(): QueryResult
    {
        $rows = $this->insertData;
        $columns = array_keys($rows[0]);

        if ($columns === []) {
            throw new InvalidArgumentException('Insert data cannot be empty.');
        }

        $guardedColumns = array_map(fn (string $column): string => $this->guardIdentifier($column), $columns);
        $valueGroups = [];

        foreach ($rows as $row) {
            if (array_keys($row) !== $columns) {
                throw new InvalidArgumentException('All inserted rows must have the same keys in the same order.');
            }

            $placeholders = [];

            foreach ($row as $value) {
                $placeholders[] = $this->bindValue($value);
            }

            $valueGroups[] = '(' . implode(', ', $placeholders) . ')';
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $this->table,
            implode(', ', $guardedColumns),
            implode(', ', $valueGroups)
        );
        $sql .= $this->buildReturningClause();

        $statement = $this->pdo->prepare($sql);
        $statement->execute($this->bindings);
        $rows = $this->fetchRowsIfReturning($statement);

        return new QueryResult($rows, $statement->rowCount(), $sql, $this->bindings);
    }

    private function executeUpdate(): QueryResult
    {
        $assignments = [];

        foreach ($this->updateData as $column => $value) {
            $assignments[] = sprintf('%s = %s', $this->guardIdentifier((string) $column), $this->bindValue($value));
        }

        $sql = sprintf('UPDATE %s SET %s', $this->table, implode(', ', $assignments));
        $sql .= $this->buildWhereClause();
        $sql .= $this->buildReturningClause();

        $statement = $this->pdo->prepare($sql);
        $statement->execute($this->bindings);
        $rows = $this->fetchRowsIfReturning($statement);

        return new QueryResult($rows, $statement->rowCount(), $sql, $this->bindings);
    }

    private function executeUpsert(): QueryResult
    {
        $rows = $this->upsertData;
        $columns = array_keys($rows[0]);

        if ($columns === []) {
            throw new InvalidArgumentException('Upsert data cannot be empty.');
        }

        $guardedColumns = array_map(fn (string $column): string => $this->guardIdentifier($column), $columns);
        $valueGroups = [];

        foreach ($rows as $row) {
            if (array_keys($row) !== $columns) {
                throw new InvalidArgumentException('All upsert rows must have the same keys in the same order.');
            }

            $placeholders = [];

            foreach ($row as $value) {
                $placeholders[] = $this->bindValue($value);
            }

            $valueGroups[] = '(' . implode(', ', $placeholders) . ')';
        }

        $conflictColumns = array_map(fn (string $column): string => $this->guardIdentifier($column), $this->upsertConflictColumns);
        $updateColumns = $this->resolveUpsertUpdateColumns($columns);
        $assignments = [];

        foreach ($updateColumns as $column) {
            $guardedColumn = $this->guardIdentifier($column);
            $assignments[] = sprintf('%s = EXCLUDED.%s', $guardedColumn, $guardedColumn);
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s ON CONFLICT (%s) DO %s',
            $this->table,
            implode(', ', $guardedColumns),
            implode(', ', $valueGroups),
            implode(', ', $conflictColumns),
            $assignments === [] ? 'NOTHING' : 'UPDATE SET ' . implode(', ', $assignments)
        );
        $sql .= $this->buildReturningClause();

        $statement = $this->pdo->prepare($sql);
        $statement->execute($this->bindings);
        $rows = $this->fetchRowsIfReturning($statement);

        return new QueryResult($rows, $statement->rowCount(), $sql, $this->bindings);
    }

    private function executeDelete(): QueryResult
    {
        $sql = sprintf('DELETE FROM %s', $this->table);
        $sql .= $this->buildWhereClause();
        $sql .= $this->buildReturningClause();

        $statement = $this->pdo->prepare($sql);
        $statement->execute($this->bindings);
        $rows = $this->fetchRowsIfReturning($statement);

        return new QueryResult($rows, $statement->rowCount(), $sql, $this->bindings);
    }

    private function where(string $column, string $operator, mixed $value): self
    {
        $this->wheres[] = $this->compileCondition($column, $operator, $value);

        return $this;
    }

    private function compileCondition(string $column, string $operator, mixed $value): string
    {
        $allowedOperators = ['=', '!=', '>', '>=', '<', '<=', 'LIKE'];
        $normalizedOperator = strtoupper(trim($operator));

        if (!in_array($normalizedOperator, $allowedOperators, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported operator: %s', $operator));
        }

        $column = $this->guardIdentifier($column);
        $placeholder = $this->bindValue($value);

        return sprintf('%s %s %s', $column, $normalizedOperator, $placeholder);
    }

    private function buildWhereClause(): string
    {
        if ($this->wheres === []) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', $this->wheres);
    }

    private function buildOrderClause(): string
    {
        if ($this->orders === []) {
            return '';
        }

        return ' ORDER BY ' . implode(', ', $this->orders);
    }

    private function buildLimitOffsetClause(): string
    {
        $sql = '';

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    private function buildReturningClause(): string
    {
        if ($this->returningColumns === null) {
            return '';
        }

        return ' RETURNING ' . $this->buildColumnList($this->returningColumns);
    }

    private function buildColumnList(array|string $columns): string
    {
        if ($columns === '*') {
            return '*';
        }

        if (is_string($columns)) {
            $columns = array_map('trim', explode(',', $columns));
        }

        if ($columns === []) {
            throw new InvalidArgumentException('Select columns cannot be empty.');
        }

        return implode(', ', array_map(fn (string $column): string => $this->guardIdentifier($column), $columns));
    }

    private function bindValue(mixed $value): string
    {
        $placeholder = ':p' . $this->placeholderCounter++;
        $this->bindings[$placeholder] = $value;

        return $placeholder;
    }

    private function normalizeRows(array $data): array
    {
        if ($data === []) {
            throw new InvalidArgumentException('Insert data cannot be empty.');
        }

        $first = reset($data);

        if (is_array($first)) {
            return $data;
        }

        return [$data];
    }

    private function fetchRowsIfReturning(PDOStatement $statement): array
    {
        if ($this->returningColumns === null) {
            return [];
        }

        return $statement->fetchAll();
    }

    private function resolveUpsertUpdateColumns(array $insertColumns): array
    {
        $updateColumns = $this->upsertUpdateColumns ?? array_values(array_diff($insertColumns, $this->upsertConflictColumns));

        foreach ($updateColumns as $column) {
            if (!in_array($column, $insertColumns, true)) {
                throw new InvalidArgumentException(sprintf('Upsert update column "%s" is missing from the provided data.', $column));
            }
        }

        return $updateColumns;
    }

    private function guardIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);

        if ($identifier === '*') {
            return '*';
        }

        $parts = explode('.', $identifier);

        foreach ($parts as $part) {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $part)) {
                throw new InvalidArgumentException(sprintf('Invalid SQL identifier: %s', $identifier));
            }
        }

        return implode('.', array_map(static fn (string $part): string => '"' . $part . '"', $parts));
    }
}

final class QueryResult
{
    public function __construct(
        public readonly mixed $data,
        public readonly int $rowCount,
        public readonly string $sql,
        public readonly array $bindings
    ) {
    }
}
?>
