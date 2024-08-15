=== Easify Server WooCommerce ===
Contributors: easify
Donate link: http://www.easify.co.uk/
Tags: easify, epos, epos software, stock control software, accounting software, invoicing software, small business software, ecommerce, e-commerce, woothemes, wordpress ecommerce, woocommerce, shopping cart
Requires at least: 5.0
Tested up to: 6.6.1
Stable tag: 4.36
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
WC tested up to: 9.0

Connects Easify Business Software to your WooCommerce online shop,
allowing you to synchronise stock levels between your physical shop and your
online shop.

== Description ==
 
This plugin connects your Easify Business software with your
WooCommerce online shop.

Orders that are placed via your WooCommerce enabled website will be 
automatically sent to your Easify Server.

Products that you add to your Easify Server will be automatically uploaded to 
your WooCommerce enabled website.

As you sell products in your traditional shop, your stock levels will be 
automatically synchronised with your WooCommerce online shop.

== Installation ==

= Minimum Requirements =

* WordPress 5.0 or greater
* PHP version 7.4 or greater
* MySQL version 5.0 or greater
* Some payment gateways require fsockopen support (for IPN access)
* WooCommerce 5 or greater
* Easify V4.78 or greater
* Requires open outgoing ports in the range 1234 to 1260, some hosts may block these
* Requires open outgoing port 443, some hosts may block outgoing ports

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of the Easify WooCommerce Connector, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “Easify Server WooCommerce” and click Search Plugins. Once you’ve found our plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

= Manual installation =

The manual installation method involves downloading our plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

Alternatively you can download the plugin and upload it from within the Wordpress control panel, Add New > Upload Plugin option.

== Frequently Asked Questions ==

= What is Easify? =

Easify is a software application for small business that runs on PCs or laptops 
with Microsoft Windows.

It provides you with stock control, invoicing, quoting, purchasing, 
EPOS software, accounting, reporting and more...

= What does the Easify WooCommerce Connector do? =

This plugin connects your Easify Business Software with your
WooCommerce online shop.

Easify gives you EPOS, Stock Control, Billing, Purchasing and Accounting
all in one easy to use package.

With this plugin and WooCommerce, Orders that are placed via your WooCommerce 
enabled website will be automatically sent to your Easify Server.

Products that you add to your Easify Server using Easify Pro will be
automatically uploaded to your WooCommerce enabled website.

As you sell products in your traditional shop, your stock levels will be 
automatically synchronised with your WooCommerce online shop.

= Where do I get Easify Software? =
You can purchase Easify from our website - <https://www.easify.co.uk>

= Where do I get support? =

support@easify.co.uk


== Screenshots ==

1. Easify, the only software you need to run your small business, including stock control, billing, purchasing, accounting, reporting etc...
2. Setup, simply enter your Easify WooCommerce Plugin subscription details and the Easify Plugin will connect to your Easify Server automatically.
3. Orders, here you can configure how WooCommerce orders are sent to your Easify Server.
4. Customers, these settings are used when customers are automatically raised in Easify.
5. Coupons, the Easify WooCommerce Plugin supports WooCommerce coupons.
6. Shipping, map various WooCommerce shipping options to your Easify Server.
7. Payment, configure how WooCommerce payments are recorded in Easify.
8. Logging, if you need it you can enable detailed logging for the Easify WooCommerce Plugin.

== Changelog ==
= 4.36 =
* Support for Easify Version 5.
* Fixed issue where main product image would appear twice if multiple images present.
= 4.35 =
* Added support for WooCommerce Local Pickup delivery option.
* Improved logging to assist with troubleshooting Easify Server connection problems.
* Tested compatibility with PHP 8.x
= 4.34 =
* Resolved issue where disabling stock control for a product in Easify still caused stock levels to be uploaded to
* website when product modified in Easify.
= 4.33 =
* Added 'General' config tab to allow configuration of web server instance id to improve handling of records sent to
* Easify Server when connecting multiple websites to a single Easify Server.
= 4.32 =
* Fixed issue where order with WooCommerce product without a SKU value not being sent
to Easify Server.
= 4.31 =
* Resolved compatibility issue with older versions of PHP.
= 4.30 =
* Resolved issue where 'virtual' or 'downloadable' status of WooCommerce product could be reset
* under certain circumstances.
= 4.29 =
* Additional improvements to WooCommerce stock status back order handling.
= 4.28 =
* Resolved issue where for certain configurations, WooCommerce product inventory 'Allow backorders'
* could be set to 'Do not allow' when product stock levels updated.
= 4.27 =
* Resolved issue where customer billing postcode could appear in billing county in Easify Pro.
= 4.26 =
* Added support for WooCommerce coupons for mixed VAT orders.
* Resolved issue where for certain Easify WooCommerce Plugin product
* settings, product stock level and price changes were not getting
* uploaded to WooCommerce.
* Verified support for WordPress 5.7 and WooCommerce 5.0.
* Various code refactorings.
= 4.25 =
* Added support for native WooCommerce Stripe payment plugin on Payments tab of
Easify Plugin Settings page.
= 4.24 =
* Fixed scenario whereby for certain configurations product categories with an 
'&' character in the name could get duplicated in WooCommerce.
= 4.23 =
* Fixed issue whereby orders sent to Easify were not being marked as paid under
certain conditions.
= 4.22 =
* Fixed issue with default payment method not being sent to Easify when an order
is placed with a non standard payment method.
= 4.21 =
* Fixed issue with backorders not being allowed when stock level reaches zero.
= 4.20 =
* Added support for WooCommerce product variations. See https://www.easify.co.uk/Help/ecommerce-woocommerce-product-variations
= 4.19 =
* Added new Product settings options to allow products to be not uploaded from 
Easify when first published, also to ignore price and/or stock level changes
from Easify.
= 4.18 =
* Fixed issue where un-published product in Easify could still update stock 
level and price of product in WooCommerce.
= 4.17 =
* Fixed issue with previous update where product images and HTML description 
still being updated even if plugin set not to accept updates.
= 4.16 =
* Added an option to the Product options page to allow you to disable updates
from Easify. You can then use Easify to publish the product to WooCommerce, and
then use WooCommerce to edit product information without it being overwritten
by subsequent changes to the product in Easify. Easify product price changes and 
stock levels will synchronise to WooCommerce.
= 4.15 =
* Added Products Options Page that allows you to prevent Easify overwriting 
WooCommerce product categories when they are updated. This is useful if you 
prefer to manage your product categories in WooCommerce instead of automatically
using the Easify product categories.
= 4.14 =
* Tested up to V5.2.4
= 4.13 =
* Changed to use 24hr time when sending orders to Easify. 
* Added extra error reporting in the event of server 500 errors
= 4.12 =
* Fixed issue where products that go out of stock, and then come back into stock can be hidden from WooCommerce product search. 
= 4.11 =
* Fixed basic authorisation could error out when activating plugin on certain 
systems.
= 4.10 =
* Fixed issue with multiple image uploads when images are stored in local file system on Easify Server.
= 4.9 =
* Added support for multiple product images to be uploaded from Easify to the 
product WooCommerce gallery (requires you to be running Easify V4.56 or later).
* When stock level is set to zero, WooCommerce now displays 'Out of stock'.
= 4.8 =
* Fixed error when attempting to save Coupon or Shipping SKUs in Easify Settings.
= 4.7 =
* Resolved error where outbound Product Info update notifications could get stuck in the Easify Pro eCommerce Channel queue.
= 4.6 =
* Improved error handling when destination Easify Server unreachable.
* Tested with WordPress 4.9.
= 4.5.1 =
* Added support for product tags being uploaded from Easify V4.47 or later.
* Fixed issue where duplicate product images were uploaded whenever product updated.
* Fixed issue where wp-config not found on certain web servers.
* Modified product upload code to use json instead of XML.
= 4.4 =
* Improved support for 3rd party WooCommerce shipping plugins.
* Tested with WordPress 4.8
= 4.3 =
* Resolved source control issue.
= 4.2 =
* Fixed issue with debug logging when Easify plugin logging enabled.
= 4.1 =
* Improved basic authentication handling for certain web hosts
* Plugin now supports WordPress being installed in a subdirectory
* Fixed CURL error when running PHP > 7.0.7 and CURL < 7.41.0
= 4.0 =
* Initial release for Easify V4.x.

== Upgrade Notice ==
= 4.36 =
* Support for Easify Version 5.
* Fixed issue where main product image would appear twice if multiple images present.