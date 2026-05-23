<?php

namespace Osoobe\LaravelTraits\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Osoobe\LaravelTraits\Support\AdvanceWhereQuery;
use Osoobe\LaravelTraits\Tests\TestCase;

class WhereQueryModel extends Model
{
    use AdvanceWhereQuery;

    protected $table = 'where_query_models';
    protected $guarded = [];
}

class AdvanceWhereQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('where_query_models', function (Blueprint $table) {
            $table->id();
            $table->string('category')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        WhereQueryModel::create(['category' => 'tech', 'status' => 'open']);
        WhereQueryModel::create(['category' => 'health', 'status' => 'closed']);
        WhereQueryModel::create(['category' => 'tech', 'status' => 'open']);
    }

    protected function tearDown(): void
    {
        Schema::drop('where_query_models');
        parent::tearDown();
    }

    public function test_scope_where_key_or_null_filters_when_value_is_provided(): void
    {
        $results = WhereQueryModel::whereKeyOrNull('category', 'tech')->get();

        $this->assertCount(2, $results);
        $results->each(fn ($m) => $this->assertEquals('tech', $m->category));
    }

    public function test_scope_where_key_or_null_returns_all_when_value_is_null(): void
    {
        $results = WhereQueryModel::whereKeyOrNull('category', null)->get();

        $this->assertCount(3, $results);
    }

    public function test_scope_where_key_or_null_returns_all_when_value_is_empty_string(): void
    {
        $results = WhereQueryModel::whereKeyOrNull('category', '')->get();

        $this->assertCount(3, $results);
    }

    public function test_scope_where_key_or_null_returns_all_when_value_is_zero(): void
    {
        $results = WhereQueryModel::whereKeyOrNull('category', 0)->get();

        $this->assertCount(3, $results);
    }

    public function test_scope_where_key_or_null_can_be_chained_with_other_scopes(): void
    {
        $results = WhereQueryModel::whereKeyOrNull('category', 'tech')
            ->whereKeyOrNull('status', 'open')
            ->get();

        $this->assertCount(2, $results);
    }

    public function test_scope_where_key_or_null_with_both_null_returns_all(): void
    {
        $results = WhereQueryModel::whereKeyOrNull('category', null)
            ->whereKeyOrNull('status', null)
            ->get();

        $this->assertCount(3, $results);
    }

    public function test_scope_where_key_or_null_returns_builder(): void
    {
        $builder = WhereQueryModel::whereKeyOrNull('category', 'tech');

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Builder::class,
            $builder
        );
    }
}
