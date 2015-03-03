# WSU Analytics

Provides tracking scripts and GA ID management for WSU WordPress sites.

## Extending Analytics

Multiple filters are available in the WSU Analytics plugin to extend functionality.

* `wsu_analytics_app_analytics_id` - Empty string by default. Provide a valid GA ID to track traffic at an application level.
* `wsu_analytics_events_override` - False by default. Return true if you would like to provide a custom events file at `wsu-analytics/events.js` in your child theme.
* `wsu_analytics_ui_events_override` - False by default. Return true if you would like to provide a custom jQuery UI events file at `wsu-analytics/ui-events.js` in your child theme.
* `wsu_analytics_local_debug` - False by default. Return true if you'd like to override how WSU Analytics handles local environments.