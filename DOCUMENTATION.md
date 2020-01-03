This addon assumes the following data in an entry:

```
start_date: '2019-10-09'
start_time: '11:00'
end_time: '12:00'
recurrence: weekly
all-day: false
multi-day: false
end_date: '2019-11-07'

```

## Sample Fieldset

See [here](/event.yaml).


## Fields

### Single Day Events

* `start_date` - **Required** - Start date of the event.
* `start_time` - **Optional (see `all_day`)** - Start time of the event.
* `end_time` - **Optional (see `all_day`)** - Start date of the event.
* `all_day` - **Optional** - boolean. If this is `true`, then neither `start_time` nor `end_time` are required
* `recurrence` - **Optional** - One of `daily`, `weekly`, `monthly`, `annually`
* `end_date` - **Optional** - when is the last event. If `recurrence` is set and this is not, the event goes on forever

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

Tag pair that returns an entire month of dates, starting on a Sunday and ending on a Saturday. 2 required params, `collection` & `month`.

*Example*:

```
{{ events:calendar collection="events" month="october" }}
  {{ date }} {{# date of event #}}
  {{ if no_results }}
    {{# whatever you need to when for an empty day #}}
  {{ else }}
    {{ dates }}
      ...other entry data...
      {{ date }}
    {{ /dates }}
  {{ /if }}
{{ /events }}
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
    {{ if prev_page }}<a href="{{ prev_page }}"{{ /if }}
    {{ if next_page }}<a href="{{ next_page }}"{{ /if }}
  {{ /pagination }}
{{ /events:next }}
```
*Data*

* All the usual data (above), plus:
```
paginate:
  next_page: /events?page=3
  prev_page: /events?page=1
```
