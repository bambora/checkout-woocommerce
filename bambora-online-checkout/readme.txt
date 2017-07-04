=== Bambora Online Checkout ===
Contributors: bambora
Tags: woocommerce, woo commerce, payment, payment gateway, gateway, bambora, checkout, integration, woocommerce bambora, woocommerce bambora online checkout, psp
Requires at least: 4.0.0
Tested up to: 4.8.0
Stable tag: 3.0.2
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrates Bambora Online Checkout payment gateway into your WooCommerce installation.

== Description ==
With Bambora online checkout for WooCommerce, you are able to integrate the Bambora Checkout payment window into your WooCommerce installation and start receiving secure online payments.

= Features =
* Receive payments securely through the Bambora Checkout payment window
* Get an overview over the status for your payments directly from your WooCommerce order page.
* Capture your payments directly from your WooCommerce order page.
* Credit your payments directly from your WooCommerce order page.
* Delete your payments directly from your WooCommerce order page.
* Supports WooCommerce 2.6+ and 3.0
* Supports WooCommerce Subscription 2.x

== Installation ==
1. Go to your WordPress administration page and log in. Example url: http://www.yourshop.com/wp-admin

2. In the left menu click **Plugins** -> **Add new**.

3. Click the **Upload plugin** button to open the **Add Plugins** dialog.

4. Click **Choose File** and browse to the folder where you saved the file from step 1. Select the file and click **Open**.

5. Click **Install Now**

6. Click **Activate Plugin** 

7. In the left menu click **WooCommerce** -> **Settings**.

8. Click the tab **Checkout** and select **Bambora**

9. Enter and adjust the settings which are described in the **Settings** section.

10. Click **Save Changes** when done and you are ready to use Bambora Checkout

<a href="http://dev.bambora.com/shopping-carts/guides/shopping-carts/woocommerce">Click here for more information about **Settings**</a>

== Changelog ==

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
* Fixes Bambora Checkout request

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

