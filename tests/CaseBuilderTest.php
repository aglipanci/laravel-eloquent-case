<?php

namespace AgliPanci\LaravelCase\Tests;

use AgliPanci\LaravelCase\Exceptions\CaseBuilderException;
use AgliPanci\LaravelCase\Facades\CaseBuilder;
use AgliPanci\LaravelCase\Query\CaseBuilder as QueryCaseBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CaseBuilderTest extends TestCase
{
    protected function wrap(string $value): string
    {
        return DB::query()->getGrammar()->wrap($value);
    }

    protected function wrapTable(string $value): string
    {
        return DB::query()->getGrammar()->wrapTable($value);
    }

    protected function quoteString(string $value): string
    {
        return DB::connection()->getPdo()->quote($value);
    }

    public function test_can_generate_simple_query()
    {
        /**
         * @var QueryCaseBuilder $caseQuery
         */
        $caseQuery = CaseBuilder::when('payment_status', 1)
            ->then('Paid')
            ->else('Due');

        $this->assertCount(1, $caseQuery->whens);
        $this->assertCount(1, $caseQuery->thens);
        $this->assertSameSize($caseQuery->whens, $caseQuery->thens);

        $this->assertEquals('case when '.$this->wrap('payment_status').' = ? then ? else ? end', $caseQuery->toSql());
        $this->assertEquals([1, 'Paid', 'Due'], $caseQuery->getBindings());
        $this->assertCount(3, $caseQuery->getBindings());
        $this->assertEquals(
            'case when '.$this->wrap('payment_status').' = 1 then '.$this->quoteString('Paid').' else '.$this->quoteString('Due').' end',
            $caseQuery->toRaw()
        );
    }

    public function test_can_generate_complex_query()
    {
        /**
         * @var QueryCaseBuilder $caseQuery
         */
        $caseQuery = CaseBuilder::when('payment_status', 1)
            ->then('Paid')
            ->when('payment_status', 2)
            ->then('Due')
            ->when('payment_status', '<=', 5)
            ->then('Canceled')
            ->else('Unknown');

        $this->assertCount(3, $caseQuery->whens);
        $this->assertCount(3, $caseQuery->thens);
        $this->assertSameSize($caseQuery->whens, $caseQuery->thens);
        $this->assertNotEmpty($caseQuery->else);

        $wrappedPaymentStatus = $this->wrap('payment_status');

        $this->assertEquals(
            'case when '.$wrappedPaymentStatus.' = ? then ? when '.$wrappedPaymentStatus.' = ? then ? when '.$wrappedPaymentStatus.' <= ? then ? else ? end',
            $caseQuery->toSql()
        );
        $this->assertEquals([1, 'Paid', 2, 'Due', 5, 'Canceled', 'Unknown'], $caseQuery->getBindings());
        $this->assertCount(7, $caseQuery->getBindings());
        $this->assertEquals(
            'case when '.$wrappedPaymentStatus.' = 1 then '.$this->quoteString('Paid').' when '.$wrappedPaymentStatus.' = 2 then '.$this->quoteString('Due').' when '.$wrappedPaymentStatus.' <= 5 then '.$this->quoteString('Canceled').' else '.$this->quoteString('Unknown').' end',
            $caseQuery->toRaw()
        );
    }

    public function test_can_generate_complex_query_with_nullish_types()
    {
        /**
         * @var QueryCaseBuilder $caseQuery
         */
        $caseQuery = CaseBuilder::when('payment_date', '>', 0)
            ->then('Paid')
            ->when('payment_date', 0)
            ->then('Due')
            ->when('payment_date', null)
            ->then('Canceled')
            ->else('Unknown');

        $this->assertCount(3, $caseQuery->whens);
        $this->assertCount(3, $caseQuery->thens);
        $this->assertSameSize($caseQuery->whens, $caseQuery->thens);
        $this->assertNotEmpty($caseQuery->else);

        $wrappedPaymentDate = $this->wrap('payment_date');

        $this->assertEquals(
            'case when '.$wrappedPaymentDate.' > ? then ? when '.$wrappedPaymentDate.' = ? then ? when '.$wrappedPaymentDate.' IS NULL then ? else ? end',
            $caseQuery->toSql()
        );
        $this->assertEquals([0, 'Paid', 0, 'Due', 'Canceled', 'Unknown'], $caseQuery->getBindings());
        $this->assertCount(6, $caseQuery->getBindings());
        $this->assertEquals(
            'case when '.$wrappedPaymentDate.' > 0 then '.$this->quoteString('Paid').' when '.$wrappedPaymentDate.' = 0 then '.$this->quoteString('Due').' when '.$wrappedPaymentDate.' IS NULL then '.$this->quoteString('Canceled').' else '.$this->quoteString('Unknown').' end',
            $caseQuery->toRaw()
        );
    }

    public function test_can_generate_complex_query_with_dot_separated_columns()
    {
        /**
         * @var QueryCaseBuilder $caseQuery
         */
        $caseQuery = CaseBuilder::when('Invoices.payment_status', 1)
            ->then('Paid')
            ->when('Invoices.payment_status', 2)
            ->then('Due')
            ->when('Invoices.payment_status', '<=', 5)
            ->then('Canceled')
            ->else('Unknown');

        $this->assertCount(3, $caseQuery->whens);
        $this->assertCount(3, $caseQuery->thens);
        $this->assertSameSize($caseQuery->whens, $caseQuery->thens);
        $this->assertNotEmpty($caseQuery->else);

        $wrappedInvoicePaymentStatus = $this->wrap('Invoices.payment_status');

        $this->assertEquals(
            'case when '.$wrappedInvoicePaymentStatus.' = ? then ? when '.$wrappedInvoicePaymentStatus.' = ? then ? when '.$wrappedInvoicePaymentStatus.' <= ? then ? else ? end',
            $caseQuery->toSql()
        );
        $this->assertEquals([1, 'Paid', 2, 'Due', 5, 'Canceled', 'Unknown'], $caseQuery->getBindings());
        $this->assertCount(7, $caseQuery->getBindings());
        $this->assertEquals(
            'case when '.$wrappedInvoicePaymentStatus.' = 1 then '.$this->quoteString('Paid').' when '.$wrappedInvoicePaymentStatus.' = 2 then '.$this->quoteString('Due').' when '.$wrappedInvoicePaymentStatus.' <= 5 then '.$this->quoteString('Canceled').' else '.$this->quoteString('Unknown').' end',
            $caseQuery->toRaw()
        );
    }

    public function test_can_use_raw_queries()
    {
        /**
         * @var QueryCaseBuilder $caseQuery
         */
        $caseQuery = CaseBuilder::whenRaw('payment_status IN (1,2,3)')
            ->thenRaw('Paid')
            ->whenRaw('payment_status >= 4')
            ->then('Due')
            ->else('Unknown');

        $this->assertCount(2, $caseQuery->whens);
        $this->assertCount(2, $caseQuery->thens);
        $this->assertSameSize($caseQuery->whens, $caseQuery->thens);
        $this->assertNotEmpty($caseQuery->else);

        $this->assertEquals('case when payment_status IN (1,2,3) then Paid when payment_status >= 4 then ? else ? end', $caseQuery->toSql());
        $this->assertEquals(['Due', 'Unknown'], $caseQuery->getBindings());
        $this->assertCount(2, $caseQuery->getBindings());
        $this->assertEquals(
            'case when payment_status IN (1,2,3) then Paid when payment_status >= 4 then '.$this->quoteString('Due').' else '.$this->quoteString('Unknown').' end',
            $caseQuery->toRaw()
        );
    }

    public function test_can_generate_raw_cases()
    {
        /**
         * @var QueryCaseBuilder $caseQuery
         */
        $caseQuery = CaseBuilder::caseRaw('count(id)')
            ->whenRaw(1)
            ->then(0)
            ->else(100);

        $this->assertCount(1, $caseQuery->whens);
        $this->assertCount(1, $caseQuery->thens);
        $this->assertSameSize($caseQuery->whens, $caseQuery->thens);

        $this->assertEquals('case count(id) when 1 then 0 else 100 end', $caseQuery->toRaw());
    }

    public function test_can_generate_simple_case_query()
    {
        /**
         * @var QueryCaseBuilder $caseQuery
         */
        $caseQuery = CaseBuilder::case('payment_status')
            ->when(1)
            ->then('Paid')
            ->else('Due');

        $this->assertEquals('case '.$this->wrap('payment_status').' when ? then ? else ? end', $caseQuery->toSql());
        $this->assertEquals([1, 'Paid', 'Due'], $caseQuery->getBindings());
        $this->assertEquals(
            'case '.$this->wrap('payment_status').' when 1 then '.$this->quoteString('Paid').' else '.$this->quoteString('Due').' end',
            $caseQuery->toRaw()
        );
    }

    public function test_can_compare_columns_in_when()
    {
        /**
         * @var QueryCaseBuilder $caseQuery
         */
        $caseQuery = CaseBuilder::whenColumn('amount_paid', 'amount_due')
            ->then('Paid')
            ->whenColumn('amount_paid', '>', 'amount_due')
            ->then('Overpaid')
            ->else('Due');

        $this->assertEquals(
            'case when '.$this->wrap('amount_paid').' = '.$this->wrap('amount_due').' then ? when '.$this->wrap('amount_paid').' > '.$this->wrap('amount_due').' then ? else ? end',
            $caseQuery->toSql()
        );
        $this->assertEquals(['Paid', 'Overpaid', 'Due'], $caseQuery->getBindings());
    }

    public function test_can_use_columns_as_then_and_else_results()
    {
        /**
         * @var QueryCaseBuilder $caseQuery
         */
        $caseQuery = CaseBuilder::when('discounted', 1)
            ->thenColumn('discounted_price')
            ->else('N/A');

        $this->assertEquals(
            'case when '.$this->wrap('discounted').' = ? then '.$this->wrap('discounted_price').' else ? end',
            $caseQuery->toSql()
        );
        $this->assertEquals([1, 'N/A'], $caseQuery->getBindings());

        /**
         * @var QueryCaseBuilder $caseQuery
         */
        $caseQuery = CaseBuilder::when('discounted', 1)
            ->then('Discounted')
            ->elseColumn('full_price');

        $this->assertEquals(
            'case when '.$this->wrap('discounted').' = ? then ? else '.$this->wrap('full_price').' end',
            $caseQuery->toSql()
        );
        $this->assertEquals([1, 'Discounted'], $caseQuery->getBindings());
    }

    public function test_can_wrap_case_in_aggregate_functions()
    {
        $wrappedBalance = $this->wrap('balance');

        $this->assertEquals(
            'sum(case when '.$wrappedBalance.' > ? then ? else ? end)',
            CaseBuilder::when('balance', '>', 0)->then(1)->else(0)->sum()->toSql()
        );

        $this->assertEquals(
            'count(case when '.$wrappedBalance.' > ? then ? else ? end)',
            CaseBuilder::when('balance', '>', 0)->then(1)->else(0)->count()->toSql()
        );

        $this->assertEquals(
            'avg(case when '.$wrappedBalance.' > ? then ? else ? end)',
            CaseBuilder::when('balance', '>', 0)->then(1)->else(0)->avg()->toSql()
        );

        $this->assertEquals(
            'min(case when '.$wrappedBalance.' > ? then ? else ? end)',
            CaseBuilder::when('balance', '>', 0)->then(1)->else(0)->min()->toSql()
        );

        $this->assertEquals(
            'max(case when '.$wrappedBalance.' > ? then ? else ? end)',
            CaseBuilder::when('balance', '>', 0)->then(1)->else(0)->max()->toSql()
        );
    }

    public function test_to_raw_handles_question_marks_in_bindings()
    {
        /**
         * @var QueryCaseBuilder $caseQuery
         */
        $caseQuery = CaseBuilder::when('payment_status', 1)
            ->then('Paid?')
            ->else('Due');

        $this->assertEquals(
            'case when '.$this->wrap('payment_status').' = 1 then '.$this->quoteString('Paid?').' else '.$this->quoteString('Due').' end',
            $caseQuery->toRaw()
        );
    }

    public function test_throws_else_is_present()
    {
        $this->expectException(CaseBuilderException::class);
        $this->expectExceptionMessage('ELSE statement is already present. The CASE statement can have only one ELSE.');

        CaseBuilder::when('payment_status', 1)
            ->then('Paid')
            ->else('Due')
            ->else('Unknown');
    }

    public function test_throws_else_is_present_with_falsy_raw_else()
    {
        $this->expectException(CaseBuilderException::class);
        $this->expectExceptionMessage('ELSE statement is already present. The CASE statement can have only one ELSE.');

        CaseBuilder::when('payment_status', 1)
            ->then('Paid')
            ->elseRaw('0')
            ->else('Unknown');
    }

    public function test_throws_no_conditions_present()
    {
        $this->expectException(CaseBuilderException::class);
        $this->expectExceptionMessage('The CASE statement must have at least one WHEN/THEN condition.');

        /**
         * @var QueryCaseBuilder $caseQuery
         */
        $caseQuery = CaseBuilder::when('payment_status', 1);
        $caseQuery->toSql();
    }

    public function test_throws_number_of_conditions_not_matching()
    {
        $this->expectException(CaseBuilderException::class);
        $this->expectExceptionMessage('The CASE statement must have a matching number of WHEN/THEN conditions.');

        /**
         * @var QueryCaseBuilder $caseQuery
         */
        $caseQuery = CaseBuilder::when('payment_status', 1)->then('Paid')->when('payment_status', 2);
        $caseQuery->toSql();
    }

    public function test_throws_subject_must_be_present_when_case_operator_not_used()
    {
        $this->expectException(CaseBuilderException::class);
        $this->expectExceptionMessage('The CASE statement subject must be present when operator and column are not present.');

        CaseBuilder::when('payment_status')
            ->then('Paid');
    }

    public function test_throws_then_cannot_be_before_when()
    {
        $this->expectException(CaseBuilderException::class);
        $this->expectExceptionMessage('THEN cannot be before WHEN on a CASE statement.');

        CaseBuilder::then('Paid')
            ->when('payment_status', 1);
    }

    public function test_throws_else_can_only_be_after_a_when_then()
    {
        $this->expectException(CaseBuilderException::class);
        $this->expectExceptionMessage('ELSE can only be set after a WHEN/THEN in a CASE statement.');

        CaseBuilder::else('Unknown')
            ->when('payment_status', 1)
            ->then('Due');
    }

    public function test_throws_else_can_only_be_after_a_when_then_middle()
    {
        $this->expectException(CaseBuilderException::class);
        $this->expectExceptionMessage('ELSE can only be set after a WHEN/THEN in a CASE statement.');

        CaseBuilder::when('payment_status', 1)
            ->else('Unknown')
            ->then('Due');
    }

    public function test_throws_wrong_when_position()
    {
        $this->expectException(CaseBuilderException::class);
        $this->expectExceptionMessage('Wrong WHEN position.');

        CaseBuilder::when('payment_status', 1)
            ->then('Paid')
            ->when('payment_status', 2)
            ->when('payment_status', 3);
    }

    public function test_to_query_returns_query_builder()
    {
        /**
         * @var QueryCaseBuilder $caseQuery
         */
        $caseQuery = CaseBuilder::when('payment_status', 1)
            ->then('Paid');

        $this->assertInstanceOf(Builder::class, $caseQuery->toQuery());
    }

    public function test_with_query_builder()
    {
        $query = DB::table('invoices')
            ->where('active', true)
            ->case(function (QueryCaseBuilder $caseBuilder) {
                $caseBuilder->when('payment_status', 1)
                    ->then('Paid')
                    ->when('payment_status', 2)
                    ->then('Due')
                    ->when('payment_status', '<=', 5)
                    ->then('Canceled')
                    ->else('Unknown');
            }, 'payment_status')
            ->where('subscription', 'premium');

        $this->assertEquals(
            'select (case when '.$this->wrap('payment_status').' = ? then ? when '.$this->wrap('payment_status').' = ? then ? when '.$this->wrap('payment_status').' <= ? then ? else ? end) as '.$this->wrap('payment_status').' from '.$this->wrapTable('invoices').' where '.$this->wrap('active').' = ? and '.$this->wrap('subscription').' = ?',
            $query->toSql()
        );
        $this->assertCount(9, $query->getBindings());
    }
}
