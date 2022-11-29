<?php

add_action( 'wfp_admin_menu', 'wfp_admin_init_bulk_cv', 10, 0 );

function wfp_admin_init_bulk_cv() {
	if ( ! wfp_validate_configuration()
	or ! current_user_can( 'wfp_edit_wing_forms' ) ) {
		return;
	}

	$result = WFP::get_option( 'bulk_validate' );
	$last_important_update = WFP_ConfigValidator::last_important_update;

	if ( ! empty( $result['version'] )
	and version_compare( $last_important_update, $result['version'], '<=' ) ) {
		return;
	}

	add_filter( 'wfp_admin_menu_change_notice',
		'wfp_admin_menu_change_notice_bulk_cv',
		10, 1
	);

	add_action( 'wfp_admin_warnings',
		'wfp_admin_warnings_bulk_cv',
		5, 3
	);
}

function wfp_admin_menu_change_notice_bulk_cv( $counts ) {
	$counts['wfp'] += 1;
	return $counts;
}

function wfp_admin_warnings_bulk_cv( $page, $action, $object ) {
	if ( 'wfp' === $page and 'validate' === $action ) {
		return;
	}

	$link = wfp_link(
		add_query_arg(
			array( 'action' => 'validate' ),
			menu_page_url( 'wfp', false )
		),
		__( 'Validate Wing Forms Configuration', 'wing-forms' )
	);

	$message = __( "Misconfiguration leads to mail delivery failure or other troubles. Validate your wing forms now.", 'wing-forms' );

	echo sprintf(
		'<div class="notice notice-warning"><p>%1$s &raquo; %2$s</p></div>',
		esc_html( $message ),
		$link
	);
}

add_action( 'wfp_admin_load', 'wfp_load_bulk_validate_page', 10, 2 );

function wfp_load_bulk_validate_page( $page, $action ) {

	$sREQUEST_METHOD = sanitize_text_field($_SERVER['REQUEST_METHOD']);

	if ( 'wfp' != $page
	or 'validate' != $action
	or ! wfp_validate_configuration()
	or 'POST' != $sREQUEST_METHOD ) {
		return;
	}

	check_admin_referer( 'wfp-bulk-validate' );

	if ( ! current_user_can( 'wfp_edit_wing_forms' ) ) {
		wp_die( __( "You are not allowed to validate configuration.", 'wing-forms' ) );
	}

	$wing_forms = WFP_WingForm::find();

	$result = array(
		'timestamp' => time(),
		'version' => WFP_VERSION,
		'count_valid' => 0,
		'count_invalid' => 0,
	);

	foreach ( $wing_forms as $wing_form ) {
		$config_validator = new WFP_ConfigValidator( $wing_form );
		$config_validator->validate();
		$config_validator->save();

		if ( $config_validator->is_valid() ) {
			$result['count_valid'] += 1;
		} else {
			$result['count_invalid'] += 1;
		}
	}

	WFP::update_option( 'bulk_validate', $result );

	$redirect_to = add_query_arg(
		array(
			'message' => 'validated',
		),
		menu_page_url( 'wfp', false )
	);

	wp_safe_redirect( $redirect_to );
	exit();
}

function wfp_admin_bulk_validate_page() {
	$wing_forms = WFP_WingForm::find();
	$count = WFP_WingForm::count();

	$submit_text = sprintf(
		_n(
			/* translators: %s: number of wing forms */
			"Validate %s wing form now",
			"Validate %s wing forms now",
			$count, 'wing-forms'
		),
		number_format_i18n( $count )
	);

?>
<div class="wrap">

<h1><?php echo esc_html( __( 'Validate Configuration', 'wing-forms' ) ); ?></h1>

<form method="post" action="">
	<input type="hidden" name="action" value="validate" />
	<?php wp_nonce_field( 'wfp-bulk-validate' ); ?>
	<p><input type="submit" class="button" value="<?php echo esc_attr( $submit_text ); ?>" /></p>
</form>

<?php
	echo wfp_link(
		__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
		__( 'FAQ about Configuration Validator', 'wing-forms' )
	);
?>

</div>
<?php
}
