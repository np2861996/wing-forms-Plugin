<?php

class WFP_Editor {

	private $wing_form;
	private $panels = array();

	public function __construct( WFP_WingForm $wing_form ) {
		$this->wing_form = $wing_form;
	}

	public function add_panel( $panel_id, $title, $callback ) {
		if ( wfp_is_name( $panel_id ) ) {
			$this->panels[$panel_id] = array(
				'title' => $title,
				'callback' => $callback,
			);
		}
	}

	public function display() {
		if ( empty( $this->panels ) ) {
			return;
		}

		echo '<ul id="wing-form-editor-tabs">';

		foreach ( $this->panels as $panel_id => $panel ) {
			echo sprintf(
				'<li id="%1$s-tab"><a href="#%1$s">%2$s</a></li>',
				esc_attr( $panel_id ),
				esc_html( $panel['title'] )
			);
		}

		echo '</ul>';

		foreach ( $this->panels as $panel_id => $panel ) {
			echo sprintf(
				'<div class="wing-form-editor-panel" id="%1$s">',
				esc_attr( $panel_id )
			);

			if ( is_callable( $panel['callback'] ) ) {
				$this->notice( $panel_id, $panel );
				call_user_func( $panel['callback'], $this->wing_form );
			}

			echo '</div>';
		}
	}

	public function notice( $panel_id, $panel ) {
		echo '<div class="config-error"></div>';
	}
}

function wfp_editor_panel_form( $post ) {
	$desc_link = wfp_link(
		__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
		__( 'Editing form template', 'wing-forms' ) );
	$description = __( "You can edit the form template here. For details, see %s.", 'wing-forms' );
	$description = sprintf( esc_html( $description ), $desc_link );
?>

<h2><?php echo esc_html( __( 'Form', 'wing-forms' ) ); ?></h2>

<fieldset>
<legend><?php echo wp_kses_post($description); ?></legend>

<?php
	$tag_generator = WFP_TagGenerator::get_instance();
	$tag_generator->print_buttons();
?>

<textarea id="wfp-form" name="wfp-form" cols="100" rows="24" class="large-text code" data-config-field="form.body"><?php echo $post->prop( 'form' ); ?></textarea>
</fieldset>
<?php
}

function wfp_editor_panel_mail( $post ) {
	wfp_editor_box_mail( $post );

	echo '<br class="clear" />';

	wfp_editor_box_mail( $post, array(
		'id' => 'wfp-mail-2',
		'name' => 'mail_2',
		'title' => __( 'Mail (2)', 'wing-forms' ),
		'use' => __( 'Use Mail (2)', 'wing-forms' ),
	) );
}

function wfp_editor_box_mail( $post, $args = '' ) {
	$args = wp_parse_args( $args, array(
		'id' => 'wfp-mail',
		'name' => 'mail',
		'title' => __( 'Mail', 'wing-forms' ),
		'use' => null,
	) );

	$id = esc_attr( $args['id'] );

	$mail = wp_parse_args( $post->prop( $args['name'] ), array(
		'active' => false,
		'recipient' => '',
		'sender' => '',
		'subject' => '',
		'body' => '',
		'additional_headers' => '',
		'attachments' => '',
		'use_html' => false,
		'exclude_blank' => false,
	) );

?>
<div class="wing-form-editor-box-mail" id="<?php echo esc_attr($id); ?>">
<h2><?php echo esc_html( $args['title'] ); ?></h2>

<?php
	if ( ! empty( $args['use'] ) ) :
?>
<label for="<?php echo esc_attr($id); ?>-active"><input type="checkbox" id="<?php echo esc_attr($id); ?>-active" name="<?php echo esc_attr($id); ?>[active]" class="toggle-form-table" value="1"<?php echo ( $mail['active'] ) ? ' checked="checked"' : ''; ?> /> <?php echo esc_html( $args['use'] ); ?></label>
<p class="description"><?php echo esc_html( __( "Mail (2) is an additional mail template often used as an autoresponder.", 'wing-forms' ) ); ?></p>
<?php
	endif;
?>

<fieldset>
<legend>
<?php
	$desc_link = wfp_link(
		__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
		__( 'Setting up mail', 'wing-forms' ) );
	$description = __( "You can edit the mail template here. For details, see %s.", 'wing-forms' );
	$description = sprintf( esc_html( $description ), $desc_link );
	echo $description;
	echo '<br />';

	echo esc_html( __( "In the following fields, you can use these mail-tags:",
		'wing-forms' ) );
	echo '<br />';
	$post->suggest_mail_tags( $args['name'] ); 
?>
</legend>
<table class="form-table">
<tbody>
	<tr>
	<th scope="row">
		<label for="<?php echo esc_attr($id); ?>-recipient"><?php echo  __( 'To', 'wing-forms' ) ; ?></label>
	</th>
	<td>
		<input type="text" id="<?php echo esc_attr($id); ?>-recipient" name="<?php echo esc_attr($id); ?>[recipient]" class="large-text code" size="70" value="<?php echo esc_attr( $mail['recipient'] ); ?>" data-config-field="<?php echo sprintf( '%s.recipient', esc_attr( $args['name'] ) ); ?>" />
	</td>
	</tr>

	<tr>
	<th scope="row">
		<label for="<?php echo esc_attr($id); ?>-sender"><?php echo esc_html( __( 'From', 'wing-forms' ) ); ?></label>
	</th>
	<td>
		<input type="text" id="<?php echo esc_attr($id); ?>-sender" name="<?php echo esc_attr($id); ?>[sender]" class="large-text code" size="70" value="<?php echo esc_attr( $mail['sender'] ); ?>" data-config-field="<?php echo sprintf( '%s.sender', esc_attr( $args['name'] ) ); ?>" />
	</td>
	</tr>

	<tr>
	<th scope="row">
		<label for="<?php echo esc_html($id); ?>-subject"><?php echo esc_html( __( 'Subject', 'wing-forms' ) ); ?></label>
	</th>
	<td>
		<input type="text" id="<?php echo esc_html($id); ?>-subject" name="<?php echo esc_html($id); ?>[subject]" class="large-text code" size="70" value="<?php echo esc_attr( $mail['subject'] ); ?>" data-config-field="<?php echo sprintf( '%s.subject', esc_attr( $args['name'] ) ); ?>" />
	</td>
	</tr>

	<tr>
	<th scope="row">
		<label for="<?php echo esc_html($id); ?>-additional-headers"><?php echo esc_html( __( 'Additional headers', 'wing-forms' ) ); ?></label>
	</th>
	<td>
		<textarea id="<?php echo esc_attr($id); ?>-additional-headers" name="<?php echo esc_attr($id); ?>[additional_headers]" cols="100" rows="4" class="large-text code" data-config-field="<?php echo sprintf( '%s.additional_headers', esc_attr( $args['name'] ) ); ?>"><?php echo esc_textarea( $mail['additional_headers'] ); ?></textarea>
	</td>
	</tr>

	<tr>
	<th scope="row">
		<label for="<?php echo esc_attr($id); ?>-body"><?php echo esc_html( __( 'Message body', 'wing-forms' ) ); ?></label>
	</th>
	<td>
		<textarea id="<?php echo esc_attr($id); ?>-body" name="<?php echo esc_attr($id); ?>[body]" cols="100" rows="18" class="large-text code" data-config-field="<?php echo sprintf( '%s.body', esc_attr( $args['name'] ) ); ?>"><?php echo esc_textarea( $mail['body'] ); ?></textarea>

		<p><label for="<?php echo esc_attr($id); ?>-exclude-blank"><input type="checkbox" id="<?php echo esc_attr($id); ?>-exclude-blank" name="<?php echo esc_attr($id); ?>[exclude_blank]" value="1"<?php echo ( ! empty( $mail['exclude_blank'] ) ) ? ' checked="checked"' : ''; ?> /> <?php echo esc_html( __( 'Exclude lines with blank mail-tags from output', 'wing-forms' ) ); ?></label></p>

		<p><label for="<?php echo esc_attr($id); ?>-use-html"><input type="checkbox" id="<?php echo esc_attr($id); ?>-use-html" name="<?php echo esc_attr($id); ?>[use_html]" value="1"<?php echo ( $mail['use_html'] ) ? ' checked="checked"' : ''; ?> /> <?php echo esc_html( __( 'Use HTML content type', 'wing-forms' ) ); ?></label></p>
	</td>
	</tr>

	<tr>
	<th scope="row">
		<label for="<?php echo esc_attr($id); ?>-attachments"><?php echo esc_html( __( 'File attachments', 'wing-forms' ) ); ?></label>
	</th>
	<td>
		<textarea id="<?php echo esc_attr($id); ?>-attachments" name="<?php echo esc_attr($id); ?>[attachments]" cols="100" rows="4" class="large-text code" data-config-field="<?php echo sprintf( '%s.attachments', esc_attr( $args['name'] ) ); ?>"><?php echo esc_textarea( $mail['attachments'] ); ?></textarea>
	</td>
	</tr>
</tbody>
</table>
</fieldset>
</div>
<?php
}

function wfp_editor_panel_messages( $post ) {
	$desc_link = wfp_link(
		__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
		__( 'Editing messages', 'wing-forms' ) );
	$description = __( "You can edit messages used in various situations here. For details, see %s.", 'wing-forms' );
	$description = sprintf( esc_html( $description ), $desc_link );

	$messages = wfp_messages();

	if ( isset( $messages['captcha_not_match'] )
	and ! wfp_use_really_simple_captcha() ) {
		unset( $messages['captcha_not_match'] );
	}

?>
<h2><?php echo esc_html( __( 'Messages', 'wing-forms' ) ); ?></h2>
<fieldset>
<legend><?php echo esc_attr($description); ?></legend>
<?php

	foreach ( $messages as $key => $arr ) {
		$field_id = sprintf( 'wfp-message-%s', strtr( $key, '_', '-' ) );
		$field_name = sprintf( 'wfp-messages[%s]', $key );

?>
<p class="description">
<label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html( $arr['description'] ); ?><br />
<input type="text" id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_name); ?>" class="large-text" size="70" value="<?php echo esc_attr( $post->message( $key, false ) ); ?>" data-config-field="<?php echo sprintf( 'messages.%s', esc_attr( $key ) ); ?>" />
</label>
</p>
<?php
	}
?>
</fieldset>
<?php
}

function wfp_editor_panel_additional_settings( $post ) {
	$desc_link = wfp_link(
		__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
		__( 'Additional settings', 'wing-forms' ) );
	$description = __( "You can add customization code snippets here. For details, see %s.", 'wing-forms' );
	$description = sprintf( esc_html( $description ), $desc_link );

?>
<h2><?php echo esc_html( __( 'Additional Settings', 'wing-forms' ) ); ?></h2>
<fieldset>
<legend><?php echo esc_attr($description); ?></legend>
<textarea id="wfp-additional-settings" name="wfp-additional-settings" cols="100" rows="8" class="large-text" data-config-field="additional_settings.body"><?php echo  $post->prop( 'additional_settings' ) ; ?></textarea>
</fieldset>
<?php
}
