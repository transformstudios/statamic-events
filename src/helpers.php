<?php

use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;

/**
 * @throws \Carbon\Exceptions\InvalidFormatException
 */
function parse_date(string $date): CarbonInterface
{
    $date = null;

    try {
        $date = Carbon::parse($date);
    } catch (InvalidFormatException $e) {
        $date = now();
    }

    return $date;
}
