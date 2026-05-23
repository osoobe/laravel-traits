<?php

namespace Osoobe\LaravelTraits\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Osoobe\LaravelTraits\Support\Active;
use Osoobe\LaravelTraits\Tests\TestCase;

class ActiveModel extends Model
{
    use Active;

    protected $table = 'active_models';
    protected $guarded = [];
}

class ActiveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('active_models', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(false);
            $table->boolean('hidden')->default(false);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::drop('active_models');
        parent::tearDown();
    }

    public function test_scope_active_returns_only_active_records(): void
    {
        ActiveModel::create(['is_active' => true, 'hidden' => false]);
        ActiveModel::create(['is_active' => false, 'hidden' => false]);
        ActiveModel::create(['is_active' => true, 'hidden' => true]);

        $results = ActiveModel::active()->get();

        $this->assertCount(2, $results);
        $results->each(fn ($m) => $this->assertEquals(1, $m->is_active));
    }

    public function test_scope_active_returns_empty_when_no_active_records(): void
    {
        ActiveModel::create(['is_active' => false]);
        ActiveModel::create(['is_active' => false]);

        $this->assertCount(0, ActiveModel::active()->get());
    }

    public function test_scope_not_active_returns_only_inactive_records(): void
    {
        ActiveModel::create(['is_active' => true]);
        ActiveModel::create(['is_active' => false]);
        ActiveModel::create(['is_active' => false]);

        $results = ActiveModel::notActive()->get();

        $this->assertCount(2, $results);
        $results->each(fn ($m) => $this->assertNotEquals(1, $m->is_active));
    }

    public function test_scope_hidden_returns_only_hidden_records(): void
    {
        ActiveModel::create(['hidden' => true]);
        ActiveModel::create(['hidden' => false]);
        ActiveModel::create(['hidden' => true]);

        $results = ActiveModel::hidden()->get();

        $this->assertCount(2, $results);
        $results->each(fn ($m) => $this->assertEquals(1, $m->hidden));
    }

    public function test_scope_hidden_returns_empty_when_no_hidden_records(): void
    {
        ActiveModel::create(['hidden' => false]);

        $this->assertCount(0, ActiveModel::hidden()->get());
    }

    public function test_active_and_not_hidden_can_be_chained(): void
    {
        ActiveModel::create(['is_active' => true, 'hidden' => false]);
        ActiveModel::create(['is_active' => true, 'hidden' => true]);
        ActiveModel::create(['is_active' => false, 'hidden' => false]);

        $results = ActiveModel::active()->where('hidden', false)->get();

        $this->assertCount(1, $results);
        $this->assertEquals(1, $results->first()->is_active);
        $this->assertEquals(0, $results->first()->hidden);
    }

    public function test_scope_active_returns_builder(): void
    {
        $builder = ActiveModel::active();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Builder::class,
            $builder
        );
    }
}
