<?php
/**
 * Controller for front-end requests, scripts, and styles
 */


add_action(
	'parse_request',
	'wfp_control_init',
	20, 0
);

/**
 * Handles a submission in non-Ajax mode.
 */
function wfp_control_init() {
	if ( WFP_Submission::is_restful() ) {
		return;
	}

	

	if ( isset( $s_wfp ) ) {
		$s_wfp = sanitize_text_field($_POST['_wfp']);
		$wing_form = wfp_wing_form( (int) $s_wfp );

		if ( $wing_form ) {
			$wing_form->submit();
		}
	}
}


/**
 * Registers main scripts and styles.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		$assets = array();
		$asset_file = wfp_plugin_path( 'includes/js/index.asset.php' );

		if ( file_exists( $asset_file ) ) {
			$assets = include( $asset_file );
		}

		$assets = wp_parse_args( $assets, array(
			'src' => wfp_plugin_url( 'includes/js/index.js' ),
			'dependencies' => array(),
			'version' => WFP_VERSION,
			'in_footer' => ( 'header' !== wfp_load_js() ),
		) );

		wp_register_script(
			'wing-forms',
			$assets['src'],
			array_merge(
				$assets['dependencies'],
				array( 'swv' )
			),
			$assets['version'],
			$assets['in_footer']
		);

		wp_register_script(
			'wing-forms-html5-fallback',
			wfp_plugin_url( 'includes/js/html5-fallback.js' ),
			array( 'jquery-ui-datepicker' ),
			WFP_VERSION,
			true
		);

		if ( wfp_load_js() ) {
			wfp_enqueue_scripts();
		}

		wp_register_style(
			'wing-forms',
			wfp_plugin_url( 'includes/css/styles.css' ),
			array(),
			WFP_VERSION,
			'all'
		);

		wp_register_style(
			'wing-forms-rtl',
			wfp_plugin_url( 'includes/css/styles-rtl.css' ),
			array(),
			WFP_VERSION,
			'all'
		);

		wp_register_style(
			'jquery-ui-smoothness',
			wfp_plugin_url(
				'includes/js/jquery-ui/themes/smoothness/jquery-ui.min.css'
			),
			array(),
			'1.12.1',
			'screen'
		);

		if ( wfp_load_css() ) {
			wfp_enqueue_styles();
		}
	},
	10, 0
);


/**
 * Enqueues scripts.
 */
function wfp_enqueue_scripts() {
	wp_enqueue_script( 'wing-forms' );

	$wfp = array(
		'api' => array(
			'root' => esc_url_raw( get_rest_url() ),
			'namespace' => 'wing-forms/v1',
		),
	);

	if ( defined( 'WP_CACHE' ) and WP_CACHE ) {
		$wfp['cached'] = 1;
	}

	wp_localize_script( 'wing-forms', 'wfp', $wfp );

	do_action( 'wfp_enqueue_scripts' );
}


/**
 * Returns true if the main script is enqueued.
 */
function wfp_script_is() {
	return wp_script_is( 'wing-forms' );
}


/**
 * Enqueues styles.
 */
function wfp_enqueue_styles() {
	wp_enqueue_style( 'wing-forms' );

	if ( wfp_is_rtl() ) {
		wp_enqueue_style( 'wing-forms-rtl' );
	}

	do_action( 'wfp_enqueue_styles' );
}


/**
 * Returns true if the main stylesheet is enqueued.
 */
function wfp_style_is() {
	return wp_style_is( 'wing-forms' );
}


add_action(
	'wp_enqueue_scripts',
	'wfp_html5_fallback',
	20, 0
);

/**
 * Enqueues scripts and styles for the HTML5 fallback.
 */
function wfp_html5_fallback() {
	if ( ! wfp_support_html5_fallback() ) {
		return;
	}

	if ( wfp_script_is() ) {
		wp_enqueue_script( 'wing-forms-html5-fallback' );
	}

	if ( wfp_style_is() ) {
		wp_enqueue_style( 'jquery-ui-smoothness' );
	}
}
