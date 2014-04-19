=== Plugin Name ===

Contributors: hoyce
Donate link: 
Tags: google, google analytics, analytics, statistics, stats, javascript, steroids, gas, ga, web analytics
Requires at least: 3.4
Tested up to: 3.9
Stable tag: 1.3.2

== Description ==

GAS Injector for Wordpress is a plugin which makes it easy for you to start collecting statistics with Google Analytics on your WordPress blog. 
This plugin make use of Google Analytics on Steriods (GAS) for more advanced analytics tracking eg. outbound links, forms, movies, mailto links etc.
This plugin is based on the GAS project on Github: https://github.com/CardinalPath/gas
After the installation of the plugin you just click on the "GAS Injector" in the "Settings" menu and add your 
Google Tracking code (eg. UA-xxxxx-1) and your domain (eg. .mydomain.com) in the admin form.


This plugin also exclude the visits from the Administrator if he/she is currently logged in.

== Installation ==

Using the Plugin Manager

1. Click Plugins
2. Click Add New
3. Search for "gas injector"
4. Click Install
5. Click Install Now
6. Click Activate Plugin

Manually

1. Download and unzip the plugin
2. Upload the `gas-injector` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Add your Google Analytics tracking code and domain name.
2. Some additional settings for debugging and adding more GAS hooks for extended tracking.

== Changelog ==

= 1.0 =
* Initial release

= 1.1 =
* Added a disable function for each tracking option with jQuery based gui disable functionallity.
* Added a custom category label for each tracking option.
* Added an option to activate debug mode
* Fixed a bug so that translation is working (English and Swedish)
* Documentation fixes

= 1.1.1 =
* Minor language fixes

= 1.2 =
* Added option for using _anonymizeIp in the script
* Added detection for multiple loading of the same Google Analytics script e.g same UA-account multiple times

= 1.3 =
* Updated to GAS 1.11.0
* Added option for adding GAS hooks
* Added support for In Page Analytics / Enhanced Link Attribution
* GAS source file src is linked to Cloudflare
* Removed swedish translation
* Added screenshots

= 1.3.1 =
* Excluding logged in admins (bug fix)
* Added DC.JS support
* Tested stability up to Wordpress 3.8.1

= 1.3.2 =
* Added this plugin to Github https://github.com/hoyce/gas-injector
* Tested stability up to Wordpress 3.9