<?php
/**
** A base module for [file] and [file*]
**/

/* form_tag handler */

add_action( 'wfp_init', 'wfp_add_form_tag_file', 10, 0 );

function wfp_add_form_tag_file() {
	wfp_add_form_tag( array( 'file', 'file*' ),
		'wfp_file_form_tag_handler',
		array(
			'name-attr' => true,
			'file-uploading' => true,
		)
	);
}

function wfp_file_form_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = wfp_get_validation_error( $tag->name );

	$class = wfp_form_controls_class( $tag->type );

	if ( $validation_error ) {
		$class .= ' wfp-not-valid';
	}

	$atts = array();

	$atts['size'] = $tag->get_size_option( '40' );
	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );

	$atts['accept'] = wfp_acceptable_filetypes(
		$tag->get_option( 'filetypes' ), 'attr'
	);

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

	$atts['type'] = 'file';
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
	'wfp_swv_add_file_rules',
	10, 2
);

function wfp_swv_add_file_rules( $schema, $wing_form ) {
	$tags = $wing_form->scan_form_tags( array(
		'basetype' => array( 'file' ),
	) );

	foreach ( $tags as $tag ) {
		if ( $tag->is_required() ) {
			$schema->add_rule(
				wfp_swv_create_rule( 'requiredfile', array(
					'field' => $tag->name,
					'error' => wfp_get_message( 'invalid_required' ),
				) )
			);
		}

		$schema->add_rule(
			wfp_swv_create_rule( 'file', array(
				'field' => $tag->name,
				'accept' => explode( ',', wfp_acceptable_filetypes(
					$tag->get_option( 'filetypes' ), 'attr'
				) ),
				'error' => wfp_get_message( 'upload_file_type_invalid' ),
			) )
		);

		$schema->add_rule(
			wfp_swv_create_rule( 'maxfilesize', array(
				'field' => $tag->name,
				'threshold' => $tag->get_limit_option(),
				'error' => wfp_get_message( 'upload_file_too_large' ),
			) )
		);
	}
}


add_filter( 'wfp_mail_tag_replaced_file', 'wfp_file_mail_tag', 10, 4 );
add_filter( 'wfp_mail_tag_replaced_file*', 'wfp_file_mail_tag', 10, 4 );

function wfp_file_mail_tag( $replaced, $submitted, $html, $mail_tag ) {
	$submission = WFP_Submission::get_instance();
	$uploaded_files = $submission->uploaded_files();
	$name = $mail_tag->field_name();

	if ( ! empty( $uploaded_files[$name] ) ) {
		$paths = (array) $uploaded_files[$name];
		$paths = array_map( 'wp_basename', $paths );

		$replaced = wfp_flat_join( $paths );
	}

	return $replaced;
}


/* Tag generator */

add_action( 'wfp_admin_init', 'wfp_add_tag_generator_file', 50, 0 );

function wfp_add_tag_generator_file() {
	$tag_generator = WFP_TagGenerator::get_instance();
	$tag_generator->add( 'file', __( 'file', 'wing-forms' ),
		'wfp_tag_generator_file' );
}

function wfp_tag_generator_file( $wing_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = 'file';

	$description = __( "Generate a form-tag for a file uploading field. For more details, see %s.", 'wing-forms' );

	$desc_link = wfp_link( __( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ), __( 'File uploading and attachment', 'wing-forms' ) );

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
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-limit' ); ?>"><?php echo esc_html( __( "File size limit (bytes)", 'wing-forms' ) ); ?></label></th>
	<td><input type="text" name="limit" class="filesize oneline option" id="<?php echo esc_attr( $args['content'] . '-limit' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-filetypes' ); ?>"><?php echo esc_html( __( 'Acceptable file types', 'wing-forms' ) ); ?></label></th>
	<td><input type="text" name="filetypes" class="filetype oneline option" id="<?php echo esc_attr( $args['content'] . '-filetypes' ); ?>" /></td>
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

	<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To attach the file uploaded through this field to mail, you need to insert the corresponding mail-tag (%s) into the File Attachments field on the Mail tab.", 'wing-forms' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
</div>
<?php
}
