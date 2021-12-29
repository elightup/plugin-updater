<?php
namespace eLightUp\PluginUpdater;

class Manager {
	public $api_url;
	public $option_name;
	public $my_account_url;
	public $buy_url;
	public $slug;
	public $plugin;
	public $parent_page;

	public function __construct( $args ) {
		$this->api_url        = $args['api_url'];
		$this->my_account_url = $args['my_account_url'];
		$this->buy_url        = $args['buy_url'];
		$this->slug           = $args['slug'];
		$this->parent_page    = isset( $args['parent_page'] ) ? $args['parent_page'] : 'options-general.php';
		$this->option_name    = $this->slug . '_license';

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$this->plugin = (object) get_plugin_data( WP_PLUGIN_DIR . "/{$this->slug}/{$this->slug}.php" );
	}

	public function setup() {
		$option       = new Option( $this );
		$checker      = new Checker( $this, $option );
		$settings     = new Settings( $this, $checker, $option );
		$notification = new Notification( $this, $checker, $option );

		$settings->setup();
		$checker->setup();
		$notification->setup();
	}
}
