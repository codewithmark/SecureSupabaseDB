# SecureSupabaseDB for PHP

`SecureSupabaseDB` is a standalone PHP class that gives you a Supabase-style fluent API while running queries through `PDO`.

---

### Setup

Include the class once at the top of your PHP file:

```php
<?php

require_once __DIR__ . '/src/SecureSupabaseDB.php';

$db = SecureSupabaseDB::connect(
    'pgsql:host=127.0.0.1;port=5432;dbname=postgres',
    'postgres',
    'secret'
);
```

All examples below assume you already have the setup above in your file.

---

### `from()`

Chooses the table you want to query.

### SYNTAX

```php
$db->from('table_name')
```

### CODE

```php
$result = $db
    ->from('users')
    ->select('*')
    ->execute();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [data] => Array
        (
            [0] => Array
                (
                    [id] => 1
                    [name] => Mark
                )
        )

    [rowCount] => 1
    [sql] => SELECT * FROM "users"
    [bindings] => Array
        (
        )
)
```

---

### `select()`

Chooses which columns to return from the table.

### SYNTAX

```php
->select('*')
->select('id, name, email')
```

### CODE

```php
$result = $db
    ->from('users')
    ->select('id, name, email')
    ->execute();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [data] => Array
        (
            [0] => Array
                (
                    [id] => 1
                    [name] => Mark
                    [email] => mark@example.com
                )
        )

    [sql] => SELECT "id", "name", "email" FROM "users"
)
```

---

### `insert()`

Inserts one or more new rows into the table.

### SYNTAX

```php
->insert([
    'name' => 'Mark',
    'email' => 'mark@example.com',
])
```

### CODE

```php
$result = $db
    ->from('users')
    ->insert([
        'name' => 'Mark',
        'email' => 'mark@example.com',
        'status' => 'active',
    ])
    ->select()
    ->single();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [data] => Array
        (
            [id] => 4
            [name] => Mark
            [email] => mark@example.com
            [status] => active
        )

    [rowCount] => 1
    [sql] => INSERT INTO "users" ("name", "email", "status") VALUES (:p0, :p1, :p2) RETURNING *
    [bindings] => Array
        (
            [:p0] => Mark
            [:p1] => mark@example.com
            [:p2] => active
        )
)
```

---

### `update()`

Updates rows that match your filters.

### SYNTAX

```php
->update([
    'status' => 'inactive',
])
```

### CODE

```php
$result = $db
    ->from('users')
    ->update([
        'status' => 'inactive',
    ])
    ->eq('id', 1)
    ->select()
    ->maybeSingle();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [data] => Array
        (
            [id] => 1
            [name] => Mark
            [status] => inactive
        )

    [rowCount] => 1
    [sql] => UPDATE "users" SET "status" = :p0 WHERE "id" = :p1 RETURNING *
    [bindings] => Array
        (
            [:p0] => inactive
            [:p1] => 1
        )
)
```

---

### `delete()`

Deletes rows that match your filters.

### SYNTAX

```php
->delete()
```

### CODE

```php
$result = $db
    ->from('users')
    ->delete()
    ->eq('id', 2)
    ->select('id, email')
    ->execute();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [data] => Array
        (
            [0] => Array
                (
                    [id] => 2
                    [email] => jane@example.com
                )
        )

    [rowCount] => 1
    [sql] => DELETE FROM "users" WHERE "id" = :p0 RETURNING "id", "email"
    [bindings] => Array
        (
            [:p0] => 2
        )
)
```

---

### `eq()`

Filters rows where a column is equal to a value.

### SYNTAX

```php
->eq('column_name', 'value')
```

### CODE

```php
$result = $db
    ->from('users')
    ->select('*')
    ->eq('status', 'active')
    ->execute();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [sql] => SELECT * FROM "users" WHERE "status" = :p0
    [bindings] => Array
        (
            [:p0] => active
        )
)
```

---

### `neq()`

Filters rows where a column is not equal to a value.

### SYNTAX

```php
->neq('column_name', 'value')
```

### CODE

```php
$result = $db
    ->from('users')
    ->select('*')
    ->neq('status', 'banned')
    ->execute();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [sql] => SELECT * FROM "users" WHERE "status" != :p0
    [bindings] => Array
        (
            [:p0] => banned
        )
)
```

---

### `gt()`

Filters rows where a column is greater than a value.

### SYNTAX

```php
->gt('column_name', 100)
```

### CODE

```php
$result = $db
    ->from('orders')
    ->select('*')
    ->gt('total', 100)
    ->execute();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [sql] => SELECT * FROM "orders" WHERE "total" > :p0
    [bindings] => Array
        (
            [:p0] => 100
        )
)
```

---

### `gte()`

Filters rows where a column is greater than or equal to a value.

### SYNTAX

```php
->gte('column_name', 100)
```

### CODE

```php
$result = $db
    ->from('orders')
    ->select('*')
    ->gte('total', 100)
    ->execute();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [sql] => SELECT * FROM "orders" WHERE "total" >= :p0
    [bindings] => Array
        (
            [:p0] => 100
        )
)
```

---

### `lt()`

Filters rows where a column is less than a value.

### SYNTAX

```php
->lt('column_name', 10)
```

### CODE

```php
$result = $db
    ->from('products')
    ->select('*')
    ->lt('stock', 10)
    ->execute();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [sql] => SELECT * FROM "products" WHERE "stock" < :p0
    [bindings] => Array
        (
            [:p0] => 10
        )
)
```

---

### `lte()`

Filters rows where a column is less than or equal to a value.

### SYNTAX

```php
->lte('column_name', 10)
```

### CODE

```php
$result = $db
    ->from('products')
    ->select('*')
    ->lte('stock', 10)
    ->execute();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [sql] => SELECT * FROM "products" WHERE "stock" <= :p0
    [bindings] => Array
        (
            [:p0] => 10
        )
)
```

---

### `like()`

Filters rows using SQL `LIKE`.

### SYNTAX

```php
->like('column_name', '%text%')
```

### CODE

```php
$result = $db
    ->from('users')
    ->select('*')
    ->like('email', '%@gmail.com')
    ->execute();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [sql] => SELECT * FROM "users" WHERE "email" LIKE :p0
    [bindings] => Array
        (
            [:p0] => %@gmail.com
        )
)
```

---

### `ilike()`

Filters rows with a case-insensitive text match.

### SYNTAX

```php
->ilike('column_name', '%text%')
```

### CODE

```php
$result = $db
    ->from('users')
    ->select('*')
    ->ilike('name', '%mark%')
    ->execute();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [sql] => SELECT * FROM "users" WHERE LOWER("name") LIKE LOWER(:p0)
    [bindings] => Array
        (
            [:p0] => %mark%
        )
)
```

---

### `in()`

Filters rows where a column matches any value in an array.

### SYNTAX

```php
->in('column_name', ['value1', 'value2'])
```

### CODE

```php
$result = $db
    ->from('users')
    ->select('*')
    ->in('role', ['admin', 'editor', 'author'])
    ->execute();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [sql] => SELECT * FROM "users" WHERE "role" IN (:p0, :p1, :p2)
    [bindings] => Array
        (
            [:p0] => admin
            [:p1] => editor
            [:p2] => author
        )
)
```

---

### `is()`

Filters rows using `IS NULL`, `IS TRUE`, or `IS FALSE`.

### SYNTAX

```php
->is('column_name', null)
->is('column_name', true)
```

### CODE

```php
$result = $db
    ->from('users')
    ->select('*')
    ->is('deleted_at', null)
    ->execute();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [sql] => SELECT * FROM "users" WHERE "deleted_at" IS NULL
    [bindings] => Array
        (
        )
)
```

---

### `or()`

Groups multiple conditions together with `OR`.

### SYNTAX

```php
->or([
    ['status', '=', 'active'],
    ['role', '=', 'admin'],
])
```

### CODE

```php
$result = $db
    ->from('users')
    ->select('*')
    ->or([
        ['status', '=', 'active'],
        ['role', '=', 'admin'],
    ])
    ->execute();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [sql] => SELECT * FROM "users" WHERE ("status" = :p0 OR "role" = :p1)
    [bindings] => Array
        (
            [:p0] => active
            [:p1] => admin
        )
)
```

Classic example mixed with `eq()`:

```php
$result = $db
    ->from('users')
    ->select('*')
    ->eq('country', 'US')
    ->or([
        ['status', '=', 'active'],
        ['role', '=', 'admin'],
    ])
    ->execute();
```

This produces SQL like:

```sql
SELECT * FROM "users"
WHERE "country" = :p0
AND ("status" = :p1 OR "role" = :p2)
```

---

### `order()`

Sorts the returned rows.

### SYNTAX

```php
->order('column_name')
->order('column_name', false)
```

### CODE

```php
$result = $db
    ->from('users')
    ->select('*')
    ->order('id', false)
    ->execute();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [sql] => SELECT * FROM "users" ORDER BY "id" DESC
    [bindings] => Array
        (
        )
)
```

---

### `limit()`

Limits how many rows are returned.

### SYNTAX

```php
->limit(2)
```

### CODE

```php
$result = $db
    ->from('users')
    ->select('*')
    ->limit(2)
    ->execute();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [data] => Array
        (
            [0] => Array
                (
                    [id] => 1
                    [name] => Mark
                    [email] => mark@example.com
                    [status] => active
                )

            [1] => Array
                (
                    [id] => 2
                    [name] => Jane
                    [email] => jane@example.com
                    [status] => inactive
                )
        )

    [rowCount] => 2
    [sql] => SELECT * FROM "users" LIMIT 2
    [bindings] => Array
        (
        )
)
```

---

### `range()`

Returns rows using a zero-based start and end range.

### SYNTAX

```php
->range(0, 9)
```

### CODE

```php
$result = $db
    ->from('users')
    ->select('*')
    ->range(0, 9)
    ->execute();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [sql] => SELECT * FROM "users" LIMIT 10 OFFSET 0
    [bindings] => Array
        (
        )
)
```

---

### `single()`

Returns exactly one row. It throws an exception if zero rows or more than one row are found.

### SYNTAX

```php
->single()
```

### CODE

```php
$result = $db
    ->from('users')
    ->select('*')
    ->eq('id', 1)
    ->single();
```

### OUTPUT

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [data] => Array
        (
            [id] => 1
            [name] => Mark
            [email] => mark@example.com
            [status] => active
        )

    [rowCount] => 1
    [sql] => SELECT * FROM "users" WHERE "id" = :p0 LIMIT 1
    [bindings] => Array
        (
            [:p0] => 1
        )
)
```

---

### `maybeSingle()`

Returns one row if it exists. If nothing matches, `$result->data` is `null`.

### SYNTAX

```php
->maybeSingle()
```

### CODE

```php
$result = $db
    ->from('users')
    ->select('*')
    ->eq('email', 'missing@example.com')
    ->maybeSingle();
```

### OUTPUT

```php
QueryResult Object
(
    [data] => 
    [rowCount] => 0
    [sql] => SELECT * FROM "users" WHERE "email" = :p0 LIMIT 1
    [bindings] => Array
        (
            [:p0] => missing@example.com
        )
)
```

---

### `execute()`

Runs the built query and returns a `QueryResult` object.

### Usage Pattern

```php
->execute()
```

### Copy-Paste Example

```php
$result = $db
    ->from('users')
    ->select('*')
    ->eq('status', 'active')
    ->order('id')
    ->limit(10)
    ->execute();
```

### Sample Result

Use `$result->data` to get the actual information you want.

```php
QueryResult Object
(
    [data] => Array
        (
            [0] => Array
                (
                    [id] => 1
                    [name] => Mark
                    [status] => active
                )
        )

    [rowCount] => 1
    [sql] => SELECT * FROM "users" WHERE "status" = :p0 ORDER BY "id" ASC LIMIT 10
    [bindings] => Array
        (
            [:p0] => active
        )
)
```

## What `QueryResult` contains

Every query returns a `QueryResult` object with:

- `$result->data`
- `$result->rowCount`
- `$result->sql`
- `$result->bindings`

`bindings` contains the parameter values that were safely attached to the prepared SQL query.

Example:

```php
$result = $db
    ->from('users')
    ->select('*')
    ->eq('status', 'active')
    ->gt('id', 10)
    ->execute();
```

Output:

```php
QueryResult Object
(
    [sql] => SELECT * FROM "users" WHERE "status" = :p0 AND "id" > :p1
    [bindings] => Array
        (
            [:p0] => active
            [:p1] => 10
        )
)
```

## Notes

- This is a standalone PHP class.
- Include only [src/SecureSupabaseDB.php](./src/SecureSupabaseDB.php) in your project.
- It does not implement Auth, Realtime, Storage, or Supabase Row Level Security behavior.
- It is a fluent `PDO` query builder with Supabase-inspired method names.
- Identifiers are validated and values are parameterized.
- For PostgreSQL, `->select()` after `insert()`, `update()`, or `delete()` uses `RETURNING ...`.
