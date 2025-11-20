<?php
/**
 * Plugin Name:       Query Loop Enhancements
 * Plugin URI:        https://queryloopblock.com?utm_source=QLE&utm_campaign=QLE+visit+site&utm_medium=link&utm_content=Plugin+URI
 * Description:       Adds advanced filtering, sorting, meta query, infinite loop to the Query Loop block, enhancing its capabilities for better content display and management.
 * Requires at least: 6.8
 * Requires PHP:      8.0
 * Version:           1.0.0
 * Author:            Phi Phan
 * Author URI:        https://queryloopblock.com?utm_source=QLE&utm_campaign=QLE+visit+site&utm_medium=link&utm_content=Author+URI
 * License:           GPL-3.0
 *
 * @package           QLE
 * @copyright         Copyright(c) 2025, Phi Phan
 */

namespace QLE;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


if ( ! class_exists( QueryLoopEnhancements::class ) ) :
	/**
	 * The main class
	 */
	class QueryLoopEnhancements {
		/**
		 * Plugin version
		 *
		 * @var String
		 */
		public $version = '1.0.0';

		/**
		 * Components
		 *
		 * @var Array
		 */
		protected $components = [];

		/**
		 * The suffix for scripts
		 *
		 * @var string
		 */
		protected $script_suffix = '';

		/**
		 * Plugin instance
		 *
		 * @var QueryLoopEnhancements
		 */
		private static $instance;

		/**
		 * A dummy constructor
		 */
		private function __construct() {}

		/**
		 * Initialize the instance.
		 *
		 * @return QueryLoopEnhancements
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new QueryLoopEnhancements();
				self::$instance->initialize();
			}

			return self::$instance;
		}

		/**
		 * Kick start function.
		 * Define constants
		 * Load dependencies
		 * Register components
		 * Run the main hooks
		 *
		 * @return void
		 */
		public function initialize() {
			// Setup constants.
			$this->setup_constants();

			// Load dependencies.
			$this->load_dependencies();

			// Register components.
			$this->register_components();

			// Run hooks.
			$this->run();
		}

		/**
		 * Setup constants
		 *
		 * @return void
		 */
		public function setup_constants() {
			$this->define_constant( 'QLE', true );
			$this->define_constant( 'QLE_ROOT_FILE', __FILE__ );
			$this->define_constant( 'QLE_VERSION', $this->version );
			$this->define_constant( 'QLE_PATH', trailingslashit( plugin_dir_path( QLE_ROOT_FILE ) ) );
			$this->define_constant( 'QLE_URL', trailingslashit( plugin_dir_url( QLE_ROOT_FILE ) ) );
			$this->define_constant( 'QLE_EDITOR_SCRIPTS_HANDLE', 'qle-editor-scripts' );
			$this->define_constant( 'QLE_EDITOR_STYLE_HANDLE', 'qle-editor-style' );
			$this->define_constant( 'QLE_FRONTEND_STYLE_HANDLE', 'qle-frontend-style' );
		}

		/**
		 * Load components
		 *
		 * @return void
		 */
		public function register_components() {
			// Load & register core components.
			$components = [];

			foreach ( $components as $file => $classname ) {
				$this->register_component( $file, $classname );
			}

			// Register additional components.
			do_action( 'qle/register_components', $this );
		}

		/**
		 * Load dependencies
		 *
		 * @return void
		 */
		public function load_dependencies() {
			// Load core component.
			$this->include_file( 'includes/core-component.php' );
		}

		/**
		 * Run main hooks
		 *
		 * @return void
		 */
		public function run() {
			// Init hook.
			add_action( 'init', [ $this, 'init' ], 5 );

			// Enqueue scripts for editor.
			add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );

			// Save version and trigger upgraded hook.
			add_action( 'plugins_loaded', [ $this, 'version_upgrade' ], 1 );

			// Run all components.
			foreach ( $this->components as $component ) {
				$component->run();
			}
		}

		/**
		 * Process on init hook
		 *
		 * @return void
		 */
		public function init() {
			/**
			 * Fires when QLE is initialized.
			 * Run before components are running.
			 */
			do_action( 'qle/init' );
		}

		/**
		 * Enqueue editor assets
		 *
		 * @return void
		 */
		public function enqueue_block_assets() {
			// Only load in the admin side.
			if ( ! is_admin() ) {
				return;
			}

			// Index access file.
			$index_asset = $this->include_file( 'build/index.asset.php' );

			// Scripts.
			wp_enqueue_script(
				QLE_EDITOR_SCRIPTS_HANDLE,
				$this->get_file_uri( 'build/index.js' ),
				$index_asset['dependencies'] ?? [],
				$this->get_script_version( $index_asset ),
				true
			);

			// For debugging.
			$this->enqueue_debug_information( QLE_EDITOR_SCRIPTS_HANDLE );

			// Styles.
			wp_enqueue_style(
				QLE_EDITOR_STYLE_HANDLE,
				$this->get_file_uri( 'build/index.css' ),
				[],
				$this->get_script_version( $index_asset )
			);
		}

		/**
		 * Save version and trigger an upgrade hook
		 *
		 * @return void
		 */
		public function version_upgrade() {
			if ( get_option( 'cbb_current_version' ) !== $this->version ) {
				do_action( 'qle/version_upgraded', get_option( 'cbb_current_version' ), $this->version );
				update_option( 'cbb_current_version', $this->version );
			}
		}

		/**
		 * Register component
		 *
		 * @param string $file The file path of the component.
		 * @param string $classname The class name of the component.
		 * @return void
		 */
		public function register_component( $file, $classname ) {
			if ( $this->include_file( $file ) ) {
				$this->components[ $classname ] = new $classname( $this );
			}
		}

		/**
		 * Get a component by class name
		 *
		 * @param string $classname The class name of the component.
		 * @return mixed
		 */
		public function get_component( $classname ) {
			return $this->components[ $classname ] ?? false;
		}

		/**
		 * Define constant
		 *
		 * @param string $name The name of the constant.
		 * @param mixed  $value The value of the constant.
		 * @return void
		 */
		public function define_constant( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Return file path for file or folder.
		 *
		 * @param string $path file path.
		 * @return string
		 */
		public function get_file_path( $path ) {
			return QLE_PATH . $path;
		}

		/**
		 * Include file path.
		 *
		 * @param string $path file path.
		 * @return mixed
		 */
		public function include_file( $path ) {
			$file_path = $this->get_file_path( $path );
			if ( ! file_exists( $file_path ) ) {
				if ( $this->is_debug_mode() ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( "[QLE]: Missing file: {$file_path}" );
				}
				return false;
			}

			return include_once $file_path;
		}

		/**
		 * Get file uri by file path.
		 *
		 * @param string $path file path.
		 * @return string
		 */
		public function get_file_uri( $path ) {
			return QLE_URL . $path;
		}

		/**
		 * Create version for scripts/styles
		 *
		 * @param array $asset_file
		 * @return string
		 */
		public function get_script_version( $asset_file ) {
			return wp_get_environment_type() !== 'production' ? $asset_file['version'] ?? QLE_VERSION : QLE_VERSION;
		}

		/**
		 * Get the suffix for the scripts
		 *
		 * @return string
		 */
		public function get_script_suffix() {
			return $this->script_suffix;
		}

		/**
		 * Get the plugin version
		 *
		 * @return string
		 */
		public function get_plugin_version() {
			return $this->version;
		}

		/**
		 * Is Debugging QLE
		 *
		 * @return boolean
		 */
		public function is_debug_mode() {
			return ( defined( 'QLE_DEBUG' ) && QLE_DEBUG ) || 'development' === wp_get_environment_type();
		}

		/**
		 * Enqueue debug log information
		 *
		 * @param string $handle
		 * @return void
		 */
		public function enqueue_debug_information( $handle ) {
			wp_add_inline_script( $handle, 'var QLELOG=' . wp_json_encode( [ 'environmentType' => $this->is_debug_mode() ? 'development' : wp_get_environment_type() ] ), 'before' );
		}
	}

	/**
	 * Kick start
	 *
	 * @return QueryLoopEnhancements instance
	 */
	function query_loop_enhancements_get_instance() {
		return QueryLoopEnhancements::get_instance();
	}

	// Instantiate.
	query_loop_enhancements_get_instance();

endif;

if ( ! function_exists( __NAMESPACE__ . '\\query_loop_enhancements_activate' ) ) {
	/**
	 * Trigger an action when the plugin is activated.
	 *
	 * @return void
	 */
	function query_loop_enhancements_activate() {
		do_action( 'qle/activate' );
	}
	register_activation_hook( __FILE__, __NAMESPACE__ . '\\query_loop_enhancements_activate' );
}
