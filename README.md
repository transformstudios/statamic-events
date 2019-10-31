## Events

This addon assumes the following data in an entry:

```
start_date: '2019-10-09 11:00'
duration: '1'
recurrence: weekly
end_date: '2019-11-07'
```

### Fields

* `start_date` - **Required** - Start date **and** time of the event.
* `duration` - **Required** - How long is the event, in hours
* `recurrence` - **Optional** - One of `daily`, `weekly`, `monthly`, `annually`
* `end_date` - **Optional** - when is the last event. If `recurrence` is set and this is not, the event goes on forever

### Usage

#### Calendar

Tag pair that returns an entire month of dates, starting on a Sunday and ending on a Saturday. 2 required params, `collection` & `month`.

*Example*:

```
{{ generate_events:calendar collection="events" month="october" }}
  {{ date }} {{# date of event #}}
  {{ if no_results }}
    {{# whatever you need to when for an empty day #}}
  {{ else }}
    {{ events }}
      ...other entry data...
      {{ next_date }}
    {{ /events}}
  {{ /if }}
{{ /generate_events }}
```

*Data*:

If there are no events on a given day, data returned is:

```
date: October 21, 2019
no_results: true
```

If there are events, each event has all the entry data **plus** `next_date` which is the next time this event happens:

```
date: October 21, 2019
events:
  -
    ...
    next_date: Octover 22, 2019
    ...
  -
  ...
```

#### Next Events

Tag pair that returns the next X event dates. 2 required params, `collection` & `limit`.


*Example*:

```
{{ generate_events:next_events collection="events" limit="2" }}
  {{ events }}
    ...other entry data
    {{ next_date }}
  {{ /events }}
{{ /generate_events:next_events }}
```

*Data*:

If there are no events on a given day, data returned is:

```
date: October 21, 2019
no_results: true
```

If there are events, each event has all the entry data **plus** `next_date` which is the next time this event happens:

```
date: October 21, 2019
...
next_date: Octover 22, 2019
```

If there are no more dates, `next_date` won't be there or will be null

**Pagination**

If you want to paginate the results, add `paginate="true"` to the tag. Then the tag will look for a `page` query parameter and paginate appropriately.

*Example*
```
{{ generate_events:next_events collection="events" limit="2" paginate="true" }}
  {{ events }}
    ...other entry data
    {{ next_date }}
  {{ /events }}
  {{ pagination }}
    {{ if prev_page }}<a href="{{ prev_page }}"{{ /if }}
    {{ if next_page }}<a href="{{ next_page }}"{{ /if }}
  {{ /pagination }}
{{ /generate_events:next_events }}
```
*Data*

* All the usual data (above), plus:
```
paginate:
  next_page: /events?page=3
  prev_page: /events?page=1
```
