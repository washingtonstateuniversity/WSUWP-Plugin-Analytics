# WSU Analytics

[![Build Status](https://api.travis-ci.org/washingtonstateuniversity/WSUWP-Plugin-Analytics.svg?branch=master)](https://travis-ci.org/washingtonstateuniversity/WSUWP-Plugin-Analytics)

WSU Analytics is a WordPress plugin that provides tracking through Google Analytics for WSU WordPress sites using [Google Tag Manager](https://www.google.com/analytics/tag-manager/).

This plugin is activated globally on the [WSUWP Platform](https://github.com/washingtonstateuniversity/wsuwp-platform) but can be used on any individual WordPress site at WSU. See the filters at the bottom of this page for providing your own application level analytics. If you have any questions about implementation, please reach out to [Web Communication](https://web.wsu.edu).

## Analytics Settings

The settings page for WSU Analytics can be found in your dashboard under "Settings -> Analytics". The following options are available.

### Site Verification

* A [Google Site Verification](https://support.google.com/webmasters/topic/4564314?hl=en&ref_topic=4564315) entry is available to verify your site with Google for access to additional information. Use the [meta tag instructions](https://support.google.com/webmasters/answer/35659?hl=en&ref_topic=4564314) to find your Google Site Verification id.
    * In this example `<meta name="google-site-verification" content="rPLtSyh-8YGAAT9US7ogfbu32nSPzfoXfxyZrn6t1zA" />`, you would copy the characters from the `content=""` section of the tag.
* A [Bing Site Verification](http://www.bing.com/webmaster/help/how-to-verify-ownership-of-your-site-afcfefc6) entry is available to similarly verify your site with Bing.
    * As with the Google example, copy the characters from the `content=""` section in the meta tag.

### Google Analytics

Traffic and associated events will be tracked automatically at the global (WSU) and app (WordPress) platforms. Additional settings are provided to track specific site data and to improve the data tracked globally.

* **Google Analytics ID** - The Google Analytics UA ID used to track analytics for your individual site.
* **Campus** - The campus, if any, a site is associated with.
* **College** - The college, if any, a site is associated with.
* **Unit Type** - The type of unit this site is associated with.
* **Parent Unit** - A parent entity for the unit the site is associated with.
* **Unit** - The unit the site is associated with.

For global administrators, options are provided to turn on and off global, application, and site analytics to aid in troubleshooting.

If a theme has decided to extend or override the default events provided by WSU Analytics, an option will appear to either extend or override. See the filters below for more advanced functionality.

## Extending Analytics

Filters are available in the WSU Analytics plugin to extend functionality.

* `wsu_analytics_app_analytics_id` - Empty string by default. Provide a valid GA ID to track traffic at an application level.
* `wsu_analytics_local_debug` - False by default. Return true if you'd like to override how WSU Analytics handles local environments.
