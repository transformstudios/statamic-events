## Events

This addon assumes the following data in an entry:

```
start_date: '2019-10-09 11:00'
duration: '1'
recurrence_type: weekly
recurrence_end_date: '2019-11-07'
```

### Fields

* `start_date` - **Required** - Start date **and** time of the event.
* `duration` - **Required** - How long is the event, in hours
* `recurrence_type` - **Optional** - One of `daily`, `weekly`, `monthly`, `annually`
* `recurrence_end_date` - **Optional** - when is the last event. If `recurrence_type` is set and this is not, the event goes on forever

### Usage

Both of these tags assume they are used in the context of an entry, i.e. you are in a `collection` loop.

#### Next Occurrence

Single tag that returns the next event date, after the current date.

*Example*:

```
{{ collection:events }}
    <p>{{ title }} - next date: {{ generate_events:next_occurrence }}</p>
{{ /collection:events }}
```

*Output*:

```
Event Title - next date: October 12th, 2019
```

#### Next Occurrences

Tag pair that returns the given number of next dates, from the current date/time.

Parameters:

* `number_of_occurrences` - how many future events?


*Example*:

```
{{ collection:events }}
    <p>{{ title }}</p>
    <ul>
        {{ generate_events:next_occurrences number_of_occurrences="3" }}
            <li>{{ occurrence }}</li>
        {{ /generate_events:next_occurrences }}
    </ul>
{{ /collection:events }}
```

*Output*:

```
Event 2

* October 12th, 2019
* October 13th, 2019
* October 14th, 2019
```




