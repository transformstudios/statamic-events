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

#### Next Events

Tag pair that returns the next X event dates. 2 required params, `collection` & `limit`.


*Example*:

```
{{ events:next collection="events" limit="2" }}
  {{ dates }}
    ...other entry data
    {{ date }}
  {{ /dates }}
{{ /events:next }}
```

*Data*:

If there are no events on a given day, data returned is:

```
date: October 21, 2019
no_results: true
```

If there are events, each event has all the entry data **plus** `date` which is the next time this event happens:

```
start_date: October 21, 2019
...
date: Octover 22, 2019
```

If there are no more dates, `date` won't be there or will be null

**Pagination**

If you want to paginate the results, add `paginate="true"` to the tag. Then the tag will look for a `page` query parameter and paginate appropriately.

*Example*
```
{{ events:next collection="events" limit="2" paginate="true" }}
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
