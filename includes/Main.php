<?php
/**
 * Theme Onboarding
 *
 * @package    themeisle-onboarding
 */

namespace TIOB;

/**
 * Class Main
 */
class Main {
	/**
	 * The version of this library
	 *
	 * @var string Version string.
	 */
	const VERSION = '1.0.0';
	/**
	 * Sites Library API URL.
	 *
	 * @var string API root string.
	 */
	const API_ROOT = 'ti-sites-lib/v1';
	/**
	 * Storage for the remote fetched info.
	 *
	 * @var string Transient slug.
	 */
	const STORAGE_TRANSIENT = 'themeisle_sites_library_data';
	/**
	 * Onboarding Path Relative to theme dir.
	 *
	 * @var string Onboarding root path.
	 */
	const OBOARDING_PATH = '/vendor/codeinwp/themeisle-onboarding';
	/**
	 * Main
	 *
	 * @var Main
	 */
	protected static $instance = null;
	/**
	 * Admin
	 *
	 * @var Admin
	 */
	protected $admin = null;

	/**
	 * Method to return path to child class in a Reflective Way.
	 *
	 * @return string
	 * @since   1.0.0
	 * @access  public
	 */
	static public function get_dir() {
		return apply_filters( 'themeisle_site_import_uri', trailingslashit( get_template_directory_uri() ) . self::OBOARDING_PATH );
	}

	/**
	 * Instantiate the class.
	 *
	 * @static
	 * @return Main
	 * @since  1.0.0
	 * @access public
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Holds the sites data.
	 *
	 * @var null
	 */
	private function init() {
		if ( ! $this->should_load() ) {
			return;
		}
		$this->setup_admin();
		$this->setup_api();
	}

	/**
	 * Utility to check if sites library should be loaded.
	 *
	 * @return bool
	 */
	private function should_load() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$theme_support = get_theme_support( 'themeisle-demo-import' );
		if ( empty( $theme_support ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Setup admin functionality.
	 *
	 * @return void
	 */
	private function setup_admin() {
		$this->admin = new Admin();
		$this->admin->init();
	}

	/**
	 * Setup the restful functionality.
	 *
	 *
	 * @return void
	 */
	private function setup_api() {
		$api = new Rest_Server();
		$api->init();
	}

	/**
	 * Disallow object clone
	 *
	 * @access public
	 * @return void
	 * @since  1.0.0
	 */
	public function __clone() {
	}

	/**
	 * Disable un-serializing
	 *
	 * @access public
	 * @return void
	 * @since  1.0.0
	 */
	public function __wakeup() {
	}
}
