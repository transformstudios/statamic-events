<?php

namespace TransformStudios\Events\Data;

use Carbon\Carbon;

class ICalEvent
{
    public function __construct(
        public string $title,
        public string $id,
        public Carbon $start,
        public Carbon $end,
        public ?string $address = null,
        public ?string $description = null,
        public ?string $url = null,
    ) {}
}
