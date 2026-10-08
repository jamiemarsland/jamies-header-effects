<?php
/**
 * Settings API for Jamie's Header Effects.
 *
 * @package JamiesHeaderEffects
 */

namespace JamiesHeaderEffects\Settings;

defined( 'ABSPATH' ) || exit;

const OPTION_GROUP = 'jamies_header_effects';
const OPTION_NAME  = 'jamies_header_effects_options';

/**
 * Register settings and their sanitization.
 */
function register_settings() {
	register_setting(
		OPTION_GROUP,
		OPTION_NAME,
		array(
			'type'              => 'array',
			'sanitize_callback' => __NAMESPACE__ . '\sanitize_options',
			'default'           => get_defaults(),
		)
	);
}

/**
 * Default option values.
 *
 * @return array
 */
function get_defaults() {
	return array(
		'enabled'                => false,
		'header_selector'        => '',


		'hide_on_scroll_down'    => false,
		'reveal_on_scroll_up'    => true,
		'hide_sensitivity_px'    => 10,
		'hide_delay_ms'          => 150,
		'background_style'       => 'solid',
		'sticky_bg_color'        => '#ffffff',
		'sticky_bg_opacity'      => 0.98,
		'blur_amount_px'         => 10,
		'admin_bar_offset'       => true,
		'z_index'                => 999,
		'disable_mobile'         => false,
		'mobile_breakpoint_px'   => 782,
		'push_content_down'      => false,
		'overlay_content'        => false,
		'transition_speed_ms'    => 300,
		'disabled_templates'     => array(),
		'disabled_post_types'   => array(),
	);
}

/**
 * Get options (merged with defaults).
 *
 * @return array
 */
function get_options() {
	$saved = get_option( OPTION_NAME, array() );
	$saved = is_array( $saved ) ? $saved : array();
	// Migrate old boolean background flags to background_style
	if ( ! isset( $saved['background_style'] ) ) {
		if ( ! empty( $saved['blur_on_scroll'] ) ) {
			$saved['background_style'] = 'blur';
		} elseif ( isset( $saved['sticky_background'] ) && ! $saved['sticky_background'] ) {
			$saved['background_style'] = 'none';
		} else {
			$saved['background_style'] = 'solid';
		}
	}
	return array_merge( get_defaults(), $saved );
}

/**
 * Sanitize and validate options on save.
 *
 * @param array $input Raw POST input.
 * @return array
 */
function sanitize_options( $input ) {
	if ( ! is_array( $input ) ) {
		return get_defaults();
	}
	$defaults = get_defaults();
	$out      = array();

	$out['enabled']                 = ! empty( $input['enabled'] );
	$out['header_selector']         = isset( $input['header_selector'] ) ? \JamiesHeaderEffects\Helpers\sanitize_header_selector( $input['header_selector'] ) : $defaults['header_selector'];


	$out['hide_on_scroll_down']     = ! empty( $input['hide_on_scroll_down'] );
	$out['reveal_on_scroll_up']     = ! empty( $input['reveal_on_scroll_up'] );
	$out['hide_sensitivity_px']     = isset( $input['hide_sensitivity_px'] ) ? max( 1, min( 200, (int) $input['hide_sensitivity_px'] ) ) : $defaults['hide_sensitivity_px'];
	$out['hide_delay_ms']           = isset( $input['hide_delay_ms'] ) ? max( 0, min( 1000, (int) $input['hide_delay_ms'] ) ) : $defaults['hide_delay_ms'];
	$out['background_style']        = in_array( $input['background_style'] ?? '', array( 'none', 'solid', 'blur', 'chameleon' ), true ) ? $input['background_style'] : $defaults['background_style'];
	$out['sticky_bg_color']         = isset( $input['sticky_bg_color'] ) ? sanitize_hex_color( $input['sticky_bg_color'] ) : $defaults['sticky_bg_color'];
	if ( empty( $out['sticky_bg_color'] ) ) {
		$out['sticky_bg_color'] = $defaults['sticky_bg_color'];
	}
	$out['sticky_bg_opacity']       = isset( $input['sticky_bg_opacity'] ) ? max( 0, min( 1, (float) $input['sticky_bg_opacity'] ) ) : $defaults['sticky_bg_opacity'];
	$out['blur_amount_px']          = isset( $input['blur_amount_px'] ) ? max( 0, min( 50, (int) $input['blur_amount_px'] ) ) : $defaults['blur_amount_px'];
	$out['admin_bar_offset']        = ! empty( $input['admin_bar_offset'] );
	$out['z_index']                 = isset( $input['z_index'] ) ? max( 1, min( 99999, (int) $input['z_index'] ) ) : $defaults['z_index'];
	$out['disable_mobile']          = ! empty( $input['disable_mobile'] );
	$out['mobile_breakpoint_px']    = isset( $input['mobile_breakpoint_px'] ) ? max( 320, min( 1920, (int) $input['mobile_breakpoint_px'] ) ) : $defaults['mobile_breakpoint_px'];
	$out['push_content_down']       = ! empty( $input['push_content_down'] );
	$out['overlay_content']         = ! empty( $input['overlay_content'] );
	$out['transition_speed_ms']     = isset( $input['transition_speed_ms'] ) ? max( 0, min( 2000, (int) $input['transition_speed_ms'] ) ) : $defaults['transition_speed_ms'];

	$allowed_templates = array( 'home', 'front_page', 'blog', 'single', 'page', 'archive', '404', 'search' );
	$out['disabled_templates'] = array();
	if ( ! empty( $input['disabled_templates'] ) && is_array( $input['disabled_templates'] ) ) {
		$out['disabled_templates'] = array_values( array_intersect( $input['disabled_templates'], $allowed_templates ) );
	}

	$out['disabled_post_types'] = array();
	if ( ! empty( $input['disabled_post_types'] ) && is_array( $input['disabled_post_types'] ) ) {
		$out['disabled_post_types'] = array_map( 'sanitize_key', $input['disabled_post_types'] );
	}

	return $out;
}
