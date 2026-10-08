<?php
/**
 * Admin UI for Jamie's Header Effects (Appearance → Header Effects).
 *
 * @package JamiesHeaderEffects
 */

namespace JamiesHeaderEffects\Admin;

defined( 'ABSPATH' ) || exit;

function register_admin_hooks() {
	add_action( 'admin_menu', __NAMESPACE__ . '\add_menu_page' );
	add_action( 'admin_init', __NAMESPACE__ . '\render_settings_sections' );
	add_action( 'admin_init', __NAMESPACE__ . '\handle_reset_defaults' );
	add_action( 'admin_post_bsh_reset_defaults', __NAMESPACE__ . '\handle_reset_defaults' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_admin_assets' );
}

function handle_reset_defaults() {
	$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
	if ( $action !== 'bsh_reset_defaults' ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to perform this action.', 'jamies-header-effects' ) );
	}
	if ( ! isset( $_POST['bsh_reset_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsh_reset_nonce'] ) ), 'bsh_reset_defaults' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'jamies-header-effects' ) );
	}
	update_option( \JamiesHeaderEffects\Settings\OPTION_NAME, \JamiesHeaderEffects\Settings\get_defaults() );
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'     => 'jamies-header-effects',
				'reset'    => '1',
				'_wpnonce' => wp_create_nonce( 'bsh_reset_success' ),
			),
			admin_url( 'themes.php' )
		)
	);
	exit;
}

function add_menu_page() {
	add_theme_page(
		__( 'Header Effects', 'jamies-header-effects' ),
		__( 'Header Effects', 'jamies-header-effects' ),
		'manage_options',
		'jamies-header-effects',
		__NAMESPACE__ . '\render_settings_page'
	);
}

function enqueue_admin_assets( $hook ) {
	if ( $hook !== 'appearance_page_jamies-header-effects' ) {
		return;
	}
	$base = plugins_url( '', \JamiesHeaderEffects\PLUGIN_FILE );
	wp_enqueue_style( 'jamies-header-effects-admin', $base . '/assets/css/admin.css', array(), \JamiesHeaderEffects\PLUGIN_VERSION );
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	wp_enqueue_script( 'jamies-header-effects-admin', $base . '/assets/js/admin.js', array( 'jquery', 'wp-color-picker' ), \JamiesHeaderEffects\PLUGIN_VERSION, true );
	wp_localize_script( 'jamies-header-effects-admin', 'bshAdmin', array(
		'resetConfirm' => __( 'Reset all settings to defaults? This cannot be undone.', 'jamies-header-effects' ),
	) );
}

function render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( isset( $_GET['reset'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'bsh_reset_success' ) ) {
		add_settings_error(
			'jamies_header_effects',
			'bsh_reset',
			__( 'Settings reset to defaults.', 'jamies-header-effects' ),
			'success'
		);
	}
	?>
	<div class="wrap bsh-admin">
		<h1><?php esc_html_e( 'Jamie\'s Header Effects', 'jamies-header-effects' ); ?></h1>
		<?php settings_errors(); ?>
		<p class="bsh-description"><?php esc_html_e( 'Configure the sticky header for block themes. Leave "Header selector" empty to use the default (header.wp-block-template-part, .wp-site-blocks > header).', 'jamies-header-effects' ); ?></p>

		<form method="post" action="options.php" id="bsh-settings-form">
			<?php
			settings_fields( \JamiesHeaderEffects\Settings\OPTION_GROUP );
			do_settings_sections( 'jamies-header-effects' );
			submit_button( __( 'Save settings', 'jamies-header-effects' ) );
			?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="bsh-reset-form" style="margin-top: 1em;">
			<input type="hidden" name="action" value="bsh_reset_defaults" />
			<?php wp_nonce_field( 'bsh_reset_defaults', 'bsh_reset_nonce' ); ?>
			<?php submit_button( __( 'Reset to defaults', 'jamies-header-effects' ), 'secondary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}

function render_settings_sections() {
	$page = 'jamies-header-effects';
	$opt  = \JamiesHeaderEffects\Settings\OPTION_NAME;

	add_settings_section(
		'bsh_general',
		__( 'General', 'jamies-header-effects' ),
		function () {
			echo '<p>' . esc_html__( 'Enable the sticky header and choose how it is targeted.', 'jamies-header-effects' ) . '</p>';
		},
		$page,
		array( 'option_name' => $opt )
	);

	add_settings_field(
		'enabled',
		__( 'Enable sticky header', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_checkbox',
		$page,
		'bsh_general',
		array( 'key' => 'enabled', 'label' => __( 'On', 'jamies-header-effects' ) )
	);

	add_settings_field(
		'header_selector',
		__( 'Header selector', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_text',
		$page,
		'bsh_general',
		array( 'key' => 'header_selector', 'type' => 'text', 'placeholder' => __( 'Default: header.wp-block-template-part, .wp-site-blocks > header', 'jamies-header-effects' ), 'class' => 'large-text' )
	);

	// Hide on scroll
	add_settings_section(
		'bsh_hide',
		__( 'Hide on scroll down', 'jamies-header-effects' ),
		function () {
			echo '<p>' . esc_html__( 'Hide header when scrolling down; reveal when scrolling up.', 'jamies-header-effects' ) . '</p>';
		},
		$page,
		array( 'option_name' => $opt )
	);

	add_settings_field(
		'hide_on_scroll_down',
		__( 'Hide on scroll down', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_checkbox',
		$page,
		'bsh_hide',
		array( 'key' => 'hide_on_scroll_down', 'label' => __( 'On', 'jamies-header-effects' ) )
	);

	add_settings_field(
		'reveal_on_scroll_up',
		__( 'Reveal on scroll up', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_checkbox',
		$page,
		'bsh_hide',
		array( 'key' => 'reveal_on_scroll_up', 'label' => __( 'On (default)', 'jamies-header-effects' ) )
	);

	add_settings_field(
		'hide_sensitivity_px',
		__( 'Sensitivity (px delta)', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_number',
		$page,
		'bsh_hide',
		array( 'key' => 'hide_sensitivity_px', 'min' => 1, 'max' => 200 )
	);

	add_settings_field(
		'hide_delay_ms',
		__( 'Delay (ms)', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_number',
		$page,
		'bsh_hide',
		array( 'key' => 'hide_delay_ms', 'min' => 0, 'max' => 1000 )
	);

	// Background
	add_settings_section(
		'bsh_background',
		__( 'Header background', 'jamies-header-effects' ),
		function () {
			echo '<p>' . esc_html__( 'How the sticky header is painted. Chameleon matches the section beneath it as you scroll; Colour and Blur amount only apply to Solid colour and Frosted blur.', 'jamies-header-effects' ) . '</p>';
		},
		$page,
		array( 'option_name' => $opt )
	);

	add_settings_field(
		'background_style',
		__( 'Background style', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_radio',
		$page,
		'bsh_background',
		array(
			'key'   => 'background_style',
			'items' => array(
				'none'      => __( 'None — keep the theme\'s own background', 'jamies-header-effects' ),
				'solid'     => __( 'Solid colour', 'jamies-header-effects' ),
				'blur'      => __( 'Frosted blur', 'jamies-header-effects' ),
				'chameleon' => __( 'Chameleon — match section background on scroll', 'jamies-header-effects' ),
			),
		)
	);

	add_settings_field(
		'sticky_bg_color',
		__( 'Colour', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_color_picker',
		$page,
		'bsh_background',
		array( 'key' => 'sticky_bg_color' )
	);

	add_settings_field(
		'sticky_bg_opacity',
		__( 'Opacity', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_opacity_slider',
		$page,
		'bsh_background',
		array( 'key' => 'sticky_bg_opacity', 'description' => __( 'Applies to solid, frosted blur, and chameleon modes.', 'jamies-header-effects' ) )
	);

	add_settings_field(
		'blur_amount_px',
		__( 'Blur amount (px)', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_number',
		$page,
		'bsh_background',
		array( 'key' => 'blur_amount_px', 'min' => 0, 'max' => 50, 'step' => 1 )
	);

	// Layout
	add_settings_section(
		'bsh_layout',
		__( 'Layout & offset', 'jamies-header-effects' ),
		function () {
			echo '<p>' . esc_html__( 'Admin bar offset and z-index.', 'jamies-header-effects' ) . '</p>';
		},
		$page,
		array( 'option_name' => $opt )
	);

	add_settings_field(
		'admin_bar_offset',
		__( 'Offset for admin bar', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_checkbox',
		$page,
		'bsh_layout',
		array( 'key' => 'admin_bar_offset', 'label' => __( 'Auto-detect admin bar (default on)', 'jamies-header-effects' ) )
	);

	add_settings_field(
		'z_index',
		__( 'Z-index', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_number',
		$page,
		'bsh_layout',
		array( 'key' => 'z_index', 'min' => 1, 'max' => 99999 )
	);

	add_settings_field(
		'push_content_down',
		__( 'Push content down', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_checkbox',
		$page,
		'bsh_layout',
		array( 'key' => 'push_content_down', 'label' => __( 'Add top padding to main content equal to header height when sticky', 'jamies-header-effects' ) )
	);

	add_settings_field(
		'overlay_content',
		__( 'Overlay page content', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_checkbox',
		$page,
		'bsh_layout',
		array( 'key' => 'overlay_content', 'label' => __( 'Float the header over the first section — ideal for transparent hero headers. Takes priority over "Push content down" if both are enabled.', 'jamies-header-effects' ) )
	);

	// Disable on
	add_settings_section(
		'bsh_disable',
		__( 'Disable on', 'jamies-header-effects' ),
		function () {
			echo '<p>' . esc_html__( 'Disable sticky header on certain viewports or contexts.', 'jamies-header-effects' ) . '</p>';
		},
		$page,
		array( 'option_name' => $opt )
	);

	add_settings_field(
		'disable_mobile',
		__( 'Disable on mobile', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_checkbox',
		$page,
		'bsh_disable',
		array( 'key' => 'disable_mobile', 'label' => __( 'Disable under breakpoint', 'jamies-header-effects' ) )
	);

	add_settings_field(
		'mobile_breakpoint_px',
		__( 'Mobile breakpoint (px)', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_number',
		$page,
		'bsh_disable',
		array( 'key' => 'mobile_breakpoint_px', 'min' => 320, 'max' => 1920 )
	);

	add_settings_field(
		'disabled_post_types',
		__( 'Disable on post types', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_post_types',
		$page,
		'bsh_disable',
		array( 'key' => 'disabled_post_types' )
	);

	add_settings_field(
		'disabled_templates',
		__( 'Disable on templates', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_templates',
		$page,
		'bsh_disable',
		array( 'key' => 'disabled_templates' )
	);

	// Animation
	add_settings_section(
		'bsh_animation',
		__( 'Animation', 'jamies-header-effects' ),
		function () {
			echo '<p>' . esc_html__( 'Transition speed. prefers-reduced-motion is always respected.', 'jamies-header-effects' ) . '</p>';
		},
		$page,
		array( 'option_name' => $opt )
	);

	add_settings_field(
		'transition_speed_ms',
		__( 'Transition speed (ms)', 'jamies-header-effects' ),
		__NAMESPACE__ . '\field_number',
		$page,
		'bsh_animation',
		array( 'key' => 'transition_speed_ms', 'min' => 0, 'max' => 2000 )
	);
}

function get_options_for_field() {
	return \JamiesHeaderEffects\Settings\get_options();
}

function field_checkbox( $args ) {
	$opts = get_options_for_field();
	$key  = $args['key'];
	$val  = ! empty( $opts[ $key ] );
	$name = \JamiesHeaderEffects\Settings\OPTION_NAME . '[' . esc_attr( $key ) . ']';
	?>
	<label>
		<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $val ); ?> />
		<?php echo esc_html( $args['label'] ?? __( 'On', 'jamies-header-effects' ) ); ?>
	</label>
	<?php
}

function field_text( $args ) {
	$opts = get_options_for_field();
	$key  = $args['key'];
	$val  = isset( $opts[ $key ] ) ? $opts[ $key ] : '';
	$name = \JamiesHeaderEffects\Settings\OPTION_NAME . '[' . esc_attr( $key ) . ']';
	$type = $args['type'] ?? 'text';
	$class       = isset( $args['class'] ) ? ' class="' . esc_attr( $args['class'] ) . '"' : '';
	$placeholder = isset( $args['placeholder'] ) ? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"' : '';
	?>
	<input type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $val ); ?>"<?php echo $class . $placeholder; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $class and $placeholder built with esc_attr above ?> />
	<?php
}

function field_number( $args ) {
	$opts = get_options_for_field();
	$key  = $args['key'];
	$val  = isset( $opts[ $key ] ) ? $opts[ $key ] : '';
	$name = \JamiesHeaderEffects\Settings\OPTION_NAME . '[' . esc_attr( $key ) . ']';
	$min  = isset( $args['min'] ) ? (int) $args['min'] : '';
	$max  = isset( $args['max'] ) ? (int) $args['max'] : '';
	$step = isset( $args['step'] ) ? (float) $args['step'] : 1;
	?>
	<input type="number" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $val ); ?>" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" step="<?php echo esc_attr( $step ); ?>" />
	<?php
}

function field_color_picker( $args ) {
	$opts = get_options_for_field();
	$key  = $args['key'];
	$val  = isset( $opts[ $key ] ) ? $opts[ $key ] : '#ffffff';
	$name = \JamiesHeaderEffects\Settings\OPTION_NAME . '[' . esc_attr( $key ) . ']';
	$id   = 'bsh-' . str_replace( '_', '-', $key );
	?>
	<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $val ); ?>" class="bsh-color-picker" data-default-color="<?php echo esc_attr( $val ); ?>" />
	<?php
}

function field_opacity_slider( $args ) {
	$opts = get_options_for_field();
	$key  = $args['key'];
	$val  = isset( $opts[ $key ] ) ? (float) $opts[ $key ] : 0.98;
	$val  = max( 0, min( 1, $val ) );
	$name = \JamiesHeaderEffects\Settings\OPTION_NAME . '[' . esc_attr( $key ) . ']';
	$id   = 'bsh-' . str_replace( '_', '-', $key );
	$pct  = round( $val * 100 );
	?>
	<div class="bsh-opacity-slider-wrap">
		<input type="range" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $val ); ?>" min="0" max="1" step="0.05" class="bsh-opacity-slider" />
		<span class="bsh-opacity-value" aria-live="polite"><?php echo esc_html( $pct ); ?>%</span>
	</div>
	<?php if ( ! empty( $args['description'] ) ) : ?>
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
	<?php endif; ?>
	<?php
}

function field_radio( $args ) {
	$opts = get_options_for_field();
	$key  = $args['key'];
	$val  = isset( $opts[ $key ] ) ? $opts[ $key ] : '';
	$base = \JamiesHeaderEffects\Settings\OPTION_NAME . '[' . esc_attr( $key ) . ']';
	foreach ( $args['items'] as $value => $label ) {
		?>
		<label><input type="radio" name="<?php echo esc_attr( $base ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $val, $value ); ?> /> <?php echo esc_html( $label ); ?></label><br />
		<?php
	}
}

function field_post_types( $args ) {
	$opts   = get_options_for_field();
	$key    = $args['key'];
	$selected = isset( $opts[ $key ] ) && is_array( $opts[ $key ] ) ? $opts[ $key ] : array();
	$types  = get_post_types( array( 'public' => true ), 'objects' );
	$base   = \JamiesHeaderEffects\Settings\OPTION_NAME . '[' . esc_attr( $key ) . '][]';
	foreach ( $types as $pt ) {
		$id = 'bsh-pt-' . $pt->name;
		?>
		<label for="<?php echo esc_attr( $id ); ?>">
			<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $base ); ?>" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, $selected, true ) ); ?> />
			<?php echo esc_html( $pt->label ); ?>
		</label><br />
		<?php
	}
}

function field_templates( $args ) {
	$opts    = get_options_for_field();
	$key     = $args['key'];
	$selected = isset( $opts[ $key ] ) && is_array( $opts[ $key ] ) ? $opts[ $key ] : array();
	$templates = array(
		'home'       => __( 'Home (blog posts)', 'jamies-header-effects' ),
		'front_page' => __( 'Front page', 'jamies-header-effects' ),
		'blog'       => __( 'Blog', 'jamies-header-effects' ),
		'single'     => __( 'Single post', 'jamies-header-effects' ),
		'page'       => __( 'Page', 'jamies-header-effects' ),
		'archive'    => __( 'Archive', 'jamies-header-effects' ),
		'404'        => __( '404', 'jamies-header-effects' ),
		'search'     => __( 'Search', 'jamies-header-effects' ),
	);
	$base = \JamiesHeaderEffects\Settings\OPTION_NAME . '[' . esc_attr( $key ) . '][]';
	foreach ( $templates as $slug => $label ) {
		$id = 'bsh-tpl-' . $slug;
		?>
		<label for="<?php echo esc_attr( $id ); ?>">
			<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $base ); ?>" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $selected, true ) ); ?> />
			<?php echo esc_html( $label ); ?>
		</label><br />
		<?php
	}
}
