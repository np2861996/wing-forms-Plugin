<?php
/**
 * Constant Wing module main file
 *
 * @link https://github.com/np2861996/wing-forms-Plugin
 */

wfp_include_module_file( 'constant-wing/service.php' );
wfp_include_module_file( 'constant-wing/wing-post-request.php' );
wfp_include_module_file( 'constant-wing/wing-form-properties.php' );
wfp_include_module_file( 'constant-wing/doi.php' );


add_action(
	'wfp_init',
	'wfp_constant_wing_register_service',
	5, 0
);

/**
 * Registers the Constant Wing service.
 */
function wfp_constant_wing_register_service() {
	$integration = WFP_Integration::get_instance();

	$service = WFP_ConstantWing::get_instance();
	$integration->add_service( 'constant_wing', $service );
}


add_action( 'wfp_submit', 'wfp_constant_wing_submit', 10, 2 );

/**
 * Callback to the wfp_submit action hook. Creates a wing
 * based on the submission.
 */
function wfp_constant_wing_submit( $wing_form, $result ) {
	$service = WFP_ConstantWing::get_instance();

	if ( ! $service->is_active() ) {
		return;
	}

	if ( $wing_form->in_demo_mode() ) {
		return;
	}

	$do_submit = true;

	if ( empty( $result['status'] )
	or ! in_array( $result['status'], array( 'mail_sent' ) ) ) {
		$do_submit = false;
	}

	$prop = $wing_form->prop( 'constant_wing' );

	if ( empty( $prop['enable_wing_list'] ) ) {
		$do_submit = false;
	}

	$do_submit = apply_filters( 'wfp_constant_wing_submit',
		$do_submit, $wing_form, $result
	);

	if ( ! $do_submit ) {
		return;
	}

	$submission = WFP_Submission::get_instance();

	$consented = true;

	foreach ( $wing_form->scan_form_tags( 'feature=name-attr' ) as $tag ) {
		if ( $tag->has_option( 'consent_for:constant_wing' )
		and null == $submission->get_posted_data( $tag->name ) ) {
			$consented = false;
			break;
		}
	}

	if ( ! $consented ) {
		return;
	}

	$request_builder_class_name = apply_filters(
		'wfp_constant_wing_wing_post_request_builder',
		'WFP_ConstantWing_WingPostRequest'
	);

	if ( ! class_exists( $request_builder_class_name ) ) {
		return;
	}

	$request_builder = new $request_builder_class_name;
	$request_builder->build( $submission );

	if ( ! $request_builder->is_valid() ) {
		return;
	}

	$email = $request_builder->get_email_address();

	if ( $email ) {
		if ( $service->email_exists( $email ) ) {
			return;
		}

		$token = null;

		do_action_ref_array( 'wfp_doi', array(
			'wfp_constant_wing',
			array(
				'email_to' => $email,
				'properties' => $request_builder->to_array(),
			),
			&$token,
		) );

		if ( isset( $token ) ) {
			return;
		}
	}

	$service->create_wing( $request_builder->to_array() );
}
