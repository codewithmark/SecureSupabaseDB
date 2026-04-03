<?php

class SecureSupabaseDB
{
    private static ?self $instance = null;
    private PDO $pdo;

    private function __construct(array $config)
    {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
            $config['host'],
            $config['port'] ?? 5432,
            $config['database'] ?? 'postgres'
        );

        $this->pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            if (empty($config)) {
                throw new InvalidArgumentException('Database config is required on first connection.');
            }
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this->pdo, $table);
    }

    public function select(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function one(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function q(string $sql, array $params = []): array
    {
        return $this->select($sql, $params);
    }

    public function query(string $sql, array $params = []): array
    {
        return $this->select($sql, $params);
    }
}

class QueryBuilder
{
    private PDO $pdo;
    private string $table;
    private string $select = '*';

    private array $whereParts = [];
    private array $params = [];
    private array $orderParts = [];

    public function __construct(PDO $pdo, string $table)
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    public function select(string|array $columns): self
    {
        if (is_array($columns)) {
            $this->select = implode(', ', $columns);
        } else {
            $this->select = $columns;
        }

        return $this;
    }

    public function where(array $conditions): self
    {
        return $this->addConditionGroup('AND', $conditions, true);
    }

    public function andWhere(array $conditions): self
    {
        return $this->addConditionGroup('AND', $conditions, false);
    }

    public function orWhere(array $conditions): self
    {
        return $this->addConditionGroup('OR', $conditions, false);
    }

    private function addConditionGroup(string $boolean, array $conditions, bool $isFirst = false): self
    {
        if (empty($conditions)) {
            return $this;
        }

        $group = [];

        foreach ($conditions as $column => $value) {
            $param = 'w_' . count($this->params);

            if ($value === null) {
                $group[] = "{$column} IS NULL";
            } else {
                $group[] = "{$column} = :{$param}";
                $this->params[$param] = $value;
            }
        }

        $sqlGroup = '(' . implode(' AND ', $group) . ')';

        if ($isFirst || empty($this->whereParts)) {
            $this->whereParts[] = [
                'boolean' => '',
                'sql' => $sqlGroup
            ];
        } else {
            $this->whereParts[] = [
                'boolean' => $boolean,
                'sql' => $sqlGroup
            ];
        }

        return $this;
    }

    public function orderBy(string|array $column, string $direction = 'ASC'): self
    {
        if (is_array($column)) {
            foreach ($column as $col => $dir) {
                $dir = strtoupper(trim($dir)) === 'DESC' ? 'DESC' : 'ASC';
                $this->orderParts[] = "{$col} {$dir}";
            }
        } else {
            $dir = strtoupper(trim($direction)) === 'DESC' ? 'DESC' : 'ASC';
            $this->orderParts[] = "{$column} {$dir}";
        }

        return $this;
    }

    private function buildSelectSql(): string
    {
        $sql = "SELECT {$this->select} FROM {$this->table}";

        if (!empty($this->whereParts)) {
            $sql .= ' WHERE ';
            $chunks = [];

            foreach ($this->whereParts as $index => $part) {
                if ($index === 0 || $part['boolean'] === '') {
                    $chunks[] = $part['sql'];
                } else {
                    $chunks[] = $part['boolean'] . ' ' . $part['sql'];
                }
            }

            $sql .= implode(' ', $chunks);
        }

        if (!empty($this->orderParts)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderParts);
        }

        return $sql;
    }

    private function buildCountSql(): string
    {
        $sql = "SELECT COUNT(*) AS total FROM {$this->table}";

        if (!empty($this->whereParts)) {
            $sql .= ' WHERE ';
            $chunks = [];

            foreach ($this->whereParts as $index => $part) {
                if ($index === 0 || $part['boolean'] === '') {
                    $chunks[] = $part['sql'];
                } else {
                    $chunks[] = $part['boolean'] . ' ' . $part['sql'];
                }
            }

            $sql .= implode(' ', $chunks);
        }

        return $sql;
    }

    private function bindParams(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
    }

    public function get(): array
    {
        $sql = $this->buildSelectSql();
        $stmt = $this->pdo->prepare($sql);
        $this->bindParams($stmt, $this->params);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function first(): ?array
    {
        $sql = $this->buildSelectSql() . ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $this->bindParams($stmt, $this->params);
        $stmt->execute();

        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function paginate(int $limit = 25, int $page = 1): array
    {
        $limit = max(1, $limit);
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $countStmt = $this->pdo->prepare($this->buildCountSql());
        $this->bindParams($countStmt, $this->params);
        $countStmt->execute();

        $total = (int)($countStmt->fetch()['total'] ?? 0);

        $sql = $this->buildSelectSql() . ' LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);

        $this->bindParams($stmt, $this->params);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int)ceil($total / $limit),
                'offset' => $offset,
            ]
        ];
    }

    public function range(int $start, int $end): array
    {
        $start = max(1, $start);
        $end = max($start, $end);

        $limit = ($end - $start) + 1;
        $offset = $start - 1;

        $countStmt = $this->pdo->prepare($this->buildCountSql());
        $this->bindParams($countStmt, $this->params);
        $countStmt->execute();

        $total = (int)($countStmt->fetch()['total'] ?? 0);

        $sql = $this->buildSelectSql() . ' LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);

        $this->bindParams($stmt, $this->params);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'range' => [
                'start' => $start,
                'end' => $end,
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
            ]
        ];
    }
}
