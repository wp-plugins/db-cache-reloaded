=== Plugin Name ===
Contributors: sirzooro
Tags: performance, caching, wp-cache, db-cache, cache
Requires at least: 2.8
Tested up to: 2.8.4
Stable tag: 1.1

The fastest cache engine for WordPress, that produces cache of database queries with easy configuration - now with bugs fixed.

== Description ==

This plugin caches every database query with given lifetime. It is much faster than other html caching plugins and uses less disk space for caching.

I think you've heard of [WP-Cache](http://wordpress.org/extend/plugins/wp-cache/) or [WP Super Cache](http://wordpress.org/extend/plugins/wp-super-cache/), they are both top plugins for WordPress, which make your site faster and responsive. Forget about them - with DB Cache Reloaded your site will work much faster and will use less disk space for cached files. Your visitors will always get actual information in sidebars and server CPU loads will be as low as possible.

This plugin is a fork of a [DB Cache](http://wordpress.org/extend/plugins/db-cache/) plugin. Because his author did not updated its plugin to WordPress 2.8, I finally (after almost three months since release of WP 2.8) took his plugin and updated it so now it works with newest WordPress version. Additionally I fixed few bugs, cleaned up the code and make it more secure.

This plugin was tested with WordPress 2.8. It may work with earlier versions too - I have not tested. If you perform such tests, let me know of the results.

Available translations:

* English
* Polish (pl_PL) - done by me
* Italian (it_IT) - thanks [Iacopo](http://www.iacchi.org/)

[Changelog](http://wordpress.org/extend/plugins/db-cache-reloaded/changelog/)

== Installation ==

1. Upload `db-cache-reloaded` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure and enjoy :)

== Frequently Asked Questions ==

= How do I know my blog is being cached? =

Check your cache directory wp-content/tmp/ for cache files. Check the load statistics in footer.
Also you can set DBC_DEBUG to true in db-cache-reloaded.php file to display as hidden comments on your html page, what queries were loaded from cache and what from mysql.

= What does this plugin do? =

This plugin decreases count of queries to DB, which means that CPU load of your web-server decreases and your blog can serve much more visitors in one moment.

= What is page generation time? =

It is time from request to server (start of generation) and the generated page sent (end of generation). This time depends on server parameters: CPU speed, RAM size and the server load (how much requests it operates at the moment, popularity of sites hosted on the server) and of course it depends on how much program code it needs to operate for page generation.

Let set the fourth parameter as constant (we can't change the program code). So we have only 3: CPU, RAM and popularity.

If you have a powerful server (costs more) it means that will be as low as possible and it can serve for example 100 visitors in one moment without slowing down. And another server (low cost) with less CPU speed and RAM size, which can operate for example 10 visitors in one moment. So if the popularity of your site grows it is needed more time to generate the page. That's why you need to use any caching plugins to decrease the generation time.

= How can I ensure of reducing server usage? =

You can show usage statistics with your custom template in your footer.

Checking count of queries, ensure that other cache plugins are disabled, because you can see cached number.

View the source of your site page, there maybe some code like this at the foot:

`<!-- 00 queries. 00 seconds. -->`

If not, please put these codes in your footer template:

`<!-- <?php echo get_num_queries(); ?> queries. <?php timer_stop(1); ?> seconds. -->`

After using the DB Cache Reloaded, I think you'll find the number of queries reducing a lot.

= Why is DB Cache Reloaded better than WP Super Cache? =

This plugin is based on a fundamentally different principle of caching queries to database instead of full pages, which optimises WordPress from the very beginning and uses less disk space for cache files because it saves only useful information.
It saves information separately and also caches hidden requests to database.

Dmitry Svarytsevych analysed server load graphs of his sites and he can say that the peaks of server load are caused of search engines bots indexing your site (they load much pages practically in one moment). He has tried WP Super Cache to decrease the server loads but it was no help from it. Simply saying WP Super Cache saves any loaded page and much of these pages that are opened only once by bots. His original plugin (DB Cache) roughly saves parts of web-page (configuration, widgets, comments, content) separately, which means that once configuration is cached it will be loaded on every page.

Here is the Google translation of [Dmitry Svarytsevych's article](http://translate.google.com/translate?prev=&hl=uk&u=http%3A%2F%2Fwordpress.net.ua%2Fmaster%2Foptimizaciya-wordpress.html&sl=uk&tl=en) on it.

= Troubleshooting =

Make sure wp-content is writeable by the web server. If not you'll need to [chmod](http://codex.wordpress.org/Changing_File_Permissions) wp-content folder for writing.

= How do I uninstall DB Cache Reloaded? =

1. Disable it at Settings->DB Cache Reloaded page. The plugin will automatically delete all cache files. If something went wrong - delete /wp-content/db.php, /wp-content/db-config.ini and /wp-content/tmp folder. While db.php file exists WordPress will use our optimised DB class instead of own.
1. Deactivate it at plugins page.

== Changelog ==

= 1.1 =
* Added Polish translation;
* Added Italian translation (thanks Iacopo);
* Show error in admin section when wpdb class already exists (this should not happen, but I got few reports about this);
* Fix: do not cause fatal error when plugin is deleted manually without deactivating it first (in this case wp-content/db.php is left). Instead display error in admin section;
* Added support for custom plugin directory;
* Some performance improvements;
* Show message in admin section when plugin is activated but caching is not enabled

= 1.0.1 =
* Fix: statistics are not working

= 1.0 =
* Took [DB Cache 0.6](http://wordpress.org/extend/plugins/db-cache/) as a baseline;
* Merged changes done in WordPress 2.9 for the wpdb class (this fixes annoying tags bug);
* Cleaned up code, moved almost everything from global scope to class;
* Secured settings page with nonces;
* Switched to po/mo files for internationalisation
