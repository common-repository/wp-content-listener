=== WP-Content Listener ===
Contributors: webheadllc
Donate link: http://webheadcoder.com/donate-wp-content-listener
Tags: protect, track, wp-content, pdf, doc, analytics, downloads, access, private, hide
Requires at least: 3.9.0
Tested up to: 4.9.8
Stable tag: 0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Protect files, track downloads on Google Analytics, and run other custom code when a file in the wp-content folder is accessed.

== Description ==

**NOTE:  The .htaccess file needs to be writable in the wp-content sub-directories.**

This is meant to be a simple plugin that lets you:

* Protect files in wp-content (excluding the mu-plugins and plugins folders)
* Use Google Analytics to track access or downloads to files like pdf and doc files
* Run custom code when a file in wp-content is accessed

**CAUTION:** This plugin may cause your website to load slower if you have pages that include a lot of protected images.  Each image that is loaded needs to load WordPress files and check if a user is logged in, if the directory is protected, and run other custom code you may have.

= Usage =
To protect file downloads select the folders you want to protect in the WP-Content Listener's settings and check the protect checkbox.

To track file downloads you'll need a Google Analytics Universal account.  On WP-Content Listener's settings page, select the folders you want to track, click the option to track, and enter your Google Analtyics Universal ID.  You can view visits and other stats on the Google Analytics website under Behavior -> Events.  The Event will be in the "Files" Category and "Download" action.

To run custom code when a file is accessed or downloaded select the folders you want to run the custom code in the settings.  Then write your code and hook into the 'wpcl_before_file_download' action.  If you need to protect your files with specific access rules (more than just if the user is logged in or not), hook into the 'wpcl_access_filter' filter.  

This plugin did at one time work on IIS, but I cannot offer any support if anything does not currently work.  Try at your own risk.

For servers that don't use .htaccess to redirect, you'll basically need to redirect anything going to your protected directory to go to /wpcl-listen-slug/?f=$1 where $1 is the path to the file after relative to your protected directory.

== Screenshots ==

1. WP-Content-Listener Settings

== Developer Options ==

Useful hooks for customizations.

= wpcl_before_file_download =
Add to this action if you want to run custom code right before a file is accessed.

= wpcl_no_access =
Add to this action if you want to do something before or in place of redirecting to a 404 when access is denied.

= wpcl_not_monitoring =
Add to this action if you want to do something before or in place of redirecting to a 404 when directory is not monitored.

= wpcl_supported_mime_types =
Use this filter to add more mime types to monitor.  
Default: wp_get_mime_types()

= wpcl_access_filter =
Use this filter to customize access to files.  
Default: true if Protect option is not checked.  otherwise if user is logged in.

= wpcl_skip_public_exception =
Use this filter to return false if you want to protect images in published posts.  
Default: false

= wpcl_access_denied_location_string =
Use this filter to redirect the user another location when access is denied.  
Default: "Location: /404"

= wpcl_paths_to_monitor =
Use this filter to add or remove directories to monitor programmatically.  
Default: Directories chosen on the options page.

= wpcl_wp_content_directory =
Use this filter to change what directory is protected.  Make sure this is a subfolder with only items you want to protect and show.  This should not be (and cannot be) set to the root directory as it looks in ALL subdirectories.  

= WPCL_SIMPLE_REDIRECT = 
Set this constant in your theme's function.php file when your site is on hosts like WPEngine where redirects may not be handled by the .htaccess file.  You will need to add your own redirect rules.  See this plugin's settings page for more details.  
Default: 0

= wpcl_track_ga_parameters =
Use this filter to change the Google Analytics tracking parameters.  
Default: array( 'ec' => 'Files', 'ea' => 'Download', 'el' => $relative_file_name )

= wpcl_is_wpcl_url =
Use this filter to alter what determines a watched url.  
Default: true if the current url is the same as wpcl_url 

= wpcl_url =
Use this filter to change the url which watched files are redirected to.  
Default: home_url/wpcl-listen-slug

== Changelog ==

= 0.4 =
Added filter to change the directory in which this plugin monitors.

= 0.3 =
Updated to be compatible with PHP 7.  

= 0.2 =
Fix for IIS web.config - changed to redirect file url instead of rewrite.  
Added description on where tracking ends up in Google Analytics.  

= 0.1 =
Initial release.
