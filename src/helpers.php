<?php

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;

if (! function_exists('parse_date')) {
    /**
     * @throws \Carbon\Exceptions\InvalidFormatException
     */
    function parse_date(string $date): CarbonInterface
    {
        try {
            $date = CarbonImmutable::parse($date);
        } catch (InvalidFormatException $e) {
            $date = now();
        }

        return $date;
    }
}
