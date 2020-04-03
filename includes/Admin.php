<?php
/**
 * Handles admin logic for the onboarding.
 *
 * @package    themeisle-onboarding
 */

namespace TIOB;

/**
 * Class Themeisle_OB_Admin
 *
 * @package themeisle-onboarding
 */
class Admin {

	/**
	 * Initialize the Admin.
	 */
	public function init() {
		add_filter( 'query_vars', [ $this, 'add_onboarding_query_var' ] );
		add_action( 'after_switch_theme', [ $this, 'get_previous_theme' ] );
		add_filter( 'neve_dashboard_page_data', [ $this, 'localize_sites_library' ] );
	}

	/**
	 * Memorize the previous theme to later display the import template for it.
	 */
	public function get_previous_theme() {
		$previous_theme = strtolower( get_option( 'theme_switched' ) );
		set_theme_mod( 'ti_prev_theme', $previous_theme );
	}

	/**
	 * Add our onboarding query var.
	 *
	 * @param array $vars_array the registered query vars.
	 *
	 * @return array
	 */
	public function add_onboarding_query_var( $vars_array ) {
		array_push( $vars_array, 'onboarding' );

		return $vars_array;
	}

	/**
	 * Render the sites library.
	 */
	public function render_site_library() {
		if ( version_compare( PHP_VERSION, '5.4.0', '<' ) ) {
			echo '<div>' . apply_filters( 'themeisle_onboarding_phprequired_text', 'ti_ob_err_phpv_less_than_5-4-0' ) . '</div>';

			return;
		}

		if ( apply_filters( 'ti_onboarding_filter_module_status', true ) !== true ) {
			return;
		}

		$this->enqueue();
		?>
		<div class="ti-sites-lib__wrap">
			<div id="ti-sites-library">
				<app></app>
			</div>
		</div>
		<?php
	}

	/**
	 * Get return steps.
	 *
	 * @return array Import steps.
	 */
	private function get_import_steps() {
		return array(
			'plugins'    => array(
				'nicename' => __( 'Installing Plugins', 'textdomain' ),
				'done'     => 'no',
			),
			'content'    => array(
				'nicename' => __( 'Importing Content', 'textdomain' ),
				'done'     => 'no',
			),
			'theme_mods' => array(
				'nicename' => __( 'Setting Up Customizer', 'textdomain' ),
				'done'     => 'no',
			),
			'widgets'    => array(
				'nicename' => __( 'Importing Widgets', 'textdomain' ),
				'done'     => 'no',
			),
		);
	}

	/**
	 * Localize the sites library.
	 *
	 * @return array
	 */
	public function  localize_sites_library($array) {

		$theme = wp_get_theme();
		$api = array(
			'root'            => esc_url_raw( rest_url( Main::API_ROOT ) ),
			'nonce'           => wp_create_nonce( 'wp_rest' ),
			'homeUrl'         => esc_url( home_url() ),
			'i18ln'           => $this->get_strings(),
			'onboarding'      => 'no',
			'readyImport'     => '',
			'contentImported' => $this->escape_bool_text( get_theme_mod( 'ti_content_imported', 'no' ) ),
			'aboutUrl'        => esc_url( admin_url( 'themes.php?page=' . $theme->__get( 'stylesheet' ) . '-welcome' ) ),
			'importSteps'     => $this->get_import_steps(),
			'logUrl'          => Logger::get_instance()->get_log_url(),
		);

		$is_onboarding = isset( $_GET['onboarding'] ) && $_GET['onboarding'] === 'yes';
		if ( $is_onboarding ) {
			$api['onboarding'] = 'yes';
		}

		if ( isset( $_GET['readyimport'] ) ) {
			$api['readyImport'] = $_GET['readyimport'];
		}
		$array['onboarding'] = $api;

		return $array;
	}

	/**
	 * Get strings.
	 *
	 * @return array
	 */
	private function get_strings() {
		return array(
			'preview_btn'                 => __( 'Preview', 'textdomain' ),
			'import_btn'                  => __( 'Import', 'textdomain' ),
			'pro_btn'                     => __( 'Get the PRO version!', 'textdomain' ),
			'importing'                   => __( 'Importing', 'textdomain' ),
			'cancel_btn'                  => __( 'Cancel', 'textdomain' ),
			'loading'                     => __( 'Loading', 'textdomain' ),
			'go_to_site'                  => __( 'View Website', 'textdomain' ),
			'edit_template'               => __( 'Add your own content', 'textdomain' ),
			'back'                        => __( 'Back to Sites Library', 'textdomain' ),
			'note'                        => __( 'Note', 'textdomain' ),
			'advanced_options'            => __( 'Advanced Options', 'textdomain' ),
			'plugins'                     => __( 'Plugins', 'textdomain' ),
			'general'                     => __( 'General', 'textdomain' ),
			'later'                       => __( 'Keep current layout', 'textdomain' ),
			'search'                      => __( 'Search', 'textdomain' ),
			'content'                     => __( 'Content', 'textdomain' ),
			'customizer'                  => __( 'Customizer', 'textdomain' ),
			'widgets'                     => __( 'Widgets', 'textdomain' ),
			'backup_disclaimer'           => __( 'We recommend you backup your website content before attempting a full site import.', 'textdomain' ),
			'placeholders_disclaimer'     => __( 'Due to copyright issues, some of the demo images will not be imported and will be replaced by placeholder images.', 'textdomain' ),
			'placeholders_disclaimer_new' => __( 'Some of the demo images will not be imported and will be replaced by placeholder images.', 'textdomain' ),
			'unsplash_gallery_link'       => __( 'Here is our own collection of related images you can use for your site.', 'textdomain' ),
			'import_done'                 => __( 'Content was successfully imported. Enjoy your new site!', 'textdomain' ),
			'pro_demo'                    => __( 'Available in the PRO version', 'textdomain' ),
			'copy_error_code'             => __( 'Copy error code', 'textdomain' ),
			'download_error_log'          => __( 'Download error log', 'textdomain' ),
			'external_plugins_notice'     => __( 'To import this demo you have to install the following plugins:', 'textdomain' ),
			'error_report'                => sprintf(
				__( 'Hi! It seems there is a configuration issue with your server that\'s causing the import to fail. Please %1$s with us with the error code below, so we can help you fix this.', 'textdomain' ),
				sprintf( '<a href="https://themeisle.com/contact">%1$s <i class="dashicons dashicons-external"></i></a>', __( 'get in touch', 'textdomain' ) )
			),
		);
	}

	/**
	 * Escape settings that return 'yes', 'no'.
	 *
	 * @param $value
	 *
	 * @return string
	 */
	private function escape_bool_text( $value ) {
		$allowed = array( 'yes', 'no' );

		if ( ! in_array( $value, $allowed, true ) ) {
			return 'no';
		}

		return esc_html( $value );
	}
}
