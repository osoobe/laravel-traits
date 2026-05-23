<?php

namespace Osoobe\LaravelTraits\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Osoobe\LaravelTraits\Support\HasLocation;
use Osoobe\LaravelTraits\Tests\TestCase;

class LocationModel extends Model
{
    use HasLocation;

    protected $table = 'location_models';
    protected $guarded = [];
    public $timestamps = false;
}

class HasLocationTest extends TestCase
{
    private function makeLocation(array $overrides = []): LocationModel
    {
        $model = new LocationModel();
        $attrs = array_merge([
            'street_address' => '123 Main St',
            'city'           => 'Springfield',
            'state'          => 'IL',
            'zip_code'       => '62701',
            'country'        => 'US',
        ], $overrides);

        foreach ($attrs as $key => $value) {
            $model->$key = $value;
        }

        return $model;
    }

    // --- getAddressArray ---

    public function test_get_address_array_returns_all_keys(): void
    {
        $model = $this->makeLocation();
        $array = $model->getAddressArray();

        $this->assertArrayHasKey('street', $array);
        $this->assertArrayHasKey('city', $array);
        $this->assertArrayHasKey('state', $array);
        $this->assertArrayHasKey('zip', $array);
        $this->assertArrayHasKey('country', $array);
    }

    public function test_get_address_array_returns_correct_values(): void
    {
        $model = $this->makeLocation();
        $array = $model->getAddressArray();

        $this->assertEquals('123 Main St', $array['street']);
        $this->assertEquals('Springfield', $array['city']);
        $this->assertEquals('IL', $array['state']);
        $this->assertEquals('62701', $array['zip']);
        $this->assertEquals('US', $array['country']);
    }

    // --- isValidLocation ---

    public function test_is_valid_location_returns_true_when_all_fields_set(): void
    {
        $model = $this->makeLocation();

        $this->assertTrue($model->isValidLocation());
    }

    public function test_is_valid_location_returns_false_when_street_missing(): void
    {
        $model = $this->makeLocation(['street_address' => null]);

        $this->assertFalse($model->isValidLocation());
    }

    public function test_is_valid_location_returns_false_when_city_missing(): void
    {
        $model = $this->makeLocation(['city' => null]);

        $this->assertFalse($model->isValidLocation());
    }

    public function test_is_valid_location_returns_false_when_state_missing(): void
    {
        $model = $this->makeLocation(['state' => null]);

        $this->assertFalse($model->isValidLocation());
    }

    public function test_is_valid_location_returns_false_when_country_missing(): void
    {
        $model = $this->makeLocation(['country' => null]);

        $this->assertFalse($model->isValidLocation());
    }

    // --- getStateAddress ---

    public function test_get_state_address_returns_state_and_country(): void
    {
        $model = $this->makeLocation();
        $address = $model->getStateAddress();

        $this->assertStringContainsString('IL', $address);
        $this->assertStringContainsString('US', $address);
    }

    // --- getCityAddress ---

    public function test_get_city_address_returns_city_state_and_country(): void
    {
        $model = $this->makeLocation();
        $address = $model->getCityAddress();

        $this->assertStringContainsString('Springfield', $address);
        $this->assertStringContainsString('IL', $address);
        $this->assertStringContainsString('US', $address);
    }

    // --- getFullAddress ---

    public function test_get_full_address_excludes_zip_by_default(): void
    {
        $model = $this->makeLocation();
        $address = $model->getFullAddress();

        $this->assertStringContainsString('123 Main St', $address);
        $this->assertStringContainsString('Springfield', $address);
        $this->assertStringContainsString('IL', $address);
        $this->assertStringContainsString('US', $address);
        $this->assertStringNotContainsString('62701', $address);
    }

    public function test_get_full_address_with_zip_code(): void
    {
        $model = $this->makeLocation();
        $address = $model->getFullAddressWithZipCode();

        $this->assertStringContainsString('62701', $address);
        $this->assertStringContainsString('123 Main St', $address);
    }

    // --- getGoogleMapLinkAttribute ---

    public function test_google_map_link_attribute_returns_google_maps_url(): void
    {
        $model = $this->makeLocation();
        $link = $model->getGoogleMapLinkAttribute();

        $this->assertStringStartsWith('http://maps.google.com/?q=', $link);
        $this->assertStringContainsString('123 Main St', $link);
    }

    public function test_google_map_link_is_accessible_as_attribute(): void
    {
        $model = $this->makeLocation();

        $this->assertStringStartsWith('http://maps.google.com/?q=', $model->google_map_link);
    }
}
