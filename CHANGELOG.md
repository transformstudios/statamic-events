# Changelog

All notable changes will be documented here.

## 4.0 - 2022-04-22

- major refactor
- only support Statamic 3.3 and Laravel 8 & 9
- removed Calendar API (temporary)
- removed event filtering (temporary)

## 3.3.3 - 2022-02-02

- `has_end_time` is now correct

## 3.3.2 - 2022-01-28

- fix `events:between` pagination, thanks @enxoco

## 3.3.1 - 2022-01-25

- incorrect Day property name

## 3.3 - 2022-01-11

- add `between` pagination (#18)

## 3.2 - 2022-01-03

- `events:between` tag (#17)
- issue w/ recurring events that landed on a year boundary

## 3.1 - 2021-12-16

- add `has_end_time` variable

## 3.0 - 2021-11-04

* support PHP8+, Laravel 8+ & Statamic 3.2+
* default download is `ics`
* add `no_results` into `upcoming` tag data
