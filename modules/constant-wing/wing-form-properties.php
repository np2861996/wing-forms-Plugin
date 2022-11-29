<?php

add_filter(
	'wfp_pre_construct_wing_form_properties',
	'wfp_constant_wing_register_property',
	10, 2
);

/**
 * Registers the constant_wing wing form property.
 */
function wfp_constant_wing_register_property( $properties, $wing_form ) {
	$service = WFP_ConstantWing::get_instance();

	if ( $service->is_active() ) {
		$properties += array(
			'constant_wing' => array(),
		);
	}

	return $properties;
}


add_filter(
	'wfp_wing_form_property_constant_wing',
	'wfp_constant_wing_setup_property',
	10, 2
);

/**
 * Sets up the constant_wing property value. For back-compat, this attempts
 * to take over the value from old settings if the property is empty.
 */
function wfp_constant_wing_setup_property( $property, $wing_form ) {
	if ( ! empty( $property ) ) {
		$property = wp_parse_args(
			$property,
			array(
				'enable_wing_list' => false,
				'wing_lists' => array(),
			)
		);

		return $property;
	}

	$property = array(
		'enable_wing_list' => true,
		'wing_lists' => array(),
	);

	if ( $wing_form->initial() ) {
		return $property;
	}

	$service_option = (array) WFP::get_option( 'constant_wing' );

	$property['enable_wing_list'] = ! $wing_form->is_false(
		'constant_wing'
	);

	if ( isset( $service_option['wing_lists'] ) ) {
		$wing_lists = (array) $service_option['wing_lists'];
		$wing_lists_selected = array();

		foreach ( $wing_lists as $list ) {
			if ( empty( $list['selected'] ) ) {
				continue;
			}

			foreach ( (array) $list['selected'] as $key => $val ) {
				if ( ! isset( $wing_lists_selected[$key] ) ) {
					$wing_lists_selected[$key] = array();
				}

				$wing_lists_selected[$key][] = $list['list_id'];
			}
		}

		$related_keys = array(
			sprintf( 'wfp_wing_form:%d', $wing_form->id() ),
			'default',
		);

		foreach ( $related_keys as $key ) {
			if ( ! empty( $wing_lists_selected[$key] ) ) {
				$property['wing_lists'] = $wing_lists_selected[$key];
				break;
			}
		}
	}

	return $property;
}


add_action(
	'wfp_save_wing_form',
	'wfp_constant_wing_save_wing_form',
	10, 1
);

/**
 * Saves the constant_wing property value.
 */
function wfp_constant_wing_save_wing_form( $wing_form ) {
	$service = WFP_ConstantWing::get_instance();

	if ( ! $service->is_active() ) {
		return;
	}

	$swfpctct = sanitize_text_field($_POST['wfp-ctct']);

	$prop = isset( $swfpctct )
		? (array) $swfpctct
		: array();

	$prop = wp_parse_args(
		$prop,
		array(
			'enable_wing_list' => false,
			'wing_lists' => array(),
		)
	);

	$wing_form->set_properties( array(
		'constant_wing' => $prop,
	) );
}


add_filter(
	'wfp_editor_panels',
	'wfp_constant_wing_editor_panels',
	10, 1
);

/**
 * Builds the editor panel for the constant_wing property.
 */
function wfp_constant_wing_editor_panels( $panels ) {
	$service = WFP_ConstantWing::get_instance();

	if ( ! $service->is_active() ) {
		return $panels;
	}

	$wing_form = WFP_WingForm::get_current();

	$prop = wp_parse_args(
		$wing_form->prop( 'constant_wing' ),
		array(
			'enable_wing_list' => false,
			'wing_lists' => array(),
		)
	);

	$editor_panel = function () use ( $prop, $service ) {

		$description = sprintf(
			esc_html(
				__( "You can set up the Constant Wing integration here. For details, see %s.", 'wing-forms' )
			),
			wfp_link(
				__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
				__( 'Constant Wing integration', 'wing-forms' )
			)
		);

		$lists = $service->get_wing_lists();

?>
<h2><?php echo esc_html( __( 'Constant Wing', 'wing-forms' ) ); ?></h2>

<fieldset>
	<legend><?php echo esc_attr($description); ?></legend>

	<table class="form-table" role="presentation">
		<tbody>
			<tr class="<?php echo esc_attr($prop['enable_wing_list']) ? '' : 'inactive'; ?>">
				<th scope="row">
		<?php

		echo esc_html( __( 'Wing lists', 'wing-forms' ) );

		?>
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
		<?php

		echo esc_html( __( 'Wing lists', 'wing-forms' ) );

		?>
						</legend>
						<label for="wfp-ctct-enable-wing-list">
							<input type="checkbox" name="wfp-ctct[enable_wing_list]" id="wfp-ctct-enable-wing-list" value="1" <?php checked( $prop['enable_wing_list'] ); ?> />
		<?php

		echo esc_html(
			__( "Add form submitters to your wing lists", 'wing-forms' )
		);

		?>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"></th>
				<td>
					<fieldset>
		<?php

		if ( $lists ) {
			echo sprintf(
				'<legend>%1$s</legend>',
				esc_html( __( 'Select lists to which wings are added:', 'wing-forms' ) )
			);

			echo '<ul>';

			foreach ( $lists as $list ) {
				echo sprintf(
					'<li><label><input %1$s /> %2$s</label></li>',
					wfp_format_atts( array(
						'type' => 'checkbox',
						'name' => 'wfp-ctct[wing_lists][]',
						'value' => $list['list_id'],
						'checked' => in_array( $list['list_id'], $prop['wing_lists'] )
							? 'checked'
							: '',
					) ),
					esc_html( $list['name'] )
				);
			}

			echo '</ul>';
		} else {
			echo sprintf(
				'<legend>%1$s</legend>',
				esc_html( __( 'You have no wing list yet.', 'wing-forms' ) )
			);
		}

		?>
					</fieldset>
		<?php

		echo sprintf(
			'<p><a %1$s>%2$s <span class="screen-reader-text">%3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>',
			wfp_format_atts( array(
				'href' => 'https://app.constantwing.com/pages/wings/ui#lists',
				'target' => '_blank',
				'rel' => 'external noreferrer noopener',
			) ),
			esc_html( __( 'Manage your wing lists', 'wing-forms' ) ),
			esc_html( __( '(opens in a new tab)', 'wing-forms' ) )
		);

		?>
				</td>
			</tr>
		</tbody>
	</table>
</fieldset>
<?php
	};

	$panels += array(
		'ctct-panel' => array(
			'title' => __( 'Constant Wing', 'wing-forms' ),
			'callback' => $editor_panel,
		),
	);

	return $panels;
}
