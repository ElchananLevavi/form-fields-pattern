<?php
/**
 * Plugin Name:       Elementor form field pattern
 * Description:       Advanced frontend validation to Elementor form fields.
 * Plugin URI:  https://ha-ayal.co.il/
 * Version:           1.2
 * Requires at least: 5.2
 * Requires PHP:      7.0
 * Author:            HaAyal studio
 * Author URI:  https://ha-ayal.co.il/
 * License:           GPL v2 or later
 * Text Domain:       eff-pattern
 * Domain Path:       /languages
 * Elementor tested up to: 5.0.0
 * Elementor Pro tested up to: 5.0.0
 */


/*******************
 * This plugin is based on code by bainternet (Ohad Raz) - https://github.com/elementor/elementor/issues/9382
 *****************/

 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

final class Elementor_Field_Pattern {

	/**
	 * Plugin Version
	 *
	 * @since 1.0
	 * @var string The plugin version.
	 */
	const VERSION = '1.0';

	const MINIMUM_ELEMENTOR_VERSION = '2.8.0';

	const MINIMUM_PHP_VERSION = '7.0';

	public function __construct() {

		// Load translation
		add_action( 'init', array( $this, 'i18n' ) );

		// Init Plugin
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Load Textdomain
	 *
	 * Load plugin localization files.
	 * Fired by `init` action hook.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function i18n() {
		load_plugin_textdomain( 'eff-pattern' );
	}

	/**
	 * Initialize the plugin
	 *
	 * Validates that Elementor is already loaded.
	 * Checks for basic plugin requirements, if one check fail don't continue,
	 * if all check have passed include the plugin class.
	 *
	 * Fired by `plugins_loaded` action hook.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function init() {

		// Check if Elementor installed and activated
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_missing_main_plugin' ) );
			return;
		}

		// Check for required Elementor version
		if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_elementor_version' ) );
			return;
		}

		// Check for required PHP version
		if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_php_version' ) );
			return;
		}

        new Elementor_Forms_Patterns_Validation();
	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have Elementor installed or activated.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function admin_notice_missing_main_plugin() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
		/* translators: 1: Plugin name 2: Elementor */
			esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'eff-pattern' ),
			'<strong>' . esc_html__( 'Elementor form field pattern', 'eff-pattern' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor & Elementor pro', 'eff-pattern' ) . '</strong>'
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have a minimum required Elementor version.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function admin_notice_minimum_elementor_version() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
		/* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'eff-pattern' ),
			'<strong>' . esc_html__( 'Elementor form field pattern', 'eff-pattern' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'eff-pattern' ) . '</strong>',
			self::MINIMUM_ELEMENTOR_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}

	/**
	 * Admin notice
	 *
	 * Warning when the site doesn't have a minimum required PHP version.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function admin_notice_minimum_php_version() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
		/* translators: 1: Plugin name 2: PHP 3: Required PHP version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'eff-pattern' ),
			'<strong>' . esc_html__( 'Elementor form field pattern', 'eff-pattern' ) . '</strong>',
			'<strong>' . esc_html__( 'PHP', 'eff-pattern' ) . '</strong>',
			self::MINIMUM_PHP_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}
}
new Elementor_Field_Pattern();


class Elementor_Forms_Patterns_Validation {

    public $allowed_fields = [
        'text',
        'email',
        'url',
        'password',
        'tel',
        'number',
    ];

    public function __construct() {
        // Add pattern attribute to form field render
        add_filter( 'elementor_pro/forms/render/item', [ $this, 'maybe_add_pattern' ], 10, 3 );

        add_action( 'elementor/element/form/section_form_fields/before_section_end', [ $this, 'add_pattern_field_control' ], 100, 2 );
    }

    /**
     * add_pattern_field_control
     * @param $element
     * @param $args
     */
    public function add_pattern_field_control( $element, $args ) {
        $elementor = \Elementor\Plugin::instance();
        $control_data = $elementor->controls_manager->get_control_from_stack( $element->get_name(), 'form_fields' );

        if ( is_wp_error( $control_data ) ) {
            return;
        }
        // create a new pattern control as a repeater field
        $tmp = new Elementor\Repeater();


       
        $tmp->add_control(
            'field_patten',
            [
                'label' => 'Pattern',
                'inner_tab' => 'form_fields_advanced_tab',
                'tab' => 'content',
                'tabs_wrapper' => 'form_fields_tabs',
                'type' => 'text',
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'operator' => 'in',
                            'value' => $this->allowed_fields,
                        ],
                    ],
                ],
            ]
        );

        $tmp->add_control(
            'field_pattern_description',
            [
                'label' => 'Pattern description',
                'inner_tab' => 'form_fields_advanced_tab',
                'tab' => 'content',
                'tabs_wrapper' => 'form_fields_tabs',
                'type' => 'text',
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'operator' => 'in',
                            'value' => $this->allowed_fields,
                        ],
                    ],
                ],
            ]
        );

        $tmp->add_control(
			'field_pattern_instructions',
			[
				'label' => esc_html__( 'How to use', 'eff-pattern' ),
                'show_label' => false,
                'inner_tab' => 'form_fields_advanced_tab',
                'tab' => 'content',
                'tabs_wrapper' => 'form_fields_tabs',
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'field_type',
                            'operator' => 'in',
                            'value' => $this->allowed_fields,
                        ],
                    ],
                ],
				'raw' => __( '<a style="line-height: 1.4;" href="https://www.html5pattern.com/" target="_blank">Learn how to use patterns for advanced field validation. ' . $this->external_link_icon() . '</a><br><br><a style="line-height: 1.4;" href="https://ha-ayal.co.il/מרכז-הידע/elementor-form-validation/" target="_blank">Plugin Description & Examples (Hebrew). ' . $this->external_link_icon() . '</a>', 'eff-pattern' ),
				'separator' => 'after',
			]
		);
    

        $pattern_fields = $tmp->get_controls();

        $pattern_field = $pattern_fields['field_patten'];
        $pattern_description_field = $pattern_fields['field_pattern_description'];
        $pattern_instructions_field = $pattern_fields['field_pattern_instructions'];       

        // insert new pattern field in advanced tab before field ID control
        $new_order = [];
        foreach ( $control_data['fields'] as $field_key => $field ) {
            if ( 'custom_id' === $field['name'] ) {
                $new_order['field_patten'] = $pattern_field;
                $new_order['field_pattern_description'] = $pattern_description_field;
                $new_order['field_pattern_instructions'] = $pattern_instructions_field;
            }
            $new_order[ $field_key ] = $field;
        }
        $control_data['fields'] = $new_order;

        $element->update_control( 'form_fields', $control_data );
    }

    public function maybe_add_pattern( $field, $field_index, $form_widget ) {
    	if ( ! empty( $field['field_patten'] ) && in_array( $field['field_type'], $this->allowed_fields ) ) {

            $validation_description =  $field['field_pattern_description'];
            if(strlen($validation_description) < 1) {
                $validation_description = '"Please match the requested format \"' . $field['field_patten'] . '\" "';  
            } else {
                $validation_description = '"' . $validation_description . '"';
            }

    		$form_widget->add_render_attribute( 'input' . $field_index,
    			[
    				'pattern' => $field['field_patten'],
    				'oninvalid' => 'this.setCustomValidity(' . $validation_description . ')',
    				'oninput' => 'this.setCustomValidity("")',
                    'title' => $validation_description,
    			]
    		);
    	}
    	return $field;
    }

    private function external_link_icon() {
    	return '<svg width="12px" height="12px" fill="#93003c" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 15.9 15.9" style="enable-background:new 0 0 15.9 15.9" xml:space="preserve"><path d="M11 14H1.8V4.8h6l1.5-1.5H1c-.4-.1-.8.3-.8.7v10.8c0 .4.3.8.8.8h10.8c.4 0 .8-.3.8-.8V6.5L11 8v6z"/><path d="M14.9.1h-4.3c-.4 0-.8.3-.8.8 0 .4.3.8.8.8H13L5.8 8.8c-.3.3-.3.8 0 1.1.1.1.3.2.5.2s.4-.1.5-.2L14 2.7v2.5c0 .4.3.8.8.8.4 0 .8-.3.8-.8V.9c0-.4-.3-.8-.7-.8z"/></svg>';
    }

}

