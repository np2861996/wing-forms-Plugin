<?php
/**
 * Sendinblue module main file
 *
 * @link https://github.com/np2861996/wing-forms-Plugin
 */

wfp_include_module_file( 'sendinblue/service.php' );
wfp_include_module_file( 'sendinblue/wing-form-properties.php' );
wfp_include_module_file( 'sendinblue/doi.php' );


add_action( 'wfp_init', 'wfp_sendinblue_register_service', 1, 0 );

/**
 * Registers the Sendinblue service.
 */
function wfp_sendinblue_register_service() {
	$integration = WFP_Integration::get_instance();

	$integration->add_service( 'sendinblue',
		WFP_Sendinblue::get_instance()
	);
}


add_action( 'wfp_submit', 'wfp_sendinblue_submit', 10, 2 );

/**
 * Callback to the wfp_submit action hook. Creates a wing
 * based on the submission.
 */
function wfp_sendinblue_submit( $wing_form, $result ) {
	if ( $wing_form->in_demo_mode() ) {
		return;
	}

	$service = WFP_Sendinblue::get_instance();

	if ( ! $service->is_active() ) {
		return;
	}

	if ( empty( $result['posted_data_hash'] ) ) {
		return;
	}

	if ( empty( $result['status'] )
	or ! in_array( $result['status'], array( 'mail_sent', 'mail_failed' ) ) ) {
		return;
	}

	$submission = WFP_Submission::get_instance();

	$consented = true;

	foreach ( $wing_form->scan_form_tags( 'feature=name-attr' ) as $tag ) {
		if ( $tag->has_option( 'consent_for:sendinblue' )
		and null == $submission->get_posted_data( $tag->name ) ) {
			$consented = false;
			break;
		}
	}

	if ( ! $consented ) {
		return;
	}

	$prop = wp_parse_args(
		$wing_form->prop( 'sendinblue' ),
		array(
			'enable_wing_list' => false,
			'wing_lists' => array(),
			'enable_transactional_email' => false,
			'email_template' => 0,
		)
	);

	if ( ! $prop['enable_wing_list'] ) {
		return;
	}

	$attributes = wfp_sendinblue_collect_parameters();

	$params = array(
		'wing' => array(),
		'email' => array(),
	);

	if ( ! empty( $attributes['EMAIL'] ) or ! empty( $attributes['SMS'] ) ) {
		$params['wing'] = apply_filters(
			'wfp_sendinblue_wing_parameters',
			array(
				'email' => $attributes['EMAIL'],
				'attributes' => (object) $attributes,
				'listIds' => (array) $prop['wing_lists'],
				'updateEnabled' => false,
			)
		);
	}

	if ( $prop['enable_transactional_email'] and $prop['email_template'] ) {
		$first_name = isset( $attributes['FIRSTNAME'] )
			? trim( $attributes['FIRSTNAME'] )
			: '';

		$last_name = isset( $attributes['LASTNAME'] )
			? trim( $attributes['LASTNAME'] )
			: '';

		if ( $first_name or $last_name ) {
			$email_to_name = sprintf(
				/* translators: 1: first name, 2: last name */
				_x( '%1$s %2$s', 'personal name', 'wing-forms' ),
				$first_name,
				$last_name
			);
		} else {
			$email_to_name = '';
		}

		$params['email'] = apply_filters(
			'wfp_sendinblue_email_parameters',
			array(
				'templateId' => absint( $prop['email_template'] ),
				'to' => array(
					array(
						'name' => $email_to_name,
						'email' => $attributes['EMAIL'],
					),
				),
				'params' => (object) $attributes,
				'tags' => array( 'Wing Forms' ),
			)
		);
	}

	if ( is_email( $attributes['EMAIL'] ) ) {
		$token = null;

		do_action_ref_array( 'wfp_doi', array(
			'wfp_sendinblue',
			array(
				'email_to' => $attributes['EMAIL'],
				'properties' => $params,
			),
			&$token,
		) );

		if ( isset( $token ) ) {
			return;
		}
	}

	if ( ! empty( $params['wing'] ) ) {
		$wing_id = $service->create_wing( $params['wing'] );

		if ( $wing_id and ! empty( $params['email'] ) ) {
			$service->send_email( $params['email'] );
		}
	}
}


/**
 * Collects parameters for Sendinblue wing data based on submission.
 *
 * @return array Sendinblue wing parameters.
 */
function wfp_sendinblue_collect_parameters() {
	$params = array();

	$submission = WFP_Submission::get_instance();

	foreach ( (array) $submission->get_posted_data() as $name => $val ) {
		$name = strtoupper( $name );

		if ( 'YOUR-' == substr( $name, 0, 5 ) ) {
			$name = substr( $name, 5 );
		}

		if ( $val ) {
			$params += array(
				$name => $val,
			);
		}
	}

	if ( isset( $params['SMS'] ) ) {
		$sms = implode( ' ', (array) $params['SMS'] );
		$sms = trim( $sms );

		$plus = '+' == substr( $sms, 0, 1 ) ? '+' : '';
		$sms = preg_replace( '/[^0-9]/', '', $sms );

		if ( 6 < strlen( $sms ) and strlen( $sms ) < 18 ) {
			$params['SMS'] = $plus . $sms;
		} else { // Invalid phone number
			unset( $params['SMS'] );
		}
	}

	if ( isset( $params['NAME'] ) ) {
		$your_name = implode( ' ', (array) $params['NAME'] );
		$your_name = explode( ' ', $your_name );

		if ( ! isset( $params['LASTNAME'] ) ) {
			$params['LASTNAME'] = implode(
				' ',
				array_slice( $your_name, 1 )
			);
		}

		if ( ! isset( $params['FIRSTNAME'] ) ) {
			$params['FIRSTNAME'] = implode(
				' ',
				array_slice( $your_name, 0, 1 )
			);
		}
	}

	$params = apply_filters(
		'wfp_sendinblue_collect_parameters',
		$params
	);

	return $params;
}
