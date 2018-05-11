<?php

// Register tab JS and CSS.
add_action( 'wp_enqueue_scripts', 'acftc_load_scripts' );
function acftc_load_scripts() {
	wp_register_script( 'jquery-accessible-nested-tabs', ACF_TABBED_CONTENT_PLUGIN_URL . '/assets/js/jquery-accessible-nested-tabs.js', array( 'jquery' ), '1.6.1', true );
	wp_register_style( 'acftc-style', ACF_TABBED_CONTENT_PLUGIN_URL . '/assets/css/acftc-style.css', array(), ACF_TABBED_CONTENT_VERSION );
}

add_action( 'template_redirect', 'acftc_theme_location' );
function acftc_theme_location() {

	// Bail if not viewing a post type we want.
	if ( ! is_singular( get_option( 'options_acftc_post_types', array() ) ) ) {
		return;
	}

// delete_option( 'options-acftc_display' );

	// Genesis Hooks.
	if ( 'genesis' === get_template() ) {
		$locations = array(
			'before' => array(
				'hook'     => 'genesis_entry_content',
				'filter'   => false,
				'priority' => 8,
			),
			'after'  => array(
				'hook'     => 'genesis_entry_content',
				'filter'   => false,
				'priority' => 10,
			),
		);
	}
	// Theme Hook Alliance.
	elseif ( current_theme_supports( 'tha_hooks', array( 'entry' ) ) ) {
		$locations = array(
			'before' => array(
				'hook'     => 'tha_entry_top',
				'filter'   => false,
				'priority' => 13,
			),
			'after'  => array(
				'hook'     => 'tha_entry_bottom',
				'filter'   => false,
				'priority' => 8,
			),
		);
	}
	// Fallback to 'the_content'.
	else {
		$locations = array(
			'before' => array(
				'hook'     => false,
				'filter'   => 'the_content',
				'priority' => 9,
			),
			'after'  => array(
				'hook'     => false,
				'filter'   => 'the_content',
				'priority' => 11,
			),
		);
	}

	// Filter theme locations.
	$locations = apply_filters( 'acftc_theme_locations', $locations );

	// Get display location.
	$display = get_option( 'options_acftc_display', 'after' );

	// Bail if not a valid display location.
	if ( ! in_array( $display, array( 'before', 'after' ) ) ) {
		return;
	}

	// Display tabs.
	if ( $locations[ $display ]['hook'] ) {
		add_action( $locations[ $display ]['hook'], "acftc_display_{$display}_content", $locations[ $display ]['priority'] );
	} elseif ( $locations[ $display ]['filter'] && ! is_feed() ) {
		add_filter( $locations[ $display ]['filter'], "acftc_display_{$display}_content_filter", $locations[ $display ]['priority'] );
	}
}

function acftc_display_before_content() {
	$tabs = acftc_get_field( get_the_ID(), acftc()->tabbed_content_fields_config() );
	echo acftc_get_tabs( $tabs );
}

function acftc_display_before_content_filter( $content ) {
	$tabs = acftc_get_field( get_the_ID(), acftc()->tabbed_content_fields_config() );
	return acftc_get_tabs( $tabs ) . $content;
}

function acftc_display_after_content() {
	$tabs = acftc_get_field( get_the_ID(), acftc()->tabbed_content_fields_config() );
	echo acftc_get_tabs( $tabs );
}

function acftc_display_after_content_filter( $content ) {
	$tabs = acftc_get_field( get_the_ID(), acftc()->tabbed_content_fields_config() );
	return $content . acftc_get_tabs( $tabs );
}

/**
 * Helper function to get the tabs, maybe as nested tabs.
 *
 * @param  array  $tabs     The ACF repeater field for the tabs. Keys of 'title', 'content', and possibley 'tabs'.
 * @param  bool   $nested   Whether this should be a horizontal or nested tab group.
 *
 * @return string|HTML      The tab group HTML.
 */
function acftc_get_tabs( $tabs = '', $nested = false ) {

	// Bail if no tabs.
	if ( ! $tabs || ! is_array( $tabs ) ) {
		return;
	}

	/**
	 * Get tabs.
	 * The initial $tabs array was the entire field group,
	 * this grabs only the repeater field.
	 */
	$tabs = isset( $tabs['tabs'] ) && ! empty( $tabs['tabs'] ) ? $tabs['tabs'] : false;

	// Bail if no tabs.
	if ( ! $tabs ) {
		return;
	}

	global $wp_embed;

	static $acftc_scripts = false;

	// Enqueue our script.
	if ( ! $acftc_scripts ) {
		wp_enqueue_script( 'jquery-accessible-nested-tabs' );
		wp_enqueue_style( 'acftc-style' );
		$acftc_scripts = true;
	}

	// Set some variables.
	$prefix  = $nested ? 'child-tab-' : 'tab-';
	$classes = 'js-tabs';
	$classes = $nested ? $classes : sprintf( '%s tabs-%s-content', $classes, sanitize_html_class( get_option( 'options_acftc_display', 'after' ) ) );
	$classes = $nested ? $classes . ' js-tabs-nested' : $classes . ' js-tabs-parent';
	$html    = '';

	$html .= sprintf( '<div class="%s">', $classes );

		// Tab wrap.
		$html .= '<ul class="js-tablist">';

			// Tabs.
			foreach ( $tabs as $tab ) {

				// Skip if no title.
				if ( ! isset( $tab['title'] ) || empty( $tab['title'] ) ) {
					continue;
				}

				// Output the title.
				$html .= sprintf( '<li class="js-tablist__item"><a href="#%s" id="label_%s" class="js-tablist__link">%s</a></li>', sanitize_title_with_dashes( $prefix . $tab['title'] ), sanitize_title_with_dashes( $prefix . $tab['title'] ), sanitize_text_field( $tab['title'] ) );
			}

		$html .= '</ul>';

		// Content.
		foreach ( $tabs as $tab ) {

			// Skip if no title.
			if ( ! isset( $tab['title'] ) || empty( $tab['title'] ) ) {
				continue;
			}

			$content = isset( $tab['content'] ) ? $tab['content'] : '';

			if ( isset( $tab['tabs'] ) ) {
				$nested_tabs = acftc_get_tabs( $tab, true );
				if ( ! empty( $nested_tabs ) ) {
					$content .= $nested_tabs;
				}
			}

			$content = wptexturize( $content );
			$content = wpautop( $content );
			$content = do_shortcode( $content );
			$content = $wp_embed->autoembed( $content );
			$content = $wp_embed->run_shortcode( $content );

			// Output the content.
			$html .= sprintf( '<div id="%s" class="js-tabcontent">%s</div>', sanitize_title_with_dashes( $prefix . $tab['title'] ), $content );
		}

	$html .= '</div>';

	return $html;
}

/**
 * Retrieves all post meta data according to the structure in the $config
 * array.
 *
 * Provides a convenient and more performant alternative to ACF's
 * `get_field()`.
 *
 * This function is especially useful when working with ACF repeater fields and
 * flexible content layouts.
 *
 * @link    https://www.timjensen.us/acf-get-field-alternative/
 *
 * @version 1.2.5
 *
 * @param integer $post_id Required. Post ID.
 * @param array   $config  Required. An array that represents the structure of
 *                         the custom fields. Follows the same format as the
 *                         ACF export field groups array.
 * @return array
 */
function acftc_get_field( $post_id, array $config ) {

	$results = array();

	foreach ( $config as $field ) {

		if ( empty( $field['name'] ) ) {
			continue;
		}

		$meta_key = $field['name'];

		if ( isset( $field['meta_key_prefix'] ) ) {
			$meta_key = $field['meta_key_prefix'] . $meta_key;
		}

		$field_value = get_post_meta( $post_id, $meta_key, true );

		if ( isset( $field['layouts'] ) ) { // We're dealing with flexible content layouts.

			if ( empty( $field_value ) ) {
				continue;
			}

			// Build a keyed array of possible layout types.
			$layout_types = [];
			foreach ( $field['layouts'] as $key => $layout_type ) {
				$layout_types[ $layout_type['name'] ] = $layout_type;
			}

			foreach ( $field_value as $key => $current_layout_type ) {
				$new_config = $layout_types[ $current_layout_type ]['sub_fields'];

				if ( empty( $new_config ) ) {
					continue;
				}

				foreach ( $new_config as &$field_config ) {
					$field_config['meta_key_prefix'] = $meta_key . "_{$key}_";
				}

				$results[ $field['name'] ][] = array_merge(
					[
						'acf_fc_layout' => $current_layout_type,
					],
					acftc_get_field( $post_id, $new_config )
				);
			}
		} elseif ( isset( $field['sub_fields'] ) ) { // We're dealing with repeater fields.

			if ( empty( $field_value ) ) {
				continue;
			}

			for ( $i = 0; $i < $field_value; $i ++ ) {
				$new_config = $field['sub_fields'];

				if ( empty( $new_config ) ) {
					continue;
				}

				foreach ( $new_config as &$field_config ) {
					$field_config['meta_key_prefix'] = $meta_key . "_{$i}_";
				}

				$results[ $field['name'] ][] = acftc_get_field( $post_id, $new_config );
			}
		} else {
			$results[ $field['name'] ] = $field_value;
		} // End if().

	} // End foreach().

	return $results;
}
