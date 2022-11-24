<?php
/**
** A base module for [select] and [select*]
**/

/* form_tag handler */

add_action( 'wfp_init', 'wfp_add_form_tag_select', 10, 0 );

function wfp_add_form_tag_select() {
	wfp_add_form_tag( array( 'select', 'select*' ),
		'wfp_select_form_tag_handler',
		array(
			'name-attr' => true,
			'selectable-values' => true,
		)
	);
}

function wfp_select_form_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = wfp_get_validation_error( $tag->name );

	$class = wfp_form_controls_class( $tag->type );

	if ( $validation_error ) {
		$class .= ' wfp-not-valid';
	}

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );

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

	$multiple = $tag->has_option( 'multiple' );
	$include_blank = $tag->has_option( 'include_blank' );
	$first_as_label = $tag->has_option( 'first_as_label' );

	if ( $tag->has_option( 'size' ) ) {
		$size = $tag->get_option( 'size', 'int', true );

		if ( $size ) {
			$atts['size'] = $size;
		} elseif ( $multiple ) {
			$atts['size'] = 4;
		} else {
			$atts['size'] = 1;
		}
	}

	if ( $data = (array) $tag->get_data_option() ) {
		$tag->values = array_merge( $tag->values, array_values( $data ) );
		$tag->labels = array_merge( $tag->labels, array_values( $data ) );
	}

	$values = $tag->values;
	$labels = $tag->labels;

	$default_choice = $tag->get_default_option( null, array(
		'multiple' => $multiple,
	) );

	if ( $include_blank
	or empty( $values ) ) {
		array_unshift( $labels, '---' );
		array_unshift( $values, '' );
	} elseif ( $first_as_label ) {
		$values[0] = '';
	}

	$html = '';
	$hangover = wfp_get_hangover( $tag->name );

	foreach ( $values as $key => $value ) {
		if ( $hangover ) {
			$selected = in_array( $value, (array) $hangover, true );
		} else {
			$selected = in_array( $value, (array) $default_choice, true );
		}

		$item_atts = array(
			'value' => $value,
			'selected' => $selected ? 'selected' : '',
		);

		$item_atts = wfp_format_atts( $item_atts );

		$label = isset( $labels[$key] ) ? $labels[$key] : $value;

		$html .= sprintf(
			'<option %1$s>%2$s</option>',
			$item_atts,
			esc_html( $label )
		);
	}

	if ( $multiple ) {
		$atts['multiple'] = 'multiple';
	}

	$atts['name'] = $tag->name . ( $multiple ? '[]' : '' );

	$html = sprintf(
		'<span class="wfp-form-control-wrap" data-name="%1$s"><select %2$s>%3$s</select>%4$s</span>',
		esc_attr( $tag->name ),
		wfp_format_atts( $atts ),
		$html,
		$validation_error
	);

	return $html;
}


add_action(
	'wfp_swv_create_schema',
	'wfp_swv_add_select_rules',
	10, 2
);

function wfp_swv_add_select_rules( $schema, $wing_form ) {
	$tags = $wing_form->scan_form_tags( array(
		'type' => array( 'select*' ),
	) );

	foreach ( $tags as $tag ) {
		$schema->add_rule(
			wfp_swv_create_rule( 'required', array(
				'field' => $tag->name,
				'error' => wfp_get_message( 'invalid_required' ),
			) )
		);
	}
}


/* Tag generator */

add_action( 'wfp_admin_init', 'wfp_add_tag_generator_menu', 25, 0 );

function wfp_add_tag_generator_menu() {
	$tag_generator = WFP_TagGenerator::get_instance();
	$tag_generator->add( 'menu', __( 'drop-down menu', 'wing-forms' ),
		'wfp_tag_generator_menu' );
}

function wfp_tag_generator_menu( $wing_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );

	$description = __( "Generate a form-tag for a drop-down menu. For more details, see %s.", 'wing-forms' );

	$desc_link = wfp_link( __( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ), __( 'Checkboxes, radio buttons and menus', 'wing-forms' ) );

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
	<th scope="row"><?php echo esc_html( __( 'Options', 'wing-forms' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Options', 'wing-forms' ) ); ?></legend>
		<textarea name="values" class="values" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>"></textarea>
		<label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><span class="description"><?php echo esc_html( __( "One option per line.", 'wing-forms' ) ); ?></span></label><br />
		<label><input type="checkbox" name="multiple" class="option" /> <?php echo esc_html( __( 'Allow multiple selections', 'wing-forms' ) ); ?></label><br />
		<label><input type="checkbox" name="include_blank" class="option" /> <?php echo esc_html( __( 'Insert a blank item as the first option', 'wing-forms' ) ); ?></label>
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
	<input type="text" name="select" class="tag code" readonly="readonly" onfocus="this.select()" />

	<div class="submitbox">
	<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'wing-forms' ) ); ?>" />
	</div>

	<br class="clear" />

	<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'wing-forms' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
</div>
<?php
}
