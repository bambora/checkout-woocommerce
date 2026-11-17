<?php

defined( 'ABSPATH' ) || exit;
/**
 * Copyright (c) 2017. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (https://bambora.com)
 * @license   Bambora Online
 * @package   bambora_online_checkout
 */

/**
 * Warns the merchant when the server runs a PHP version without the latest security fixes.
 *
 * This only warns. The minimum PHP branch is enforced by WordPress via the "Requires PHP" plugin header.
 * Blocking on patch level is not done, since Linux distributions backport security fixes without changing the
 * version number, and blocking would stop payments without protecting anyone.
 */
class Bambora_Online_Checkout_Php_Version {

	/**
	 * The oldest patch release with all known security fixes for each supported PHP branch.
	 * Update this list when PHP releases security fixes.
	 *
	 * @var array<string, string>
	 */
	const MINIMUM_PATCHED_VERSIONS = array(
		'8.2' => '8.2.35',
		'8.3' => '8.3.36',
		'8.4' => '8.4.27',
		'8.5' => '8.5.12',
	);

	const STATUS_OK          = 'ok';
	const STATUS_OUTDATED    = 'outdated';
	const STATUS_UNSUPPORTED = 'unsupported';

	/**
	 * Register the admin notice and the Site Health test.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
		add_filter( 'site_status_tests', array( __CLASS__, 'add_site_health_test' ) );
	}

	/**
	 * Get the status of a PHP version.
	 *
	 * @param string $version - PHP version as major.minor.release (no distribution suffix).
	 * @return string One of the STATUS_ constants.
	 */
	public static function get_status( $version ) {
		$parts = explode( '.', $version );
		if ( count( $parts ) < 3 ) {
			return self::STATUS_UNSUPPORTED;
		}
		$branch = "{$parts[0]}.{$parts[1]}";

		if ( isset( self::MINIMUM_PATCHED_VERSIONS[ $branch ] ) ) {
			return version_compare( $version, self::MINIMUM_PATCHED_VERSIONS[ $branch ], '>=' ) ? self::STATUS_OK : self::STATUS_OUTDATED;
		}

		// A branch newer than the list is not flagged. Only older branches are unsupported.
		$branches = array_keys( self::MINIMUM_PATCHED_VERSIONS );
		return version_compare( $branch, reset( $branches ), '<' ) ? self::STATUS_UNSUPPORTED : self::STATUS_OK;
	}

	/**
	 * Get the running PHP version without any distribution suffix.
	 *
	 * @return string
	 */
	public static function get_running_version() {
		return PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
	}

	/**
	 * Get the warning message for the running PHP version, or null if it is up to date.
	 *
	 * @return string|null
	 */
	public static function get_warning_message() {
		$version = self::get_running_version();
		$status  = self::get_status( $version );
		if ( self::STATUS_OK === $status ) {
			return null;
		}

		if ( self::STATUS_UNSUPPORTED === $status ) {
			/* translators: 1: running PHP version, 2: list of supported PHP versions */
			$message = sprintf( __( 'Worldline Online Checkout: The server runs PHP %1$s, which no longer receives security fixes. Ask your hosting provider to upgrade to one of these versions or newer: %2$s.', 'bambora-online-checkout' ), $version, implode( ', ', self::MINIMUM_PATCHED_VERSIONS ) );
		} else {
			$branch = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
			/* translators: 1: running PHP version, 2: minimum patched PHP version */
			$message = sprintf( __( 'Worldline Online Checkout: The server runs PHP %1$s, which is missing security fixes. Ask your hosting provider to update PHP to %2$s or newer.', 'bambora-online-checkout' ), $version, self::MINIMUM_PATCHED_VERSIONS[ $branch ] );
		}

		if ( '' !== PHP_EXTRA_VERSION ) {
			$message .= ' ' . __( 'This PHP build is from a Linux distribution, which may include the security fixes without changing the version number. Check with your hosting provider.', 'bambora-online-checkout' );
		}

		return $message;
	}

	/**
	 * Show the warning on the plugins page and the WooCommerce settings pages.
	 *
	 * @return void
	 */
	public static function admin_notice() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce capability.
			return;
		}
		$screen = get_current_screen();
		if ( empty( $screen ) || ! in_array( $screen->id, array( 'plugins', 'woocommerce_page_wc-settings' ), true ) ) {
			return;
		}
		$message = self::get_warning_message();
		if ( null === $message ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Add the PHP version test to Site Health.
	 *
	 * @param array $tests - Site Health tests.
	 * @return array
	 */
	public static function add_site_health_test( $tests ) {
		$tests['direct']['bambora_online_checkout_php_version'] = array(
			'label' => __( 'Worldline Online Checkout PHP version', 'bambora-online-checkout' ),
			'test'  => array( __CLASS__, 'site_health_test' ),
		);
		return $tests;
	}

	/**
	 * Site Health test result.
	 *
	 * @return array
	 */
	public static function site_health_test() {
		$message = self::get_warning_message();
		$result  = array(
			'label'       => __( 'PHP has the latest security fixes', 'bambora-online-checkout' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'Security', 'bambora-online-checkout' ),
				'color' => 'blue',
			),
			/* translators: %s: running PHP version */
			'description' => '<p>' . esc_html( sprintf( __( 'The server runs PHP %s.', 'bambora-online-checkout' ), self::get_running_version() ) ) . '</p>',
			'test'        => 'bambora_online_checkout_php_version',
		);

		if ( null !== $message ) {
			$result['label']          = __( 'PHP is missing security fixes', 'bambora-online-checkout' );
			$result['status']         = 'recommended';
			$result['badge']['color'] = 'orange';
			$result['description']    = '<p>' . esc_html( $message ) . '</p>';
		}

		return $result;
	}
}
