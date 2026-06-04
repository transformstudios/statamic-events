<?php

use Carbon\CarbonImmutable;
use Statamic\Modifiers\Modify;

it('returns true when date is in param month', function () {
    expect(modifyInMonth(value: '2026-05-15', params: ['May']))->toBeTrue();
});

it('returns false when date is not in param month', function () {
    expect(modifyInMonth(value: '2026-04-15', params: ['May']))->toBeFalse();
});

it('returns true for first day of month', function () {
    expect(modifyInMonth(value: '2026-05-01', params: ['May']))->toBeTrue();
});

it('returns true for last day of month', function () {
    expect(modifyInMonth(value: '2026-05-31', params: ['May']))->toBeTrue();
});

it('returns false for day before month', function () {
    expect(modifyInMonth(value: '2026-04-30', params: ['May']))->toBeFalse();
});

it('returns false for day after month', function () {
    expect(modifyInMonth(value: '2026-06-01', params: ['May']))->toBeFalse();
});

it('returns true when date matches month from context', function () {
    expect(modifyInMonth(value: '2026-05-15', context: ['get' => ['month' => 'May']]))->toBeTrue();
});

it('returns false when date does not match month from context', function () {
    expect(modifyInMonth(value: '2026-04-15', context: ['get' => ['month' => 'May']]))->toBeFalse();
});

it('param takes precedence over context', function () {
    expect(modifyInMonth(value: '2026-06-01', params: ['June'], context: ['get' => ['month' => 'May']]))->toBeTrue();
});

it('uses current month when no param or context given', function () {
    CarbonImmutable::setTestNow('2026-05-15');

    expect(modifyInMonth(value: '2026-05-01'))->toBeTrue();
    expect(modifyInMonth(value: '2026-04-30'))->toBeFalse();

    CarbonImmutable::setTestNow();
});

function modifyInMonth(string $value, array $params = [], array $context = []): bool
{
    return Modify::value($value)->context($context)->inMonth($params)->fetch();
}
