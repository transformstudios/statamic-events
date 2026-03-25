<?php

use Carbon\Carbon;
use Statamic\Facades\Site as SiteFacade;
use Statamic\Modifiers\Modify;
use Statamic\Sites\Site;

it('uses current sites locale to determine start of week', function () {
    SiteFacade::shouldReceive('current')
        ->andReturn(new Site('default', [
            'name' => 'Laravel',
            'url' => '/',
            'locale' => 'en_US',
            'lang' => 'en',
        ], true));

    Carbon::setTestNow('2026-3-25 12:00pm');

    $modified = modify('2026-3-22');
    expect($modified)->toBe(true);
});

function modify(string $value)
{
    return Modify::value($value)->isStartOfWeek()->fetch();
}
