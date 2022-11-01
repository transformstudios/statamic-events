Table Of Contents:

* [Upgrading](https://github.com/transformstudios/statamic-events/blob/master/UPGRADING.md)
* [Fieldset](#fieldset)
* [Fields](#fields)
* [Usage](#usage)

## Fieldset

In your collection's blueprint, make sure you have fields like in our sample [fieldset](https://github.com/transformstudios/statamic-events/blob/main/resources/fieldsets/event.yaml).

You can also use our sample fieldset by publishing it with `php artisan vendor:publish --tag=events-fieldsets`, then in your blueprint import the `Events` fieldset.

## Fields

### Single Day Events

* `start_date` - **Required** - Start date of the event.
* `start_time` - **Optional (see `all_day`)** - Start time of the event.
* `end_time` - **Optional (see `all_day`)** - Start date of the event.
* `all_day` - **Optional** - boolean. If this is `true`, then neither `start_time` nor `end_time` are required

### Recurring Events

* all the single day fields
* `recurrence` - **Optional** - One of `daily`, `weekly`, `monthly`, `annually`, `every`
* `interval` - **Optional** - required if `recurrence` is `every` and indicates the frequency of the event
* `period` - **Optional** - required if `recurrence` is `every` and indicates the period of recurrence. One of `days`, `weeks`, `months`, `years`
* `end_date` - **Optional** - when is the last event. If `recurrence` is set and this is not, the event goes on forever

### Multi-Day Events:

NOTE: the intention is that these days are consecutive.

* all the single day fields
* `multi_day` - boolean. When true, `start_date`, `start_time`, `end_time`, `recurrence`, `end_date` are not used
* `days` - **Required** - array of days:

```
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
  -
    date: '2019-11-25'
    start_time: '11:00'
    end_time: '15:00'
```

## Usage

### Query Tags (Between, In, Today)

Standard Entries are returned, with some extra data:

**Additional Variables**

* `start` - Carbon date/time of the occurrence start
* `end` - Carbon date/time of the occurrence end. Note if it's an all date event this will be set to 11:59:59pm
* `has_end_time` - Boolean that indicates if this occurrence has a set end time. In All Day events, this is `false`
* when it is a multi-day event, there is also an array of `days` that contains:
  * `start`
  * `end`
  * `has_end_time`

**Pagination**

Only supported on `between`, `in`, & `today` tags.
If you want to paginate the results, add `paginate="xx"`, where `xx` is the number of items per page, to the tag. The tag will look for a `page` query parameter and paginate appropriately.

The output is identical to the `collection` tag [pagination](https://statamic.dev/tags/collection#pagination).


*Example*
```
{{ events:between collection="events" from="Jan 1 2022" to="March 31 2022" paginate="2" }}
  {{ results }}
    ...entry data as explained above
  {{ /results }}
  {{ pagination }}
    {{ if prev_page }}<a href="{{ prev_page }}">Previous</a>{{ /if }}
    {{ if next_page }}<a href="{{ next_page }}">Next</a>{{ /if }}
  {{ /pagination }}
{{ /events:between }}
```

**Filtering**

Currently only simple taxonomy filtering is supported, using the same syntax as the `collection` tag:

```
{{ events:between from="Jan 1 2022" to="March 31 2022" taxonomy:categories="harry-potter" }}
...
{{ /events:between }}
```

Further filtering is coming.

### Between Tag

Tag pair that returns a range of dates.
3 required params, `collection`, `from` & `to`.

* `collection` - optional - which collection has the events, defaults to 'events'
* `from` - optional, date to start from, defaults to 'now'
* `to` - required, date to end at

*Example*:

```
{{ events:between collection="events" from="Jan 1 2022" to="March 31 2022" }}
  {{ if no_results }}
    {{# whatever you need to when for an empty day #}}
  {{ else }}
    {{ start }} {{ end }} {{ has_end_time }}
    ...other entry data...
  {{ /if }}
{{ /events:between }}
```

### Calendar

Tag pair that returns an entire month of dates, starting on the beginning of the week and ending on the end of the week.
This means, for example, that April 2022's calendar starts on March 27th and ends on May 7th.

Parameters:

* `collection` - optional - which collection has the events, defaults to 'events'
* `month` - optional, defaults to current month
* `year` - optional, defaults to the current year

Output:

A collection of dates, each one containing either `no_results` or `occurrences`, which list all the event occurrences on that particular date.

*Example*:

```
{{ events:calendar month="october" }}
  {{ date }} {{# date of event #}}
  {{ if no_results }}
    {{# whatever you need to when for an empty day #}}
  {{ else }}
    {{ occurrences }}
      {{ start }} {{ end }} {{ has_end_time }}
      ...other entry data...
    {{ /occurrences }}
  {{ /if }}
{{ /events:calendar }}
```

Full example [here](https://github.com/transformstudios/statamic-events/blob/master/calendar.html).


### In

Tag pair that returns a range of dates 2 required params, `collection` & `next`

* `collection` - optional - which collection has the events, defaults to 'events'
* `next` - a period that is [parsable](https://www.php.net/manual/en/datetime.formats.relative.php) by DateTime. Examples include `'2 weeks'`, `'90 days'`

*Example*:

```
{{ events:in collection="events" next="90 days" }}
  {{ if no_results }}
    {{# whatever you need to when for an empty day #}}
  {{ else }}
    {{ start }} {{ end }} {{ has_end_time }}
    ...other entry data...
  {{ /if }}
{{ /events:in }}
```

### Today

Tag pair that returns occurrences today:

* `collection` - optional - which collection has the events, defaults to 'events'
* `ignore_past` - boolean, optional, defaults to `false`. When true only current or future occurrences are shown.

Data and templating like the `events:in` tag

### Upcoming

Tag pair that returns the next X event dates.

* `collection` - optional - which collection has the events, defaults to 'events'
* `limit` - required, number of occurrences to return
* `collapse_multi_days` - optional, only relevant on multi-day, all-day events. When `true`, multi-day events will only show up once in the event list.

*Example*:

```
{{ events:upcoming collection="events" limit="2" }}
  {{ start }} {{ end }} {{ has_end_time }}
  ...other entry data
{{ /events:upcoming }}
```

### Download Links

Single Tag returns a url to the event data and add it to your calendar. Tag presumes you are in an events context, i.e. your events entry

Parameters:

* `date` - required if a recurring event
* `type` - supported options are: `google`, `yahoo`, `webOutlook`, `ics`. Defaults to `ics`

To give the file a name, use the `download` attribute.

*Example*:

```
<a
    download="event-{{ date format='Ymd' }}.ics"
    href="{{ events:download_link event="some-id" }}
    start_date="{{ get:date }}" {{# getting the date from the `date` query param #}}
    type="google"
}}">Google</a>
```
