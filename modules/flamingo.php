<?php
/**
** Module for Flamingo plugin.
** http://wordpress.org/extend/plugins/flamingo/
**/

add_action( 'wfp_submit', 'wfp_flamingo_submit', 10, 2 );

function wfp_flamingo_submit( $wing_form, $result ) {
	if ( ! class_exists( 'Flamingo_Wing' )
	or ! class_exists( 'Flamingo_Inbound_Message' ) ) {
		return;
	}

	if ( $wing_form->in_demo_mode() ) {
		return;
	}

	$cases = (array) apply_filters( 'wfp_flamingo_submit_if',
		array( 'spam', 'mail_sent', 'mail_failed' )
	);

	if ( empty( $result['status'] )
	or ! in_array( $result['status'], $cases ) ) {
		return;
	}

	$submission = WFP_Submission::get_instance();

	if ( ! $submission
	or ! $posted_data = $submission->get_posted_data() ) {
		return;
	}

	if ( $submission->get_meta( 'do_not_store' ) ) {
		return;
	}

	$email = wfp_flamingo_get_value( 'email', $wing_form );
	$name = wfp_flamingo_get_value( 'name', $wing_form );
	$subject = wfp_flamingo_get_value( 'subject', $wing_form );

	$meta = array();

	$special_mail_tags = array( 'serial_number', 'remote_ip',
		'user_agent', 'url', 'date', 'time', 'post_id', 'post_name',
		'post_title', 'post_url', 'post_author', 'post_author_email',
		'site_title', 'site_description', 'site_url', 'site_admin_email',
		'user_login', 'user_email', 'user_display_name',
	);

	foreach ( $special_mail_tags as $smt ) {
		$tagname = sprintf( '_%s', $smt );

		$mail_tag = new WFP_MailTag(
			sprintf( '[%s]', $tagname ),
			$tagname,
			''
		);

		$meta[$smt] = apply_filters( 'wfp_special_mail_tags', null,
			$tagname, false, $mail_tag
		);
	}

	$akismet = isset( $submission->akismet )
		? (array) $submission->akismet : null;

	$timestamp = $submission->get_meta( 'timestamp' );

	if ( $timestamp and $datetime = date_create( '@' . $timestamp ) ) {
		$datetime->setTimezone( wp_timezone() );
		$last_winged = $datetime->format( 'Y-m-d H:i:s' );
	} else {
		$last_winged = '0000-00-00 00:00:00';
	}

	if ( 'mail_sent' == $result['status'] ) {
		$flamingo_wing = Flamingo_Wing::add( array(
			'email' => $email,
			'name' => $name,
			'last_winged' => $last_winged,
		) );
	}

	$post_meta = get_post_meta( $wing_form->id(), '_flamingo', true );

	$channel_id = isset( $post_meta['channel'] )
		? (int) $post_meta['channel']
		: wfp_flamingo_add_channel(
				$wing_form->name(),
				$wing_form->title()
			);

	if ( $channel_id ) {
		if ( ! isset( $post_meta['channel'] )
		or $post_meta['channel'] !== $channel_id ) {
			$post_meta = empty( $post_meta ) ? array() : (array) $post_meta;
			$post_meta = array_merge( $post_meta, array(
				'channel' => $channel_id,
			) );

			update_post_meta( $wing_form->id(), '_flamingo', $post_meta );
		}

		$channel = get_term( $channel_id,
			Flamingo_Inbound_Message::channel_taxonomy
		);

		if ( ! $channel or is_wp_error( $channel ) ) {
			$channel = 'wing-forms';
		} else {
			$channel = $channel->slug;
		}
	} else {
		$channel = 'wing-forms';
	}

	$args = array(
		'channel' => $channel,
		'status' => $submission->get_status(),
		'subject' => $subject,
		'from' => trim( sprintf( '%s <%s>', $name, $email ) ),
		'from_name' => $name,
		'from_email' => $email,
		'fields' => $posted_data,
		'meta' => $meta,
		'akismet' => $akismet,
		'spam' => ( 'spam' == $result['status'] ),
		'consent' => $submission->collect_consent(),
		'timestamp' => $timestamp,
		'posted_data_hash' => $submission->get_posted_data_hash(),
	);

	if ( $args['spam'] ) {
		$args['spam_log'] = $submission->get_spam_log();
	}

	if ( isset( $submission->recaptcha ) ) {
		$args['recaptcha'] = $submission->recaptcha;
	}

	$args = apply_filters( 'wfp_flamingo_inbound_message_parameters', $args );

	$flamingo_inbound = Flamingo_Inbound_Message::add( $args );

	if ( empty( $flamingo_wing ) ) {
		$flamingo_wing_id = 0;
	} elseif ( method_exists( $flamingo_wing, 'id' ) ) {
		$flamingo_wing_id = $flamingo_wing->id();
	} else {
		$flamingo_wing_id = $flamingo_wing->id;
	}

	if ( empty( $flamingo_inbound ) ) {
		$flamingo_inbound_id = 0;
	} elseif ( method_exists( $flamingo_inbound, 'id' ) ) {
		$flamingo_inbound_id = $flamingo_inbound->id();
	} else {
		$flamingo_inbound_id = $flamingo_inbound->id;
	}

	$result += array(
		'flamingo_wing_id' => absint( $flamingo_wing_id ),
		'flamingo_inbound_id' => absint( $flamingo_inbound_id ),
	);

	do_action( 'wfp_after_flamingo', $result );
}

function wfp_flamingo_get_value( $field, $wing_form ) {
	if ( empty( $field )
	or empty( $wing_form ) ) {
		return false;
	}

	$value = '';

	if ( in_array( $field, array( 'email', 'name', 'subject' ) ) ) {
		$template = $wing_form->pref( 'flamingo_' . $field );

		if ( null === $template ) {
			$template = sprintf( '[your-%s]', $field );
		} else {
			$template = trim( wfp_strip_quote( $template ) );
		}

		$value = wfp_mail_replace_tags( $template );
	}

	$value = apply_filters( 'wfp_flamingo_get_value', $value,
		$field, $wing_form
	);

	return $value;
}

function wfp_flamingo_add_channel( $slug, $name = '' ) {
	if ( ! class_exists( 'Flamingo_Inbound_Message' ) ) {
		return false;
	}

	$parent = term_exists( 'wing-forms',
		Flamingo_Inbound_Message::channel_taxonomy
	);

	if ( ! $parent ) {
		$parent = wp_insert_term( __( 'Wing Forms', 'wing-forms' ),
			Flamingo_Inbound_Message::channel_taxonomy,
			array( 'slug' => 'wing-forms' )
		);

		if ( is_wp_error( $parent ) ) {
			return false;
		}
	}

	$parent = (int) $parent['term_id'];

	if ( ! is_taxonomy_hierarchical( Flamingo_Inbound_Message::channel_taxonomy ) ) {
		// backward compat for Flamingo 1.0.4 and lower
		return $parent;
	}

	if ( empty( $name ) ) {
		$name = $slug;
	}

	$channel = term_exists( $slug,
		Flamingo_Inbound_Message::channel_taxonomy,
		$parent
	);

	if ( ! $channel ) {
		$channel = wp_insert_term( $name,
			Flamingo_Inbound_Message::channel_taxonomy,
			array( 'slug' => $slug, 'parent' => $parent )
		);

		if ( is_wp_error( $channel ) ) {
			return false;
		}
	}

	return (int) $channel['term_id'];
}

add_action( 'wfp_after_update', 'wfp_flamingo_update_channel', 10, 1 );

function wfp_flamingo_update_channel( $wing_form ) {
	if ( ! class_exists( 'Flamingo_Inbound_Message' ) ) {
		return false;
	}

	$post_meta = get_post_meta( $wing_form->id(), '_flamingo', true );

	$channel = isset( $post_meta['channel'] )
		? get_term( $post_meta['channel'],
				Flamingo_Inbound_Message::channel_taxonomy
			)
		: get_term_by( 'slug', $wing_form->name(),
				Flamingo_Inbound_Message::channel_taxonomy
			);

	if ( ! $channel or is_wp_error( $channel ) ) {
		return;
	}

	if ( $channel->name !== wp_unslash( $wing_form->title() ) ) {
		wp_update_term( $channel->term_id,
			Flamingo_Inbound_Message::channel_taxonomy,
			array(
				'name' => $wing_form->title(),
				'slug' => $wing_form->name(),
				'parent' => $channel->parent,
			)
		);
	}
}


add_filter( 'wfp_special_mail_tags', 'wfp_flamingo_serial_number', 10, 4 );

/**
 * Returns output string of a special mail-tag.
 *
 * @param string $output The string to be output.
 * @param string $name The tag name of the special mail-tag.
 * @param bool $html Whether the mail-tag is used in an HTML content.
 * @param WFP_MailTag $mail_tag An object representation of the mail-tag.
 * @return string Output of the given special mail-tag.
 */
function wfp_flamingo_serial_number( $output, $name, $html, $mail_tag = null ) {
	if ( ! $mail_tag instanceof WFP_MailTag ) {
		wfp_doing_it_wrong(
			sprintf( '%s()', __FUNCTION__ ),
			__( 'The fourth parameter ($mail_tag) must be an instance of the WFP_MailTag class.', 'wing-forms' ),
			'5.2.2'
		);
	}

	if ( '_serial_number' != $name ) {
		return $output;
	}

	if ( ! class_exists( 'Flamingo_Inbound_Message' )
	or ! method_exists( 'Flamingo_Inbound_Message', 'count' ) ) {
		return $output;
	}

	if ( ! $wing_form = WFP_WingForm::get_current() ) {
		return $output;
	}

	$post_meta = get_post_meta( $wing_form->id(), '_flamingo', true );

	$channel_id = isset( $post_meta['channel'] )
		? (int) $post_meta['channel']
		: wfp_flamingo_add_channel(
				$wing_form->name(), $wing_form->title()
			);

	if ( $channel_id ) {
		return 1 + (int) Flamingo_Inbound_Message::count(
			array( 'channel_id' => $channel_id )
		);
	}

	return 0;
}
