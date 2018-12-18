=== Zanto WP Translation (For Multisites) ===
Contributors: brooksX
Tags: translation, multilingual, localization, multisite, language switcher, languages, Internationalization, i18n, l10n
Requires at least: 3.0
Tested up to: 4.1.1
Stable tag: 0.3.4
License: GPLv2 or later

Zanto WP Translation helps you run a multilingual site by providing linkage between content in blogs of different languages in a WordPress multisite.

== Description ==

Zanto WP Translation enables you to convert blogs in a multisite into translations of each other. It provides a language switcher to switch between the different translations
of  pages, posts, categories, custom types and custom taxonomies. The plugin keeps track of what has been translated and what has not and provides an intuitive interface
that allows you to carry out translation. The number of languages you can run are unlimited. Get more WordPress multilingual, Localization and Internationalization addons from here
<a title="WordPress translation, localization and Internationalization addons" href="http://shop.zanto.org">multilingual addons</a> or keep up to-date with the best Internationalization and 
localization practices at <a title="WordPress translation, localization and Internationalization" href="http://zanto.org">zanto.org/blog</a>

= Demo and Documentation =
<a title="Demo" href="http://lang1.zanto.org">zanto.org/Demo</a>

= Features: =

* Translation of posts, categories, custom taxonomies, custom types.
* Browser Language re-direct  i.e re-direct users to their preferred language in their browser language settings.
* An easily customizable language switcher.
* Ability to use custom made Language switcher themes.
* Add a language parameter to the URL for SEO purposes
* Ability to create multiple translation networks within the same multisite. i.e blog A is a translation of Blog B and C. Blog X a translation of blog Z, while all blogs are in the same multisite.
* Different languages for both the front and back end.
* Each admin will have his admin language preferences stored
* Over 60 in-built languages and flags.
* Ability for users to add their own native languages i.e from the ones not included.
* Integrated support for domain mapping plugin
* Translated posts highlighting to prevent double translation
* Copy posts data from one blog to the translation area
* A translation editor to translate all posts from one blog to all others without switching between blogs. (Additional Translation Manager plugin required)
* Create and Manage translators from a single blog. Translators have their own dashboard. (Additional Translation Manager plugin required)
* Assign Translations to individual translators.(Additional Translation Manager plugin required)
* Export and Import XLIFF translations for posts taxonomies, comments, custom fields. (Additional Translation Manager plugin required)

= Tutorials =

Learn how to use Zanto WP Translation plugin with these tutorials 

* <a href="http://zanto.org/wordpress-translation-docs/">Documentation</a>
* <a href="http://zanto.org/wordpress-translation-docs/installing-zanto/">Installation</a>
* <a href="http://zanto.org/wordpress-translation-docs/language-url-formats/">Language Url formats</a>
* <a href="http://zanto.org/wordpress-translation-docs/browser-language-re-direct/">Browser Language Redirect</a>
* <a href="http://zanto.org/wordpress-translation-docs/language-switcher-custom-flags/">Custom flags for your language switcher</a>
* <a href="http://zanto.org/wordpress-translation-docs/creating-a-custom-language-switcher-theme/">Creating a custom language switcher theme</a>
* <a href="http://zanto.org/wordpress-translation-docs/sharing-users-across-the-wordpress-network/">Sharing users across the translation network</a>
* Many more to come!

= Translations =

* The french translation shall be ready soon for both the site and plugin

Please <a href="http://zanto.org/contact" target="_blank">let us know</a> if you would like to contribute a translation.


== Installation ==

Upload the Zanto WP Translation plugin to your WordPress plugin directory and activate it for each blog you want to do translations on or Network-wide if you want to do translation on all blogs in the multisite.

== Screenshots ==

1. Default Front end Language switcher added using either the inbuilt language switcher widget or custom code provided in the plugin settings that you place anywhere in your theme template.
2. Settings Section for downloading .mo files, changing your admin language or changing the Front end language settings.
3. Part of the blog Zanto WP Translation settings page
4. Admin Language Switcher
5. Setting up a translation network from available blogs in the multisite

== Changelog ==
= 0.3.3 =
* Added a page to easily collect debug information
* Made improvements to the custom language switcher logic to prevent it from making the page inaccessible when poorly loaded

= 0.3.2 =
* Added loading indicators to content being fetched by ajax on post translation page
* Fixed bug that would not allow loading of the plugin's .mo file
* Added French translation for the plugin.
* Added support for pretty permalinks in the Language switcher urls

= 0.3.1 =
* Fixed bug on the post translation page

= 0.3.0 =
* Changed Locale Manager to Language Manager for easy understanding
* Changed the plugin name to allow for more Internationalization/localization plugins to be created under the Zanto wing.
* Changed the method of defining custom flag URLS to enable fetching flags from anywhere in the content directory
* Improved language switcher theme logic. Now the Language switcher themes are fetched from both the parent theme and child themes of your WordPress themes
  as opposed to only the parent theme as was previously.
* Added theme header support for custom language themes similar to that used throughout wordpress so the authors can add relevant meta information such as theme name, theme author, 
  Author url, description, version.
* fixed issue of custom languages not saving well
* added support for duplicate language codes to allow for addition of languages with multiple locales eg. de_DE, de_LI which locales are both for German with the same language code "de".
* Fixed issue where only admin can login to the back end
* Made improvements on the way Zanto WP Translation handles notices. add_notice() function in now used to queue notices at any stage during program execution
* Added a method for queuing mail (Used in the added translation Manager plugin)

= 0.2.3 =
*Fixed an if statement bug that was causing some options not to save
= 0.2.2 =
*Fixed technical bugs related to php opening tags
*Added filters to allow hooking into and modifying how the plugin handles content without translation
= 0.2.1 =
*Fixed bugs that eluded us in 0.2.0
*Added gray highlighting for translated posts so you can tell the difference between the translated posts and un-translated posts when associating the posts
on the post edit page. You can read about the new changes here http://zanto.org/zanto-0-2-1-starting-new-year-high-spirits/

= 0.2.0 =
* Fixed language download bug when version number is only 2 levels. 
* On downloading languages, missing translation files will be searched for two versions back instead of one.
* Fixed front page language switcher bug when using URL's with language in directories or added as a parameter Zanto WP Translation feature.
* Integrated support for domain mapping for the language switcher when using the domain mapping plugin.
* Improved interface to better suite the WordPress admin (some interfaces were not displaying properly in version 3.8).
* Fixed Language switcher settings being over-written when general settings are saved.

== Frequently Asked Questions ==
= Does Zanto WP Translation work for single site installs =

Zanto WP Translation works specifically for multisite installs. to convert your single site to a multisite, follow <a href="http://codex.wordpress.org/Create_A_Network">this tutorial</a>

= Does Zanto WP Translation have support for RTL Languages =

Yes, Zanto WP Translation will work perfectly when a RTL language is detected

= Has Zanto WP Translation been tested with large amounts of data =
Yes, Zanto WP Translation has been subjected to performance tests when large amounts of data are involved and it passed without breaking a sweat.
For that very purpose, we created this testing plugin <a href="http://wordpress.org/plugins/multilingual-demo-data-creator/">multilingual demo data creator</a>

= Who runs Zanto WP Translation? =
Glad you asked that :) The plugin is meant to be a free community  plugin, we intend to move it to Github soon so we can have all who want to get involved not to miss out
on the fun. We hope to translate the Zanto website itself to other languages once we get volunteer individuals who are up to the task.

= Is support free =
Yes, we provide free support for all our plugins users who have been kind enough to use them :) We also have a <a href="http://zanto.org/support/">dedicated support forum</a> to make sure
you are never alone and stuck while using our plugins.

= Is there more to Zanto WP Translation? =
Yes, we have so many features in store under developement, keep tuned in at <a href="http://zanto.org/blog/">Our blog</a> and subscribe to our posts on to get the latest information on WordPress translation, new feature developments,
and participate in our forum. Get to decide what you want in your favorite free multilingual plugin and we'll fold our shirt sleeves to get it ready for the next version.


== Upgrade Notice ==

= 0.3.4 =
A couple of Bug fixes from 0.3.3

= 0.3.3 =
Upgrade to fix language switcher issues from the previous version. The new version has a debug page to easily send relevant information to make the debug process faster

= 0.3.2 =
Upgrade to fix bugs from the previous version. The latest version also has some improvements in the user interface that uses ajax functions.

= 0.3.1 =
Upgrade to fix bug on translation page 

= 0.3.0 =
Upgrade to fix all reported issues in the previous version. 
Becasue of the <a href="http://zanto.org/?p=274">plugin name change</a>, if you get any error after upgrade (expected behaviour), simply re-activate the plugin.

= 0.2.3 =
Upgrade to fix issue of some options not saving.

= 0.2.2 =
Upgrade to fix bug from previous version. 

= 0.2.1 =
Upgrade to fix bugs from previous version and better translation process by gray highlighting for translated posts so you can tell the difference between the translated posts and un-translated posts when associating the posts
on the post edit page. 

= 0.2.0 =
Upgrade to fix all bugs from the previous version and better integration with the domain mapping plugin.
