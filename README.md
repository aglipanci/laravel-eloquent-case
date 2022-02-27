# Laravel Case Statement Support
![Test Status](https://img.shields.io/github/workflow/status/aglipanci/laravel-case/run-tests?label=tests)

This packages adds [CASE](https://dev.mysql.com/doc/refman/5.7/en/flow-control-functions.html#operator_case) statement support to Laravel Query Builder. It supports Laravel 8 & 9.

## Usage

### Add a CASE statement select on a Laravel Query

```php
use App\Models\User;
use \AgliPanci\LaravelCase\Query\CaseBuilder;

$users = User::query()
            ->case(function (CaseBuilder $case) {
                $case->when('payment_status', 1)
                    ->then('Paid')
                    ->when('payment_status', 2)
                    ->then('In Process')
                    ->else('Pending');
            }, 'payment_status')
            ->get();
```

Produces the following SQL query:

```mysql
select (case when `payment_status` = 1 then 'Paid' when `payment_status` = 2 then 'In Process' else 'Pending' end) as `payment_status` from `users` where `users`.`deleted_at` is null
```

### Build the case query separately

```php
use \AgliPanci\LaravelCase\Facades\CaseBuilder;

$caseQuery = CaseBuilder::when('payment_status', 1)
                    ->then('Paid')
                    ->when('payment_status', '>' ,2)
                    ->then('In Process');
                    
$users = User::query()
            ->case($caseQuery, 'payment_status')
            ->get();
```

### Raw CASE conditions

```php
use \AgliPanci\LaravelCase\Facades\CaseBuilder;

$caseQuery = CaseBuilder::whenRaw('payment_status = ?', [1])
                    ->thenRaw("'Paid'")
                    ->elseRaw("'N/A'")
                    
$users = User::query()
            ->case($caseQuery, 'payment_status')
            ->get();
```

### Use as raw SELECT

```php
use \AgliPanci\LaravelCase\Facades\CaseBuilder;

$caseQuery = CaseBuilder::whenRaw('payment_status = ?', [1])
                    ->thenRaw("'Paid'")
                    ->elseRaw("'N/A'")
                    
$users = User::query()
            ->selectRaw($caseQuery->toRaw())
            ->get();
```

### Available methods

```php
use \AgliPanci\LaravelCase\Facades\CaseBuilder;

$caseQuery = CaseBuilder::whenRaw('payment_status = ?', [1])
                    ->thenRaw("'Paid'")
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
composer require aglipanci/laravel-case
```

### Testing

```bash
composer test
```

### Security

If you discover any security related issues, please email agli.panci@gmail.com instead of using the issue tracker.

## Credits

- [Agli Pançi](https://github.com/aglipanci)
- [Eduard Lleshi](https://github.com/eduardlleshi)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
