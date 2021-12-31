<?php
namespace eLightUp\PluginUpdater;

class Notification {
	private $manager;
	private $option;

	public function __construct( Manager $manager, Checker $checker, Option $option ) {
		$this->manager = $manager;
		$this->checker = $checker;
		$this->option  = $option;
	}

	public function setup() {
		add_action( 'admin_notices', [ $this, 'notify' ] );
	}

	public function notify() {
		// Do not show notification on License page.
		if ( filter_input( INPUT_GET, 'page' ) === $this->manager->settings_page_slug ) {
			return;
		}

		$messages = [
			// Translators: %1$s - URL to the settings page, %2$s - URL to the pricing page, %3$s - plugin name.
			'no_key'  => __( 'You have not set a license key for %3$s yet, which means you are missing out on automatic updates and support! Please <a href="%1$s">enter your license key</a> or <a href="%2$s" target="_blank">get a new one here</a>.', 'elightup-plugin-updater' ),
			// Translators: %1$s - URL to the settings page, %2$s - URL to the pricing page, %3$s - plugin name.
			'invalid' => __( 'Your license key for %3$s is <b>invalid</b>. Please <a href="%1$s">update your license key</a> or <a href="%2$s" target="_blank">get a new one</a> to enable automatic updates.', 'elightup-plugin-updater' ),
			// Translators: %1$s - URL to the settings page, %2$s - URL to the pricing page, %3$s - plugin name.
			'error'   => __( 'Your license key for %3$s is <b>invalid</b>. Please <a href="%1$s">update your license key</a> or <a href="%2$s" target="_blank">get a new one</a> to enable automatic updates.', 'elightup-plugin-updater' ),
			// Translators: %1$s - URL to the settings page, %2$s - URL to the pricing page, %3$s - plugin name.
			'expired' => __( 'Your license key for %3$s is <b>expired</b>. Please renew your license to get automatic updates and premium support.', 'elightup-plugin-updater' ),
		];
		$status   = $this->option->get_license_status();
		if ( ! isset( $messages[ $status ] ) ) {
			return;
		}

		echo '<div class="notice notice-warning is-dismissible"><p><span class="dashicons dashicons-warning" style="color: #f56e28"></span> ', wp_kses_post( sprintf( $messages[ $status ], $this->manager->settings_page, $this->manager->buy_url, $this->manager->plugin->Name ) ), '</p></div>';
	}
}
