<?php

namespace Osoobe\LaravelTraits\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Osoobe\LaravelTraits\Support\HasLang;
use Osoobe\LaravelTraits\Tests\TestCase;

class LangModel extends Model
{
    use HasLang;

    protected $table = 'lang_models';
    protected $guarded = [];
}

class HasLangTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('lang_models', function (Blueprint $table) {
            $table->id();
            $table->string('lang', 10)->default('en');
            $table->string('title')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::drop('lang_models');
        parent::tearDown();
    }

    // --- scopeLang ---

    public function test_scope_lang_filters_by_current_locale(): void
    {
        App::setLocale('en');

        LangModel::create(['lang' => 'en', 'title' => 'English']);
        LangModel::create(['lang' => 'fr', 'title' => 'French']);
        LangModel::create(['lang' => 'es', 'title' => 'Spanish']);

        $results = LangModel::lang()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('English', $results->first()->title);
    }

    public function test_scope_lang_changes_with_locale(): void
    {
        LangModel::create(['lang' => 'en', 'title' => 'English']);
        LangModel::create(['lang' => 'fr', 'title' => 'French']);

        App::setLocale('fr');
        $results = LangModel::lang()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('French', $results->first()->title);
    }

    public function test_scope_lang_returns_empty_when_no_match(): void
    {
        App::setLocale('de');
        LangModel::create(['lang' => 'en', 'title' => 'English']);

        $this->assertCount(0, LangModel::lang()->get());
    }

    // --- scopeLangEN ---

    public function test_scope_lang_en_returns_only_english_records(): void
    {
        LangModel::create(['lang' => 'en', 'title' => 'English 1']);
        LangModel::create(['lang' => 'en', 'title' => 'English 2']);
        LangModel::create(['lang' => 'fr', 'title' => 'French']);

        $results = LangModel::langEN()->get();

        $this->assertCount(2, $results);
        $results->each(fn ($m) => $this->assertEquals('en', $m->lang));
    }

    public function test_scope_lang_en_returns_empty_when_no_english_records(): void
    {
        LangModel::create(['lang' => 'fr', 'title' => 'French']);

        $this->assertCount(0, LangModel::langEN()->get());
    }

    public function test_scope_lang_en_is_not_affected_by_current_locale(): void
    {
        App::setLocale('fr');
        LangModel::create(['lang' => 'en', 'title' => 'English']);

        $results = LangModel::langEN()->get();

        $this->assertCount(1, $results);
    }

    // --- language() relationship ---

    public function test_language_method_returns_has_one_relation(): void
    {
        $model = new LangModel();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasOne::class,
            $model->language()
        );
    }
}
