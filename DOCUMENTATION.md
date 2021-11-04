Table Of Contents:

* [Fieldset](#fieldset)
* [Settings](#settings)
* [Templating](#templating)
* [Fields](#fields)
* [Usage](#usage)


## Fieldset

In your collection's blueprint, make sure you have fields like in our sample [fieldset](https://github.com/transformstudios/statamic-events-v3/blob/main/resources/fieldsets/event.yaml).

You can also use our sample fieldset by publishing it `php artisan vendor:publish --tag=events-fieldsets`, then in your blueprint import the `Events` fieldset.

## Settings

Publish the config with `php artisan vendor:publish --tag=events-config`.

Then you can set the collection to use:
```php
return [
    'events_collection' => 'events',
];
```

## Templating

Please see the example [calendar](https://github.com/transformstudios/statamic-events-v3/blob/master/calendar.html) for how to create a monthly calendar using the `{{ events:calendar }}` tag.

## Fields

### Single Day Events

* `start_date` - **Required** - Start date of the event.
* `start_time` - **Optional (see `all_day`)** - Start time of the event.
* `end_time` - **Optional (see `all_day`)** - Start date of the event.
* `all_day` - **Optional** - boolean. If this is `true`, then neither `start_time` nor `end_time` are required

### Recurring Events

* `recurrence` - **Optional** - One of `daily`, `weekly`, `monthly`, `annually`, `every`
* `interval` - **Optional** - required if `recurrence` is `every` and indicates the frequency of the event
* `period` - **Optional** - required if `recurrence` is `every` and indicates the period of recurrence. One of `days`, `weeks`, `months`, `years`
* `end_date` - **Optional** - when is the last event. If `recurrence` is set and this is not, the event goes on forever
* `except_on` - **Optional** - a list of dates to **exclude**

### Multi-Day Events:
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

### Calendar

#### Tag

Tag pair that returns an entire month of dates, starting on a Sunday and ending on a Saturday.

Parameters:
* `month` - defaults to current month
* `year` - defaults to the current year

*Example*:

```
{{ events:calendar month="october" }}
  {{ date }} {{# date of event #}}
  {{ if no_results }}
    {{# whatever you need to when for an empty day #}}
  {{ else }}
    {{ dates }}
      ...other entry data...
      {{ date }}
    {{ /dates }}
  {{ /if }}
{{ /events:calendar }}
```

Full example [here](https://github.com/transformstudios/statamic-events/blob/master/calendar.html).

#### API

You can also retrieve this data via the endpoint `!/Events/calendar` and pass the `month` (defaults to current month) & `year` (defaults to current year)

#### Data

If there are no events on a given day, data returned is:

```
date: October 21, 2019
no_results: true
```

If there are events, each event has all the entry data **plus** `date` which is the next time this event happens:

```
date: October 21, 2019
dates:
  -
    ...
    date: Octover 22, 2019
    ...
  -
  ...
```

### In

Tag pair that returns a range of dates 2 required params, `collection` & `next`
* `collection` - which collection has the events
* `next` - a period that is [parsable](https://www.php.net/manual/en/datetime.formats.relative.php) by DateTime. Examples include `'2 weeks'`, `'90 days'`

*Example*:

```
{{ events:in collection="events" next="90 days" }}
  {{ date }} {{# date of event #}}
  {{ if no_results }}
    {{# whatever you need to when for an empty day #}}
  {{ else }}
    {{ dates }}
      ...other entry data...
      {{ date }}
    {{ /dates }}
  {{ /if }}
{{ /events:in }}
```

*Data*:

If there are no events on a given day, data returned is:

```
date: October 21, 2019
no_results: true
```

If there are events, each event has all the entry data **plus** `date` which is the next time this event happens:

```
date: October 21, 2019
dates:
  -
    ...
    date: Octover 22, 2019
    ...
  -
  ...
```
### Today

Tag pair that returns events today, 1 required param, `collection`
* `collection` - required - which collection has the events
* `ignore_finished` - boolean, optional, defaults to `false`. When true only current or future events are shown.

Data and templating like the `events:in` tag

### Upcoming Events

Tag pair that returns the next X event dates. 2 required params, `collection` & `limit`.

One optional param, only relevant on multi-day, all-day events, `collapse_multi_days`. When `true`, multi-day events will only show up once in the event list.


*Example*:

```
{{ events:upcoming collection="events" limit="2" }}
  {{ dates }}
    ...other entry data
    {{ date }}
  {{ /dates }}
{{ /events:upcoming }}
```

*Data*:

If there are no events on a given day, data returned is:

```
date: October 21, 2019
no_results: true
```

If there are events, each event has all the entry data **plus**:

* `date` - which is the next time this event happens
* `multi_day` - `true` when this is a multi-day event (like Thanksgiving or a conference)
  * when it is a multi-day event, there is also an array of `days` that contains:
    * `start_time`
    * `end_time`
    * `date`
* `all_day` - `true` when an all day event (like a holiday)


```
start_date: October 21, 2019
multi_day: true
all_day: true
...
date: Octover 22, 2019
```


If there are no more dates, `date` won't be there or will be null

**Pagination**

If you want to paginate the results, add `paginate="true"` to the tag. Then the tag will look for a `page` query parameter and paginate appropriately.

*Example*
```
{{ events:upcoming collection="events" limit="2" paginate="true" }}
  {{ dates }}
    ...other entry data
    {{ date }}
  {{ /dates }}
  {{ pagination }}
    {{ if prev_page }}<a href="{{ prev_page }}">Previous</a>{{ /if }}
    {{ if next_page }}<a href="{{ next_page }}">Next</a>{{ /if }}
  {{ /pagination }}
{{ /events:upcoming }}
```
*Data*

* All the usual data (above), plus:
```
paginate:
  next_page: /events?page=3
  prev_page: /events?page=1
```

### Download Links

Single Tag returns a url to download the event data and add it to your calendar. Tag presumes you are in an events context, i.e. your events entry

Parameters:

* `date` - required if a recurring event
* `type` - supported options are: `google`, `yahoo`, `webOutlook`, `ics`. Defaults to `ics`

*Example*:

```
<a href="{{ events:download_link
    start_date="{{ get:date }}" {{# getting the date from the `date` query param #}}
    type="google"
}}">Google</a>
```
