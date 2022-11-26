<?php
/**
** A base module for the following types of tags:
** 	[text] and [text*]		# Single-line text
** 	[email] and [email*]	# Email address
** 	[url] and [url*]		# URL
** 	[tel] and [tel*]		# Telephone number
**/

/* form_tag handler */

add_action( 'wfp_init', 'wfp_add_form_tag_text', 10, 0 );

function wfp_add_form_tag_text() {
	wfp_add_form_tag(
		array( 'text', 'text*', 'email', 'email*', 'url', 'url*', 'tel', 'tel*' ),
		'wfp_text_form_tag_handler',
		array(
			'name-attr' => true,
		)
	);
}

function wfp_text_form_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = wfp_get_validation_error( $tag->name );

	$class = wfp_form_controls_class( $tag->type, 'wfp-text' );

	if ( in_array( $tag->basetype, array( 'email', 'url', 'tel' ) ) ) {
		$class .= ' wfp-validates-as-' . $tag->basetype;
	}

	if ( $validation_error ) {
		$class .= ' wfp-not-valid';
	}

	$atts = array();

	$atts['size'] = $tag->get_size_option( '40' );
	$atts['maxlength'] = $tag->get_maxlength_option();
	$atts['minlength'] = $tag->get_minlength_option();

	if ( $atts['maxlength'] and $atts['minlength']
	and $atts['maxlength'] < $atts['minlength'] ) {
		unset( $atts['maxlength'], $atts['minlength'] );
	}

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );

	$atts['autocomplete'] = $tag->get_option( 'autocomplete',
		'[-0-9a-zA-Z]+', true );

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
	'wfp_swv_add_text_rules',
	10, 2
);

function wfp_swv_add_text_rules( $schema, $wing_form ) {
	$tags = $wing_form->scan_form_tags( array(
		'basetype' => array( 'text', 'email', 'url', 'tel' ),
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

		if ( 'email' === $tag->basetype ) {
			$schema->add_rule(
				wfp_swv_create_rule( 'email', array(
					'field' => $tag->name,
					'error' => wfp_get_message( 'invalid_email' ),
				) )
			);
		}

		if ( 'url' === $tag->basetype ) {
			$schema->add_rule(
				wfp_swv_create_rule( 'url', array(
					'field' => $tag->name,
					'error' => wfp_get_message( 'invalid_url' ),
				) )
			);
		}

		if ( 'tel' === $tag->basetype ) {
			$schema->add_rule(
				wfp_swv_create_rule( 'tel', array(
					'field' => $tag->name,
					'error' => wfp_get_message( 'invalid_tel' ),
				) )
			);
		}

		if ( $minlength = $tag->get_minlength_option() ) {
			$schema->add_rule(
				wfp_swv_create_rule( 'minlength', array(
					'field' => $tag->name,
					'threshold' => absint( $minlength ),
					'error' => wfp_get_message( 'invalid_too_short' ),
				) )
			);
		}

		if ( $maxlength = $tag->get_maxlength_option() ) {
			$schema->add_rule(
				wfp_swv_create_rule( 'maxlength', array(
					'field' => $tag->name,
					'threshold' => absint( $maxlength ),
					'error' => wfp_get_message( 'invalid_too_long' ),
				) )
			);
		}
	}
}


/* Messages */

add_filter( 'wfp_messages', 'wfp_text_messages', 10, 1 );

function wfp_text_messages( $messages ) {
	$messages = array_merge( $messages, array(
		'invalid_email' => array(
			'description' =>
				__( "Email address that the sender entered is invalid", 'wing-forms' ),
			'default' =>
				__( "Please enter an email address.", 'wing-forms' ),
		),

		'invalid_url' => array(
			'description' =>
				__( "URL that the sender entered is invalid", 'wing-forms' ),
			'default' =>
				__( "Please enter a URL.", 'wing-forms' ),
		),

		'invalid_tel' => array(
			'description' =>
				__( "Telephone number that the sender entered is invalid", 'wing-forms' ),
			'default' =>
				__( "Please enter a telephone number.", 'wing-forms' ),
		),
	) );

	return $messages;
}


/* Tag generator */

add_action( 'wfp_admin_init', 'wfp_add_tag_generator_text', 15, 0 );

function wfp_add_tag_generator_text() {
	$tag_generator = WFP_TagGenerator::get_instance();
	$tag_generator->add( 'text', __( 'text', 'wing-forms' ),
		'wfp_tag_generator_text' );
	$tag_generator->add( 'email', __( 'email', 'wing-forms' ),
		'wfp_tag_generator_text' );
	$tag_generator->add( 'url', __( 'URL', 'wing-forms' ),
		'wfp_tag_generator_text' );
	$tag_generator->add( 'tel', __( 'tel', 'wing-forms' ),
		'wfp_tag_generator_text' );
}

function wfp_tag_generator_text( $wing_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = $args['id'];

	if ( ! in_array( $type, array( 'email', 'url', 'tel' ) ) ) {
		$type = 'text';
	}

	if ( 'text' == $type ) {
		$description = __( "Generate a form-tag for a single-line plain text input field. For more details, see %s.", 'wing-forms' );
	} elseif ( 'email' == $type ) {
		$description = __( "Generate a form-tag for a single-line email address input field. For more details, see %s.", 'wing-forms' );
	} elseif ( 'url' == $type ) {
		$description = __( "Generate a form-tag for a single-line URL input field. For more details, see %s.", 'wing-forms' );
	} elseif ( 'tel' == $type ) {
		$description = __( "Generate a form-tag for a single-line telephone number input field. For more details, see %s.", 'wing-forms' );
	}

	$desc_link = wfp_link( __( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ), __( 'Text fields', 'wing-forms' ) );

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

<?php if ( in_array( $type, array( 'text', 'email', 'url' ) ) ) : ?>
	<tr>
	<th scope="row"><?php echo esc_html( __( 'Akismet', 'wing-forms' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Akismet', 'wing-forms' ) ); ?></legend>

<?php if ( 'text' == $type ) : ?>
		<label>
			<input type="checkbox" name="akismet:author" class="option" />
			<?php echo esc_html( __( "This field requires author's name", 'wing-forms' ) ); ?>
		</label>
<?php elseif ( 'email' == $type ) : ?>
		<label>
			<input type="checkbox" name="akismet:author_email" class="option" />
			<?php echo esc_html( __( "This field requires author's email address", 'wing-forms' ) ); ?>
		</label>
<?php elseif ( 'url' == $type ) : ?>
		<label>
			<input type="checkbox" name="akismet:author_url" class="option" />
			<?php echo esc_html( __( "This field requires author's URL", 'wing-forms' ) ); ?>
		</label>
<?php endif; ?>

		</fieldset>
	</td>
	</tr>
<?php endif; ?>

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
