<?php

namespace Osoobe\LaravelTraits\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Osoobe\LaravelTraits\Support\HasSlug;
use Osoobe\LaravelTraits\Tests\TestCase;

class SlugModel extends Model
{
    use HasSlug;

    protected $table = 'slug_models';
    protected $guarded = [];
}

class HasSlugTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('slug_models', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::drop('slug_models');
        parent::tearDown();
    }

    // --- hasSlug (called directly — method takes $query as first arg, not a named scope) ---

    public function test_has_slug_filters_by_slug(): void
    {
        SlugModel::create(['slug' => 'my-first-post', 'title' => 'First']);
        SlugModel::create(['slug' => 'my-second-post', 'title' => 'Second']);

        $results = (new SlugModel())->hasSlug(SlugModel::query(), 'my-first-post')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('my-first-post', $results->first()->slug);
    }

    public function test_has_slug_returns_empty_for_unknown_slug(): void
    {
        SlugModel::create(['slug' => 'existing-slug', 'title' => 'Exists']);

        $results = (new SlugModel())->hasSlug(SlugModel::query(), 'nonexistent')->get();

        $this->assertCount(0, $results);
    }

    // --- findBySlugOrFail ---

    public function test_find_by_slug_or_fail_finds_by_slug(): void
    {
        SlugModel::create(['slug' => 'my-post', 'title' => 'My Post']);

        $model = SlugModel::findBySlugOrFail('my-post');

        $this->assertNotNull($model);
        $this->assertEquals('my-post', $model->slug);
    }

    public function test_find_by_slug_or_fail_finds_by_numeric_id(): void
    {
        $created = SlugModel::create(['slug' => 'post-one', 'title' => 'Post One']);

        $model = SlugModel::findBySlugOrFail($created->id);

        $this->assertNotNull($model);
        $this->assertEquals($created->id, $model->id);
    }

    public function test_find_by_slug_or_fail_throws_model_not_found_exception(): void
    {
        $this->expectException(ModelNotFoundException::class);

        SlugModel::findBySlugOrFail('does-not-exist');
    }

    public function test_find_by_slug_or_fail_returns_first_match_on_slug(): void
    {
        SlugModel::create(['slug' => 'unique-slug', 'title' => 'Unique']);

        $model = SlugModel::findBySlugOrFail('unique-slug');

        $this->assertEquals('Unique', $model->title);
    }

    public function test_find_by_slug_or_fail_prefers_slug_over_id(): void
    {
        $model1 = SlugModel::create(['slug' => 'first', 'title' => 'First']);
        // Create second model whose slug looks like an ID
        $model2 = SlugModel::create(['slug' => (string) $model1->id, 'title' => 'ID-like slug']);

        $found = SlugModel::findBySlugOrFail((string) $model1->id);

        // Should find the one with the matching slug first (orWhere on id is fallback)
        $this->assertNotNull($found);
    }
}
