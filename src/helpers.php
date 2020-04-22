<?php

use Carbon\Carbon;

if (! function_exists('bool')) {
    function bool($value)
    {
        return ! in_array(strtolower($value), ['no', 'false', '0', '', '-1']);
    }
}

if (! function_exists('carbon')) {
    function carbon($value)
    {
        if (! $value instanceof Carbon) {
            $value = (is_numeric($value)) ? Carbon::createFromTimestamp($value) : Carbon::parse($value);
        }

        return $value;
    }
}
