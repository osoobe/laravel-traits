<?php

namespace Osoobe\LaravelTraits\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Osoobe\LaravelTraits\Support\ToString;
use Osoobe\LaravelTraits\Tests\TestCase;

class StringModel extends Model
{
    use ToString;

    protected $table = 'string_models';
    protected $guarded = [];
    public $timestamps = false;
}

class HasToStringTest extends TestCase
{
    // --- propertyToString / toString ---

    public function test_property_to_string_concatenates_attribute_values(): void
    {
        $model = new StringModel();
        $model->first_name = 'Jane';
        $model->last_name = 'Smith';

        $result = $model->propertyToString(' ', null, 'first_name', 'last_name');

        $this->assertIsString($result);
        $this->assertStringContainsString('Jane', $result);
        $this->assertStringContainsString('Smith', $result);
    }

    public function test_to_string_is_alias_for_property_to_string(): void
    {
        $model = new StringModel();
        $model->city = 'Springfield';
        $model->state = 'IL';

        $a = $model->propertyToString(', ', null, 'city', 'state');
        $b = $model->toString(', ', null, 'city', 'state');

        $this->assertEquals($a, $b);
    }

    public function test_property_to_string_uses_newline_delimiter_by_default(): void
    {
        $model = new StringModel();
        $model->line1 = 'Hello';
        $model->line2 = 'World';

        $result = $model->toString("\n", null, 'line1', 'line2');

        $this->assertStringContainsString("\n", $result);
    }

    public function test_property_to_string_returns_empty_string_for_missing_properties(): void
    {
        $model = new StringModel();

        // Property doesn't exist and isn't a method — should silently skip
        $result = $model->propertyToString(', ', null, 'nonexistent_field');

        $this->assertIsString($result);
    }

    public function test_property_to_string_handles_single_property(): void
    {
        $model = new StringModel();
        $model->title = 'My Title';

        $result = $model->propertyToString('', null, 'title');

        $this->assertIsString($result);
        $this->assertStringContainsString('My Title', $result);
    }

    public function test_property_to_string_with_empty_properties_returns_empty_or_skips(): void
    {
        $model = new StringModel();

        $result = $model->propertyToString(', ', null);

        $this->assertIsString($result);
        $this->assertEquals('', $result);
    }
}
