<?php

namespace  Osoobe\LaravelTraits\Support;

trait IsVerified
{

    /**
     * Scope a query to only include active objects.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', '=', 1);
    }

    /**
     * Scope a query to only include not active objects.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotVerified($query)
    {
        return $query->where('verified', '!=', 1);
    }

    /**
     * Is verified
     *
     * @return boolean
     */
    public function getIsVerifiedAttribute(): bool {
        return (bool) $this->verified;
    }

}
