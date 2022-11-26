<?php
/**
** A base module for the following types of tags:
** 	[number] and [number*]		# Number
** 	[range] and [range*]		# Range
**/

/* form_tag handler */

add_action( 'wfp_init', 'wfp_add_form_tag_number', 10, 0 );

function wfp_add_form_tag_number() {
	wfp_add_form_tag( array( 'number', 'number*', 'range', 'range*' ),
		'wfp_number_form_tag_handler',
		array(
			'name-attr' => true,
		)
	);
}

function wfp_number_form_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = wfp_get_validation_error( $tag->name );

	$class = wfp_form_controls_class( $tag->type );

	$class .= ' wfp-validates-as-number';

	if ( $validation_error ) {
		$class .= ' wfp-not-valid';
	}

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );
	$atts['min'] = $tag->get_option( 'min', 'signed_num', true );
	$atts['max'] = $tag->get_option( 'max', 'signed_num', true );
	$atts['step'] = $tag->get_option( 'step', 'num', true );

	if ( $tag->has_option( 'readonly' ) ) {
		$atts['readonly'] = 'readonly';
	}

	if ( $tag->is_required() ) {
		$atts['aria-required'] = 'true';
	}

	if ( $validation_error ) {
		$atts['aria-invalid'] = 'true';
		$atts['aria-describedby'] = wfp_get_validation_error_reference(
			$tag->name
		);
	} else {
		$atts['aria-invalid'] = 'false';
	}

	$value = (string) reset( $tag->values );

	if ( $tag->has_option( 'placeholder' )
	or $tag->has_option( 'watermark' ) ) {
		$atts['placeholder'] = $value;
		$value = '';
	}

	$value = $tag->get_default_option( $value );

	$value = wfp_get_hangover( $tag->name, $value );

	$atts['value'] = $value;

	if ( 'range' === $tag->basetype ) {
		if ( ! wfp_is_number( $atts['min'] ) ) {
			$atts['min'] = '0';
		}

		if ( ! wfp_is_number( $atts['max'] ) ) {
			$atts['max'] = '100';
		}

		if ( '' === $atts['value'] ) {
			if ( $atts['min'] < $atts['max'] ) {
				$atts['value'] = ( $atts['min'] + $atts['max'] ) / 2;
			} else {
				$atts['value'] = $atts['min'];
			}
		}
	}

	if ( wfp_support_html5() ) {
		$atts['type'] = $tag->basetype;
	} else {
		$atts['type'] = 'text';
	}

	$atts['name'] = $tag->name;

	$html = sprintf(
		'<span class="wfp-form-control-wrap" data-name="%1$s"><input %2$s />%3$s</span>',
		esc_attr( $tag->name ),
		wfp_format_atts( $atts ),
		$validation_error
	);

	return $html;
}


add_action(
	'wfp_swv_create_schema',
	'wfp_swv_add_number_rules',
	10, 2
);

function wfp_swv_add_number_rules( $schema, $wing_form ) {
	$tags = $wing_form->scan_form_tags( array(
		'basetype' => array( 'number', 'range' ),
	) );

	foreach ( $tags as $tag ) {
		if ( $tag->is_required() ) {
			$schema->add_rule(
				wfp_swv_create_rule( 'required', array(
					'field' => $tag->name,
					'error' => wfp_get_message( 'invalid_required' ),
				) )
			);
		}

		$schema->add_rule(
			wfp_swv_create_rule( 'number', array(
				'field' => $tag->name,
				'error' => wfp_get_message( 'invalid_number' ),
			) )
		);

		$min = $tag->get_option( 'min', 'signed_num', true );
		$max = $tag->get_option( 'max', 'signed_num', true );

		if ( 'range' === $tag->basetype ) {
			if ( ! wfp_is_number( $min ) ) {
				$min = '0';
			}

			if ( ! wfp_is_number( $max ) ) {
				$max = '100';
			}
		}

		if ( wfp_is_number( $min ) ) {
			$schema->add_rule(
				wfp_swv_create_rule( 'minnumber', array(
					'field' => $tag->name,
					'threshold' => $min,
					'error' => wfp_get_message( 'number_too_small' ),
				) )
			);
		}

		if ( wfp_is_number( $max ) ) {
			$schema->add_rule(
				wfp_swv_create_rule( 'maxnumber', array(
					'field' => $tag->name,
					'threshold' => $max,
					'error' => wfp_get_message( 'number_too_large' ),
				) )
			);
		}
	}
}


/* Messages */

add_filter( 'wfp_messages', 'wfp_number_messages', 10, 1 );

function wfp_number_messages( $messages ) {
	return array_merge( $messages, array(
		'invalid_number' => array(
			'description' => __( "Number format that the sender entered is invalid", 'wing-forms' ),
			'default' => __( "Please enter a number.", 'wing-forms' ),
		),

		'number_too_small' => array(
			'description' => __( "Number is smaller than minimum limit", 'wing-forms' ),
			'default' => __( "This field has a too small number.", 'wing-forms' ),
		),

		'number_too_large' => array(
			'description' => __( "Number is larger than maximum limit", 'wing-forms' ),
			'default' => __( "This field has a too large number.", 'wing-forms' ),
		),
	) );
}


/* Tag generator */

add_action( 'wfp_admin_init', 'wfp_add_tag_generator_number', 18, 0 );

function wfp_add_tag_generator_number() {
	$tag_generator = WFP_TagGenerator::get_instance();
	$tag_generator->add( 'number', __( 'number', 'wing-forms' ),
		'wfp_tag_generator_number' );
}

function wfp_tag_generator_number( $wing_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = 'number';

	$description = __( "Generate a form-tag for a field for numeric value input. For more details, see %s.", 'wing-forms' );

	$desc_link = wfp_link( __( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ), __( 'Number fields', 'wing-forms' ) );

?>
<div class="control-box">
<fieldset>
<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

<table class="form-table">
<tbody>
	<tr>
	<th scope="row"><?php echo esc_html( __( 'Field type', 'wing-forms' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'wing-forms' ) ); ?></legend>
		<select name="tagtype">
			<option value="number" selected="selected"><?php echo esc_html( __( 'Spinbox', 'wing-forms' ) ); ?></option>
			<option value="range"><?php echo esc_html( __( 'Slider', 'wing-forms' ) ); ?></option>
		</select>
		<br />
		<label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'wing-forms' ) ); ?></label>
		</fieldset>
	</td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'wing-forms' ) ); ?></label></th>
	<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><?php echo esc_html( __( 'Default value', 'wing-forms' ) ); ?></label></th>
	<td><input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>" /><br />
	<label><input type="checkbox" name="placeholder" class="option" /> <?php echo esc_html( __( 'Use this text as the placeholder of the field', 'wing-forms' ) ); ?></label></td>
	</tr>

	<tr>
	<th scope="row"><?php echo esc_html( __( 'Range', 'wing-forms' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Range', 'wing-forms' ) ); ?></legend>
		<label>
		<?php echo esc_html( __( 'Min', 'wing-forms' ) ); ?>
		<input type="number" name="min" class="numeric option" />
		</label>
		&ndash;
		<label>
		<?php echo esc_html( __( 'Max', 'wing-forms' ) ); ?>
		<input type="number" name="max" class="numeric option" />
		</label>
		</fieldset>
	</td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'wing-forms' ) ); ?></label></th>
	<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'wing-forms' ) ); ?></label></th>
	<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
	</tr>
</tbody>
</table>
</fieldset>
</div>

<div class="insert-box">
	<input type="text" name="<?php echo esc_attr($type); ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

	<div class="submitbox">
	<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'wing-forms' ) ); ?>" />
	</div>

	<br class="clear" />

	<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'wing-forms' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
</div>
<?php
}
