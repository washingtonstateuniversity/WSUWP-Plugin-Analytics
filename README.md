# WSU Analytics

Provides tracking scripts and GA ID management for WSU WordPress sites.

## Analytics Settings

The settings page for WSU Analytics can be found in your dashboard under "Settings -> Analytics". The following options are available.

### Site Verification

* A [Google Site Verification](https://support.google.com/webmasters/topic/4564314?hl=en&ref_topic=4564315) entry is available to verify your site with Google for access to additional information. Use the [meta tag instructions](https://support.google.com/webmasters/answer/35659?hl=en&ref_topic=4564314) to find your Google Site Verification id.
    * In this example `<meta name="google-site-verification" content="rPLtSyh-8YGAAT9US7ogfbu32nSPzfoXfxyZrn6t1zA" />`, you would copy the characters from the `content=""` section of the tag.
* A [Bing Site Verification](http://www.bing.com/webmaster/help/how-to-verify-ownership-of-your-site-afcfefc6) entry is available to similarly verify your site with Bing.
    * As with the Google example, copy the characters from the `content=""` section in the meta tag.

### Google Analytics

Analytics will be tracked automatically at the global (WSU) and app (WordPress) platforms. Additional settings are provided to track specific site data and to improve the data tracked globally.

* **Google Analytics ID** - The Google Analytics UA ID used to track analytics for your individual site.
* **Campus** - The campus, if any, a site is associated with.
* **College** - The college, if any, a site is associated with.
* **Unit Type** - The type of unit this site is associated with.
* **Parent Unit** - A parent entity for the unit the site is associated with.
* **Unit** - The unit the site is associated with.

Additional, advanced options are provided.

* **Track jQuery UI Events** - If jQuery UI is in use on this site, should default events be tracked?
* **Track Site Analytics** - Should site analytics be turned on or off for this site?
    * This allows site analytics to be temporarily disabled even with a GA ID stored.

For network administrators and super admins, options are provided to turn on and off global and app analytics to aid in troubleshooting.

If a theme has decided to extend or override the default events provided by WSU Analytics, an option will appear to either extend or override. See the filters below for more advanced functionality.

## Extending Analytics

Multiple filters are available in the WSU Analytics plugin to extend functionality.

* `wsu_analytics_app_analytics_id` - Empty string by default. Provide a valid GA ID to track traffic at an application level.
* `wsu_analytics_events_override` - False by default. Return true if you would like to provide a custom events file at `wsu-analytics/events.js` in your child theme.
* `wsu_analytics_ui_events_override` - False by default. Return true if you would like to provide a custom jQuery UI events file at `wsu-analytics/ui-events.js` in your child theme.
* `wsu_analytics_local_debug` - False by default. Return true if you'd like to override how WSU Analytics handles local environments.