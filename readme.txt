=== Custom Post Type Multiroutes ===
Contributors: arg82
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=UMNUKQHKN8AQC&item_name=Help+me+continue+developing+CPTMultiroutes+plugin%21&currency_code=EUR&source=url
Tags: routes, custom post types, multiroutes, translate slugs, multilingual, slugs
Requires at least: 4.9
Requires PHP: 5.6
Tested up to: 5.3.2
Stable tag: 0.1.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Allows to have 'custom post types' on multiple custom routes for single/archive pages. Compatible with WPML.

== Description ==

Custom Post Type Multiroutes allows you to manage, add, edit and update multiple routes for your Custom Post Types.
Minimum configuration needed. Install it, type your routes for your posts and refresh permalinks.

== Use case == 

Say you have a custom post type called 'Galleries' and you want it to appear on two archive pages:

`/news/gallery/`
`/media/album/`

And its single pages to be: `/news/gallery/<gallery-name>` and `/media/album/<gallery-name>`

All you have to do is:

1. Go to Settings > CPT Multiroutes (you will se a list of your custom post types)
2. Click on "Add Route" for your 'Galleries' post.
3. Fill the new imput for that new route, in this case 'news/gallery'
4. Repeat 2&3 per route you want to add.
5. Select the checkbox 'Rewrite this post type archive to these routes' if you want that route to be your archive page for the custom post type.
6. Click Save Routes.

Additionally a custom Metabox will appear on each of those custom post types on its admin edit page, there you will be able to choose individually on which route you wish the post to be visible. By default it will be visible on all defined routes.

If you are using WPML for multilanguage, when you add a route, an input for each acive language will appear so you can translate those new routes.

== Installation ==

1. Upload the entire `Custom-Post-Type-Multiroutes` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= Can i use this plugin for existing custom post types? =

Yes. It will work with all CPT with these properties: 'show_ui' => true, 'show_in_menu' => true.

= Can i translate the routes? =

Yes. But currently it's only compatible with WPML.

= Is there a route limit? = 

No, you can add as much routes as you need.

== Screenshots ==

1. Settings.
2. Admin custom post edit list.
3. Metabox on admin edit post page.

== Changelog ==

= 0.1.1 =
* Added admin edit post list column and filter by post cptmr_route postmeta.
* Bugfix.

= 0.1.0 =
* Initial release.
