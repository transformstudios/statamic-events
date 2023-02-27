# Changelog

## v4.1.3 - 2023-02-27

### 🐛 Fixed

- Fix `limit` on `upcoming` tag. [@edalzell](https://github.com/edalzell) (#56)

## v4.1.2 - 2023-02-03

### 🐛 Fixed

- Don't set `id` [@edalzell](https://github.com/edalzell) (#53)

## v4.1.1 - 2023-01-23

### 🐛 Fixed

- Add the `id` due to Statamic runtime parser issue [@edalzell](https://github.com/edalzell) (#51)

## v4.1.0 - 2023-01-18

### 🚀 New

- Add location to ics [@edalzell](https://github.com/edalzell) (#50)

### 🔧 Improved

- 🔄 Synced file(s) with edalzell/.github [@edalzell](https://github.com/edalzell) (#48)

## v4.0.7 - 2022-11-01

### 🐛 Fixed

- Remove unused config file [@edalzell](https://github.com/edalzell) (#47)
- Typo in docs [@edalzell](https://github.com/edalzell) (#46)

### 🔧 Improved

- 🔄 Synced file(s) with edalzell/.github [@edalzell](https://github.com/edalzell) (#44)
- 🔄 Synced file(s) with edalzell/.github [@edalzell](https://github.com/edalzell) (#43)
- 🔄 Synced file(s) with edalzell/.github [@edalzell](https://github.com/edalzell) (#42)

## v4.0.6 - 2022-06-22

### Changes

### 🐛 Fixed

- Fix iCal downloads for single day events @edalzell (#41)

## 4.0.5 - 2022-06-08

- handle empty taxonomy param
- handle multi-day days edge case

## 4.0.4 - 2022-06-08

- fix start/end week modifiers to be correct

## 4.0.3 - 2022-05-27

- all day logic was incorrect
- add `collapse_multi_days` to the supplemental data
- filter values can be different types

## 4.0.2 - 2022-05-26

- proper single day event end time

## 4.0.1 - 2022-05-23

- set the carbon locale from the current site

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

- support PHP8+, Laravel 8+ & Statamic 3.2+
- default download is `ics`
- add `no_results` into `upcoming` tag data
