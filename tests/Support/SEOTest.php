<?php

namespace Osoobe\LaravelTraits\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Osoobe\LaravelTraits\Support\SEO;
use Osoobe\LaravelTraits\Tests\TestCase;

class SeoModel extends Model
{
    use SEO;

    protected $table = 'seo_models';

    public function getRouteURL(): string
    {
        return 'https://example.com/products/' . ($this->slug ?? 'test');
    }

    public function getSEOTitleAttribute(): string
    {
        return ($this->name ?? 'Test Product') . ' | TestApp';
    }
}

class SEOTest extends TestCase
{
    // --- getURLAttribute ---

    public function test_url_attribute_returns_route_url(): void
    {
        $model = new SeoModel();
        $model->slug = 'my-product';

        $this->assertEquals('https://example.com/products/my-product', $model->url);
    }

    public function test_url_attribute_delegates_to_get_route_url(): void
    {
        $model = new SeoModel();

        $this->assertEquals($model->getRouteURL(), $model->url);
    }

    // --- getSEOTitleAttribute ---

    public function test_seo_title_attribute_returns_implemented_value(): void
    {
        $model = new SeoModel();
        $model->name = 'Cool Widget';

        $this->assertEquals('Cool Widget | TestApp', $model->seo_title);
    }

    // --- getSEODescriptionAttribute ---

    public function test_seo_description_attribute_is_auto_generated(): void
    {
        $model = new SeoModel();
        $model->name = 'Cool Widget';

        $description = $model->seo_description;

        $this->assertIsString($description);
        $this->assertNotEmpty($description);
    }

    public function test_seo_description_contains_app_name(): void
    {
        $this->app['config']->set('app.name', 'TestApp');
        $model = new SeoModel();
        $model->name = 'Cool Widget';

        $description = $model->seo_description;

        $this->assertStringContainsString('TestApp', $description);
    }

    public function test_seo_description_contains_seo_title(): void
    {
        $model = new SeoModel();
        $model->name = 'Cool Widget';

        $description = $model->seo_description;

        // Description references the seo_title in "Check out <title>"
        $this->assertStringContainsString('Cool Widget', $description);
    }

    // --- getRouteURL abstract requirement ---

    public function test_get_route_url_is_implemented_and_returns_string(): void
    {
        $model = new SeoModel();

        $this->assertIsString($model->getRouteURL());
    }

    public function test_seo_model_name_property_is_accessible(): void
    {
        $model = new SeoModel();
        $model->seo_model_name = 'Product';

        $this->assertEquals('Product', $model->seo_model_name);
    }
}
