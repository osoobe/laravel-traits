<?php

namespace Osoobe\LaravelTraits\Tests\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Osoobe\LaravelTraits\Support\TimeDiff;
use Osoobe\LaravelTraits\Tests\TestCase;

class TimeDiffModel extends Model
{
    use TimeDiff;

    protected $table = 'time_diff_models';
    protected $guarded = [];
    protected $casts   = ['expiry_date' => 'datetime'];
}

class TimeDiffTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('time_diff_models', function (Blueprint $table) {
            $table->id();
            $table->timestamp('expiry_date')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::drop('time_diff_models');
        parent::tearDown();
    }

    // --- Accessors ---

    public function test_created_time_diff_attribute_returns_human_readable_string(): void
    {
        $model = TimeDiffModel::create([]);

        $this->assertIsString($model->created_time_diff);
        $this->assertNotEmpty($model->created_time_diff);
    }

    public function test_updated_time_diff_attribute_returns_human_readable_string(): void
    {
        $model = TimeDiffModel::create([]);

        $this->assertIsString($model->updated_time_diff);
        $this->assertNotEmpty($model->updated_time_diff);
    }

    public function test_posted_time_diff_is_alias_for_created_time_diff(): void
    {
        $model = TimeDiffModel::create([]);

        $this->assertEquals($model->created_time_diff, $model->posted_time_diff);
    }

    public function test_expiry_date_time_diff_returns_non_disclosure_when_expiry_not_set(): void
    {
        $model = TimeDiffModel::create(['expiry_date' => null]);

        $this->assertStringContainsString('Non-Disclosure', $model->expiry_date_time_diff);
    }

    public function test_expiry_date_time_diff_returns_human_readable_when_expiry_set(): void
    {
        $model = TimeDiffModel::create(['expiry_date' => Carbon::now()->addDays(5)]);

        $diff = $model->expiry_date_time_diff;

        $this->assertIsString($diff);
        $this->assertNotEmpty($diff);
    }

    public function test_created_time_diff_returns_non_disclosure_on_exception(): void
    {
        $model = new TimeDiffModel();
        // created_at not set — triggers catch block

        $result = $model->getCreatedTimeDiffAttribute();

        $this->assertStringContainsString('Non-Disclosure', $result);
    }

    // --- formatDateField ---

    public function test_format_date_field_returns_ymd_format(): void
    {
        $model = TimeDiffModel::create(['expiry_date' => Carbon::parse('2025-06-15')]);

        $this->assertEquals('2025-06-15', $model->formatDateField('expiry_date'));
    }

    public function test_format_date_field_returns_empty_string_when_field_is_null(): void
    {
        $model = TimeDiffModel::create(['expiry_date' => null]);

        $this->assertEquals('', $model->formatDateField('expiry_date'));
    }

    // --- isExpired ---

    public function test_is_expired_returns_true_for_past_expiry(): void
    {
        $model = TimeDiffModel::create(['expiry_date' => Carbon::now()->subHour()]);

        $this->assertTrue($model->isExpired());
    }

    public function test_is_expired_returns_false_for_future_expiry(): void
    {
        $model = TimeDiffModel::create(['expiry_date' => Carbon::now()->addHour()]);

        $this->assertFalse($model->isExpired());
    }

    // --- expireInDays / expireInHours ---

    public function test_expire_in_days_sets_expiry_date(): void
    {
        $model = TimeDiffModel::create(['expiry_date' => Carbon::now()]);

        $model->expireInDays(10);

        $this->assertGreaterThan(Carbon::now()->addDays(9), $model->expiry_date);
    }

    public function test_expire_in_hours_sets_expiry_date(): void
    {
        $model = TimeDiffModel::create(['expiry_date' => Carbon::now()]);

        $model->expireInHours(6);

        $this->assertGreaterThan(Carbon::now()->addHours(5), $model->expiry_date);
    }

    // --- expiresInMinutes / expiresInSeconds ---

    public function test_expires_in_minutes_returns_positive_when_not_expired(): void
    {
        $model = TimeDiffModel::create(['expiry_date' => Carbon::now()->addHour()]);

        $this->assertGreaterThan(0, $model->expiresInMinutes());
    }

    public function test_expires_in_minutes_returns_zero_when_expired(): void
    {
        $model = TimeDiffModel::create(['expiry_date' => Carbon::now()->subHour()]);

        $this->assertEquals(0, $model->expiresInMinutes());
    }

    public function test_expires_in_seconds_returns_positive_when_not_expired(): void
    {
        $model = TimeDiffModel::create(['expiry_date' => Carbon::now()->addHour()]);

        $this->assertGreaterThan(0, $model->expiresInSeconds());
    }

    public function test_expires_in_seconds_returns_zero_when_expired(): void
    {
        $model = TimeDiffModel::create(['expiry_date' => Carbon::now()->subHour()]);

        $this->assertEquals(0, $model->expiresInSeconds());
    }

    // --- Time-based scopes ---

    public function test_scope_created_since_week_returns_recent_records(): void
    {
        TimeDiffModel::create([]);

        $old = TimeDiffModel::create([]);
        $old->forceFill(['created_at' => Carbon::now()->subDays(10)])->save();

        $results = TimeDiffModel::createdSinceWeek()->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_recently_created_returns_records_within_window(): void
    {
        TimeDiffModel::create([]);

        $old = TimeDiffModel::create([]);
        $old->forceFill(['created_at' => Carbon::now()->subDays(10)])->save();

        $results = TimeDiffModel::query()->recentlyCreated(3, 'subDays')->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_recently_created_falls_back_to_sub_days_on_invalid_fn(): void
    {
        TimeDiffModel::create([]);

        $results = TimeDiffModel::query()->recentlyCreated(3, 'invalidFn')->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_created_today_returns_todays_records(): void
    {
        TimeDiffModel::create([]);

        $old = TimeDiffModel::create([]);
        $old->forceFill(['created_at' => Carbon::now()->subDays(2)])->save();

        $results = TimeDiffModel::createdToday()->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_created_date_filters_by_specific_date(): void
    {
        $target = TimeDiffModel::create([]);
        $target->forceFill(['created_at' => Carbon::parse('2024-03-15 12:00:00')])->save();

        TimeDiffModel::create([]);

        $results = TimeDiffModel::createdDate(Carbon::parse('2024-03-15'))->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_created_week_returns_records_in_the_same_week(): void
    {
        $now = Carbon::now();
        TimeDiffModel::create([]);

        $old = TimeDiffModel::create([]);
        $old->forceFill(['created_at' => $now->copy()->subWeeks(2)])->save();

        $results = TimeDiffModel::createdWeek($now)->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_created_month_returns_records_in_same_month(): void
    {
        $now = Carbon::now();
        TimeDiffModel::create([]);

        $old = TimeDiffModel::create([]);
        $old->forceFill(['created_at' => $now->copy()->subMonths(2)])->save();

        $results = TimeDiffModel::createdMonth($now)->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_created_hours_ago_returns_recent_records(): void
    {
        TimeDiffModel::create([]);

        $old = TimeDiffModel::create([]);
        $old->forceFill(['created_at' => Carbon::now()->subDays(1)])->save();

        $results = TimeDiffModel::createdHoursAgo(3)->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_created_minutes_ago_returns_recent_records(): void
    {
        TimeDiffModel::create([]);

        $old = TimeDiffModel::create([]);
        $old->forceFill(['created_at' => Carbon::now()->subHours(2)])->save();

        $results = TimeDiffModel::createdMinutesAgo(30)->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_recently_updated_returns_recently_updated_records(): void
    {
        TimeDiffModel::create([]);

        $old = TimeDiffModel::create([]);
        $old->forceFill(['updated_at' => Carbon::now()->subDays(10)])->save();

        $results = TimeDiffModel::query()->recentlyUpdated(3, 'subDays')->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_updated_since_week_returns_recently_updated(): void
    {
        TimeDiffModel::create([]);

        $old = TimeDiffModel::create([]);
        $old->forceFill(['updated_at' => Carbon::now()->subDays(10)])->save();

        $results = TimeDiffModel::updatedSinceWeek()->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_older_than_returns_records_not_updated_recently(): void
    {
        $stale = TimeDiffModel::create([]);
        $stale->forceFill(['updated_at' => Carbon::now()->subDays(10)])->save();

        TimeDiffModel::create([]);

        $results = TimeDiffModel::olderThan(5)->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_between_dates_returns_records_in_range(): void
    {
        $r1 = TimeDiffModel::create([]);
        $r1->forceFill(['created_at' => Carbon::parse('2024-01-10')])->save();

        $r2 = TimeDiffModel::create([]);
        $r2->forceFill(['created_at' => Carbon::parse('2024-01-20')])->save();

        $r3 = TimeDiffModel::create([]);
        $r3->forceFill(['created_at' => Carbon::parse('2024-02-05')])->save();

        $results = TimeDiffModel::betweenDates(
            'created_at',
            Carbon::parse('2024-01-01'),
            Carbon::parse('2024-01-31')
        )->get();

        $this->assertCount(2, $results);
    }

    public function test_scope_daily_returns_records_for_a_given_day(): void
    {
        TimeDiffModel::create([]);

        $old = TimeDiffModel::create([]);
        $old->forceFill(['created_at' => Carbon::now()->subDays(5)])->save();

        // daily(0) = today
        $results = TimeDiffModel::daily(0)->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_weekly_returns_records_for_current_week(): void
    {
        TimeDiffModel::create([]);

        $old = TimeDiffModel::create([]);
        $old->forceFill(['created_at' => Carbon::now()->subWeeks(3)])->save();

        $results = TimeDiffModel::weekly(0)->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_monthly_returns_records_for_current_month(): void
    {
        TimeDiffModel::create([]);

        $old = TimeDiffModel::create([]);
        $old->forceFill(['created_at' => Carbon::now()->subMonths(3)])->save();

        $results = TimeDiffModel::monthly(0)->get();

        $this->assertCount(1, $results);
    }

    public function test_scope_yearly_returns_records_for_current_year(): void
    {
        TimeDiffModel::create([]);

        $old = TimeDiffModel::create([]);
        $old->forceFill(['created_at' => Carbon::now()->subYears(3)])->save();

        $results = TimeDiffModel::yearly(0)->get();

        $this->assertCount(1, $results);
    }

    // --- Instance checks ---

    public function test_recently_created_returns_true_for_new_record(): void
    {
        $model = TimeDiffModel::create([]);

        $this->assertTrue($model->recentlyCreated(1, 'subDays'));
    }

    public function test_recently_created_returns_false_for_old_record(): void
    {
        $model = TimeDiffModel::create([]);
        $model->forceFill(['created_at' => Carbon::now()->subDays(10)])->save();

        $this->assertFalse($model->recentlyCreated(1, 'subDays'));
    }

    public function test_recently_updated_returns_true_for_recently_updated_record(): void
    {
        $model = TimeDiffModel::create([]);

        $this->assertTrue($model->recentlyUpdated(1, 'subHours'));
    }

    public function test_just_created_returns_true_for_brand_new_record(): void
    {
        $model = TimeDiffModel::create([]);

        $this->assertTrue($model->justCreated());
    }

    public function test_just_created_returns_false_for_old_record(): void
    {
        $model = TimeDiffModel::create([]);
        $model->forceFill(['created_at' => Carbon::now()->subHour()])->save();

        $this->assertFalse($model->justCreated());
    }

    public function test_just_created_accepts_custom_minutes(): void
    {
        $model = TimeDiffModel::create([]);

        $this->assertTrue($model->justCreated(10));
    }
}
