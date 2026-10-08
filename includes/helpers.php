<?php
/**
 * Helper functions for Jamie's Header Effects.
 *
 * @package JamiesHeaderEffects
 */

namespace JamiesHeaderEffects\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Default CSS selector for the header (block theme friendly).
 *
 * @return string
 */
function get_default_header_selector() {
	return 'header.wp-block-template-part, .wp-site-blocks > header, .wp-site-blocks header';
}

/**
 * User-configured or default header selector (sanitized for use in JS/CSS).
 *
 * @return string
 */
function get_header_selector() {
	$opts = \JamiesHeaderEffects\Settings\get_options();
	$raw  = isset( $opts['header_selector'] ) ? $opts['header_selector'] : '';
	$trim = is_string( $raw ) ? trim( $raw ) : '';
	if ( $trim === '' ) {
		return get_default_header_selector();
	}
	return sanitize_header_selector( $trim );
}

/**
 * Sanitize a CSS selector: allow alphanumeric, spaces, commas, dots, #, [], "", '', -, _, >, +, ~, :, ()
 * Avoid script injection; keep it to a single line.
 *
 * @param string $selector
 * @return string
 */
function sanitize_header_selector( $selector ) {
	$selector = preg_replace( '/\s+/', ' ', $selector );
	$selector = wp_strip_all_tags( $selector );
	$length   = 500;
	if ( strlen( $selector ) > $length ) {
		$selector = substr( $selector, 0, $length );
	}
	return $selector;
}

/**
 * Whether the user prefers reduced motion (for accessibility).
 *
 * @return bool
 */
function user_prefers_reduced_motion() {
	if ( ! function_exists( 'wp_add_inline_script' ) ) {
		return false;
	}
	return false; // Actual check is done in JS via matchMedia('(prefers-reduced-motion: reduce)')
}

/**
 * Check if current viewport is at or below the given breakpoint (server-side best guess via user agent).
 * Frontend JS will do the real check; this is for conditional loading.
 *
 * @param int $breakpoint_px
 * @return bool
 */
function is_mobile_breakpoint( $breakpoint_px = 782 ) {
	if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
		return false;
	}
	$ua = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
	$ua = strtolower( $ua );
	$mobile_indicators = array( 'mobile', 'android', 'iphone', 'ipod', 'blackberry', 'opera mini', 'iemobile', 'wp(windows phone)' );
	foreach ( $mobile_indicators as $indicator ) {
		if ( strpos( $ua, $indicator ) !== false ) {
			return true;
		}
	}
	return false;
}

/**
 * Get current template slug for conditional disable (home, single, page, archive, etc.).
 *
 * @return string|null
 */
function get_current_template_slug() {
	if ( is_front_page() && is_home() ) {
		return 'home';
	}
	if ( is_front_page() ) {
		return 'front_page';
	}
	if ( is_home() ) {
		return 'blog';
	}
	if ( is_singular( 'post' ) ) {
		return 'single';
	}
	if ( is_singular( 'page' ) ) {
		return 'page';
	}
	if ( is_singular() ) {
		return 'single';
	}
	if ( is_archive() ) {
		return 'archive';
	}
	if ( is_404() ) {
		return '404';
	}
	if ( is_search() ) {
		return 'search';
	}
	return null;
}
