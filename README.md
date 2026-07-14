# Laravel Eloquent CASE Statement Support

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aglipanci/laravel-eloquent-case.svg)](https://packagist.org/packages/aglipanci/laravel-eloquent-case)
[![Total Downloads](https://img.shields.io/packagist/dt/aglipanci/laravel-eloquent-case.svg)](https://packagist.org/packages/aglipanci/laravel-eloquent-case)
[![Test Status](https://img.shields.io/github/actions/workflow/status/aglipanci/laravel-eloquent-case/run-tests.yml?branch=main)](https://github.com/aglipanci/laravel-eloquent-case/actions)
[![License](https://img.shields.io/packagist/l/aglipanci/laravel-eloquent-case.svg)](LICENSE.md)

This package adds [CASE](https://dev.mysql.com/doc/refman/8.0/en/flow-control-functions.html#operator_case) statement support to the Laravel Query Builder. It supports Laravel 9.x, 10.x, 11.x, 12.x & 13.x on PHP 8.2+.

## Installation

You can install the package via composer:

```bash
composer require aglipanci/laravel-eloquent-case
```

## Usage

### Add a CASE statement select on a Laravel Query

```php
use App\Models\Invoice;
use AgliPanci\LaravelCase\Query\CaseBuilder;

$invoices = Invoice::query()
            ->select('*')
            ->case(function (CaseBuilder $case) {
                $case->when('balance', '<', 0)->then('Overpaid')
                    ->when('balance', 0)->then('Paid')
                    ->else('Balance Due');
            }, 'payment_status')
            ->get();
```

Produces the following SQL query:

```mysql
SELECT *,
  ( CASE
      WHEN `balance` < 0 THEN 'Overpaid'
      WHEN `balance` = 0 THEN 'Paid'
      ELSE 'Balance Due'
    END ) AS `payment_status`
FROM
  `invoices`
```

> **Note:** the `case()` macro adds the CASE expression to the select list. If you don't
> select any other columns, the query will return *only* the CASE column, so add
> `->select('*')` (or the columns you need) first when you want the rest of the row too.

### Build the case query separately

```php
use App\Models\Invoice;
use AgliPanci\LaravelCase\Facades\CaseBuilder;

$caseQuery = CaseBuilder::when('balance', 0)->then('Paid')
                    ->when('balance', '>', 0)->then('Balance Due');

$invoices = Invoice::query()
            ->select('*')
            ->case($caseQuery, 'payment_status')
            ->get();
```

### Simple CASE statements

Besides searched CASE statements (`CASE WHEN balance = 0 ...`), you can build simple
CASE statements (`CASE balance WHEN 0 ...`) by setting a subject first:

```php
use AgliPanci\LaravelCase\Facades\CaseBuilder;

$caseQuery = CaseBuilder::case('payment_status')
                    ->when(1)->then('Paid')
                    ->when(2)->then('Due')
                    ->else('Unknown');

// case `payment_status` when ? then ? when ? then ? else ? end
```

Use `caseRaw()` when the subject is an expression:

```php
$caseQuery = CaseBuilder::caseRaw('count(id)')
                    ->whenRaw(1)->then(0)
                    ->else(100);
```

### NULL comparisons

Passing `null` as the value compiles to `IS NULL` / `IS NOT NULL`:

```php
$caseQuery = CaseBuilder::when('payment_date', null)->then('Pending')
                    ->when('payment_date', '!=', null)->then('Processed');

// case when `payment_date` IS NULL then ? when `payment_date` IS NOT NULL then ? end
```

### Compare columns

Use `whenColumn()` to compare two columns, and `thenColumn()` / `elseColumn()` to
return a column value instead of a literal:

```php
use AgliPanci\LaravelCase\Facades\CaseBuilder;

$caseQuery = CaseBuilder::whenColumn('amount_paid', '>=', 'amount_due')
                    ->then('Paid')
                    ->elseColumn('amount_due');

// case when `amount_paid` >= `amount_due` then ? else `amount_due` end

$caseQuery = CaseBuilder::when('discounted', 1)
                    ->thenColumn('discounted_price')
                    ->elseColumn('full_price');

// case when `discounted` = ? then `discounted_price` else `full_price` end
```

### Aggregates

Wrap the CASE statement in an aggregate function using `sum()`, `count()`, `avg()`,
`min()` or `max()` — handy for conditional aggregation:

```php
use App\Models\Invoice;
use AgliPanci\LaravelCase\Facades\CaseBuilder;

$caseQuery = CaseBuilder::when('balance', '>', 0)->then(1)->else(0)->sum();

// sum(case when `balance` > ? then ? else ? end)

$paidInvoicesCount = Invoice::query()
            ->case($caseQuery, 'unpaid_invoices')
            ->value('unpaid_invoices');
```

### Raw CASE conditions

```php
use App\Models\Invoice;
use AgliPanci\LaravelCase\Facades\CaseBuilder;

$caseQuery = CaseBuilder::whenRaw('balance = ?', [0])->thenRaw("'Paid'")
                    ->elseRaw("'N/A'");

$invoices = Invoice::query()
            ->select('*')
            ->case($caseQuery, 'payment_status')
            ->get();
```

### Use as raw SELECT

```php
use App\Models\Invoice;
use AgliPanci\LaravelCase\Facades\CaseBuilder;

$caseQuery = CaseBuilder::whenRaw('balance = ?', [0])->thenRaw("'Paid'")
                    ->elseRaw("'N/A'");

$invoices = Invoice::query()
            ->selectRaw($caseQuery->toRaw())
            ->get();
```

### Use in ORDER BY, GROUP BY or WHERE

The compiled SQL and its bindings can be used anywhere the query builder accepts raw
expressions:

```php
use App\Models\Invoice;
use AgliPanci\LaravelCase\Facades\CaseBuilder;

$caseQuery = CaseBuilder::when('status', 'overdue')->then(1)
                    ->when('status', 'due')->then(2)
                    ->else(3);

$invoices = Invoice::query()
            ->orderByRaw($caseQuery->toSql(), $caseQuery->getBindings())
            ->get();
```

### Available methods

```php
use AgliPanci\LaravelCase\Facades\CaseBuilder;

$caseQuery = CaseBuilder::whenRaw('balance = ?', [0])->thenRaw("'Paid'")
                    ->elseRaw("'N/A'");

// Get the SQL representation of the query.
$caseQuery->toSql();

// Get the query bindings.
$caseQuery->getBindings();

// Get the SQL representation of the query with bindings.
$caseQuery->toRaw();

 // Get an Illuminate\Database\Query\Builder instance.
$caseQuery->toQuery();
```

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

### Security

If you discover any security related issues, please email agli.panci@gmail.com instead of using the issue tracker.

## Credits

- [Agli Pançi](https://github.com/aglipanci)
- [Eduard Lleshi](https://github.com/eduardlleshi)
- [All Contributors](https://github.com/aglipanci/laravel-eloquent-case/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
