<?php
/**
 * Wing form helper functions
 */


/**
 * Wrapper function of WFP_WingForm::get_instance().
 *
 * @param int|WP_Post $post Post ID or post object.
 * @return WFP_WingForm Wing form object.
 */
function wfp_wing_form( $post ) {
	return WFP_WingForm::get_instance( $post );
}


/**
 * Searches for a wing form by an old unit ID.
 *
 * @param int $old_id Old unit ID.
 * @return WFP_WingForm Wing form object.
 */
function wfp_get_wing_form_by_old_id( $old_id ) {
	global $wpdb;

	$q = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_old_cf7_unit_id'"
		. $wpdb->prepare( " AND meta_value = %d", $old_id );

	if ( $new_id = $wpdb->get_var( $q ) ) {
		return wfp_wing_form( $new_id );
	}
}


/**
 * Searches for a wing form by title.
 *
 * @param string $title Title of wing form.
 * @return WFP_WingForm|null Wing form object if found, null otherwise.
 */
function wfp_get_wing_form_by_title( $title ) {
	$page = get_page_by_title( $title, OBJECT, WFP_WingForm::post_type );

	if ( $page ) {
		return wfp_wing_form( $page->ID );
	}

	return null;
}


/**
 * Wrapper function of WFP_WingForm::get_current().
 *
 * @return WFP_WingForm Wing form object.
 */
function wfp_get_current_wing_form() {
	if ( $current = WFP_WingForm::get_current() ) {
		return $current;
	}
}


/**
 * Returns true if it is in the state that a non-Ajax submission is accepted.
 */
function wfp_is_posted() {
	if ( ! $wing_form = wfp_get_current_wing_form() ) {
		return false;
	}

	return $wing_form->is_posted();
}


/**
 * Retrieves the user input value through a non-Ajax submission.
 *
 * @param string $name Name of form control.
 * @param string $default_value Optional default value.
 * @return string The user input value through the form-control.
 */
function wfp_get_hangover( $name, $default_value = null ) {
	if ( ! wfp_is_posted() ) {
		return $default_value;
	}

	$submission = WFP_Submission::get_instance();

	if ( ! $submission
	or $submission->is( 'mail_sent' ) ) {
		return $default_value;
	}

	$sformfuncname = sanitize_text_field($_POST[$name]);

	return isset( $sformfuncname ) ? wp_unslash( $sformfuncname ) : sanitize_text_field($default_value);
}


/**
 * Retrieves an HTML snippet of validation error on the given form control.
 *
 * @param string $name Name of form control.
 * @return string Validation error message in a form of HTML snippet.
 */
function wfp_get_validation_error( $name ) {
	if ( ! $wing_form = wfp_get_current_wing_form() ) {
		return '';
	}

	return $wing_form->validation_error( $name );
}


/**
 * Returns a reference key to a validation error message.
 *
 * @param string $name Name of form control.
 * @param string $unit_tag Optional. Unit tag of the wing form.
 * @return string Reference key code.
 */
function wfp_get_validation_error_reference( $name, $unit_tag = '' ) {
	if ( '' === $unit_tag ) {
		$wing_form = wfp_get_current_wing_form();

		if ( $wing_form and $wing_form->validation_error( $name ) ) {
			$unit_tag = $wing_form->unit_tag();
		} else {
			return null;
		}
	}

	return preg_replace( '/[^0-9a-z_-]+/i', '',
		sprintf(
			'%1$s-ve-%2$s',
			$unit_tag,
			$name
		)
	);
}


/**
 * Retrieves a message for the given status.
 */
function wfp_get_message( $status ) {
	if ( ! $wing_form = wfp_get_current_wing_form() ) {
		return '';
	}

	return $wing_form->message( $status );
}


/**
 * Returns a class names list for a form-tag of the specified type.
 *
 * @param string $type Form-tag type.
 * @param string $default_classes Optional default classes.
 * @return string Whitespace-separated list of class names.
 */
function wfp_form_controls_class( $type, $default_classes = '' ) {
	$type = trim( $type );
	$default_classes = array_filter( explode( ' ', $default_classes ) );

	$classes = array_merge( array( 'wfp-form-control' ), $default_classes );

	$typebase = rtrim( $type, '*' );
	$required = ( '*' == substr( $type, -1 ) );

	$classes[] = 'wfp-' . $typebase;

	if ( $required ) {
		$classes[] = 'wfp-validates-as-required';
	}

	$classes = array_unique( $classes );

	return implode( ' ', $classes );
}


/**
 * Callback function for the wing-forms shortcode.
 */
function wfp_wing_form_tag_func( $atts, $content = null, $code = '' ) {
	if ( is_feed() ) {
		return '[wing-forms]';
	}

	if ( 'wing-forms' == $code ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
				'title' => '',
				'html_id' => '',
				'html_name' => '',
				'html_class' => '',
				'output' => 'form',
			),
			$atts, 'wfp'
		);

		$id = (int) $atts['id'];
		$title = trim( $atts['title'] );

		if ( ! $wing_form = wfp_wing_form( $id ) ) {
			$wing_form = wfp_get_wing_form_by_title( $title );
		}

	} else {
		if ( is_string( $atts ) ) {
			$atts = explode( ' ', $atts, 2 );
		}

		$id = (int) array_shift( $atts );
		$wing_form = wfp_get_wing_form_by_old_id( $id );
	}

	if ( ! $wing_form ) {
		return sprintf(
			'[wing-forms 404 "%s"]',
			esc_html( __( 'Not Found', 'wing-forms' ) )
		);
	}

	return $wing_form->form_html( $atts );
}


/**
 * Saves the wing form data.
 */
function wfp_save_wing_form( $args = '', $context = 'save' ) {
	$args = wp_parse_args( $args, array(
		'id' => -1,
		'title' => null,
		'locale' => null,
		'form' => null,
		'mail' => null,
		'mail_2' => null,
		'messages' => null,
		'additional_settings' => null,
	) );

	$args = wp_unslash( $args );

	$args['id'] = (int) $args['id'];

	if ( -1 == $args['id'] ) {
		$wing_form = WFP_WingForm::get_template();
	} else {
		$wing_form = wfp_wing_form( $args['id'] );
	}

	if ( empty( $wing_form ) ) {
		return false;
	}

	if ( null !== $args['title'] ) {
		$wing_form->set_title( $args['title'] );
	}

	if ( null !== $args['locale'] ) {
		$wing_form->set_locale( $args['locale'] );
	}

	$properties = array();

	if ( null !== $args['form'] ) {
		$properties['form'] = wfp_sanitize_form( $args['form'] );
	}

	if ( null !== $args['mail'] ) {
		$properties['mail'] = wfp_sanitize_mail( $args['mail'] );
		$properties['mail']['active'] = true;
	}

	if ( null !== $args['mail_2'] ) {
		$properties['mail_2'] = wfp_sanitize_mail( $args['mail_2'] );
	}

	if ( null !== $args['messages'] ) {
		$properties['messages'] = wfp_sanitize_messages( $args['messages'] );
	}

	if ( null !== $args['additional_settings'] ) {
		$properties['additional_settings'] = wfp_sanitize_additional_settings(
			$args['additional_settings']
		);
	}

	$wing_form->set_properties( $properties );

	do_action( 'wfp_save_wing_form', $wing_form, $args, $context );

	if ( 'save' == $context ) {
		$wing_form->save();
	}

	return $wing_form;
}


/**
 * Sanitizes the form property data.
 */
function wfp_sanitize_form( $input, $default_template = '' ) {
	if ( null === $input ) {
		return $default_template;
	}

	$output = trim( $input );

	if ( ! current_user_can( 'unfiltered_html' ) ) {
		$output = wfp_kses( $output, 'form' );
	}

	return $output;
}


/**
 * Sanitizes the mail property data.
 */
function wfp_sanitize_mail( $input, $defaults = array() ) {
	$input = wp_parse_args( $input, array(
		'active' => false,
		'subject' => '',
		'sender' => '',
		'recipient' => '',
		'body' => '',
		'additional_headers' => '',
		'attachments' => '',
		'use_html' => false,
		'exclude_blank' => false,
	) );

	$input = wp_parse_args( $input, $defaults );

	$output = array();
	$output['active'] = (bool) $input['active'];
	$output['subject'] = trim( $input['subject'] );
	$output['sender'] = trim( $input['sender'] );
	$output['recipient'] = trim( $input['recipient'] );
	$output['body'] = trim( $input['body'] );

	if ( ! current_user_can( 'unfiltered_html' ) ) {
		$output['body'] = wfp_kses( $output['body'], 'mail' );
	}

	$output['additional_headers'] = '';

	$headers = str_replace( "\r\n", "\n", $input['additional_headers'] );
	$headers = explode( "\n", $headers );

	foreach ( $headers as $header ) {
		$header = trim( $header );

		if ( '' !== $header ) {
			$output['additional_headers'] .= $header . "\n";
		}
	}

	$output['additional_headers'] = trim( $output['additional_headers'] );
	$output['attachments'] = trim( $input['attachments'] );
	$output['use_html'] = (bool) $input['use_html'];
	$output['exclude_blank'] = (bool) $input['exclude_blank'];

	return $output;
}


/**
 * Sanitizes the messages property data.
 */
function wfp_sanitize_messages( $input, $defaults = array() ) {
	$output = array();

	foreach ( wfp_messages() as $key => $val ) {
		if ( isset( $input[$key] ) ) {
			$output[$key] = trim( $input[$key] );
		} elseif ( isset( $defaults[$key] ) ) {
			$output[$key] = $defaults[$key];
		}
	}

	return $output;
}


/**
 * Sanitizes the additional settings property data.
 */
function wfp_sanitize_additional_settings( $input, $default_template = '' ) {
	if ( null === $input ) {
		return $default_template;
	}

	$output = trim( $input );
	return $output;
}
