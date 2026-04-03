# SecureSupabaseDB 🚀

A lightweight, secure, and chainable PHP PDO wrapper for Supabase (PostgreSQL)

---

## 📌 What is this?

**SecureSupabaseDB** is a simple PHP library that lets you interact with your **Supabase PostgreSQL database** using clean, readable, and secure code.

It removes the need to write repetitive SQL and PDO boilerplate, while still giving you full control and performance of direct database access.

---

## 🎯 Purpose

This library is built for developers who want to:

* Build SaaS apps quickly ⚡
* Create clean APIs without heavy frameworks
* Work directly with Supabase Postgres (no REST overhead)
* Keep code simple, readable, and maintainable
* Avoid complex ORMs while still enjoying a fluent interface

---

## ⚙️ How it works

Instead of writing raw SQL everywhere:

```php
$users = $db->select("SELECT * FROM users WHERE country = :country", [
  'country' => 'United States'
]);
```

You can write:

```php
$users = $db->table('users')
    ->where(['country' => 'United States'])
    ->get();
```

👉 Under the hood:

* Uses **PDO with prepared statements**
* Connects directly to **Supabase PostgreSQL**
* Prevents SQL injection automatically
* Optimized for performance

---

## 🚀 Key Features

* 🔒 Secure (prepared statements)
* ⚡ Direct DB connection (no API layer)
* 🔗 Chainable query builder
* 📊 Pagination & range support
* 🧱 Built-in CRUD helpers
* 🧪 Raw SQL support when needed
* 📦 Lightweight (no dependencies)

---

## 🧠 Who is this for?

* PHP developers building SaaS products
* Developers using Supabase with a PHP backend
* Anyone who wants a **simple alternative to Laravel/ORMs**
* Projects where performance and simplicity matter

---

## 🔌 Quick Start

```php
require 'SecureSupabaseDB.php';

$db = SecureSupabaseDB::getInstance([
    'host' => 'db.YOUR_PROJECT_REF.supabase.co',
    'port' => 5432,
    'database' => 'postgres',
    'user' => 'postgres',
    'pass' => 'YOUR_DB_PASSWORD',
]);
```

---

## ✨ Example

```php
$users = $db->table('users')
    ->select('id, email, country')
    ->where(['country' => 'United States'])
    ->orderBy('created_at', 'DESC')
    ->paginate(25, 1);
```

---

## 🧭 Why not just use raw PDO?

You can — but this library gives you:

* Cleaner code
* Less repetition
* Faster development
* Built-in helpers (CRUD, pagination, chaining)

👉 Without sacrificing performance.

---
