## Installation

Install via Composer:

```bash
composer require transformstudios/events
```

### Quick Start

Get up and running in three steps:

1. Create or use a Statamic collection (default: `events`)
2. Import the provided fieldset: `events::event`
3. Add a template:

```antlers
{{ events:upcoming limit="5" }}
  {{ title }} – {{ start }}
{{ /events:upcoming }}
```

You now have a working event listing.

---

## Configuration

### Default Timezone

By default, events use your application timezone (typically UTC).

You can override this in the Control Panel. This value is used when an event does not have a timezone set.

### Collection

The default collection is `events`.

If you are using a different Statamic collection, update it in the addon settings.

### ICS Downloads

ICS downloads use the following fields if they exist:

- `address`
- `coordinates`
- `description`

The `coordinates` field must be a keyed array:

```php
'coordinates' => [
    'latitude' => 40,
    'longitude' => 50,
],
```

If your field names differ from the defaults above, use a [Computed Value](https://statamic.dev/content-modeling/computed-values#defining-computed-values) to map them.

---

## Fieldset

Your collection blueprint must include the required event fields for Events to work correctly.

You can:

- Define fields manually
- Import the provided fieldset: `events::event`

Using the sample fieldset is the fastest way to get started.

---

## Fields

### Single-Day Events

| Field        | Required | Description |
|-------------|----------|-------------|
| `start_date` | Yes      | Start date of the event |
| `start_time` | No       | Start time (not required if `all_day` is true) |
| `end_time`   | No       | End time |
| `all_day`    | No       | If true, times are not required |

### Recurring Events

Extends single-day events with recurrence rules:

| Field            | Description |
|------------------|-------------|
| `recurrence`     | `daily`, `weekly`, `monthly`, `annually`, `every` |
| `specific_days`  | For rules like “3rd Tuesday” |
| `interval`       | Required when using `every` |
| `period`         | `days`, `weeks`, `months`, `years` |
| `end_date`       | Optional end to recurrence |
| `exclude_dates`  | Dates to exclude from occurrences |

If no `end_date` is set, the event continues indefinitely.

### Multi-Day Events

Use this when events span specific dates.

| Field       | Required | Description |
|------------|----------|-------------|
| `multi_day` | Yes      | Enables multi-day mode |
| `days`      | Yes      | Array of event days |

Example:

```yaml
multi_day: true
days:
  -
    date: '2019-11-23'
    start_time: '19:00'
    end_time: '21:00'
  -
    date: '2019-11-24'
    start_time: '11:00'
    end_time: '15:00'
```

When `multi_day` is enabled, standard single-day and recurrence fields are ignored.

---

## Usage

### Available Tags

- `events:between`
- `events:calendar`
- `events:in`
- `events:today`
- `events:upcoming`
- `events:download_link`

These tags return event occurrences (individual dates generated from your events).

### Common Parameters

| Parameter  | Description |
|------------|-------------|
| `site`     | Site handle (defaults to current site) |
| `timezone` | Adjusts all occurrences to this timezone |

### Returned Data

Each occurrence includes:

| Field           | Description |
|-----------------|-------------|
| `start`         | Start datetime |
| `end`           | End datetime |
| `has_end_time`  | Boolean |

Multi-day events also include a `days` array with per-day data.

### Pagination

Supported on:

- `between`
- `in`
- `today`

Example:

```antlers
paginate="10"
```

### Filtering

Supports both standard conditions and taxonomy filtering using standard Statamic syntax:

```antlers
taxonomy:categories:not="example" title:contains="awesome"
```

### Sorting

Default: ascending
To reverse:

```antlers
sort="desc"
```

---

## Tag Reference

### events:between

Returns events within a date range.

**Parameters:**
- `collection` (optional)
- `from` (optional, defaults to now)
- `to` (required)

### events:calendar

Returns a full calendar grid for a given month.

Each day contains either:
- `no_results`
- `occurrences`

Additional flags:
- `spanning`
- `spanning_start`
- `spanning_end`

### events:in

Returns events within a future time window.

Example:

```antlers
next="90 days"
```

### events:today

Returns events occurring today.

Optional:
- `ignore_past="true"`

### events:upcoming

Returns the next set of event occurrences.

**Parameters:**
- `limit` (required)
- `collection` (optional)
- `event` (optional)
- `collapse_multi_days` (optional)
- `offset` (optional)

### events:download_link

Generates an ICS download link.

Includes:
- `location`
- `description`
- `link`
