<?php

namespace Osoobe\LaravelTraits\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Osoobe\LaravelTraits\Support\IsDefault;
use Osoobe\LaravelTraits\Tests\TestCase;

class DefaultModel extends Model
{
    use IsDefault;

    protected $table = 'default_models';
    protected $guarded = [];
}

class IsDefaultTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('default_models', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_default')->default(false);
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::drop('default_models');
        parent::tearDown();
    }

    // --- scopeIsDefault ---

    public function test_scope_is_default_returns_only_default_records(): void
    {
        DefaultModel::create(['is_default' => true, 'name' => 'Primary']);
        DefaultModel::create(['is_default' => false, 'name' => 'Secondary']);
        DefaultModel::create(['is_default' => false, 'name' => 'Tertiary']);

        $results = DefaultModel::isDefault()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Primary', $results->first()->name);
    }

    public function test_scope_is_default_returns_empty_when_none_set(): void
    {
        DefaultModel::create(['is_default' => false]);
        DefaultModel::create(['is_default' => false]);

        $this->assertCount(0, DefaultModel::isDefault()->get());
    }

    public function test_scope_is_default_returns_multiple_defaults(): void
    {
        DefaultModel::create(['is_default' => true, 'name' => 'A']);
        DefaultModel::create(['is_default' => true, 'name' => 'B']);
        DefaultModel::create(['is_default' => false, 'name' => 'C']);

        $this->assertCount(2, DefaultModel::isDefault()->get());
    }

    // --- scopeNotDefault ---

    public function test_scope_not_default_returns_non_default_records(): void
    {
        DefaultModel::create(['is_default' => true, 'name' => 'Primary']);
        DefaultModel::create(['is_default' => false, 'name' => 'Secondary']);
        DefaultModel::create(['is_default' => false, 'name' => 'Tertiary']);

        $results = DefaultModel::notDefault()->get();

        $this->assertCount(2, $results);
        $results->each(fn ($m) => $this->assertNotEquals(1, $m->is_default));
    }

    public function test_scope_not_default_returns_all_when_none_is_default(): void
    {
        DefaultModel::create(['is_default' => false]);
        DefaultModel::create(['is_default' => false]);

        $this->assertCount(2, DefaultModel::notDefault()->get());
    }

    public function test_scope_is_default_and_not_default_are_mutually_exclusive(): void
    {
        DefaultModel::create(['is_default' => true]);
        DefaultModel::create(['is_default' => false]);

        $defaultCount    = DefaultModel::isDefault()->count();
        $notDefaultCount = DefaultModel::notDefault()->count();

        $this->assertEquals(2, $defaultCount + $notDefaultCount);
    }
}
