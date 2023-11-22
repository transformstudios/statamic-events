<?php

use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;

if (! function_exists('parse_date')) {
    /**
     * @throws \Carbon\Exceptions\InvalidFormatException
     */
    function parse_date(string $date): CarbonInterface
    {
        try {
            $date = Carbon::parse($date);
        } catch (InvalidFormatException $e) {
            $date = now();
        }

        return $date;
    }
}
