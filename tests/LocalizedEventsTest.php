<?php

namespace TransformStudios\Events\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use TransformStudios\Events\Events;

beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'English', 'locale' => 'en_US', 'url' => '/'],
        'ar' => ['name' => 'Arabic', 'locale' => 'ar', 'url' => '/ar/'],
    ]);

    $this->collection->sites(['default', 'ar'])->save();

    Carbon::setTestNow('2026-08-15 10:00:00');
});

test('includes localized events that inherit start_date from the origin', function () {
    $origin = Entry::make()
        ->collection('events')
        ->locale('default')
        ->slug('art-collective')
        ->data([
            'title' => 'Collective Art Activities',
            'start_date' => '2026-08-31',
            'start_time' => '14:00',
            'end_time' => '16:00',
            'timezone' => 'America/Los_Angeles',
        ]);
    $origin->save();

    $localized = $origin->makeLocalization('ar');
    $localized->set('title', 'أنشطة فنية جماعية');
    $localized->save();

    expect($localized->has('start_date'))->toBeFalse()
        ->and($localized->get('start_date'))->toBeNull()
        ->and($localized->value('start_date'))->toBe('2026-08-31');

    Site::setCurrent('ar');

    $occurrences = Events::fromCollection('events')
        ->site('ar')
        ->between(
            CarbonImmutable::parse('2026-08-01')->startOfDay(),
            CarbonImmutable::parse('2026-08-31')->endOfDay()
        );

    expect($occurrences)->toHaveCount(1)
        ->and($occurrences->first()->title)->toBe('أنشطة فنية جماعية')
        ->and($occurrences->first()->start->toDateString())->toBe('2026-08-31');
});

test('still drops localized events whose origin start date is in the past', function () {
    $origin = Entry::make()
        ->collection('events')
        ->locale('default')
        ->slug('past-event')
        ->data([
            'title' => 'Past Event',
            'start_date' => '2026-07-01',
            'start_time' => '14:00',
        ]);
    $origin->save();

    $localized = $origin->makeLocalization('ar');
    $localized->set('title', 'حدث سابق');
    $localized->save();

    Site::setCurrent('ar');

    expect(
        Events::fromCollection('events')->site('ar')->upcoming(1)
    )->toBeEmpty();
});
