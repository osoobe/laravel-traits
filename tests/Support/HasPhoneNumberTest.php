<?php

namespace Osoobe\LaravelTraits\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Osoobe\LaravelTraits\Support\HasPhoneNumber;
use Osoobe\LaravelTraits\Tests\TestCase;

class PhoneModel extends Model
{
    use HasPhoneNumber;

    protected $table = 'phone_models';
    protected $guarded = [];
}

class HasPhoneNumberTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('phone_models', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::drop('phone_models');
        parent::tearDown();
    }

    // --- getPhoneNumberBaseAttribute ---

    public function test_phone_number_base_strips_plus_one_prefix(): void
    {
        $model = new PhoneModel();
        $model->phone_number = '+15551234567';

        $this->assertEquals('5551234567', $model->phone_number_base);
    }

    public function test_phone_number_base_returns_unchanged_when_no_plus_one(): void
    {
        $model = new PhoneModel();
        $model->phone_number = '5551234567';

        $this->assertEquals('5551234567', $model->phone_number_base);
    }

    public function test_phone_number_base_only_removes_plus_one_not_other_prefixes(): void
    {
        $model = new PhoneModel();
        $model->phone_number = '+445551234567';

        $this->assertEquals('+445551234567', $model->phone_number_base);
    }

    public function test_phone_number_base_handles_null(): void
    {
        $model = new PhoneModel();
        $model->phone_number = null;

        $this->assertEquals('', $model->phone_number_base);
    }

    // --- scopePhoneNumber ---

    public function test_scope_phone_number_finds_exact_match(): void
    {
        PhoneModel::create(['phone_number' => '5551234567']);
        PhoneModel::create(['phone_number' => '5559876543']);

        $result = PhoneModel::phoneNumber('5551234567')->first();

        $this->assertNotNull($result);
        $this->assertEquals('5551234567', $result->phone_number);
    }

    public function test_scope_phone_number_returns_null_when_not_found(): void
    {
        PhoneModel::create(['phone_number' => '5551234567']);

        $result = PhoneModel::phoneNumber('0000000000')->first();

        $this->assertNull($result);
    }

    public function test_scope_phone_number_returns_builder(): void
    {
        $builder = PhoneModel::phoneNumber('5551234567');

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Builder::class,
            $builder
        );
    }
}
