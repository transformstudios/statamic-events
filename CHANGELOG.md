# Changelog

## v5.6.2 - 2025-04-22

### 🐛 Fixed

- [5.x] Add support for PHP 8.4 & Laravel 12 [@edalzell](https://github.com/edalzell) (#112)
- Default first day of week to Sunday [@edalzell](https://github.com/edalzell) (#113)

## v5.6.1 - 2025-03-03

### 🐛 Fixed

- Make sure multi-day event days are in order [@edalzell](https://github.com/edalzell) (#109)
- Restrict to Carbon v2 [@edalzell](https://github.com/edalzell) (#108)

### 🧰 Maintenance

- Update cache action [@edalzell](https://github.com/edalzell) (#107)

## v5.6.0 - 2025-02-07

### 🚀 New

- Make `location` field configurable [@edalzell](https://github.com/edalzell) (#104)

## v5.5.0 - 2025-01-29

### 🚀 New

- Support By Day recurrence [@edalzell](https://github.com/edalzell) (#101)
- Add offset parameter [@cpswsg](https://github.com/cpswsg) (#99)

### 🧰 Maintenance

- Never upload assets when creating release [@edalzell](https://github.com/edalzell) (#102)
- Get test suite running [@edalzell](https://github.com/edalzell) (#100)

## v5.4.4 - 2024-12-17

### 🐛 Fixed

- Remove legacy `recurrence` computed field [@edalzell](https://github.com/edalzell) (#98)
- Use correct status filter @panda4man (#97)

## v5.4.3 - 2024-05-01

### 🐛 Fixed

- Use `collection` in config [@edalzell](https://github.com/edalzell) (#93)

## v5.4.2 - 2024-04-23

### 🐛 Fixed

- Fix config publishing [@edalzell](https://github.com/edalzell) (#92)

## v5.4.1 - 2024-04-17

### 🐛 Fixed

- Use `site` to get events [@edalzell](https://github.com/edalzell) (#91)

## v5.4 - 2024-04-11

### 🐛 New

- Add support for Laravel 11, Statamic 5, and Forma 3 [@edalzell](https://github.com/edalzell) (#90)

## v5.3.4 - 2024-03-26

### 🐛 Fixed

- Auto-publish config if it doesn’t exist [@edalzell](https://github.com/edalzell) (#89)

## v5.3.3 - 2024-02-21

### 🐛 Fixed

- Explicitly set `multi_day` [@edalzell](https://github.com/edalzell) (#86)

## v5.3.2 - 2024-02-21

### 🐛 Fixed

- Better handling of legacy multi-day events [@edalzell](https://github.com/edalzell) (#85)

## v5.3.1 - 2024-02-21

### 🐛 Fixed

- Improve legacy data handling [@edalzell](https://github.com/edalzell) (#84)

## v5.3.0 - 2024-02-14

### 🚀 New

- Re-organize fieldset fields [@edalzell](https://github.com/edalzell) (#83)
- Add ability to exclude dates from recurring events [@edalzell](https://github.com/edalzell) (#80)

### 🐛 Fixed

- Fix `end` timezone on recurring events that don't have an `end_time` set [@edalzell](https://github.com/edalzell) (#82)

## v5.2.0 - 2023-12-22

### 🚀 New

- Ability to sort occurrences [@edalzell](https://github.com/edalzell) (#77)

## v5.1.1 - 2023-11-22

### 🐛 Fixed

- Fix date parsing [@edalzell](https://github.com/edalzell) (#75)

## v5.1 - 2023-11-10

### 🚀 New

- Expose filtering to Tag [@DwainsWorld](https://github.com/DwainsWorld) (#71)

### 🐛 Fixed

- Add `preProcessIndex` [@edalzell](https://github.com/edalzell) (#72)
- Handle bad params and porrly formatted dates [@edalzell](https://github.com/edalzell) (#73)
- Prevent error about missing `$entry` [@edalzell](https://github.com/edalzell) (#74)

## v5.0.5 - 2023-10-11

### 🐛 Fixed

- Use first blueprint [@edalzell](https://github.com/edalzell) (#70)

## v5.0.4 - 2023-10-09

### 🐛 Fixed

- Fix timezones even more [@edalzell](https://github.com/edalzell) (#68)

## v5.0.3 - 2023-10-05

### 🐛 Fixed

- Add Timezone support [@edalzell](https://github.com/edalzell) (#67)
- Remove EntryQueryBuilder type declarations in Entry query [@DwainsWorld](https://github.com/DwainsWorld) (#65)
- Update Statamic version constraint for the Cascade class [@edalzell](https://github.com/edalzell) (#66)

## v5.0.2 - 2023-09-22

### 🐛 Fixed

- Fix pagination [@edalzell](https://github.com/edalzell) (#64)

## v5.0.1 - 2023-08-04

### 🐛 Fixed

- Return 404 when event doesn't exist [@edalzell](https://github.com/edalzell) (#62)

## v5.0 - 2023-05-16

### 🚀 New

- Update to require latest versions of all the things [@edalzell](https://github.com/edalzell) (#60)

## v4.2.0 - 2023-03-27

### 🚀 New

- Occurrences of single event [@edalzell](https://github.com/edalzell) (#58)

## v4.1.4 - 2023-03-17

### 🐛 Fixed

- Fix Multi-day, all-day events [@edalzell](https://github.com/edalzell) (#57)

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
