<?php

add_filter(
	'wfp_pre_construct_wing_form_properties',
	'wfp_sendinblue_register_property',
	10, 2
);

/**
 * Registers the sendinblue wing form property.
 */
function wfp_sendinblue_register_property( $properties, $wing_form ) {
	$service = WFP_Sendinblue::get_instance();

	if ( $service->is_active() ) {
		$properties += array(
			'sendinblue' => array(),
		);
	}

	return $properties;
}


add_action(
	'wfp_save_wing_form',
	'wfp_sendinblue_save_wing_form',
	10, 3
);

/**
 * Saves the sendinblue property value.
 */
function wfp_sendinblue_save_wing_form( $wing_form, $args, $context ) {
	$service = WFP_Sendinblue::get_instance();

	if ( ! $service->is_active() ) {
		return;
	}

	$swfpsendinblue = sanitize_text_field($_POST['wfp-sendinblue']);

	$prop = isset( $swfpsendinblue )
		? (array) $swfpsendinblue
		: array();

	$prop = wp_parse_args(
		$prop,
		array(
			'enable_wing_list' => false,
			'wing_lists' => array(),
			'enable_transactional_email' => false,
			'email_template' => 0,
		)
	);

	$prop['wing_lists'] = array_map( 'absint', $prop['wing_lists'] );

	$prop['email_template'] = absint( $prop['email_template'] );

	$wing_form->set_properties( array(
		'sendinblue' => $prop,
	) );
}


add_filter(
	'wfp_editor_panels',
	'wfp_sendinblue_editor_panels',
	10, 1
);

/**
 * Builds the editor panel for the sendinblue property.
 */
function wfp_sendinblue_editor_panels( $panels ) {
	$service = WFP_Sendinblue::get_instance();

	if ( ! $service->is_active() ) {
		return $panels;
	}

	$wing_form = WFP_WingForm::get_current();

	$prop = wp_parse_args(
		$wing_form->prop( 'sendinblue' ),
		array(
			'enable_wing_list' => false,
			'wing_lists' => array(),
			'enable_transactional_email' => false,
			'email_template' => 0,
		)
	);

	$editor_panel = function () use ( $prop, $service ) {

		$description = sprintf(
			esc_html(
				__( "You can set up the Sendinblue integration here. For details, see %s.", 'wing-forms' )
			),
			wfp_link(
				__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
				__( 'Sendinblue integration', 'wing-forms' )
			)
		);

		$lists = $service->get_lists();
		$templates = $service->get_templates();

?>
<h2><?php echo esc_html( __( 'Sendinblue', 'wing-forms' ) ); ?></h2>

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
						<label for="wfp-sendinblue-enable-wing-list">
							<input type="checkbox" name="wfp-sendinblue[enable_wing_list]" id="wfp-sendinblue-enable-wing-list" value="1" <?php checked( $prop['enable_wing_list'] ); ?> />
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
						'name' => 'wfp-sendinblue[wing_lists][]',
						'value' => $list['id'],
						'checked' => in_array( $list['id'], $prop['wing_lists'] )
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
				'href' => 'https://my.sendinblue.com/lists',
				'target' => '_blank',
				'rel' => 'external noreferrer noopener',
			) ),
			esc_html( __( 'Manage your wing lists', 'wing-forms' ) ),
			esc_html( __( '(opens in a new tab)', 'wing-forms' ) )
		);

		?>
				</td>
			</tr>
			<tr class="<?php echo esc_attr($prop['enable_transactional_email']) ? '' : 'inactive'; ?>">
				<th scope="row">
		<?php

		echo esc_html( __( 'Welcome email', 'wing-forms' ) );

		?>
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
		<?php

		echo esc_html( __( 'Welcome email', 'wing-forms' ) );

		?>
						</legend>
						<label for="wfp-sendinblue-enable-transactional-email">
							<input type="checkbox" name="wfp-sendinblue[enable_transactional_email]" id="wfp-sendinblue-enable-transactional-email" value="1" <?php checked( $prop['enable_transactional_email'] ); ?> />
		<?php

		echo esc_html(
			__( "Send a welcome email to new wings", 'wing-forms' )
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

		if ( $templates ) {
			echo sprintf(
				'<legend>%1$s</legend>',
				esc_html( __( 'Select an email template:', 'wing-forms' ) )
			);

			echo '<select name="wfp-sendinblue[email_template]">';

			echo sprintf(
				'<option %1$s>%2$s</option>',
				wfp_format_atts( array(
					'value' => 0,
					'selected' => 0 === $prop['email_template']
						? 'selected'
						: '',
				) ),
				esc_html( __( '&mdash; Select &mdash;', 'wing-forms' ) )
			);

			foreach ( $templates as $template ) {
				echo sprintf(
					'<option %1$s>%2$s</option>',
					wfp_format_atts( array(
						'value' => $template['id'],
						'selected' => $prop['email_template'] === $template['id']
							? 'selected'
							: '',
					) ),
					esc_html( $template['name'] )
				);
			}

			echo '</select>';
		} else {
			echo sprintf(
				'<legend>%1$s</legend>',
				esc_html( __( 'You have no active email template yet.', 'wing-forms' ) )
			);
		}

		?>
					</fieldset>
		<?php

		echo sprintf(
			'<p><a %1$s>%2$s <span class="screen-reader-text">%3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>',
			wfp_format_atts( array(
				'href' => 'https://my.sendinblue.com/camp/lists/template',
				'target' => '_blank',
				'rel' => 'external noreferrer noopener',
			) ),
			esc_html( __( 'Manage your email templates', 'wing-forms' ) ),
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
		'sendinblue-panel' => array(
			'title' => __( 'Sendinblue', 'wing-forms' ),
			'callback' => $editor_panel,
		),
	);

	return $panels;
}
