<?php
/**
 * Plugin Name:       Jamie's Header Effects
 * Plugin URI:        https://github.com/jamiemarsland/jamies-header-effects
 * Description:       Beautiful sticky header effects for block themes: Chameleon mode that recolours the header to match each section as you scroll, frosted-glass blur, hide-on-scroll-down, and transparent overlay headers. Theme-agnostic, accessible, no jQuery.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Jamie Marsland
 * Author URI:        https://profiles.wordpress.org/jamiemarsland/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       jamies-header-effects
 *
 * @package JamiesHeaderEffects
 */

namespace JamiesHeaderEffects;

defined( 'ABSPATH' ) || exit;

const PLUGIN_VERSION = '1.0.0';
const PLUGIN_FILE   = __FILE__;
const PLUGIN_DIR    = __DIR__;

require_once PLUGIN_DIR . '/includes/helpers.php';
require_once PLUGIN_DIR . '/includes/settings.php';
require_once PLUGIN_DIR . '/includes/admin.php';

/**
 * Bootstrap: register hooks and enqueue assets when conditions are met.
 */
add_action( 'plugins_loaded', __NAMESPACE__ . '\init' );

function init() {
	Settings\register_settings();
	Admin\register_admin_hooks();
	add_action( 'wp', __NAMESPACE__ . '\maybe_enqueue_frontend' );
	add_action( 'wp', __NAMESPACE__ . '\maybe_add_body_class_filter' );
}

/**
 * Enqueue frontend CSS/JS only when sticky header is enabled and not disabled for this context.
 */
function maybe_enqueue_frontend() {
	if ( ! is_admin() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && should_show_sticky_header() ) {
		add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_frontend_assets', 20 );
		add_action( 'wp_footer', __NAMESPACE__ . '\inline_header_config', 5 );
	}
}

/**
 * Add body class when sticky header is active (for CSS scoping).
 * Must run on 'wp' so the filter is registered before body_class() is called.
 */
function maybe_add_body_class_filter() {
	if ( should_show_sticky_header() ) {
		add_filter( 'body_class', __NAMESPACE__ . '\add_bsh_body_class' );
	}
}

function add_bsh_body_class( array $classes ) {
	$classes[] = 'bsh-enabled';
	$opts = Settings\get_options();
	if ( ! empty( $opts['overlay_content'] ) ) {
		$classes[] = 'bsh-overlay';
	}
	return $classes;
}

/**
 * Enqueue frontend CSS and JS.
 */
function enqueue_frontend_assets() {
	$base = plugins_url( '', PLUGIN_FILE );
	$dir  = PLUGIN_DIR;

	wp_enqueue_style(
		'jamies-header-effects-frontend',
		$base . '/assets/css/frontend.css',
		array(),
		PLUGIN_VERSION
	);

	wp_enqueue_script(
		'jamies-header-effects-frontend',
		$base . '/assets/js/frontend.js',
		array(),
		PLUGIN_VERSION,
		true
	);

	wp_localize_script( 'jamies-header-effects-frontend', 'jamiesHeaderEffects', get_frontend_config() );
}

/**
 * Output inline config for header targeting (selector, etc.) so JS can run before DOM ready if needed.
 */
function inline_header_config() {
	$config = get_frontend_config();
	echo '<script type="application/json" id="bsh-config">' . wp_json_encode( $config ) . '</script>' . "\n";
}

/**
 * Build config array for frontend (sanitized for JS).
 */
function get_frontend_config() {
	$opts = Settings\get_options();
	$out  = array(
		'selector'              => Helpers\get_header_selector(),
		'enabled'                => ! empty( $opts['enabled'] ),


		'hide_on_scroll_down'    => ! empty( $opts['hide_on_scroll_down'] ),
		'reveal_on_scroll_up'    => ! empty( $opts['reveal_on_scroll_up'] ),
		'hide_sensitivity_px'    => isset( $opts['hide_sensitivity_px'] ) ? (int) $opts['hide_sensitivity_px'] : 10,
		'hide_delay_ms'          => isset( $opts['hide_delay_ms'] ) ? (int) $opts['hide_delay_ms'] : 150,
		'background_style'       => isset( $opts['background_style'] ) ? $opts['background_style'] : 'solid',
		'sticky_bg_color'        => isset( $opts['sticky_bg_color'] ) ? $opts['sticky_bg_color'] : '#ffffff',
		'sticky_bg_opacity'      => isset( $opts['sticky_bg_opacity'] ) ? (float) $opts['sticky_bg_opacity'] : 0.98,
		'blur_amount_px'         => isset( $opts['blur_amount_px'] ) ? (int) $opts['blur_amount_px'] : 10,
		'admin_bar_offset'       => ! empty( $opts['admin_bar_offset'] ),
		'z_index'                => isset( $opts['z_index'] ) ? (int) $opts['z_index'] : 999,
		'disable_mobile'         => ! empty( $opts['disable_mobile'] ),
		'mobile_breakpoint_px'   => isset( $opts['mobile_breakpoint_px'] ) ? (int) $opts['mobile_breakpoint_px'] : 782,
		'push_content_down'     => ! empty( $opts['push_content_down'] ),
		'overlay_content'        => ! empty( $opts['overlay_content'] ),
		'transition_speed_ms'    => isset( $opts['transition_speed_ms'] ) ? (int) $opts['transition_speed_ms'] : 300,
		'reduced_motion'         => false, // JS checks prefers-reduced-motion
		'disabled_templates'     => isset( $opts['disabled_templates'] ) && is_array( $opts['disabled_templates'] ) ? $opts['disabled_templates'] : array(),
		'disabled_post_types'   => isset( $opts['disabled_post_types'] ) && is_array( $opts['disabled_post_types'] ) ? $opts['disabled_post_types'] : array(),
	);
	return $out;
}

/**
 * Whether the sticky header should be shown in the current context (post type, template, mobile).
 */
function should_show_sticky_header() {
	$opts = Settings\get_options();
	if ( empty( $opts['enabled'] ) ) {
		return false;
	}

	$post_type = get_post_type();
	if ( $post_type && ! empty( $opts['disabled_post_types'] ) && in_array( $post_type, $opts['disabled_post_types'], true ) ) {
		return false;
	}

	$template = Helpers\get_current_template_slug();
	if ( $template && ! empty( $opts['disabled_templates'] ) && in_array( $template, $opts['disabled_templates'], true ) ) {
		return false;
	}

	if ( ! empty( $opts['disable_mobile'] ) && Helpers\is_mobile_breakpoint( (int) ( $opts['mobile_breakpoint_px'] ?? 782 ) ) ) {
		return false;
	}

	return true;
}
