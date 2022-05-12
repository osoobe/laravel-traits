<?php

namespace  Osoobe\LaravelTraits\Support;

use Illuminate\Support\Str;
use Osoobe\Utilities\Helpers\FormatHelper;

trait ToString {


    /**
     * To string method
     *
     * @param string $delimiter
     * @param string $format
     * @param string ...$strings
     * @return string
     */
    public function toString(string $delimiter="\n", string $format=null, ...$string) {
        return $this->propertyToString($delimiter, $format, ...$string);
    }

    /**
     * Execute multiple toString for model attributes
     *
     * @param string $delimiter
     * @param string $format
     * @param string ...$strings
     * @return string
     */
    public function propertyToString(string $delimiter="\n", string $format=null, string ...$strings): string {
        $string = "";
        $func = function ($property, $value) use($delimiter, $format) {
            if ( is_string($value) ) {
                return FormatHelper::formatString($value, $format, Str::headline($property))
                    .$delimiter;
            }elseif ( method_exists($value, 'toString') ) {
                try {
                    return $value->toString($delimiter, $format);
                } catch (\Throwable $th) {
                    logger($th->getMessage());
                }
            }
            return "";
        };

        foreach($strings as $property) {
            if ( property_exists($this, $property)) {
                $string .= $func($property, $this->property);
            } elseif ( method_exists($this, $property) ) {
                $string .= $this->$property($delimiter, $format);
            } else {
                try {
                    $string .= $func($property, $this->$property);
                } catch (\Throwable $th) {
                    logger($th->getMessage());
                }
            }
        }

        return $string;
    }

}

?>
