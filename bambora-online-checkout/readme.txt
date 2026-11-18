=== Worldline Online Checkout ===
Contributors: bambora
Tags: woocommerce, payment gateway, worldline, bambora, subscriptions
Requires at least: 6.6
Tested up to: 7.1.4
Stable tag: 9.0.1
Requires PHP: 8.2
WC requires at least: 8.0
WC tested up to: 11.3.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Integrates Worldline Online Checkout payment gateway into your WooCommerce installation.

== Description ==
Bambora is now known as Worldline. As your payment partner, we’re now becoming stronger and better.
However, all the things that you love about Bambora will remain the same – the contract, the people, and the solutions.
This extension is for the European Merchants using the Worldline Online Checkout payment system.

= Seamless shopping experience with high security and conversion rates =

Worldline Online Checkout is a Payment Service Provider that provide payment solutions for all types of businesses. We offer functional, flexible e-commerce solutions to meet your every needs.
Whether you want a secure solution that is quick and easy to implement in your online store, or a fully integrated, customized solution that meets your specific needs,
Worldline Online Checkout solution has the answer.

With our payment service, your customers will enjoy an effortless shopping experience thanks to our user-friendly design and one-click payment system.
Customer card details are stored, so multiple purchases can be made with a single click.
Customer convenience, and top marks for your conversion! With this extension, you will get the Worldline Online Checkout payment system integrated into your store
and with a few clicks, you are able to start receiving secure online payments from all major payment cards

Our process of getting you on board is one of the fastest on the market. Simply create an account, and a few click later you are ready to go.

To get started with Worldline Online Checkout head over to our boarding page and signup for a free merchant test account in one of the following link:
<a href="https://boarding.bambora.com/CheckoutTestaccount-dk" >Get a free test account – Denmark</a>
<a href="https://boarding.bambora.com/CheckoutTestaccount-se" >Get a free test account – Sweden</a>
<a href="https://boarding.bambora.com/CheckoutTestaccount-no" >Get a free test account – Norway</a>
<a href="https://boarding.bambora.com/CheckoutTestaccount-GB" >Get a free test account – Europe</a>

Worldline Online Checkout Key Features
Fast payments
Safe, fast, direct daily deposits are made to the account of your choice.

All major payment cards accepted
Major payment cards, including Visa and MasterCard, are always accepted.
Smooth design
Be up and running within 24 hours
Extension Features

Information and Pricing:

For more details, please visit:
<a href="https://worldline.com/da-dk/home/main-navigation/solutions/merchants/solutions-and-services/online.html" >Worldline Online Checkout - Denmark</a>
<a href="https://worldline.com/sv-se/home/main-navigation/solutions/merchants/solutions-and-services/online.html" >Worldline Online Checkout - Sweden</a>
<a href="https://worldline.com/no-no/home/main-navigation/solutions/merchants/solutions-and-services/online.html" >Worldline Online Checkout - Norway</a>


= Security =
By using Worldline Online Checkout you won't have to worry about the security of your client’s payment details. The integration works by redirection the customer to our PCI-DSS secure servers where the customer enters their payment information directly into our secure environment so that the web shop never comes into contact with your payment data. When the payment is done the customer is redirected back to your web shop and our servers will notify your web shop that the payment is completed.
= Features =
* Receive payments securely through the Worldline Online Checkout payment window
* Get an overview over the status for your payments directly from your WooCommerce order page.
* Capture your payments directly from your WooCommerce order page.
* Credit your payments directly from your WooCommerce order page.
* Delete your payments directly from your WooCommerce order page.
* Create payment requests and send them to your customers directly from your WooCommerce order page.
* Sign up, process, cancel, reactivate and change subscriptions
* Supports WooCommerce
* Supports WooCommerce Subscription

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

10. Click **Save Changes** when done, and you are ready to use Worldline Online Checkout

<a href="https://developer.bambora.com/europe/shopping-carts/shopping-carts/woocommerce">Click here for more information about **Settings**</a>

== Upgrade Notice ==
= 9.0.1 =
Fixes callbacks being rejected with HTTP 400 ("The hash is not the last parameter") when the hosting adds parameters to the callback URL.

= 9.0.0 =
The MD5 key is now required. Configure the same MD5 key in the plugin settings and in the Worldline administration BEFORE you update. Without it the payment method is hidden at checkout and callbacks are rejected. Requires PHP 8.2 or newer.

== Changelog ==
= 9.0.1 =
Fixes:
* Fixed callbacks being rejected with HTTP 400 "The hash is not the last parameter" when the hosting adds parameters to the callback URL (ex. a rewrite rule, a CDN or a redirect). Parameters after the hash are not signed, so they are now ignored instead of rejected and are never used. The ignored parameters are written to the log

= 9.0.0 =
This is a major release. Please read the breaking changes before updating.

Breaking changes:
* The MD5 key is now required. Callbacks are rejected when no MD5 key is configured, and the orders are not marked as paid. Configure the same MD5 key in the plugin settings and in the Worldline administration before updating
* Callbacks are validated against the transaction in the Merchant API (order id, currency and amount)
* Callbacks with parameters after the hash are rejected
* Requires PHP 8.2 or newer
* Capture, delete and payment request actions in the order administration require the "edit_shop_orders" capability and the role selected in the plugin settings (administrators are always allowed). Users without the role no longer see the payment request actions
* Refunds via Worldline Online Checkout require the same capability and role
* Low Value Exemption is only used when the setting is enabled
* The payment method is hidden at checkout while the MD5 key is missing, and a notice is shown in the administration
* An order is put on hold instead of being marked as paid when the authorized amount or currency does not match the order (ex. the cart was changed after the payment window was opened). Developers can turn the check off with the filter bambora_online_checkout_validate_callback_amount
* Developers: Removed verify_action_nounce() and the GET based admin actions (?bambora_action=...). The actions are now posted to admin-post.php. Changed parameters of create_bambora_address(), get_event_extra() and the meta box callbacks. Replaced build_transaction_log_table() and build_transaction_log_rows() with build_transaction_history_html()

Fixes:
* Fixed amounts being one minor unit too low (ex. 19.99 charged as 19.98) with the rounding mode "Round down"
* Fixed TTD using 0 decimals instead of 2
* Fixed refunds below 1.00 being rejected
* Fixed full refund detection and refund line descriptions
* Fixed the billing address being sent as both billing and shipping address
* Fixed the subscription description not being sent to Worldline
* Fixed the Walley name in the transaction history
* Fixed payment type, bank logo and payment request created date in the order administration
* Fixed HPOS support for legacy transaction and subscription ids and for the order meta boxes
* Fixed payment requests using the wrong merchant with WPML
* Fixed blocks checkout reading the wrong settings
* Fixed escaping of notices in the order administration
* Fixed a React error in the browser console on the block checkout
* Fixed cancelling one subscription deleting the Worldline subscription shared with other subscriptions from the same order
* Fixed the Worldline subscription not being stored when a customer changes the payment method from another payment method to Worldline
* Fixed "Capture on status Completed" failing when the payment was already captured fully or partly. Only the remaining amount is captured
* Fixed the VAT percentage on refund lines always being 0
* Fixed an error for order lines with quantity 0
* Fixed empty error messages when the Worldline API does not answer with a valid response
* Fixed the payment request recipient being taken from the customer account instead of the billing address of the order
* Fixed the payment request status not being updated right after a payment request was created, sent or deleted
* An incomplete renewal order (missing currency or customer) is no longer sent to Worldline. The order fails with a note that explains why. WooCommerce Subscriptions can save such an order when the database rolls back part of it, ex. on MariaDB 11.6 or newer while another order is saved at the same time

Improvements:
* Refreshed the payment method at checkout: a small Worldline logo next to the title, smaller payment logos and the description as a short line underneath
* Redesigned the Worldline Online Checkout box on the order page to match the WordPress administration, including wallet details and a transaction history timeline
* Redesigned the payment request box on the order page: the amount and status at the top, a button to copy the payment link, a message over several lines, and a warning for an invalid email address
* The payment request column in the order list is now called "Payment Request" and shows the status as a badge, or "Unavailable" when the status cannot be retrieved from Worldline
* Added a warning in the administration and in Site Health when PHP is missing security fixes
* Validated the arguments of the payment types REST endpoint
* Scripts and styles are reloaded by the browser whenever they change
* Added a notice when the browser uses an outdated cached version of the administration script
* Notices in the administration are only shown to the user who performed the action
* Faster checkout and order administration: payment types, payment request permissions, payment request status and response code texts are cached for a short time
* The payment types request at checkout has a 5 second timeout
* The files of the module can no longer be requested directly
* Developers: Removed the unused Bambora_Online_Checkout_Helper::format_date_time(), format_number(), translate_action() and get_order_id()
* Developers: Removed Bambora_Online_Checkout_Api::get_checkout_payment_window_url() and get_checkout_payment_window_js_url(). They have not been used since version 4.1.0
* Developers: The URL of the payment logos is now in Bambora_Online_Checkout_Endpoints::get_payment_logos_endpoint()

Compatibility:
* Added support for WordPress 7.1.4
* Added support for Woocommerce 11.3.0
* Added support for Woocommerce Subscriptions 9.4.1

= 8.0.8 =
* Fix WP_Debug translation error
* Added support for WordPress 6.9.4
* Added support for Woocommerce 10.7.0
* Added support for Woocommerce Subscriptions 8.6.1

= 8.0.7 =
* Fix module version

= 8.0.6 =
* Fix bug related to Renewal date not marked correctly

= 8.0.5 =
* Fix bug related to Change Subscription Payment Method
* Added support for Woocommerce 10.4.3
* Added support for Woocommerce Subscriptions 8.3.1

= 8.0.4 =
* Added support for WordPress 6.9
* Added support for Woocommerce 10.4.2
* Added support for Woocommerce Subscriptions 8.2.0

= 8.0.3 =
* Set correct status on failed Subscription renewal
* Added support for WordPress 6.8.3
* Added support for Woocommerce 10.2.2
* Added support for Woocommerce Subscriptions 7.9.0 

= 8.0.2 =
* Fix user role bug for WC Refund

= 8.0.1 =
* Fix header content-length bug
* Fix Callback fatal error if order do not exist

= 8.0.0 =
* Added support for WordPress 6.8.1
* Added support for Woocommerce 9.8.4
* Added support for Woocommerce Subscriptions 7.4.0
* Added support for PHP 8.4
* Enhanced error handling, logging and performance
* Bug and translation fixes
* Code optimization and refactoring to comply with WordPress Code Standard

= 7.1.1 =
* Fix for build.

= 7.1.0 =
* Support for WooCommerce Blocks

= 7.0.1 =
* Rebranding to Worldline Online Checkout
* Fixes for Fatal Errors for some meta boxes
* Making sure amount is sent as integer to the api
* Wallet logos updated
* Updates to all translations

= 6.0.1 =
* Fix to make Finnish language work.

= 6.0.0 =
* Support for WooCommerce HPOS (High Performance Order Storage) and WooCommerce 8.2.
* Before upgrading to HPOS, please make sure to make a database backup. HPOS stores the orders in new database tables. Recommending compatibility mode to start with.

= 5.1.6 =
* Made sure division of zero does not happen when using 3rd party shipping plugin.

= 5.1.5 =
* Added adjustment orderline for orders where the rounding gives a minor error for the total.

= 5.1.4 =
* Declare that this module is not yet compatible with WooCommerce HPOS

= 5.1.2 =
* Fix for language-setting on payment window not working for other languages than English.

= 5.1.1 =
* Fix for Fatal Error when editing anything other than orders.

= 5.1.0 =
* Bambora Online Payment Requests is now part of the module. Contact Sales or Support if you would like to enable it.

= 5.0.2 =
* Make sure Walley Rounding change compatible with older version of php as well.

= 5.0.1 =
* Rounding changes for Walley

= 5.0.0 =
* Refactoring & formatting of code
* Added support for PHP 8.1
* Removed support for WooCommerce below 3.1
* Functionality to see if the merchants Bambora Credentials are valid in the settings tab
* Spelling fixes

= 4.7.2 =
* Makes sure default values are set if WPML is recently installed.

= 4.7.1 =
* Adds support for WPML. Subscriptions are not supported for WPML with settings other than default.

= 4.6.6 =
* Show Acquirer reference and bank logos for Direct Banking

= 4.6.5 =
* Added alt-tags to cards for accessibility.

= 4.6.4 =
* Added support for proxy. Settings for proxy should be made in wp-config.

= 4.6.3 =
* Logo change Rebranding Worldline

= 4.6.2 =
* Renaming of CollectorBank to Walley
* Fix for Type Exception
* Support for ISK

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
* Fix for log being flooded

= 4.0.3 =
* Adds Payment Type and logo to Order detail page in the backoffice

= 4.0.2 =
* Fix for renewal order status not being set to failed when subscription payments fails

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
* Improved quality for Bambora payment type logos
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
