<?php
/**
 * Rest Endpoints Handler.
 *
 * @package         themeisle-onboarding
 */

namespace TIOB;

use TIOB\Importers\Content_Importer;
use TIOB\Importers\Plugin_Importer;
use TIOB\Importers\Theme_Mods_Importer;
use TIOB\Importers\Widgets_Importer;
use TIOB\Importers\Zelle_Importer;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class Rest_Server
 */
class Rest_Server {

	/**
	 * Front Page Id
	 *
	 * @var int
	 */
	private $frontpage_id;

	/**
	 * The theme support contents.
	 *
	 * @var array
	 */
	private $theme_support = array();
	/**
	 * @var Plugin_Importer
	 */
	private $plugin_importer;
	/**
	 * @var Content_Importer
	 */
	private $content_importer;
	/**
	 * @var Theme_Mods_Importer
	 */
	private $customizer_importer;
	/**
	 * @var Widgets_Importer
	 */
	private $widgets_importer;

	/**
	 * The array that passes all template data to the app.
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * Initialize the rest functionality.
	 */
	public function init() {
		$this->content_importer    = new Content_Importer();
		$this->plugin_importer     = new Plugin_Importer();
		$this->customizer_importer = new Theme_Mods_Importer();
		$this->widgets_importer    = new Widgets_Importer();
		$this->setup_props();
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
	}

	/**
	 * Setup class properties.
	 */
	public function setup_props() {
		$theme_support = get_theme_support( 'themeisle-demo-import' );

		if ( empty( $theme_support[0] ) || ! is_array( $theme_support[0] ) ) {
			return;
		}

		$this->theme_support = $theme_support[0];
	}

	/**
	 * Register endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			Main::API_ROOT,
			'/install_plugins',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'run_plugin_importer' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
		register_rest_route(
			Main::API_ROOT,
			'/import_content',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'run_xml_importer' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
		register_rest_route(
			Main::API_ROOT,
			'/import_theme_mods',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'run_theme_mods_importer' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
		register_rest_route(
			Main::API_ROOT,
			'/import_widgets',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'run_widgets_importer' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
		register_rest_route(
			Main::API_ROOT,
			'/migrate_frontpage',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'run_front_page_migration' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
		register_rest_route(
			Main::API_ROOT,
			'/dismiss_migration',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'dismiss_migration' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Run the plugin importer.
	 *
	 * @param WP_REST_Request $request the async request.
	 *
	 * @return WP_REST_Response
	 */
	public function run_plugin_importer( WP_REST_Request $request ) {
		return $this->plugin_importer->install_plugins( $request );
	}

	/**
	 * Run the XML importer.
	 *
	 * @param WP_REST_Request $request the async request.
	 *
	 * @return WP_REST_Response
	 */
	public function run_xml_importer( WP_REST_Request $request ) {
		return $this->content_importer->import_remote_xml( $request );
	}

	/**
	 * Run the theme mods importer.
	 *
	 * @param WP_REST_Request $request the async request.
	 *
	 * @return WP_REST_Response
	 */
	public function run_theme_mods_importer( WP_REST_Request $request ) {
		return $this->customizer_importer->import_theme_mods( $request );
	}

	/**
	 * Run the widgets importer.
	 *
	 * @param WP_REST_Request $request the async request.
	 *
	 * @return WP_REST_Response
	 */
	public function run_widgets_importer( WP_REST_Request $request ) {
		$import = $this->widgets_importer->import_widgets( $request );

		set_theme_mod( 'ti_content_imported', 'yes' );

		return $import;
	}

	/**
	 * Run front page migration.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function run_front_page_migration( WP_REST_Request $request ) {
		$params = $request->get_json_params();

		if ( ! isset( $params['template'] ) || ! isset( $params['template_name'] ) ) {
			return new WP_REST_Response(
				array(
					'data'    => 'ti__ob_rest_err_5',
					'success' => false,
				)
			);
		}

		$migrator  = new Zelle_Importer();
		$old_theme = get_theme_mod( 'ti_prev_theme', 'ti_onboarding_undefined' );
		$import    = $migrator->import_zelle_frontpage( $params['template'], $old_theme );

		if ( is_wp_error( $import ) ) {
			return new WP_REST_Response(
				array(
					'data'    => $import->get_error_code(),
					'success' => false,
				)
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $import,
			)
		);
	}

	/**
	 * Dismiss the front page migration notice.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function dismiss_migration( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( ! isset( $params['theme_mod'] ) ) {
			return new WP_REST_Response(
				array(
					'data'    => 'ti__ob_rest_err_8',
					'success' => false,
				)
			);
		}
		set_theme_mod( $params['theme_mod'], 'yes' );

		return new WP_REST_Response( array( 'success' => true ) );
	}
}
