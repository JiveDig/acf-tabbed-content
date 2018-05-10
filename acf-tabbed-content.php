<?php

/**
 * Plugin Name:     Tabbed Content
 * Plugin URI:      https://github.com/JiveDig/acf-tabbed-content
 * Description:     Easily create responsive tabbed content for posts and pages.
 * Version:         0.1.0
 *
 * Author:          Mike Hemberger
 * Author URI:      https://github.com/JiveDig/acf-tabbed-content
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main ACF_Tabbed_Content Class.
 *
 * @since 1.0.0
 */
final class ACF_Tabbed_Content {

	/**
	 * @var ACF_Tabbed_Content The one true ACF_Tabbed_Content
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Main ACF_Tabbed_Content Instance.
	 *
	 * Insures that only one instance of ACF_Tabbed_Content exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   1.0.0
	 * @static  var array $instance
	 * @return  object | ACF_Tabbed_Content The one true ACF_Tabbed_Content
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new ACF_Tabbed_Content;
			// Methods
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->setup();
			self::$instance->run();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since   1.0.0
	 * @access  protected
	 * @return  void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'acf-tabbed-content' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since   1.0.0
	 * @access  protected
	 * @return  void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'acf-tabbed-content' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function setup_constants() {

		// Plugin version.
		if ( ! defined( 'ACF_TABBED_CONTENT_VERSION' ) ) {
			define( 'ACF_TABBED_CONTENT_VERSION', '0.1.0' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'ACF_TABBED_CONTENT_PLUGIN_DIR' ) ) {
			define( 'ACF_TABBED_CONTENT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Includes Path
		if ( ! defined( 'ACF_TABBED_CONTENT_INCLUDES_DIR' ) ) {
			define( 'ACF_TABBED_CONTENT_INCLUDES_DIR', ACF_TABBED_CONTENT_PLUGIN_DIR . 'includes/' );
		}

		// Plugin Folder URL.
		if ( ! defined( 'ACF_TABBED_CONTENT_PLUGIN_URL' ) ) {
			define( 'ACF_TABBED_CONTENT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File.
		if ( ! defined( 'ACF_TABBED_CONTENT_PLUGIN_FILE' ) ) {
			define( 'ACF_TABBED_CONTENT_PLUGIN_FILE', __FILE__ );
		}

		// Plugin Base Name
		if ( ! defined( 'ACF_TABBED_CONTENT_BASENAME' ) ) {
			define( 'ACF_TABBED_CONTENT_BASENAME', dirname( plugin_basename( __FILE__ ) ) );
		}

	}

	/**
	 * Include required files.
	 *
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function includes() {
		foreach ( glob( ACF_TABBED_CONTENT_INCLUDES_DIR . '*.php' ) as $file ) { include $file; }
	}

	public function setup() {
		register_activation_hook(   __FILE__, 'flush_rewrite_rules' );
		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
		add_action( 'admin_init', array( $this, 'updater' ) );
		add_action( 'acf/init',   array( $this, 'settings_page' ) );
	}

	/**
	 * Setup the updater.
	 *
	 * @uses    https://github.com/YahnisElsts/plugin-update-checker/
	 *
	 * @return  void
	 */
	public function updater() {
		if ( ! class_exists( 'Puc_v4_Factory' ) ) {
			require_once ACF_TABBED_CONTENT_INCLUDES_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php'; // 4.4
		}
		$updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/JiveDig/acf-tabbed-content/', __FILE__, 'acf-tabbed-content' );
	}

	public function settings_page() {
		acf_add_options_sub_page( array(
			'page_title' 	=> 'Tabbed Content',
			'menu_title'	=> 'Tabbed Content',
			'parent_slug'	=> 'options-general.php',
		) );
	}

	public function run() {
		add_filter( 'acf/load_field/key=field_5af33b0730c53', array( $this, 'load_post_types' ) );
		add_action( 'acf/init',                               array( $this, 'load_field_groups' ) );
	}

	public function load_post_types( $field ) {
		$post_types       = get_post_types( array( 'public' => true ), 'names' );
		$field['choices'] = apply_filters( 'acftc_post_types', $post_types );
		// Set page as a default.
		if ( isset( $field['choices']['page'] ) ) {
			$field['default_value'][] = 'page';
		}
		// Set post as a default.
		if ( isset( $field['choices']['post'] ) ) {
			$field['default_value'][] = 'post';
		}
		return $field;
	}

	public function load_field_groups() {
		// Settings.
		acf_add_local_field_group( array(
			'key'    => 'group_5af33255a81bb',
			'title'  => 'Tabbed Content Settings',
			'fields' => array(
				array(
					'key'           => 'field_5af33b0730c53',
					'label'         => 'Post Types',
					'name'          => 'acftc_post_types',
					'type'          => 'checkbox',
					'instructions'  => 'Display the tabbed content metabox on the following post types.',
					'choices'       => $this->get_post_types(),
					'default_value' => $this->get_post_type_defaults(),
					'layout'        => 'vertical',
					'return_format' => 'value',
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'acf-options-tabbed-content',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => 1,
			'description'           => '',
		) );

		// Tabbed Content.
		acf_add_local_field_group( array(
			'key'                   => 'group_59c02d79966e4',
			'title'                 => 'Tabbed Content',
			'fields'                => $this->tabbed_content_fields_config(),
			'location'              => $this->get_metabox_location(),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => 1,
			'description'           => '',
		) );
	}

	public function tabbed_content_fields_config() {
		return array(
			array(
				'key'          => 'field_59c02db106f90',
				'label'        => 'Tabs',
				'name'         => 'tabs',
				'type'         => 'repeater',
				'collapsed'    => 'field_59c02dee06f91',
				'layout'       => 'block',
				'button_label' => 'Add Tab',
				'sub_fields'   => array(
					array(
						'key'          => 'field_59c02dee06f91',
						'label'        => 'Title',
						'name'         => 'title',
						'type'         => 'text',
						'required'     => 1,
					),
					array(
						'key'          => 'field_59c02f1d06f92',
						'label'        => 'Content',
						'name'         => 'content',
						'type'         => 'wysiwyg',
						'tabs'         => 'all',
						'toolbar'      => 'full',
						'media_upload' => 1,
						'delay'        => 0,
					),
					array(
						'key'          => 'field_59c030d2694b7',
						'label'        => 'Child Tabs',
						'name'         => 'tabs',
						'type'         => 'repeater',
						'collapsed'    => 'field_59e7d655a8de2',
						'layout'       => 'block',
						'button_label' => 'Add Child Tab',
						'sub_fields'   => array(
							array(
								'key'      => 'field_59e7d655a8de2',
								'label'    => 'Title',
								'name'     => 'title',
								'type'     => 'text',
								'required' => 1,
							),
							array(
								'key'          => 'field_59e7d66ca8de3',
								'label'        => 'Content',
								'name'         => 'content',
								'type'         => 'wysiwyg',
								'tabs'         => 'all',
								'toolbar'      => 'full',
								'media_upload' => 1,
								'delay'        => 0,
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Get formatted post types for metaboxes.
	 *
	 * return array(
	 *    'name' => 'singluar_name',
	 * );
	 *
	 * return array(
	 *    'page' => 'Page',
	 * );
	 */
	public function get_post_types() {
		$choices    = array();
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		foreach( $post_types as $post_type ) {
			$choices[ $post_type->name ] = $post_type->labels->singular_name;
		}
		return apply_filters( 'acftc_post_types', $choices );
	}

	public function get_post_type_defaults() {
		$defaults   = array();
		$post_types = $this->get_post_types();
		// Set page as a default.
		if ( isset( $post_types['page'], $post_types ) ) {
			$defaults[] = 'page';
		}
		// Set post as a default.
		if ( isset( $post_types['post'], $post_types ) ) {
			$defaults[] = 'post';
		}
		return apply_filters( 'acftc_post_type_defaults', $defaults );
	}

	public function get_metabox_location() {
		$location   = array();
		$post_types = get_option( 'options_acftc_post_types', array() );
		if ( ! empty( $post_types ) ) {
			foreach( $post_types as $name ) {
				$location[][] = array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => $name,
				);
			}
		}
		return $location;
	}

}

/**
 * The main function for that returns ACF_Tabbed_Content
 *
 * The main function responsible for returning the one true ACF_Tabbed_Content
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $plugin = acftc(); ?>
 *
 * @since 1.0.0
 *
 * @return object|ACF_Tabbed_Content The one true ACF_Tabbed_Content Instance.
 */
function acftc() {
	return ACF_Tabbed_Content::instance();
}

// Get acftc Running.
acftc();
