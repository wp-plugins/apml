=== Plugin Name ===
Contributors: pfefferle
Donate link: http://notizblog.org
Tags: APML, Attention Data, Feed, Taxonomy, Tags, Tag, Category, Categories, DataPortability
Requires at least: 2.0.11
Tested up to: 2.3.2
Stable tag: 2.3.1

This plugin creates an APML Feed using the the native tags and categories of WordPress 2.3.x,
but it also supports UltimateTagWarrior and SimpleTagging for WordPress 2.0.11 - 2.2.x. 

== Description ==

This plugin creates an APML Feed using the the native tags and categories of WordPress 2.3.x,
but it also supports UltimateTagWarrior and SimpleTagging for WordPress 2.0.11 - 2.2.x.

Features:

* WordPress 2.x Support
* Permalinks support
* Supports native Tagging or UltimateTagWarrior/SimpleTagging
* Matching (Search for relevant posts using your apml file)
* Separate APML files for Links, Feeds, Tags and Categories

You can find a demo file here: [notizBlog.org/apml/](http://notizblog.org/apml/).

== Installation ==

* Upload the `apml` folder to your `wp-content/plugins` folder
* Activate it at the admin interface

Thats it

If you have mod_rewrite activated, you can access it at:

* /ampl/
* wp-apml.php

else:

* index.php?apml=apml

== Frequently Asked Questions ==

== How to access the separate APML files ==

You can access for example the tags APML feed using:

* http://example.com/ampl/tags
* http://example.com/index.php?apml=tags

== What are the next features? ==

* A better matching algorithm

== Screenshots ==

You can find a demo file here: [notizBlog.org/apml/](http://notizblog.org/apml/).