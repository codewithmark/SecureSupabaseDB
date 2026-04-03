# SecureSupabaseDB for PHP

`SecureSupabaseDB` is a standalone PHP class that gives you a Supabase-style fluent API while running queries through `PDO`.

## Setup

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

## from()

## What It Does

Chooses the table you want to query.

### Usage Pattern

```php
$db->from('table_name')
```

### Copy-Paste Example

```php
$result = $db
    ->from('users')
    ->select('*')
    ->execute();
```

### Sample Result

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

## `table()`

### What It Does

`table()` is the same as `from()`. It is just another name for selecting a table.

### Usage Pattern

```php
$db->table('table_name')
```

### Copy-Paste Example

```php
$result = $db
    ->table('users')
    ->select('*')
    ->execute();
```

### Sample Result

```php
QueryResult Object
(
    [sql] => SELECT * FROM "users"
    [rowCount] => 3
)
```

## `select()`

### What It Does

Chooses which columns to return from the table.

### Usage Pattern

```php
->select('*')
->select('id, name, email')
```

### Copy-Paste Example

```php
$result = $db
    ->from('users')
    ->select('id, name, email')
    ->execute();
```

### Sample Result

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

## `insert()`

### What It Does

Inserts one or more new rows into the table.

### Usage Pattern

```php
->insert([
    'name' => 'Mark',
    'email' => 'mark@example.com',
])
```

### Copy-Paste Example

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

### Sample Result

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

## `update()`

### What It Does

Updates rows that match your filters.

### Usage Pattern

```php
->update([
    'status' => 'inactive',
])
```

### Copy-Paste Example

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

### Sample Result

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

## `delete()`

### What It Does

Deletes rows that match your filters.

### Usage Pattern

```php
->delete()
```

### Copy-Paste Example

```php
$result = $db
    ->from('users')
    ->delete()
    ->eq('id', 2)
    ->select('id, email')
    ->execute();
```

### Sample Result

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

## `eq()`

### What It Does

Filters rows where a column is equal to a value.

### Usage Pattern

```php
->eq('column_name', 'value')
```

### Copy-Paste Example

```php
$result = $db
    ->from('users')
    ->select('*')
    ->eq('status', 'active')
    ->execute();
```

### Sample Result

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

## `neq()`

### What It Does

Filters rows where a column is not equal to a value.

### Usage Pattern

```php
->neq('column_name', 'value')
```

### Copy-Paste Example

```php
$result = $db
    ->from('users')
    ->select('*')
    ->neq('status', 'banned')
    ->execute();
```

### Sample Result

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

## `gt()`

### What It Does

Filters rows where a column is greater than a value.

### Usage Pattern

```php
->gt('column_name', 100)
```

### Copy-Paste Example

```php
$result = $db
    ->from('orders')
    ->select('*')
    ->gt('total', 100)
    ->execute();
```

### Sample Result

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

## `gte()`

### What It Does

Filters rows where a column is greater than or equal to a value.

### Usage Pattern

```php
->gte('column_name', 100)
```

### Copy-Paste Example

```php
$result = $db
    ->from('orders')
    ->select('*')
    ->gte('total', 100)
    ->execute();
```

### Sample Result

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

## `lt()`

### What It Does

Filters rows where a column is less than a value.

### Usage Pattern

```php
->lt('column_name', 10)
```

### Copy-Paste Example

```php
$result = $db
    ->from('products')
    ->select('*')
    ->lt('stock', 10)
    ->execute();
```

### Sample Result

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

## `lte()`

### What It Does

Filters rows where a column is less than or equal to a value.

### Usage Pattern

```php
->lte('column_name', 10)
```

### Copy-Paste Example

```php
$result = $db
    ->from('products')
    ->select('*')
    ->lte('stock', 10)
    ->execute();
```

### Sample Result

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

## `like()`

### What It Does

Filters rows using SQL `LIKE`.

### Usage Pattern

```php
->like('column_name', '%text%')
```

### Copy-Paste Example

```php
$result = $db
    ->from('users')
    ->select('*')
    ->like('email', '%@gmail.com')
    ->execute();
```

### Sample Result

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

## `ilike()`

### What It Does

Filters rows with a case-insensitive text match.

### Usage Pattern

```php
->ilike('column_name', '%text%')
```

### Copy-Paste Example

```php
$result = $db
    ->from('users')
    ->select('*')
    ->ilike('name', '%mark%')
    ->execute();
```

### Sample Result

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

## `in()`

### What It Does

Filters rows where a column matches any value in an array.

### Usage Pattern

```php
->in('column_name', ['value1', 'value2'])
```

### Copy-Paste Example

```php
$result = $db
    ->from('users')
    ->select('*')
    ->in('role', ['admin', 'editor', 'author'])
    ->execute();
```

### Sample Result

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

## `is()`

### What It Does

Filters rows using `IS NULL`, `IS TRUE`, or `IS FALSE`.

### Usage Pattern

```php
->is('column_name', null)
->is('column_name', true)
```

### Copy-Paste Example

```php
$result = $db
    ->from('users')
    ->select('*')
    ->is('deleted_at', null)
    ->execute();
```

### Sample Result

```php
QueryResult Object
(
    [sql] => SELECT * FROM "users" WHERE "deleted_at" IS NULL
    [bindings] => Array
        (
        )
)
```

## `or()`

### What It Does

Groups multiple conditions together with `OR`.

### Usage Pattern

```php
->or([
    ['status', '=', 'active'],
    ['role', '=', 'admin'],
])
```

### Copy-Paste Example

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

### Sample Result

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

## `order()`

### What It Does

Sorts the returned rows.

### Usage Pattern

```php
->order('column_name')
->order('column_name', false)
```

### Copy-Paste Example

```php
$result = $db
    ->from('users')
    ->select('*')
    ->order('id', false)
    ->execute();
```

### Sample Result

```php
QueryResult Object
(
    [sql] => SELECT * FROM "users" ORDER BY "id" DESC
    [bindings] => Array
        (
        )
)
```

## `limit()`

### What It Does

Limits how many rows are returned.

### Usage Pattern

```php
->limit(2)
```

### Copy-Paste Example

```php
$result = $db
    ->from('users')
    ->select('*')
    ->limit(2)
    ->execute();
```

### Sample Result

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

## `range()`

### What It Does

Returns rows using a zero-based start and end range.

### Usage Pattern

```php
->range(0, 9)
```

### Copy-Paste Example

```php
$result = $db
    ->from('users')
    ->select('*')
    ->range(0, 9)
    ->execute();
```

### Sample Result

```php
QueryResult Object
(
    [sql] => SELECT * FROM "users" LIMIT 10 OFFSET 0
    [bindings] => Array
        (
        )
)
```

## `single()`

### What It Does

Returns exactly one row. It throws an exception if zero rows or more than one row are found.

### Usage Pattern

```php
->single()
```

### Copy-Paste Example

```php
$result = $db
    ->from('users')
    ->select('*')
    ->eq('id', 1)
    ->single();
```

### Sample Result

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

## `maybeSingle()`

### What It Does

Returns one row if it exists. If nothing matches, `$result->data` is `null`.

### Usage Pattern

```php
->maybeSingle()
```

### Copy-Paste Example

```php
$result = $db
    ->from('users')
    ->select('*')
    ->eq('email', 'missing@example.com')
    ->maybeSingle();
```

### Sample Result

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

## `execute()`

### What It Does

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
