<?php

namespace Osoobe\LaravelTraits\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Osoobe\LaravelTraits\Support\Userstamp;
use Osoobe\LaravelTraits\Tests\TestCase;

class StampUser extends Authenticatable
{
    protected $table = 'stamp_users';
    protected $guarded = [];
    public $timestamps = false;
}

class StampedModel extends Model
{
    use Userstamp;

    protected $table = 'stamped_models';
    protected $guarded = [];
}

class UserstampTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('stamp_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->default('secret');
        });

        Schema::create('stamped_models', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->nullableMorphs('creator');
            $table->nullableMorphs('editor');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Auth::logout();
        Schema::drop('stamped_models');
        Schema::drop('stamp_users');
        parent::tearDown();
    }

    // --- Boot / automatic stamping ---

    public function test_creator_and_editor_are_stamped_on_create(): void
    {
        $user = StampUser::create(['name' => 'Alice', 'email' => 'alice@test.com']);
        $this->actingAs($user);

        $model = StampedModel::create(['title' => 'test']);

        $this->assertEquals($user->id, $model->creator_id);
        $this->assertEquals(get_class($user), $model->creator_type);
        $this->assertEquals($user->id, $model->editor_id);
        $this->assertEquals(get_class($user), $model->editor_type);
    }

    public function test_editor_is_updated_on_update(): void
    {
        $alice = StampUser::create(['name' => 'Alice', 'email' => 'alice@test.com']);
        $bob   = StampUser::create(['name' => 'Bob', 'email' => 'bob@test.com']);

        $this->actingAs($alice);
        $model = StampedModel::create(['title' => 'original']);

        $this->actingAs($bob);
        $model->update(['title' => 'updated']);

        $fresh = StampedModel::find($model->id);
        $this->assertEquals($alice->id, $fresh->creator_id);
        $this->assertEquals($bob->id, $fresh->editor_id);
    }

    public function test_no_stamps_set_when_not_authenticated(): void
    {
        Auth::logout();

        $model = StampedModel::create(['title' => 'anonymous']);

        $this->assertNull($model->creator_id);
        $this->assertNull($model->editor_id);
    }

    // --- Relationships ---

    public function test_creator_method_returns_morph_to_relation(): void
    {
        $model = new StampedModel();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\MorphTo::class,
            $model->creator()
        );
    }

    public function test_editor_method_returns_morph_to_relation(): void
    {
        $model = new StampedModel();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\MorphTo::class,
            $model->editor()
        );
    }

    public function test_creator_relationship_loads_correct_user(): void
    {
        $user = StampUser::create(['name' => 'Alice', 'email' => 'alice@test.com']);
        $this->actingAs($user);

        $model = StampedModel::create(['title' => 'post']);

        $loaded = StampedModel::with('creator')->find($model->id);
        $this->assertNotNull($loaded->creator);
        $this->assertEquals($user->id, $loaded->creator->id);
    }

    public function test_editor_relationship_loads_correct_user(): void
    {
        $user = StampUser::create(['name' => 'Bob', 'email' => 'bob@test.com']);
        $this->actingAs($user);

        $model = StampedModel::create(['title' => 'post']);

        $loaded = StampedModel::with('editor')->find($model->id);
        $this->assertNotNull($loaded->editor);
        $this->assertEquals($user->id, $loaded->editor->id);
    }
}
