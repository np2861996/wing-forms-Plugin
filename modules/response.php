<?php
/**
** A base module for [response]
**/

/* form_tag handler */

add_action( 'wfp_init', 'wfp_add_form_tag_response', 10, 0 );

function wfp_add_form_tag_response() {
	wfp_add_form_tag( 'response',
		'wfp_response_form_tag_handler',
		array(
			'display-block' => true,
		)
	);
}

function wfp_response_form_tag_handler( $tag ) {
	if ( $wing_form = wfp_get_current_wing_form() ) {
		return $wing_form->form_response_output();
	}
}
