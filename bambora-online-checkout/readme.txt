=== Bambora Online Checkout ===
Contributors: bambora
Tags: woocommerce, woo commerce, payment, payment gateway, gateway, bambora, checkout, integration, woocommerce bambora, woocommerce bambora online checkout, psp, subscription, subscriptions
Requires at least: 4.0.0
Tested up to: 5.8
Stable tag: 4.6.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
WC requires at least: 2.6
WC tested up to: 5.5

Integrates Bambora Online Checkout payment gateway into your WooCommerce installation.

== Description ==
With Bambora Online Checkout for WooCommerce, you are able to integrate the Bambora Online Checkout payment window into your WooCommerce installation and start receiving secure online payments.

= Features =
* Receive payments securely through the Bambora Online Checkout payment window
* Get an overview over the status for your payments directly from your WooCommerce order page.
* Capture your payments directly from your WooCommerce order page.
* Credit your payments directly from your WooCommerce order page.
* Delete your payments directly from your WooCommerce order page.
* Sign up, process, cancel, reactivate and change subscriptions
* Supports WooCommerce 2.6 and up.
* Supports WooCommerce Subscription 2.x and 3.x

== Installation ==
1. Go to your WordPress administration page and log in. Example url: https://www.yourshop.com/wp-admin

2. In the left menu click **Plugins** -> **Add new**.

3. Click the **Upload plugin** button to open the **Add Plugins** dialog.

4. Click **Choose File** and browse to the folder where you saved the file from step 1. Select the file and click **Open**.

5. Click **Install Now**

6. Click **Activate Plugin** 

7. In the left menu click **WooCommerce** -> **Settings**.

8. Click the tab **Checkout** and select **Bambora**

9. Enter and adjust the settings which are described in the **Settings** section.

10. Click **Save Changes** when done and you are ready to use Bambora Online Checkout

<a href="https://developer.bambora.com/europe/shopping-carts/shopping-carts/woocommerce">Click here for more information about **Settings**</a>

== Changelog ==

= 4.6.1 =
* Added functionality to allow LowValuePayments
* Improved transaction log history display

= 4.5.2 =
* Added fees to orderlines
* Only allow Collector to refund full orderlines.
* Minor fixes for shipping orderlines.


= 4.5.1 =
* Fix for possible division of zero/null when refunding without quantity.

= 4.5.0 =
* Fix for capture on status complete.
* Added support for setting user-role-based access to Refund, Capture & delete.
* Fix for compatibility with other payment gateways.
* Update of Capture & Refund flow for Collector
* Updated translations
* Instant Capture Setting for renewal of Subscriptions

= 4.4.3 =
* Fix for logosize

= 4.4.2 =
* Update of logo
* Fix for deprecated use of array_key_exists

= 4.4.1 =
* Security enhancements

= 4.4.0 =
* Change checkout integration for better Woocommerce support
* Overlay no longer supported

= 4.3.0 =
* Adds Capture on order status changed to Completed
* Adds Bulk capture on orders by order status Completed
* Fix for no status messages in Administration
* Refactoring of capture, refund and delete flows

= 4.2.4 =
* Adds fix for declineurl using encoded version of & 

= 4.2.3 =
* Changed MD5 validation to support appended GET parameters after the HASH parameter

= 4.2.2 =
* Fix for unsupported date/time function in WC below 3.1 

= 4.2.1 =
* Adds hooks for payment actions like capture, refund and delete
* Code refactoring to comply with WordPress 5.x 

= 4.2.0 =
* Adds support for multiple subscription sign-up
* Adds Norwegian Bokmaal translations

= 4.1.1 =
* Fix for ES6 syntax in Checkout Web SDK integration

= 4.1.0 =
* Adds new Checkout Web SDK

= 4.0.5 =
* Fix for transaction id gets rounded on callback

= 4.0.4 =
* Adds more logging and exception handling to getPaymentType
* Fix for log beeing flooded

= 4.0.3 =
* Adds Payment Type and logo to Order detail page in the backoffice

= 4.0.2 =
* Fix for renewal order status not beeing set to failed when subscription payments fails

= 4.0.1 =
* Adds Swedish translations

= 4.0.0 =
* Refactoring of module to comply with WooCommerce 3.x and WooCommerce Subscription 2.x standards
* Adds Change payment for subscriptions
* Adds Reactivation of failed subscriptions
* Adds Reactivation of canceled subscriptions
* Removed support for multiple-subscriptions
* Improved information flow
* Improved error handling
* Adds filter to callback url and callback completion
* Changed payment icons
* Code cleanup and performance enhancement
* Updates orders from earlier modules to comply with new standards
* Labels and Translations updated
* Adds support for WooCommerce Sequential Order Numbers
* Adds debug logging with access from module configuration page

= 3.0.5 =
* Fix for transaction id not found on capture, credit and delete

= 3.0.4 =
* Fix for transaction id not being saved in Woocommerce 3.x
* Adds check for bambora subscription id

= 3.0.3 =
* Changed bambora Logo size and css

= 3.0.2 =
* Improved quality for Bambora logo
* Improved quality for Bambora payment type logoes
* Adds rounding option in module configuration
* Fix for warning when using get order tax

= 3.0.1 =
* Fixes bug where a failed subscription cancellation could cause an unhandled exception

= 3.0.0 =
* Adds support for WooCommerce 3.0

= 2.1.2 =
* Fix for Bambora callback keeps posting when the order is completed

= 2.1.1 =
* Fix for format error in capture input field
* Fix for no error message displayed if capture, delete or refund fails
* Language updated

= 2.1.0 =
* Adds support for WooCommerce Subscription 2.x
* Changed module to load as Singleton
* Minor bug fixes and unused code clean up

= 2.0.1 =
* Adds better error description if callback fails
* Fix for getTransactionOperation error if request is bad
* Adds license header to project files
* Endpoints updated to new Bambora endpoints
* Link to documentation changed

= 2.0 =
* Module name changed from woocommerce-gateway-bambora to bambora-online-checkout
* Code refactored to comply with WordPress code standard

= 1.4.7 =
* Updates configurations
* Adds readme.txt for markedplace integration

= 1.4.6 =
* Added check for capture input field
* Added refresh on refund completed
* Fix for action params persistent in the url
* Added Danish language
* Added check for missing settings
* Changed how callback are handled
* Added response header
* Added math abs to refund order lines quantity and price
* Added style to error message
* New way to handle API key
* Better logic for handling transaction operations
* Added ECI value to transaction operations
* Code refactoring

= 1.4.5 =
* Adds additional header information to all Bambora API calls

= 1.4.4 =
* Improves compatibility with other payment gateway modules.
* Removes capturemulti from Checkout requests
* Fixes some styling issues of the admin-page meta box
* Improves settings backwards-compatibility when upgrading from older versions

= 1.4.3 =
* Fixes possible division by zero error

= 1.4.2 = 
* Fixes Bambora Online Checkout request

= 1.4.1 =
* Adds supported payment type icons on checkout page

= 1.4.0 =
* Adds support for Lindorff refund

= 1.3.0 =
* Adds support for Lindorff invoice lines

= 1.2.9 =
* Adds phone number country code from order billing information to checkout request
* Fixes order reference on callback
* Sets VERIFYPEER to false because of difficulties handling certificate file

