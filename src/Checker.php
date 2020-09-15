<?php
namespace eLightUp\PluginUpdater;

class Checker {
	private $manager;
	private $option;

	public function __construct( Manager $manager, Option $option ) {
		$this->manager = $manager;
		$this->option = $option;
	}

	public function setup() {
		add_action( 'init', [ $this, 'enable_update' ], 1 );
	}

	public function enable_update() {
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );
		add_filter( 'plugins_api', [ $this, 'get_info' ], 10, 3 );
	}

	public function check_update( $data ) {
		static $response = null;

		// Make sure to send remote request once.
		if ( null === $response ) {
			$response = $this->request();
		}

		if ( false === $response ) {
			return $data;
		}

		if ( empty( $data ) ) {
			$data = new \stdClass;
		}
		if ( ! isset( $data->response ) ) {
			$data->response = [];
		}

		if ( isset( $response['data']->new_version ) && version_compare( $this->manager->plugin->Version, $response['data']->new_version, '<' ) ) {
			$data->response[ $this->manager->slug ] = $response['data'];
		}

		$this->option->update( [
			'status' => $response['status'],
		] );

		return $data;
	}

	public function get_info( $data, $action, $args ) {
		if ( 'plugin_information' !== $action || ! isset( $args->slug ) || $args->slug !== $this->manager->slug ) {
			return $data;
		}

		$response = $this->request();

		return $response ? $response['data'] : $data;
	}

	public function request( $args = [] ) {
		$args = wp_parse_args( $args, [
			'action'  => 'get_info',
			'api_key' => $this->option->get_license_key(),
			'product' => $this->manager->slug,
		] );

		$request = wp_remote_post( $this->manager->api_url, [
			'body' => $args,
		] );

		$response = wp_remote_retrieve_body( $request );
		return $response ? @unserialize( $response ) : false;
	}
}
