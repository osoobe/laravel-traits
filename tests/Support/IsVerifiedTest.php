<?php

namespace Osoobe\LaravelTraits\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Osoobe\LaravelTraits\Support\IsVerified;
use Osoobe\LaravelTraits\Tests\TestCase;

class VerifiedModel extends Model
{
    use IsVerified;

    protected $table = 'verified_models';
    protected $guarded = [];
}

class IsVerifiedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('verified_models', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('verified')->default(0);
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::drop('verified_models');
        parent::tearDown();
    }

    // --- scopeVerified ---

    public function test_scope_verified_returns_only_verified_records(): void
    {
        VerifiedModel::create(['verified' => 1, 'name' => 'Alice']);
        VerifiedModel::create(['verified' => 0, 'name' => 'Bob']);
        VerifiedModel::create(['verified' => 1, 'name' => 'Charlie']);

        $results = VerifiedModel::verified()->get();

        $this->assertCount(2, $results);
        $results->each(fn ($m) => $this->assertEquals(1, $m->verified));
    }

    public function test_scope_verified_returns_empty_when_none_verified(): void
    {
        VerifiedModel::create(['verified' => 0]);

        $this->assertCount(0, VerifiedModel::verified()->get());
    }

    // --- scopeNotVerified ---

    public function test_scope_not_verified_returns_only_unverified_records(): void
    {
        VerifiedModel::create(['verified' => 1, 'name' => 'Alice']);
        VerifiedModel::create(['verified' => 0, 'name' => 'Bob']);

        $results = VerifiedModel::notVerified()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Bob', $results->first()->name);
    }

    public function test_scope_not_verified_returns_all_when_none_verified(): void
    {
        VerifiedModel::create(['verified' => 0]);
        VerifiedModel::create(['verified' => 0]);

        $this->assertCount(2, VerifiedModel::notVerified()->get());
    }

    // --- getIsVerifiedAttribute ---

    public function test_is_verified_attribute_returns_true_for_verified(): void
    {
        $model = VerifiedModel::create(['verified' => 1]);

        $this->assertTrue($model->is_verified);
        $this->assertIsBool($model->is_verified);
    }

    public function test_is_verified_attribute_returns_false_for_unverified(): void
    {
        $model = VerifiedModel::create(['verified' => 0]);

        $this->assertFalse($model->is_verified);
        $this->assertIsBool($model->is_verified);
    }

    public function test_is_verified_attribute_casts_integer_to_bool(): void
    {
        $model = new VerifiedModel(['verified' => 1]);

        $this->assertIsBool($model->is_verified);
        $this->assertTrue($model->is_verified);
    }

    public function test_verified_and_not_verified_partition_all_records(): void
    {
        VerifiedModel::create(['verified' => 1]);
        VerifiedModel::create(['verified' => 0]);
        VerifiedModel::create(['verified' => 1]);

        $verifiedCount    = VerifiedModel::verified()->count();
        $notVerifiedCount = VerifiedModel::notVerified()->count();

        $this->assertEquals(3, $verifiedCount + $notVerifiedCount);
    }
}
