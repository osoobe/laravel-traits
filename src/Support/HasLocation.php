<?php

namespace  Osoobe\LaravelTraits\Support;

use Osoobe\Utilities\Helpers\Utilities;

trait HasLocation {


    /**
     * Get the given object's state and country address.
     *
     * @return string
     */
    public function getStateAddress() {
        $state = Utilities::getObjectValue($this, 'state', '');
        $country = Utilities::getObjectValue($this, 'country', '');
        return "$state $country";
    }

    /**
     * Get the given object's city, state and country address.
     *
     * @return string
     */
    public function getCityAddress() {
        $city = Utilities::getObjectValue($this, 'city', '');
        $state_address = $this->getStateAddress();
        return "$city $state_address";
    }

    public function isValidLocation() {
        return (
            !empty($this->street_address) &&
            !empty($this->city) &&
            !empty($this->state) &&
            !empty($this->country)
        );
    }


    /**
     * Get the given object's street, city, state and country address.
     *
     * @param string $delimiter
     * @param bool $exclude_zip     Exclude zip code
     * @return string
     */
    public function getFullAddress(string $delimiter=null, bool $exclude_zip=true) {
        $string = "";
        $address_array = $this->getAddressArray();
        unset($address_array['country']);
        if ( $exclude_zip ) {
            unset($address_array['zip']);
        }
        foreach($address_array as $text) {
            if ( !empty($text) ) {
                $string .= $text.$delimiter." ";
            }
        }
        return trim($string)." $this->country";
    }


    /**
     * Get the given object's street, city, state and country address and zip code.
     *
     * @return string
     */
    public function getFullAddressWithZipCode() {
        return $this->getFullAddress(null, false);
    }

    public function getGoogleMapLinkAttribute() {
        return "http://maps.google.com/?q=".$this->getFullAddress();
    }


    /**
     * Get address data as array
     *
     * @return array
     */
    public function getAddressArray() {
        return [
            "street" => $this->street_address,
            "city" => $this->city,
            "state" => $this->state,
            "zip" => $this->zip_code,
            "country" => $this->country,
        ];
    }

}
