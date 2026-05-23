<?php

namespace Osoobe\LaravelTraits\Tests\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Osoobe\LaravelTraits\Support\HasEmail;
use Osoobe\LaravelTraits\Tests\TestCase;

class EmailModel extends Model
{
    use HasEmail;

    protected $table = 'email_models';
    protected $guarded = [];
    protected $casts = ['email_verified_at' => 'datetime'];
}

class HasEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('email_models', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::drop('email_models');
        parent::tearDown();
    }

    // --- isEmailVerified ---

    public function test_is_email_verified_returns_true_when_email_and_verified_at_are_set(): void
    {
        $model = EmailModel::create([
            'email'             => 'test@example.com',
            'email_verified_at' => Carbon::now(),
        ]);

        $this->assertTrue($model->isEmailVerified());
    }

    public function test_is_email_verified_returns_false_when_email_verified_at_is_null(): void
    {
        $model = EmailModel::create([
            'email'             => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $this->assertFalse($model->isEmailVerified());
    }

    public function test_is_email_verified_returns_false_when_email_is_empty(): void
    {
        $model = EmailModel::create([
            'email'             => null,
            'email_verified_at' => Carbon::now(),
        ]);

        $this->assertFalse($model->isEmailVerified());
    }

    public function test_is_email_verified_returns_false_when_both_are_null(): void
    {
        $model = EmailModel::create(['email' => null, 'email_verified_at' => null]);

        $this->assertFalse($model->isEmailVerified());
    }

    // --- scopeEmail ---

    public function test_scope_email_filters_by_exact_email(): void
    {
        EmailModel::create(['email' => 'alice@example.com']);
        EmailModel::create(['email' => 'bob@example.com']);

        $results = EmailModel::email('alice@example.com')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('alice@example.com', $results->first()->email);
    }

    public function test_scope_email_returns_empty_for_unknown_email(): void
    {
        EmailModel::create(['email' => 'alice@example.com']);

        $this->assertCount(0, EmailModel::email('nobody@example.com')->get());
    }

    // --- scopeEmailVerified ---

    public function test_scope_email_verified_returns_only_verified(): void
    {
        EmailModel::create(['email' => 'a@x.com', 'email_verified_at' => Carbon::now()]);
        EmailModel::create(['email' => 'b@x.com', 'email_verified_at' => null]);
        EmailModel::create(['email' => 'c@x.com', 'email_verified_at' => Carbon::now()]);

        $results = EmailModel::emailVerified()->get();

        $this->assertCount(2, $results);
        $results->each(fn ($m) => $this->assertNotNull($m->email_verified_at));
    }

    // --- scopeEmailNotVerified ---

    public function test_scope_email_not_verified_returns_only_unverified(): void
    {
        EmailModel::create(['email' => 'a@x.com', 'email_verified_at' => Carbon::now()]);
        EmailModel::create(['email' => 'b@x.com', 'email_verified_at' => null]);

        $results = EmailModel::emailNotVerified()->get();

        $this->assertCount(1, $results);
        $this->assertNull($results->first()->email_verified_at);
    }

    // --- scopeEmailVerifiedSince ---

    public function test_scope_email_verified_since_returns_recently_created_records(): void
    {
        // created_at defaults to now(); this record should be included
        EmailModel::create(['email' => 'new@x.com', 'email_verified_at' => Carbon::now()]);

        // Force an old record
        $old = EmailModel::create(['email' => 'old@x.com', 'email_verified_at' => Carbon::now()]);
        $old->forceFill(['created_at' => Carbon::now()->subDays(10)])->save();

        $results = EmailModel::emailVerifiedSince(5)->get();

        $this->assertCount(1, $results);
        $this->assertEquals('new@x.com', $results->first()->email);
    }

    public function test_scope_email_verified_since_supports_custom_carbon_function(): void
    {
        EmailModel::create(['email' => 'new@x.com', 'email_verified_at' => Carbon::now()]);

        $old = EmailModel::create(['email' => 'old@x.com', 'email_verified_at' => Carbon::now()]);
        // Must be on a different day for whereDate to exclude it
        $old->forceFill(['created_at' => Carbon::now()->subDays(3)])->save();

        // Within last 1 day
        $results = EmailModel::emailVerifiedSince(1, 'subDays')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('new@x.com', $results->first()->email);
    }

    public function test_scope_email_verified_since_falls_back_to_sub_days_on_invalid_function(): void
    {
        EmailModel::create(['email' => 'now@x.com', 'email_verified_at' => Carbon::now()]);

        // An invalid carbon function should fall back to subDays
        $results = EmailModel::emailVerifiedSince(3, 'invalidCarbonMethod')->get();

        $this->assertCount(1, $results);
    }
}
