<?php

namespace TransformStudios\Events\Modifiers;

use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use Statamic\Modifiers\Modifier;
use Statamic\Support\Arr;

class InMonth extends Modifier
{
    public function index($value, $params, $context)
    {
        $month = $this->parseDate(
            Arr::get($context, 'get.month', Carbon::now()->englishMonth).
            ' '.
            Arr::get($context, 'get.year', Carbon::now()->year)
        )->month;

        return Carbon::parse($value)->month == $month;
    }

    /**
     * @throws \Carbon\Exceptions\InvalidFormatException
     */
    private function parseDate(string $date): CarbonInterface
    {
        $date = null;

        try {
            $date = Carbon::parse($date);
        } catch (InvalidFormatException $e) {
            $date = now();
        }

        return $date;
    }
}
