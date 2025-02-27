<?php

namespace AgliPanci\LaravelCase\Tests;

use AgliPanci\LaravelCase\Exceptions\CaseBuilderException;
use AgliPanci\LaravelCase\Facades\CaseBuilder;
use AgliPanci\LaravelCase\Query\CaseBuilder as QueryCaseBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CaseBuilderTest extends TestCase
{
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

        $this->assertEquals('case when `payment_status` = ? then ? else ? end', $caseQuery->toSql());
        $this->assertEquals([1, 'Paid', 'Due'], $caseQuery->getBindings());
        $this->assertCount(3, $caseQuery->getBindings());
        $this->assertEquals('case when `payment_status` = 1 then "Paid" else "Due" end', $caseQuery->toRaw());
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

        $this->assertEquals('case when `payment_status` = ? then ? when `payment_status` = ? then ? when `payment_status` <= ? then ? else ? end', $caseQuery->toSql());
        $this->assertEquals([1, 'Paid', 2, 'Due', 5, 'Canceled', 'Unknown'], $caseQuery->getBindings());
        $this->assertCount(7, $caseQuery->getBindings());
        $this->assertEquals('case when `payment_status` = 1 then "Paid" when `payment_status` = 2 then "Due" when `payment_status` <= 5 then "Canceled" else "Unknown" end', $caseQuery->toRaw());
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

        $this->assertEquals('case when `payment_date` > ? then ? when `payment_date` = ? then ? when `payment_date` IS NULL then ? else ? end', $caseQuery->toSql());
        $this->assertEquals([0, 'Paid', 0, 'Due', 'Canceled', 'Unknown'], $caseQuery->getBindings());
        $this->assertCount(6, $caseQuery->getBindings());
        $this->assertEquals('case when `payment_date` > 0 then "Paid" when `payment_date` = 0 then "Due" when `payment_date` IS NULL then "Canceled" else "Unknown" end', $caseQuery->toRaw());
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

        $this->assertEquals('case when `Invoices`.`payment_status` = ? then ? when `Invoices`.`payment_status` = ? then ? when `Invoices`.`payment_status` <= ? then ? else ? end', $caseQuery->toSql());
        $this->assertEquals([1, 'Paid', 2, 'Due', 5, 'Canceled', 'Unknown'], $caseQuery->getBindings());
        $this->assertCount(7, $caseQuery->getBindings());
        $this->assertEquals('case when `Invoices`.`payment_status` = 1 then "Paid" when `Invoices`.`payment_status` = 2 then "Due" when `Invoices`.`payment_status` <= 5 then "Canceled" else "Unknown" end', $caseQuery->toRaw());
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
        $this->assertEquals('case when payment_status IN (1,2,3) then Paid when payment_status >= 4 then "Due" else "Unknown" end', $caseQuery->toRaw());
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

    public function test_throws_else_is_present()
    {
        $this->expectException(CaseBuilderException::class);
        $this->expectExceptionMessage('ELSE statement is already present. The CASE statement can have only one ELSE.');

        CaseBuilder::when('payment_status', 1)
            ->then('Paid')
            ->else('Due')
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

        $this->assertEquals('select (case when `payment_status` = ? then ? when `payment_status` = ? then ? when `payment_status` <= ? then ? else ? end) as `payment_status` from `invoices` where `active` = ? and `subscription` = ?', $query->toSql());
        $this->assertCount(9, $query->getBindings());
    }
}
