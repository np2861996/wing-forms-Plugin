<?php
/**
** A base module for the following types of tags:
** 	[date] and [date*]		# Date
**/

/* form_tag handler */

add_action( 'wfp_init', 'wfp_add_form_tag_date', 10, 0 );

function wfp_add_form_tag_date() {
	wfp_add_form_tag( array( 'date', 'date*' ),
		'wfp_date_form_tag_handler',
		array(
			'name-attr' => true,
		)
	);
}

function wfp_date_form_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = wfp_get_validation_error( $tag->name );

	$class = wfp_form_controls_class( $tag->type );

	$class .= ' wfp-validates-as-date';

	if ( $validation_error ) {
		$class .= ' wfp-not-valid';
	}

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );
	$atts['min'] = $tag->get_date_option( 'min' );
	$atts['max'] = $tag->get_date_option( 'max' );
	$atts['step'] = $tag->get_option( 'step', 'int', true );

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

	if ( $value ) {
		$datetime_obj = date_create_immutable(
			preg_replace( '/[_]+/', ' ', $value ),
			wp_timezone()
		);

		if ( $datetime_obj ) {
			$value = $datetime_obj->format( 'Y-m-d' );
		}
	}

	$value = wfp_get_hangover( $tag->name, $value );

	$atts['value'] = $value;

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
	'wfp_swv_add_date_rules',
	10, 2
);

function wfp_swv_add_date_rules( $schema, $wing_form ) {
	$tags = $wing_form->scan_form_tags( array(
		'basetype' => array( 'date' ),
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
			wfp_swv_create_rule( 'date', array(
				'field' => $tag->name,
				'error' => wfp_get_message( 'invalid_date' ),
			) )
		);

		$min = $tag->get_date_option( 'min' );
		$max = $tag->get_date_option( 'max' );

		if ( false !== $min ) {
			$schema->add_rule(
				wfp_swv_create_rule( 'mindate', array(
					'field' => $tag->name,
					'threshold' => $min,
					'error' => wfp_get_message( 'date_too_early' ),
				) )
			);
		}

		if ( false !== $max ) {
			$schema->add_rule(
				wfp_swv_create_rule( 'maxdate', array(
					'field' => $tag->name,
					'threshold' => $max,
					'error' => wfp_get_message( 'date_too_late' ),
				) )
			);
		}
	}
}


/* Messages */

add_filter( 'wfp_messages', 'wfp_date_messages', 10, 1 );

function wfp_date_messages( $messages ) {
	return array_merge( $messages, array(
		'invalid_date' => array(
			'description' => __( "Date format that the sender entered is invalid", 'wing-forms' ),
			'default' => __( "Please enter a date in YYYY-MM-DD format.", 'wing-forms' ),
		),

		'date_too_early' => array(
			'description' => __( "Date is earlier than minimum limit", 'wing-forms' ),
			'default' => __( "This field has a too early date.", 'wing-forms' ),
		),

		'date_too_late' => array(
			'description' => __( "Date is later than maximum limit", 'wing-forms' ),
			'default' => __( "This field has a too late date.", 'wing-forms' ),
		),
	) );
}


/* Tag generator */

add_action( 'wfp_admin_init', 'wfp_add_tag_generator_date', 19, 0 );

function wfp_add_tag_generator_date() {
	$tag_generator = WFP_TagGenerator::get_instance();
	$tag_generator->add( 'date', __( 'date', 'wing-forms' ),
		'wfp_tag_generator_date' );
}

function wfp_tag_generator_date( $wing_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = 'date';

	$description = __( "Generate a form-tag for a date input field. For more details, see %s.", 'wing-forms' );

	$desc_link = wfp_link( __( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ), __( 'Date field', 'wing-forms' ) );

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
		<input type="date" name="min" class="date option" />
		</label>
		&ndash;
		<label>
		<?php echo esc_html( __( 'Max', 'wing-forms' ) ); ?>
		<input type="date" name="max" class="date option" />
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
