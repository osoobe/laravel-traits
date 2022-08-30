<?php

namespace  Osoobe\LaravelTraits\Support;
use Osoobe\Utilities\Helpers\Utilities;


trait HasPhoneNumber {


    public function getPhoneNumberBaseAttribute() {
        return str_replace('+1', '', $this->phone_number);
    }

    /**
     * Scope a query to only include objects  verified by phone number.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param string $phone_number
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePhoneNumber($query, string $phone_number)
    {
        return $query->whereIn('phone_number', [Utilities::formatPhoneNumber($phone_number), $phone_number]);
    }

}

?>
