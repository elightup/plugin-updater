<?php
namespace eLightUp\PluginUpdater;

class Settings {
	protected $manager;
	protected $option;
	protected $checker;

	public function __construct( Manager $manager, Checker $checker, Option $option ) {
		$this->manager = $manager;
		$this->checker = $checker;
		$this->option  = $option;
	}

	public function setup() {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
	}

	public function add_settings_page() {
		// Translators: %s - The plugin name.
		$title = sprintf( __( '%s License', 'elightup-plugin-updater' ), $this->manager->plugin->Name );
		$page  = add_submenu_page(
			$this->manager->parent_page,
			$title,
			$title,
			'manage_options',
			$this->manager->slug . '-license',
			[ $this, 'render' ]
		);
		add_action( "load-{$page}", [ $this, 'handle_save' ] );
	}

	public function render() {
		?>
		<div class="wrap">
			<h1><?= esc_html( get_admin_page_title() ) ?></h1>
			<p>
				<?php
				// Translators: %s - The plugin name.
				echo esc_html( sprintf( __( 'Please enter your license key to enable automatic updates for %s.', 'elightup-plugin-updater' ), $this->manager->plugin->Name ) );
				?>
			</p>
			<p>
				<?php
				printf(
					// Translators: %1$s - URL to the My Account page, %2$s - URL to the pricing page.
					wp_kses_post( __( 'To get the license key, visit the <a href="%1$s" target="_blank">My Account</a> page. If you have not purchased any extension yet, please <a href="%2$s" target="_blank">get a new license here</a>.', 'elightup-plugin-updater' ) ),
					esc_url( $this->manager->my_account_url ),
					esc_url( $this->manager->buy_url )
				);
				?>
			</p>

			<form action="" method="post">
				<?php wp_nonce_field( 'save' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'License Key', 'elightup-plugin-updater' ); ?></th>
						<td>
							<?php $this->render_input() ?>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save Changes', 'elightup-plugin-updater' ) ); ?>
			</form>
		</div>
		<?php
	}

	protected function render_input() {
		$messages    = [
			// Translators: %1$s - URL to the buy page.
			'invalid' => __( 'Your license key is <b>invalid</b>. Please update your license key or <a href="%1$s" target="_blank">get a new one here</a>.', 'elightup-plugin-updater' ),
			// Translators: %1$s - URL to the buy page.
			'error'   => __( 'Your license key is <b>invalid</b>. Please update your license key or <a href="%1$s" target="_blank">get a new one here</a>.', 'elightup-plugin-updater' ),
			// Translators: %2$s - URL to the My Account page.
			'expired' => __( 'Your license key is <b>expired</b>. Please <a href="%2$s" target="_blank">renew your license</a>.', 'elightup-plugin-updater' ),
			'active'  => __( 'Your license key is <b>active</b>.', 'elightup-plugin-updater' ),
		];
		$status      = $this->option->get_license_status();
		$license_key = $this->option->get_license_key();
		?>
		<input class="regular-text" name="<?= esc_attr( $this->manager->option_name ) ?>[api_key]" value="<?= esc_attr( $license_key ) ?>" type="password">
		<?php if ( isset( $messages[ $status ] ) ) : ?>
			<p class="description"><?= wp_kses_post( sprintf( $messages[ $status ], $this->manager->buy_url, $this->manager->my_account_url ) ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function handle_save() {
		if ( empty( $_POST['submit'] ) ) {
			return;
		}
		check_admin_referer( 'save' );

		$this->save();

		add_action( 'admin_notices', 'settings_errors' );
	}

	public function save() {
		// @codingStandardsIgnoreLine.
		$option           = isset( $_POST[ $this->manager->option_name ] ) ? (array) $_POST[ $this->manager->option_name ] : [];
		$option['status'] = 'active';

		$args           = $option;
		$args['action'] = 'check_license';
		$response       = $this->checker->request( $args );
		$status         = isset( $response['status'] ) ? $response['status'] : 'invalid';

		if ( false === $response ) {
			add_settings_error( '', 'epu-error', __( 'Something wrong with the connection. Please try again later.', 'elightup-plugin-updater' ) );
		} elseif ( 'active' === $status ) {
			add_settings_error( '', 'epu-success', __( 'Your license is activated.', 'elightup-plugin-updater' ), 'updated' );
		} elseif ( 'expired' === $status ) {
			// Translators: %s - URL to the My Account page.
			$message = __( 'License expired. Please renew on the <a href="%s" target="_blank">My Account</a> page.', 'elightup-plugin-updater' );
			$message = wp_kses_post( sprintf( $message, $this->manager->my_account_url ) );

			add_settings_error( '', 'epu-expired', $message );
		} else {
			// Translators: %1$s - URL to the My Account page, %2$s - URL to the pricing page.
			$message = __( 'Invalid license. Please <a href="%1$s" target="_blank">check again</a> or <a href="%2$s" target="_blank">get a new license here</a>.', 'elightup-plugin-updater' );
			$message = wp_kses_post( sprintf( $message, $this->manager->my_account_url, $this->manager->buy_url ) );

			add_settings_error( '', 'epu-invalid', $message );
		}

		$option['status'] = $status;
		$this->option->update( $option );
	}
}
