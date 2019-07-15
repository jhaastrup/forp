=== Sendbox-Shipping ===
Contributors: Sendbox, Adejoke Haastrup
Donate link: #
Tags: shipping, shipping zones, local shipping, international shipping
Requires at least: 3.0.1
Tested up to: 5.2
Stable tag: 4.3 
Requires PHP: 5.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

<blockquote>This is a woocommerce plugin that allows you ship form your store in nigeria to anywhere in the world.</blockquote>

== Description ==

<p>Sendbox-Shipping is a woocommerce plugin created by Sendbox.</p>

<p>To use this plguin, you have to create an account on  <a href="https://sendbox.co/" rel="follow"> Sendbox</a> and get your API key</p>

<p>It uses sendbox APIs and enables you ship from your store in nigeria to anywhere in the world.</p> 

<p>This plugin allows your woocommerce order status to automatically update from on hold to processing then completed.</p>

<p>Slug name for this plugin is wooss(woocommerce sendbox shipping), so don't be afraid when you see wooss when using the plugin.</p>

<p>You can add extra fees for yourself to sendbox shipping quotes.</p>




A few notes about the sections above:
*   Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for
stable.

    Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

    If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.

== Installation ==

<h4>Automatic installation</h4>
<p>Automatic installation is the easiest option as WordPress handles the file transfers itself and you do not need to leave your web browser. to
do an automatic installation. 
Login to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type "Sendbox-Shipping" and click Search Plugins. 
Once you find this plugin install it and activate it.</p>

<h4>Manual installation</h4>

<p>You can manually install this plugin by downloading the sendbox-shipping.zip from <a href = "">Here</a>
Then go to your wordpress dashboard and go into the plguin directory
Click on add new plugin 
Then you upload the zip file and click on install and then activate. </p>


== Frequently Asked Questions ==

===What do i need to install this plugin?===

<p>This plugin is dependent on woocommerce. For this plugin to work, ensure to have installed woocommerce plugin on your wordpress site</p>


===How do i get my API Key===
<p> To get your key, you should go to <a href = "https://sendbox.co/">sendbox</a>, create an account, navigate to 
settings on you sendbox dashboard, scroll and find your API Key.<p> 

===How do i use the API Key?===
<p>After copying your key from sendbox dashboard, come back to your wordpress dashboard, navigate to woocommerce shipping settings,
then you click on sendbox shipping, enable it by checking the check box. Paste your API Key and then click on connect to sendbox button</p> 

===Will this plugin update the order status on my site?=== 
<p>Sendbox shipping plugin automatically updates the order status of orders made on your site.
Be sure to set your woocommerce to auto email customers each time order status is change, your customers can know the status of their order
when it is updated. </p>


== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot 

1. Sendbox-Shipping settings page 
2. Request a new shippment 
3. Order status.

== Changelog ==

= 1.0 =
* A change since the previous version.
* Another change.

= 0.5 =
* List versions from most recent at top to oldest at bottom.

== Upgrade Notice ==

= 1.0 =
Upgrade notices describe the reason a user should upgrade.  No more than 300 characters.

= 0.5 =
This version fixes a security related bug.  Upgrade immediately.

== Arbitrary section ==

You may provide arbitrary sections, in the same format as the ones above.  This may be of use for extremely complicated
plugins where more information needs to be conveyed that doesn't fit into the categories of "description" or
"installation."  Arbitrary sections will be shown below the built-in sections outlined above.

== A brief Markdown Example ==

Ordered list:

1. Some feature
1. Another feature
1. Something else about the plugin

Unordered list:

* something
* something else
* third thing

Here's a link to [WordPress](http://wordpress.org/ "Your favorite software") and one to [Markdown's Syntax Documentation][markdown syntax].
Titles are optional, naturally.

[markdown syntax]: http://daringfireball.net/projects/markdown/syntax
            "Markdown is what the parser uses to process much of the readme file"

Markdown uses email style notation for blockquotes and I've been told:
> Asterisks for *emphasis*. Double it up  for **strong**.

`<?php code(); // goes in backticks ?>`