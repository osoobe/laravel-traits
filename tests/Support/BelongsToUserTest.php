<?php

namespace Osoobe\LaravelTraits\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Osoobe\LaravelTraits\Support\BelongsToUser;
use Osoobe\LaravelTraits\Tests\TestCase;

class UserOwnedModel extends Model
{
    use BelongsToUser;

    protected $table = 'user_owned_models';
    protected $guarded = [];
}

class BelongsToUserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('user_owned_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::drop('user_owned_models');
        Schema::drop('users');
        parent::tearDown();
    }

    public function test_user_method_returns_belongs_to_relation(): void
    {
        $model = new UserOwnedModel();

        $this->assertInstanceOf(BelongsTo::class, $model->user());
    }

    public function test_user_relation_uses_user_id_foreign_key(): void
    {
        $model = new UserOwnedModel();
        $relation = $model->user();

        $this->assertEquals('user_id', $relation->getForeignKeyName());
    }

    public function test_user_relation_points_to_user_model(): void
    {
        $model = new UserOwnedModel();
        $relation = $model->user();

        $this->assertStringContainsString('User', get_class($relation->getRelated()));
    }

    public function test_user_relation_returns_null_when_no_user_associated(): void
    {
        $model = UserOwnedModel::create(['title' => 'orphan', 'user_id' => null]);

        $this->assertNull($model->user);
    }
}
