<?php

use Carbon\CarbonImmutable;
use Statamic\Modifiers\Modify;

it('returns true when date is in param month', function () {
    expect(modify(value: '2026-05-15', params: ['May', '2026']))->toBeTrue();
});

it('returns false when date is not in param month', function () {
    expect(modify(value: '2026-04-15', params: ['May', '2026']))->toBeFalse();
});

it('returns true for first day of month', function () {
    expect(modify(value: '2026-05-01', params: ['May', '2026']))->toBeTrue();
});

it('returns true for last day of month', function () {
    expect(modify(value: '2026-05-31', params: ['May', '2026']))->toBeTrue();
});

it('returns false for day before month', function () {
    expect(modify(value: '2026-04-30', params: ['May', '2026']))->toBeFalse();
});

it('returns false for day after month', function () {
    expect(modify(value: '2026-06-01', params: ['May', '2026']))->toBeFalse();
});

it('returns true when date matches month from context', function () {
    expect(modify(value: '2026-05-15', context: ['get' => ['month' => 'May', 'year' => '2026']]))->toBeTrue();
});

it('returns false when date does not match month from context', function () {
    expect(modify(value: '2026-04-15', context: ['get' => ['month' => 'May', 'year' => '2026']]))->toBeFalse();
});

it('param takes precedence over context', function () {
    expect(modify(value: '2026-06-01', params: ['June', '2026'], context: ['get' => ['month' => 'May', 'year' => '2026']]))->toBeTrue();
});

it('uses current month when no param or context given', function () {
    CarbonImmutable::setTestNow('2026-05-15');

    expect(modify(value: '2026-05-01'))->toBeTrue();
    expect(modify(value: '2026-04-30'))->toBeFalse();

    CarbonImmutable::setTestNow();
});


function modify(string $value, array $params = [], array $context = []): bool
{
    return Modify::value($value)->context($context)->inMonth($params)->fetch();
}
