# Laravel Case Statement Support

This packages adds MySQL `CASE` statement support to Laravel Query Builder.

## Basic usage

### Add a CASE statement select on a Laravel Query:

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

### Build the case query separately:

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

### Raw conditions:

```php
use \AgliPanci\LaravelCase\Facades\CaseBuilder;

$caseQuery = CaseBuilder::whenRaw('payment_status = ?', [1])
                    ->thenRaw("'Paid'")
                    ->elseRaw("'N/A'")
                    
$users = User::query()
            ->case($caseQuery, 'payment_status')
            ->get();
```

### Available methods

```php
use \AgliPanci\LaravelCase\Facades\CaseBuilder;

$caseQuery = CaseBuilder::whenRaw('payment_status = ?', [1])
                    ->thenRaw("'Paid'")
                    ->elseRaw("'N/A'")
                    
$caseQuery->toSql(); // Get the SQL representation of the query.
$caseQuery->getBindings(); // Get the query bindings.
$caseQuery->toRaw(); // Get the SQL representation of the query with bindings.
$caseQuery->toQuery(); // Get a Illuminate\Database\Query\Builder instance.
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

- [Agli Panci](https://github.com/aglipanci)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
