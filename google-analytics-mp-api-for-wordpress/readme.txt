Provides an API for developers to easily send data to Google Analytics from the server using the Google Analytic's Measurement Protocol.

** For Developers Only or as a add-on to other plugins.  This will not do anything without writing some code unless another plugin has specifically done the work. **
This plugin provides some simple API to Google Analytic's Measurement Protocol.  It allows the server to send tracking data for times where a page is not loaded.  For example if you want to track when a cron job runs (like renewing a subscription) call the gamp_track function and send data directly from the server without the need for javascript or a page load.  With the help of the WP Content Listener plugin you can track pdf and image downloads.


**Usage**

Add a filter for 'gamp_google_analytics_id' and return your Google Universal Analytics ID.

Call 'gamp_track_pageview' to send a page view.

Call 'gamp_track_event' to send an event.

Call 'gamp_track' to send more customized analytics.


