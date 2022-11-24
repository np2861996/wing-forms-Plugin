<?php
/**
 * Double Opt-In Helper module
 *
 * @link https://github.com/np2861996/wing-forms-Plugin
 */


add_action( 'wfp_doi', 'wfp_doihelper_start_session', 10, 3 );

/**
 * Starts a double opt-in session.
 */
function wfp_doihelper_start_session( $agent_name, $args, &$token ) {
	if ( isset( $token ) ) {
		return;
	}

	if ( ! function_exists( 'doihelper_start_session' ) ) {
		return;
	}

	$submission = WFP_Submission::get_instance();

	if ( ! $submission ) {
		return;
	}

	$wing_form = $submission->get_wing_form();

	$do_doi = apply_filters( 'wfp_do_doi',
		! $wing_form->is_false( 'doi' ),
		$agent_name,
		$args
	);

	if ( ! $do_doi ) {
		return;
	}

	$token = doihelper_start_session( $agent_name, $args );
}
