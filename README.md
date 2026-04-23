## Meet Events

Dates, timezones, and calendars are hard. Events makes recurring, multi-day, and single events in Statamic easy. It provides a structured, battle-tested system for managing and displaying event data that respects timezones and removes the need to build complex date logic from scratch.

### Key Benefits

- **Drop-In Event Fieldset** – Start fast with a ready-to-use fieldset that handles recurrence, multi-day events, and timezones.
- **Render Calendars with Minimal Code** – Generate calendar views and event listings using simple template tags.
- **Add to Calendar (ICS)** – Let users download events and add them directly to their calendar.

---

## How It Works

After installing via Composer, create or use a standard Statamic collection with structured event fields. You can use the provided [sample fieldset](https://github.com/transformstudios/statamic-events/blob/main/resources/fieldsets/event.yaml) to get started quickly.

Once configured, use simple template tags like:

`events:upcoming`, `events:between`, `events:calendar`, and `events:today`

to query and display event data in your templates.

These tags return normalized event data so you can build anything from simple lists to full calendar interfaces. We also provide an [example calendar](https://github.com/transformstudios/statamic-events/blob/main/calendar.html) to help you get there faster.

---

## Documentation

For full setup, configuration, and usage details, view the [full Statamic Events addon docs](https://statamic.com/addons/transform/events/docs).