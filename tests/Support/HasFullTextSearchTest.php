<?php

namespace Osoobe\LaravelTraits\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Osoobe\LaravelTraits\Support\HasFullTextSearch;
use Osoobe\LaravelTraits\Tests\TestCase;

class SearchModel extends Model
{
    use HasFullTextSearch;

    protected $table = 'search_models';
}

class HasFullTextSearchTest extends TestCase
{
    // buildFullTextSearch and selectFTSScore are pure string builders —
    // no database required. scopeFullTextSearch requires MySQL FULLTEXT
    // indexes and cannot be tested with SQLite.

    // --- buildFullTextSearch ---

    public function test_build_full_text_search_generates_match_against_sql(): void
    {
        $sql = SearchModel::buildFullTextSearch(['title', 'body'], 'laravel');

        $this->assertStringContainsString('MATCH', $sql);
        $this->assertStringContainsString('AGAINST', $sql);
        $this->assertStringContainsString('title', $sql);
        $this->assertStringContainsString('body', $sql);
        $this->assertStringContainsString('laravel', $sql);
    }

    public function test_build_full_text_search_without_boolean_mode(): void
    {
        $sql = SearchModel::buildFullTextSearch(['title'], 'keyword', false);

        $this->assertStringNotContainsString('IN BOOLEAN MODE', $sql);
    }

    public function test_build_full_text_search_with_boolean_mode(): void
    {
        $sql = SearchModel::buildFullTextSearch(['title'], 'keyword', true);

        $this->assertStringContainsString('IN BOOLEAN MODE', $sql);
    }

    public function test_build_full_text_search_sanitizes_special_characters(): void
    {
        $sql = SearchModel::buildFullTextSearch(['title'], 'hello!@#$%^&*()world');

        // Special chars should be replaced with spaces
        $this->assertStringNotContainsString('!', $sql);
        $this->assertStringNotContainsString('@', $sql);
        $this->assertStringNotContainsString('#', $sql);
        $this->assertStringContainsString('hello', $sql);
        $this->assertStringContainsString('world', $sql);
    }

    public function test_build_full_text_search_with_multiple_columns(): void
    {
        $sql = SearchModel::buildFullTextSearch(['title', 'body', 'summary'], 'test');

        $this->assertStringContainsString('title,body,summary', $sql);
    }

    public function test_build_full_text_search_with_single_column(): void
    {
        $sql = SearchModel::buildFullTextSearch(['title'], 'test');

        $this->assertStringContainsString('(title)', $sql);
    }

    public function test_build_full_text_search_with_alphanumeric_text(): void
    {
        $sql = SearchModel::buildFullTextSearch(['title'], 'laravel10 PHP83');

        $this->assertStringContainsString('laravel10', $sql);
        $this->assertStringContainsString('PHP83', $sql);
    }

    // --- selectFTSScore ---

    public function test_select_fts_score_appends_as_fts_score_alias(): void
    {
        $expr = SearchModel::selectFTSScore(['title'], 'keyword');

        $this->assertStringContainsString('as fts_score', $expr);
    }

    public function test_select_fts_score_includes_match_against(): void
    {
        $expr = SearchModel::selectFTSScore(['title', 'body'], 'keyword');

        $this->assertStringContainsString('MATCH', $expr);
        $this->assertStringContainsString('AGAINST', $expr);
        $this->assertStringContainsString('keyword', $expr);
    }

    public function test_select_fts_score_with_boolean_mode(): void
    {
        $expr = SearchModel::selectFTSScore(['title'], 'keyword', true);

        $this->assertStringContainsString('IN BOOLEAN MODE', $expr);
        $this->assertStringContainsString('as fts_score', $expr);
    }

    public function test_select_fts_score_without_boolean_mode(): void
    {
        $expr = SearchModel::selectFTSScore(['title'], 'keyword', false);

        $this->assertStringNotContainsString('IN BOOLEAN MODE', $expr);
        $this->assertStringContainsString('as fts_score', $expr);
    }
}
