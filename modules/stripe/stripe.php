<?php
/**
 * Stripe module main file
 *
 * @link https://github.com/np2861996/wing-forms-Plugin
 */

wfp_include_module_file( 'stripe/service.php' );
wfp_include_module_file( 'stripe/api.php' );


add_action(
	'wfp_init',
	'wfp_stripe_register_service',
	10, 0
);

/**
 * Registers the Stripe service.
 */
function wfp_stripe_register_service() {
	$integration = WFP_Integration::get_instance();

	$integration->add_service( 'stripe',
		WFP_Stripe::get_instance()
	);
}


add_action(
	'wfp_enqueue_scripts',
	'wfp_stripe_enqueue_scripts',
	10, 0
);

/**
 * Enqueues scripts and styles for the Stripe module.
 */
function wfp_stripe_enqueue_scripts() {
	$service = WFP_Stripe::get_instance();

	if ( ! $service->is_active() ) {
		return;
	}

	wp_enqueue_style( 'wfp-stripe',
		wfp_plugin_url( 'modules/stripe/style.css' ),
		array(), WFP_VERSION, 'all'
	);

	wp_enqueue_script( 'stripe',
		'https://js.stripe.com/v3/',
		array(), null
	);

	$assets = array();

	$asset_file = wfp_plugin_path( 'modules/stripe/index.asset.php' );

	if ( file_exists( $asset_file ) ) {
		$assets = include( $asset_file );
	}

	$assets = wp_parse_args( $assets, array(
		'src' => wfp_plugin_url( 'modules/stripe/index.js' ),
		'dependencies' => array(
			'wp-polyfill',
		),
		'version' => WFP_VERSION,
	) );

	wp_enqueue_script(
		'wfp-stripe',
		$assets['src'],
		array_merge( array(
			'wing-forms',
			'stripe',
		), $assets['dependencies'] ),
		$assets['version'],
		true
	);

	$api_keys = $service->get_api_keys();

	if ( $api_keys['publishable'] ) {
		wp_localize_script( 'wfp-stripe', 'wfp_stripe', array(
			'publishable_key' => $api_keys['publishable'],
		) );
	}
}


add_filter(
	'wfp_skip_spam_check',
	'wfp_stripe_skip_spam_check',
	10, 2
);

/**
 * Skips the spam check if it is not necessary.
 *
 * @return bool True if the spam check is not necessary.
 */
function wfp_stripe_skip_spam_check( $skip_spam_check, $submission ) {
	$service = WFP_Stripe::get_instance();

	if ( ! $service->is_active() ) {
		return $skip_spam_check;
	}

	if ( ! empty( $_POST['_wfp_stripe_payment_intent'] ) ) {
		$s_wfp_stripe_payment_intent = sanitize_text_field($_POST['_wfp_stripe_payment_intent']);
		$pi_id = trim( $s_wfp_stripe_payment_intent );
		$payment_intent = $service->api()->retrieve_payment_intent( $pi_id );

		if ( isset( $payment_intent['status'] )
		and ( 'succeeded' === $payment_intent['status'] ) ) {
			$submission->payment_intent = $pi_id;
		}
	}

	if ( ! empty( $submission->payment_intent )
	and $submission->verify_posted_data_hash() ) {
		$skip_spam_check = true;
	}

	return $skip_spam_check;
}


add_action(
	'wfp_before_send_mail',
	'wfp_stripe_before_send_mail',
	10, 3
);

/**
 * Creates Stripe's Payment Intent.
 */
function wfp_stripe_before_send_mail( $wing_form, &$abort, $submission ) {
	$service = WFP_Stripe::get_instance();

	if ( ! $service->is_active() ) {
		return;
	}

	$tags = $wing_form->scan_form_tags( array( 'type' => 'stripe' ) );

	if ( ! $tags ) {
		return;
	}

	if ( ! empty( $submission->payment_intent ) ) {
		return;
	}

	$tag = $tags[0];
	$amount = $tag->get_option( 'amount', 'int', true );
	$currency = $tag->get_option( 'currency', '[a-zA-Z]{3}', true );

	$payment_intent_params = apply_filters(
		'wfp_stripe_payment_intent_parameters',
		array(
			'amount' => $amount ? absint( $amount ) : null,
			'currency' => $currency ? strtolower( $currency ) : null,
			'receipt_email' => $submission->get_posted_data( 'your-email' ),
		)
	);

	$payment_intent = $service->api()->create_payment_intent(
		$payment_intent_params
	);

	if ( $payment_intent ) {
		$submission->add_result_props( array(
			'stripe' => array(
				'payment_intent' => array(
					'id' => $payment_intent['id'],
					'client_secret' => $payment_intent['client_secret'],
				),
			),
		) );

		$submission->set_status( 'payment_required' );

		$submission->set_response(
			__( "Payment is required. Please pay by credit card.", 'wing-forms' )
		);
	}

	$abort = true;
}


/**
 * Returns payment link URL.
 *
 * @param string $pi_id Payment Intent ID.
 * @return string The URL.
 */
function wfp_stripe_get_payment_link( $pi_id ) {
	return sprintf(
		'https://dashboard.stripe.com/payments/%s',
		urlencode( $pi_id )
	);
}


add_filter(
	'wfp_special_mail_tags',
	'wfp_stripe_smt',
	10, 4
);

/**
 * Registers the [_stripe_payment_link] special mail-tag.
 */
function wfp_stripe_smt( $output, $tag_name, $html, $mail_tag = null ) {
	if ( '_stripe_payment_link' === $tag_name ) {
		$submission = WFP_Submission::get_instance();

		if ( ! empty( $submission->payment_intent ) ) {
			$output = wfp_stripe_get_payment_link( $submission->payment_intent );
		}
	}

	return $output;
}


add_filter(
	'wfp_flamingo_inbound_message_parameters',
	'wfp_stripe_add_flamingo_inbound_message_params',
	10, 1
);

/**
 * Adds Stripe-related meta data to Flamingo Inbound Message parameters.
 */
function wfp_stripe_add_flamingo_inbound_message_params( $args ) {
	$submission = WFP_Submission::get_instance();

	if ( empty( $submission->payment_intent ) ) {
		return $args;
	}

	$pi_link = wfp_stripe_get_payment_link( $submission->payment_intent );

	$meta = (array) $args['meta'];

	$meta['stripe_payment_link'] = $pi_link;

	$args['meta'] = $meta;

	return $args;
}


add_action(
	'wfp_init',
	'wfp_add_form_tag_stripe',
	10, 0
);

/**
 * Registers the stripe form-tag handler.
 */
function wfp_add_form_tag_stripe() {
	wfp_add_form_tag(
		'stripe',
		'wfp_stripe_form_tag_handler',
		array(
			'display-block' => true,
			'singular' => true,
		)
	);
}


/**
 * Defines the stripe form-tag handler.
 *
 * @return string HTML content that replaces a stripe form-tag.
 */
function wfp_stripe_form_tag_handler( $tag ) {
	$card_element = sprintf(
		'<div %s></div>',
		wfp_format_atts( array(
			'class' => 'card-element wfp-form-control',
			'aria-invalid' => 'false',
		) )
	);

	$card_element = sprintf(
		'<div class="wfp-form-control-wrap hidden">%s</div>',
		$card_element
	);

	$button_1_label = __( 'Proceed to checkout', 'wing-forms' );

	if ( isset( $tag->values[0] ) ) {
		$button_1_label = trim( $tag->values[0] );
	}

	$button_1 = sprintf(
		'<button %1$s>%2$s</button>',
		wfp_format_atts( array(
			'type' => 'submit',
			'class' => 'first',
		) ),
		esc_html( $button_1_label )
	);

	$button_2_label = __( 'Complete payment', 'wing-forms' );

	if ( isset( $tag->values[1] ) ) {
		$button_2_label = trim( $tag->values[1] );
	}

	$button_2 = sprintf(
		'<button %1$s>%2$s</button>',
		wfp_format_atts( array(
			'type' => 'button',
			'class' => 'second hidden',
		) ),
		esc_html( $button_2_label )
	);

	$buttons = sprintf(
		'<span class="buttons has-spinner">%1$s %2$s</span>',
		$button_1, $button_2
	);

	return sprintf(
		'<div class="wfp-stripe">%1$s %2$s %3$s</div>',
		$card_element,
		$buttons,
		'<input type="hidden" name="_wfp_stripe_payment_intent" value="" />'
	);
}
