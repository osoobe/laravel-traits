<?php

namespace  Osoobe\LaravelTraits\Support;

use Carbon\Carbon;


/**
 * Use time difference between the current date and the given date files.
 * @property-read string $posted_time_diff          Time difference between the current date
 *                                                  and the posted date.
 * @property-read string $expiry_date_time_diff     Time difference between the current date
 *                                                  and the expiry date.
 */
trait TimeDiff {

    /**
     * Get the time difference between the current date and
     * the created date.
     *
     * @uses Carbon\Carbon::diffForHumans
     * @example $this->posted_time_diff  1 day ago.
     * @return string
     */
    public function getCreatedTimeDiffAttribute() {
        try {
            return $this->created_at->diffForHumans(
                ['options' => Carbon::JUST_NOW]
            );
        } catch (\Throwable $th) {
            return "Non-Disclosure ";
        }
    }


    /**
     * Get the time difference between the current date and
     * the created date.
     *
     * @uses Carbon\Carbon::diffForHumans
     * @example $this->posted_time_diff  1 day ago.
     * @return string
     */
    public function getUpdatedTimeDiffAttribute() {
        try {
            return $this->updated_at->diffForHumans(
                ['options' => Carbon::JUST_NOW]
            );
        } catch (\Throwable $th) {
            return "Non-Disclosure ";
        }
    }

    public function formatDateField($field) {
        if ( empty($this->$field) ) {
            return '';
        }
        return $this->$field->format('Y-m-d');
    }


    /**
     * Get the time difference between the current date and
     * the created date.
     *
     * @uses Carbon\Carbon::diffForHumans
     * @example $this->posted_time_diff  1 day ago.
     * @return string
     */
    public function getPostedTimeDiffAttribute() {
        return $this->created_time_diff;
    }


    /**
     * Get the time difference between the current date and
     * the expiry date.
     *
     * @uses Carbon\Carbon::diffForHumans
     * @example $this->posted_time_diff  1 day ago.
     * @return string
     */
    public function getExpiryDateTimeDiffAttribute() {
        if ( !isset($this->expiry_date)) {
            return "Non-Disclosure ";
        }
        return $this->expiry_date->diffForHumans(
            ['options' => Carbon::JUST_NOW]
        );
    }

    /**
     * Check if the model is expired.
     *
     * @return boolean
     */
    public function isExpired(){
        if ( !isset($this->expiry_date)) {
            return "Non-Disclosure ";
        }
        return $this->expiry_date < Carbon::now();
    }

    /**
     * Set expiry_date by the number of days from the current date.
     *
     * @param integer $days
     * @return void
     */
    public function expireInDays(int $days){
        if ( !isset($this->expiry_date)) {
            return "Non-Disclosure ";
        }
        $this->expiry_date = Carbon::now()->addDays($days);
    }

    /**
     * Set expiry_date by the number of hours from the current datetime.
     *
     * @param integer $days
     * @return void
     */
    public function expireInHours(int $hours) {
        if ( !isset($this->expiry_date)) {
            return "Non-Disclosure ";
        }
        $this->expiry_date = Carbon::now()->addHours($hours);
    }

    /**
     * Get the pickup expiry time left in minutes
     *
     * @return mixed
     */
    public function expiresInMinutes() {
        $now = Carbon::now();
        try {
            if ( $now <= $this->expiry_date ) {
                return $now->diffInMinutes($this->expiry_date);
            }
            return 0;
        } catch (\Throwable $th) {
            return 0;
        }
    }

    /**
     * Get the pickup expiry time left in seconds
     *
     * @return mixed
     */
    public function expiresInSeconds() {
        $now = Carbon::now();
        try {
            if ( $now <= $this->expiry_date ) {
                return $now->diffInSeconds($this->expiry_date);
            }
            return 0;
        } catch (\Throwable $th) {
            return 0;
        }
    }


    /**
     * Scope a query to only exclude expired objects.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query) {
        if ( !isset($this->expiry_date)) {
            return $query;
        }
        return $query->where('expiry_date', '<',  Carbon::now());
    }


    /**
     * Scope a query to only include not expired objects.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotExpired($query) {
        if ( !isset($this->expiry_date)) {
            return $query;
        }
        return $query->where('expiry_date', '>=',  Carbon::now());
    }


    /**
     * Scope a query for objects that were created within the last 7 days.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreatedSinceWeek($query) {
        return $query->where('created_at', '>=',  Carbon::now()->subDays(7));
    }


    /**
     * Scope a query for objects that were created within the last given days.
     * Default is 3 days.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param int $days     Within the last given days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecentlyCreated($query, int $time=3, string $carbon_fn = 'subDays') {
        try {
            return $query->where('created_at', '>=',  Carbon::now()->$carbon_fn($time));
        } catch (\Throwable $th) {
            return $query->where('created_at', '>=', Carbon::now()->subDays($time));
        }
    }

    /**
     * Scope a query for objects that were created within the last given days.
     * Default is 3 days.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param int $days     Within the last given days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecentlyUpdated($query, int $time=3, string $carbon_fn = 'subDays') {
        try {
            return $query->where('updated_at', '>=',  Carbon::now()->$carbon_fn($time));
        } catch (\Throwable $th) {
            return $query->where('updated_at', '>=', Carbon::now()->subDays($time));
        }
    }

    /**
     * Scope a query for objects that were created within the last given days.
     * Default is 3 days.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param int $days     Within the last given days
     * @param string $column
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOlderThan($query, int $days=3, string $column='updated_at') {
        return $query->where($column, '<=',  Carbon::now()->subDays($days));
    }


    /**
     * Scope a query for objects that were created today.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreatedToday($query) {
        return $query->where('created_at', '>=',  Carbon::now()->startOfDay() );
    }


    /**
     * Scope a query for objects that were created today.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreatedDate($query, Carbon $date=null) {
        if ( empty($date) ) {
            $date = Carbon::now();
        }
        return $query->where('created_at', '>=',  $date->copy()->startOfDay() )
        ->where('created_at', '<=',  $date->copy()->endOfDay() );
    }


    /**
     * Scope a query for objects that were created today.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreatedWeek($query, Carbon $date=null) {
        if ( empty($date) ) {
            $date = Carbon::now();
        }
        return $query->where('created_at', '>=',  $date->copy()->startOfWeek() )
            ->where('created_at', '<=',  $date->copy()->endOfWeek() );
    }


    /**
     * Scope a query for objects that were created today.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreatedMonth($query, Carbon $date=null) {
        if ( empty($date) ) {
            $date = Carbon::now();
        }
        return $query->where('created_at', '>=',  $date->copy()->startOfMonth() )
            ->where('created_at', '<=',  $date->copy()->endOfMonth() );
    }

    /**
     * Check if the model was recently created.
     * Default is 1 day.
     *
     * @param int $time             Within the last given days, hours, minutes, etc.
     * @param string $carbon_fn     Carbon subtract function
     * @return bool
     */
    public function recentlyCreated(int $time=1, string $carbon_fn = 'subDays') {
        try {
            return $this->created_at >=  Carbon::now()->$carbon_fn($time);
        } catch (\Throwable $th) {
            return $this->created_at >=  Carbon::now()->subDays($time);
        }
    }


    /**
     * Query between two dates
     *
     * @param  \Illuminate\Database\Eloquent\Builder        $query
     * @param string $column
     * @param Carbon $start_date
     * @param Carbon $end_date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetweenDates($query, string $column, Carbon $start_date=null, Carbon $end_date=null) {
        return $query->where($column, '>=',  $start_date )
            ->when($end_date, function($query, $end_date) use($column) {
                return $query->where($column, '<=',  $end_date );
            });
    }

    /**
     * Check if the model was recently created.
     * Default is 5 minutes.
     *
     * @param integer $minutes
     * @return bool
     */
    public function justCreated($minutes=5) {
        return $this->recentlyCreated($minutes, 'subMinutes');
    }


    /**
     * Check if the model was recently updated.
     * Default is 1 hour.
     *
     * @param int $time             Within the last given days, hours, minutes, etc.
     * @param string $carbon_fn     Carbon subtract function
     * @return bool
     */
    public function recentlyUpdated(int $time=1, string $carbon_fn = 'subHours') {
        try {
            return $this->updated_at >=  Carbon::now()->$carbon_fn($time);
        } catch (\Throwable $th) {
            return $this->updated_at >=  Carbon::now()->subDays($time);
        }
    }


    /**
     * Scope a query for objects that were updated within the last 7 days.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUpdatedSinceWeek($query) {
        return $query->where('updated_at', '>=',  Carbon::now()->subDays(7));
    }

    /**
     * Scope a query for objects that were created within the last given hours.
     * Default is 3.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param int $hours     Within the last given hours
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreatedHoursAgo($query, $hours=3) {
        return $query->where('created_at', '>=',  Carbon::now()->subHours($hours));
    }

    /**
     * Scope a query for objects that were created within the last given minutes.
     * Default is 3.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param int $mins     Within the last given minutes
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreatedMinutesAgo($query, $mins=3) {
        return $query->where('created_at', '>=',  Carbon::now()->subMinutes($mins));
    }



    public function scopeTimeFrequencey($query, $frequency, $subtract=0, $field='created_at') {
        $subFn = "sub".ucfirst($frequency).'s';
        $startFn = "startOf".ucfirst($frequency);
        $endFn = "endOf".ucfirst($frequency);

        $date = now()->$subFn($subtract);

        return $query->where(function($query2) use($date, $startFn, $endFn, $field) {
            return $query2->where($field, '>=',  $date->$startFn() )
                ->where($field, '<=',  $date->copy()->$endFn() );
        });
    }

    public function scopeMinutely($query, $subtract=0, $field='created_at') {
        return $query->timeFrequency($query, 'minute', $subtract, $field);
    }

    public function scopeHourly($query, $hours=0) {
        $date = now()->subHours($hours);
        return $query->where(function($query2) use($date) {
            return $query2->where('created_at', '>=',  $date->startOfHour() )
                ->where('created_at', '<=',  $date->copy()->endOfHour() );
        });
    }

    public function scopeDaily($query, $days=0) {
        $date = now()->subDays($days);
        return $query->where(function($query2) use($date) {
            return $query2->where('created_at', '>=',  $date->startOfDay() )
                ->where('created_at', '<=',  $date->copy()->endOfDay() );
        });
    }

    public function scopeWeekly($query, $weeks=0) {
        $date = now()->subWeeks($weeks);
        return $query->where(function($query2) use($date) {
            return $query2->where('created_at', '>=',  $date->startOfWeek() )
                ->where('created_at', '<=',  $date->copy()->endOfWeek() );
        });
    }

    public function scopeMonthly($query, $months=0) {
        $date = now()->subMonths($months);
        return $query->where(function($query2) use($date) {
            return $query2->where('created_at', '>=',  $date->startOfMonth() )
                ->where('created_at', '<=',  $date->copy()->endOfMonth() );
        });
    }

    public function scopeYearly($query, $months=0) {
        $date = now()->subYears($months);
        return $query->where(function($query2) use($date) {
            return $query2->where('created_at', '>=',  $date->startOfYear() )
                ->where('created_at', '<=',  $date->copy()->endOfYear() );
        });
    }


}
