<?php
/**
 * Plugin Name: Writing For Green Scheduled Event
 * Description: This plugin will handle all the custom scheduled event that will run on the plugin.
 * Version: 1.0.0
 * Author: David Lane
 * Text Domain: wfg-scheduled-event
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

class WFG_Scheduled_Event {
	/**
	 * This plugin's instance
	 *
	 * @var WFG_Scheduled_Event The one true WFG_Scheduled_Event
	 * @since 1.0
	 */
	private static $instance;
	/**
	 * WFG_Scheduled_Event version.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $version = '1.0.0';

	/**
	 * WFG_Scheduled_Event constructor.
	 */
	private function __construct() {
		$this->define_constants();
		register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );

		add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
		add_action( 'coach_daily_docs_assignment_notification', array( $this, 'coach_daily_notifications' ) );

	}

	/**
	 * Define all constants
	 * @return void
	 * @since 1.0.0
	 */
	public function define_constants() {
		$this->define( 'WFG_Scheduled_Event_PLUGIN_VERSION', $this->version );
		$this->define( 'WFG_Scheduled_Event_PLUGIN_FILE', __FILE__ );
		$this->define( 'WFG_Scheduled_Event_PLUGIN_DIR', dirname( __FILE__ ) );
		$this->define( 'WFG_Scheduled_Event_PLUGIN_INC_DIR', dirname( __FILE__ ) . '/includes' );
	}

	/**
	 * Define constant if not already defined
	 *
	 * @param string $name
	 * @param string|bool $value
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Main WFG_Scheduled_Event Instance
	 *
	 * Insures that only one instance of WFG_Scheduled_Event exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @return WFG_Scheduled_Event The one true WFG_Scheduled_Event
	 * @since 1.0.0
	 * @static var array $instance
	 */
	public static function init() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WFG_Scheduled_Event ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register activation hook
	 */
	public function activate_plugin() {
		if ( ! wp_next_scheduled( 'coach_daily_docs_assignment_notification' ) ) {
			wp_schedule_event( time(), 'daily', 'coach_daily_docs_assignment_notification' );
		}
	}

	/**
	 * Register deactivation hook.
	 */
	public function deactivate_plugin() {
		wp_clear_scheduled_hook( 'coach_daily_docs_assignment_notification' );
	}

	/**
	 * Call the scheduled event
	 */
	public function coach_daily_notifications() {
		Coach_Notification::coach_daily_notifications();
	}

	/**
	 * Return plugin version.
	 *
	 * @return string
	 * @since 1.0.0
	 * @access public
	 **/
	public function get_version() {
		return $this->version;
	}

	/**
	 * Plugin URL getter.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Plugin path getter.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Plugin base path name getter.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function plugin_basename() {
		return plugin_basename( __FILE__ );
	}

	/**
	 * Initialize plugin for localization
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function localization_setup() {
		load_plugin_textdomain( 'wfg-scheduled-event', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages' );
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @access protected
	 * @return void
	 */

	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wfg-scheduled-event' ), '1.0.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @access protected
	 * @return void
	 */

	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wfg-scheduled-event' ), '1.0.0' );
	}

	/**
	 * Load the plugin when WooCommerce loaded.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function init_plugin() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 * @since 1.0.0
	 */
	public function includes() {

		require_once dirname( __FILE__ ) . '/includes/class-coach-notifications.php';
		do_action( 'WFG_Scheduled_Event__loaded' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'localization_setup' ) );

	}
}

/**
 * The main function responsible for returning the one true WC Serial Numbers
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @return WFG_Scheduled_Event
 * @since 1.0.0
 */
function WFG_Scheduled_Event() {
	return WFG_Scheduled_Event::init();
}

WFG_Scheduled_Event();