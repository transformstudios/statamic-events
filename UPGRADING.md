# Upgrading from V3

First, please make sure you read the full [documenation](https://github.com/transformstudios/statamic-events/blob/master/DOCUMENTATION.md).

There are a few changes in v4 that require changes to your templates.

## General
* Output of the tags are now full entries, so all normal entry variables are available.
* There is no `{{ dates }}` pair anymore, now data is returned just like the `collection` tag. So if you had:

```
{{ events:upcoming .... }}
  {{ dates }}
    ....
  {{ /dates }}
{{ /events:upcoming }}
```

It would now look like:

```
{{ events:upcoming .... }}
    ....
{{ /events:upcoming }}
```

* There is no special `{{ date }}` variable any more. Instead there is `{{ start }}`, `{{ end }}`.

## Pagination
* Instead of `{{ ... paginate="true" limit="5" }}` you now do `{{ ... paginate="5" }}`
* Output is now like the `collection` tag's output and has a `{{ results }}` variable pair and a `{{ paginage}}` pair
