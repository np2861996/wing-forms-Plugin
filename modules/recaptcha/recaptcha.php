<?php
/**
 * reCAPTCHA module main file
 *
 * @link https://github.com/np2861996/wing-forms-Plugin
 */

wfp_include_module_file( 'recaptcha/service.php' );


add_action( 'wfp_init', 'wfp_recaptcha_register_service', 15, 0 );

/**
 * Registers the reCAPTCHA service.
 */
function wfp_recaptcha_register_service() {
	$integration = WFP_Integration::get_instance();

	$integration->add_service( 'recaptcha',
		WFP_RECAPTCHA::get_instance()
	);
}


add_action(
	'wp_enqueue_scripts',
	'wfp_recaptcha_enqueue_scripts',
	20, 0
);

/**
 * Enqueues frontend scripts for reCAPTCHA.
 */
function wfp_recaptcha_enqueue_scripts() {
	$service = WFP_RECAPTCHA::get_instance();

	if ( ! $service->is_active() ) {
		return;
	}

	$url = 'https://www.google.com/recaptcha/api.js';

	if ( apply_filters( 'wfp_use_recaptcha_net', false ) ) {
		$url = 'https://www.recaptcha.net/recaptcha/api.js';
	}

	wp_enqueue_script( 'google-recaptcha',
		add_query_arg(
			array(
				'render' => $service->get_sitekey(),
			),
			$url
		),
		array(),
		'3.0',
		true
	);

	$assets = array();
	$asset_file = wfp_plugin_path( 'modules/recaptcha/index.asset.php' );

	if ( file_exists( $asset_file ) ) {
		$assets = include( $asset_file );
	}

	$assets = wp_parse_args( $assets, array(
		'src' => wfp_plugin_url( 'modules/recaptcha/index.js' ),
		'dependencies' => array(
			'google-recaptcha',
			'wp-polyfill',
		),
		'version' => WFP_VERSION,
		'in_footer' => true,
	) );

	wp_register_script(
		'wfp-recaptcha',
		$assets['src'],
		$assets['dependencies'],
		$assets['version'],
		$assets['in_footer']
	);

	wp_enqueue_script( 'wfp-recaptcha' );

	wp_localize_script( 'wfp-recaptcha',
		'wfp_recaptcha',
		array(
			'sitekey' => $service->get_sitekey(),
			'actions' => apply_filters( 'wfp_recaptcha_actions', array(
				'homepage' => 'homepage',
				'wingform' => 'wingform',
			) ),
		)
	);
}


add_filter(
	'wfp_form_hidden_fields',
	'wfp_recaptcha_add_hidden_fields',
	100, 1
);

/**
 * Adds hidden form field for reCAPTCHA.
 */
function wfp_recaptcha_add_hidden_fields( $fields ) {
	$service = WFP_RECAPTCHA::get_instance();

	if ( ! $service->is_active() ) {
		return $fields;
	}

	return array_merge( $fields, array(
		'_wfp_recaptcha_response' => '',
	) );
}


add_filter( 'wfp_spam', 'wfp_recaptcha_verify_response', 9, 2 );

/**
 * Verifies reCAPTCHA token on the server side.
 */
function wfp_recaptcha_verify_response( $spam, $submission ) {
	if ( $spam ) {
		return $spam;
	}

	$service = WFP_RECAPTCHA::get_instance();

	if ( ! $service->is_active() ) {
		return $spam;
	}

	$s_wfp_recaptcha_response = sanitize_text_field($_POST['_wfp_recaptcha_response']);
	$token = isset( $s_wfp_recaptcha_response )
		? trim( $s_wfp_recaptcha_response ) : '';

	if ( $service->verify( $token ) ) { // Human
		$spam = false;
	} else { // Bot
		$spam = true;

		if ( '' === $token ) {
			$submission->add_spam_log( array(
				'agent' => 'recaptcha',
				'reason' => __(
					'reCAPTCHA response token is empty.',
					'wing-forms'
				),
			) );
		} else {
			$submission->add_spam_log( array(
				'agent' => 'recaptcha',
				'reason' => sprintf(
					__(
						'reCAPTCHA score (%1$.2f) is lower than the threshold (%2$.2f).',
						'wing-forms'
					),
					$service->get_last_score(),
					$service->get_threshold()
				),
			) );
		}
	}

	return $spam;
}


add_action( 'wfp_init', 'wfp_recaptcha_add_form_tag_recaptcha', 10, 0 );

/**
 * Registers form-tag types for reCAPTCHA.
 */
function wfp_recaptcha_add_form_tag_recaptcha() {
	$service = WFP_RECAPTCHA::get_instance();

	if ( ! $service->is_active() ) {
		return;
	}

	wfp_add_form_tag( 'recaptcha',
		'__return_empty_string', // no output
		array( 'display-block' => true )
	);
}


add_action( 'wfp_upgrade', 'wfp_upgrade_recaptcha_v2_v3', 10, 2 );

/**
 * Adds warnings for users upgrading from reCAPTCHA v2 to v3.
 */
function wfp_upgrade_recaptcha_v2_v3( $new_ver, $old_ver ) {
	if ( version_compare( '5.1-dev', $old_ver, '<=' ) ) {
		return;
	}

	$service = WFP_RECAPTCHA::get_instance();

	if ( ! $service->is_active() or $service->get_global_sitekey() ) {
		return;
	}

	// Maybe v2 keys are used now. Warning necessary.
	WFP::update_option( 'recaptcha_v2_v3_warning', true );
	WFP::update_option( 'recaptcha', null );
}


add_action( 'wfp_admin_menu', 'wfp_admin_init_recaptcha_v2_v3', 10, 0 );

/**
 * Adds filters and actions for warnings.
 */
function wfp_admin_init_recaptcha_v2_v3() {
	if ( ! WFP::get_option( 'recaptcha_v2_v3_warning' ) ) {
		return;
	}

	add_filter(
		'wfp_admin_menu_change_notice',
		'wfp_admin_menu_change_notice_recaptcha_v2_v3',
		10, 1
	);

	add_action(
		'wfp_admin_warnings',
		'wfp_admin_warnings_recaptcha_v2_v3',
		5, 3
	);
}


/**
 * Increments the admin menu counter for the Integration page.
 */
function wfp_admin_menu_change_notice_recaptcha_v2_v3( $counts ) {
	$counts['wfp-integration'] += 1;
	return $counts;
}


/**
 * Prints warnings on the admin screen.
 */
function wfp_admin_warnings_recaptcha_v2_v3( $page, $action, $object ) {
	if ( 'wfp-integration' !== $page ) {
		return;
	}

	$message = sprintf(
		esc_html( __(
			"API keys for reCAPTCHA v3 are different from those for v2; keys for v2 do not work with the v3 API. You need to register your sites again to get new keys for v3. For details, see %s.",
			'wing-forms'
		) ),
		wfp_link(
			__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
			__( 'reCAPTCHA (v3)', 'wing-forms' )
		)
	);

	echo sprintf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		$message
	);
}
