<?php
namespace eLightUp\PluginUpdater;

class Option {
	private $manager;

	public function __construct( Manager $manager ) {
		$this->manager = $manager;
	}

	public function get( $name, $default = null ) {
		$option = get_option( $this->manager->option_name, [] );
		return isset( $option[ $name ] ) ? $option[ $name ] : $default;
	}

	public function get_license_key() {
		return $this->get( 'api_key' );
	}

	public function get_license_status() {
		return $this->get_license_key() ? $this->get( 'status', 'active' ) : 'no_key';
	}

	public function update( $option ) {
		$old_option = (array) get_option( $this->manager->option_name, [] );
		$option     = array_merge( $old_option, $option );
		update_option( $this->manager->option_name, $option );
	}
}
