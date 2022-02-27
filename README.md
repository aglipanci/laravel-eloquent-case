# Laravel Eloquent CASE Statement Support
![Test Status](https://img.shields.io/github/workflow/status/aglipanci/laravel-eloquent-case/run-tests?label=tests)

This packages adds [CASE](https://dev.mysql.com/doc/refman/5.7/en/flow-control-functions.html#operator_case) statement support to Laravel Query Builder. It supports Laravel 8.x & 9.x.

## Usage

### Add a CASE statement select on a Laravel Query

```php
use App\Models\Invoice;
use AgliPanci\LaravelCase\Query\CaseBuilder;

$invoices = Invoice::query()
            ->case(function (CaseBuilder $case) {
                $case->when('balance', '<', 0)->then('Overpaid')
                    ->when('balance', 0)->then('Paid')
                    ->else('Balance Due');
            }, 'payment_status')
            ->get();
```

Produces the following SQL query:

```mysql
SELECT
  ( CASE
      WHEN `balance` < 0 THEN 'Overpaid'
      WHEN `balance` = 0 THEN 'Paid'
      ELSE 'Balance Due'
    END ) AS `payment_status`
FROM
  `invoices`
```

### Build the case query separately

```php
use App\Models\Invoice;
use AgliPanci\LaravelCase\Facades\CaseBuilder;

$caseQuery = CaseBuilder::when('balance', 0)->then('Paid')
                    ->when('balance', '>', 0)->then('Balance Due');
                    
$invoices = Invoice::query()
            ->case($caseQuery, 'payment_status')
            ->get();
```

### Raw CASE conditions

```php
use App\Models\Invoice;
use AgliPanci\LaravelCase\Facades\CaseBuilder;

$caseQuery = CaseBuilder::whenRaw('balance = ?', [0])->thenRaw("'Paid'")
                    ->elseRaw("'N/A'")
                    
$invoices = Invoice::query()
            ->case($caseQuery, 'payment_status')
            ->get();
```

### Use as raw SELECT

```php
use App\Models\Invoice;
use \AgliPanci\LaravelCase\Facades\CaseBuilder;

$caseQuery = CaseBuilder::whenRaw('balance = ?', [0])->thenRaw("'Paid'")
                    ->elseRaw("'N/A'")
                    
$invoices = Invoice::query()
            ->selectRaw($caseQuery->toRaw())
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

## Installation

You can install the package via composer:

```bash
composer require aglipanci/laravel-eloquent-case
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
- [All Contributors](https://github.com/aglipanci/laravel-case/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
