<?php

add_action( 'wfp_init', 'wfp_add_form_tag_hidden', 10, 0 );

function wfp_add_form_tag_hidden() {
	wfp_add_form_tag( 'hidden',
		'wfp_hidden_form_tag_handler',
		array(
			'name-attr' => true,
			'display-hidden' => true,
		)
	);
}

function wfp_hidden_form_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$atts = array();

	$class = wfp_form_controls_class( $tag->type );
	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();

	$value = (string) reset( $tag->values );
	$value = $tag->get_default_option( $value );
	$atts['value'] = $value;

	$atts['type'] = 'hidden';
	$atts['name'] = $tag->name;
	$atts = wfp_format_atts( $atts );

	$html = sprintf( '<input %s />', $atts );
	return $html;
}
