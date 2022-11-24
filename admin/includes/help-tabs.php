<?php

class WFP_Help_Tabs {

	private $screen;

	public function __construct( WP_Screen $screen ) {
		$this->screen = $screen;
	}

	public function set_help_tabs( $screen_type ) {
		switch ( $screen_type ) {
			case 'list':
				$this->screen->add_help_tab( array(
					'id' => 'list_overview',
					'title' => __( 'Overview', 'wing-forms' ),
					'content' => $this->content( 'list_overview' ),
				) );

				$this->screen->add_help_tab( array(
					'id' => 'list_available_actions',
					'title' => __( 'Available Actions', 'wing-forms' ),
					'content' => $this->content( 'list_available_actions' ),
				) );

				$this->sidebar();

				return;
			case 'edit':
				$this->screen->add_help_tab( array(
					'id' => 'edit_overview',
					'title' => __( 'Overview', 'wing-forms' ),
					'content' => $this->content( 'edit_overview' ),
				) );

				$this->screen->add_help_tab( array(
					'id' => 'edit_form_tags',
					'title' => __( 'Form-tags', 'wing-forms' ),
					'content' => $this->content( 'edit_form_tags' ),
				) );

				$this->screen->add_help_tab( array(
					'id' => 'edit_mail_tags',
					'title' => __( 'Mail-tags', 'wing-forms' ),
					'content' => $this->content( 'edit_mail_tags' ),
				) );

				$this->sidebar();

				return;
			case 'integration':
				$this->screen->add_help_tab( array(
					'id' => 'integration_overview',
					'title' => __( 'Overview', 'wing-forms' ),
					'content' => $this->content( 'integration_overview' ),
				) );

				$this->sidebar();

				return;
		}
	}

	private function content( $name ) {
		$content = array();

		$content['list_overview'] = '<p>' . __( "On this screen, you can manage wing forms provided by Wing Forms. You can manage an unlimited number of wing forms. Each wing form has a unique ID and Wing Forms shortcode ([wing-forms ...]). To insert a wing form into a post or a text widget, insert the shortcode into the target.", 'wing-forms' ) . '</p>';

		$content['list_available_actions'] = '<p>' . __( "Hovering over a row in the wing forms list will display action links that allow you to manage your wing form. You can perform the following actions:", 'wing-forms' ) . '</p>';
		$content['list_available_actions'] .= '<p>' . __( "<strong>Edit</strong> - Navigates to the editing screen for that wing form. You can also reach that screen by clicking on the wing form title.", 'wing-forms' ) . '</p>';
		$content['list_available_actions'] .= '<p>' . __( "<strong>Duplicate</strong> - Clones that wing form. A cloned wing form inherits all content from the original, but has a different ID.", 'wing-forms' ) . '</p>';

		$content['edit_overview'] = '<p>' . __( "On this screen, you can edit a wing form. A wing form is comprised of the following components:", 'wing-forms' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( "<strong>Title</strong> is the title of a wing form. This title is only used for labeling a wing form, and can be edited.", 'wing-forms' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( "<strong>Form</strong> is a content of HTML form. You can use arbitrary HTML, which is allowed inside a form element. You can also use Wing Forms&#8217;s form-tags here.", 'wing-forms' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( "<strong>Mail</strong> manages a mail template (headers and message body) that this wing form will send when users submit it. You can use Wing Forms&#8217;s mail-tags here.", 'wing-forms' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( "<strong>Mail (2)</strong> is an additional mail template that works similar to Mail. Mail (2) is different in that it is sent only when Mail has been sent successfully.", 'wing-forms' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( "In <strong>Messages</strong>, you can edit various types of messages used for this wing form. These messages are relatively short messages, like a validation error message you see when you leave a required field blank.", 'wing-forms' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( "<strong>Additional Settings</strong> provides a place where you can customize the behavior of this wing form by adding code snippets.", 'wing-forms' ) . '</p>';

		$content['edit_form_tags'] = '<p>' . __( "A form-tag is a short code enclosed in square brackets used in a form content. A form-tag generally represents an input field, and its components can be separated into four parts: type, name, options, and values. Wing Forms supports several types of form-tags including text fields, number fields, date fields, checkboxes, radio buttons, menus, file-uploading fields, CAPTCHAs, and quiz fields.", 'wing-forms' ) . '</p>';
		$content['edit_form_tags'] .= '<p>' . __( "While form-tags have a comparatively complex syntax, you do not need to know the syntax to add form-tags because you can use the straightforward tag generator (<strong>Generate Tag</strong> button on this screen).", 'wing-forms' ) . '</p>';

		$content['edit_mail_tags'] = '<p>' . __( "A mail-tag is also a short code enclosed in square brackets that you can use in every Mail and Mail (2) field. A mail-tag represents a user input value through an input field of a corresponding form-tag.", 'wing-forms' ) . '</p>';
		$content['edit_mail_tags'] .= '<p>' . __( "There are also special mail-tags that have specific names, but do not have corresponding form-tags. They are used to represent meta information of form submissions like the submitter&#8217;s IP address or the URL of the page.", 'wing-forms' ) . '</p>';

		$content['integration_overview'] = '<p>' . __( "On this screen, you can manage services that are available through Wing Forms. Using API will allow you to collaborate with any services that are available.", 'wing-forms' ) . '</p>';
		$content['integration_overview'] .= '<p>' . __( "You may need to first sign up for an account with the service that you plan to use. When you do so, you would need to authorize Wing Forms to access the service with your account.", 'wing-forms' ) . '</p>';
		$content['integration_overview'] .= '<p>' . __( "Any information you provide will not be shared with service providers without your authorization.", 'wing-forms' ) . '</p>';

		if ( ! empty( $content[$name] ) ) {
			return $content[$name];
		}
	}

	public function sidebar() {
		$content = '<p><strong>' . __( 'For more information:', 'wing-forms' ) . '</strong></p>';
		$content .= '<p>' . wfp_link( __( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ), __( 'Docs', 'wing-forms' ) ) . '</p>';
		$content .= '<p>' . wfp_link( __( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ), __( 'FAQ', 'wing-forms' ) ) . '</p>';
		$content .= '<p>' . wfp_link( __( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ), __( 'Support', 'wing-forms' ) ) . '</p>';

		$this->screen->set_help_sidebar( $content );
	}
}
