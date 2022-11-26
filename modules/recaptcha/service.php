<?php

if ( ! class_exists( 'WFP_Service' ) ) {
	return;
}

class WFP_RECAPTCHA extends WFP_Service {

	private static $instance;
	private $sitekeys;
	private $last_score;


	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}


	private function __construct() {
		$this->sitekeys = WFP::get_option( 'recaptcha' );
	}


	public function get_title() {
		return __( 'reCAPTCHA', 'wing-forms' );
	}


	public function is_active() {
		$sitekey = $this->get_sitekey();
		$secret = $this->get_secret( $sitekey );
		return $sitekey && $secret;
	}


	public function get_categories() {
		return array( 'spam_protection' );
	}


	public function icon() {
	}


	public function link() {
		echo wfp_link(
			'https://www.google.com/recaptcha/intro/index.html',
			'google.com/recaptcha'
		);
	}


	public function get_global_sitekey() {
		static $sitekey = '';

		if ( $sitekey ) {
			return $sitekey;
		}

		if ( defined( 'WFP_RECAPTCHA_SITEKEY' ) ) {
			$sitekey = WFP_RECAPTCHA_SITEKEY;
		}

		$sitekey = apply_filters( 'wfp_recaptcha_sitekey', $sitekey );

		return $sitekey;
	}


	public function get_global_secret() {
		static $secret = '';

		if ( $secret ) {
			return $secret;
		}

		if ( defined( 'WFP_RECAPTCHA_SECRET' ) ) {
			$secret = WFP_RECAPTCHA_SECRET;
		}

		$secret = apply_filters( 'wfp_recaptcha_secret', $secret );

		return $secret;
	}


	public function get_sitekey() {
		if ( $this->get_global_sitekey() and $this->get_global_secret() ) {
			return $this->get_global_sitekey();
		}

		if ( empty( $this->sitekeys )
		or ! is_array( $this->sitekeys ) ) {
			return false;
		}

		$sitekeys = array_keys( $this->sitekeys );

		return $sitekeys[0];
	}


	public function get_secret( $sitekey ) {
		if ( $this->get_global_sitekey() and $this->get_global_secret() ) {
			return $this->get_global_secret();
		}

		$sitekeys = (array) $this->sitekeys;

		if ( isset( $sitekeys[$sitekey] ) ) {
			return $sitekeys[$sitekey];
		} else {
			return false;
		}
	}


	protected function log( $url, $request, $response ) {
		wfp_log_remote_request( $url, $request, $response );
	}


	public function verify( $token ) {
		$is_human = false;

		if ( empty( $token ) or ! $this->is_active() ) {
			return $is_human;
		}

		$endpoint = 'https://www.google.com/recaptcha/api/siteverify';

		$sitekey = $this->get_sitekey();
		$secret = $this->get_secret( $sitekey );

		$request = array(
			'body' => array(
				'secret' => $secret,
				'response' => $token,
			),
		);

		$response = wp_remote_post( esc_url_raw( $endpoint ), $request );

		if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
			if ( WP_DEBUG ) {
				$this->log( $endpoint, $request, $response );
			}

			return $is_human;
		}

		$response_body = wp_remote_retrieve_body( $response );
		$response_body = json_decode( $response_body, true );

		$this->last_score = $score = isset( $response_body['score'] )
			? $response_body['score']
			: 0;

		$threshold = $this->get_threshold();
		$is_human = $threshold < $score;

		$is_human = apply_filters( 'wfp_recaptcha_verify_response',
			$is_human, $response_body );

		if ( $submission = WFP_Submission::get_instance() ) {
			$submission->recaptcha = array(
				'version' => '3.0',
				'threshold' => $threshold,
				'response' => $response_body,
			);
		}

		return $is_human;
	}


	public function get_threshold() {
		return apply_filters( 'wfp_recaptcha_threshold', 0.50 );
	}


	public function get_last_score() {
		return $this->last_score;
	}


	protected function menu_page_url( $args = '' ) {
		$args = wp_parse_args( $args, array() );

		$url = menu_page_url( 'wfp-integration', false );
		$url = add_query_arg( array( 'service' => 'recaptcha' ), $url );

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}


	protected function save_data() {
		WFP::update_option( 'recaptcha', $this->sitekeys );
	}


	protected function reset_data() {
		$this->sitekeys = null;
		$this->save_data();
	}


	public function load( $action = '' ) {
		if ( 'setup' == $action and 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			check_admin_referer( 'wfp-recaptcha-setup' );

			if ( ! empty( $_POST['reset'] ) ) {
				$this->reset_data();
				$redirect_to = $this->menu_page_url( 'action=setup' );
			} else {
				$ssitekey = sanitize_text_field($_POST['sitekey']);
				$sitekey = isset( $ssitekey  ) ? trim( $ssitekey  ) : '';

				$ssecret = sanitize_text_field($_POST['secret']);
				$secret = isset( $ssecret ) ? trim( $ssecret ) : '';

				if ( $sitekey and $secret ) {
					$this->sitekeys = array( $sitekey => $secret );
					$this->save_data();

					$redirect_to = $this->menu_page_url( array(
						'message' => 'success',
					) );
				} else {
					$redirect_to = $this->menu_page_url( array(
						'action' => 'setup',
						'message' => 'invalid',
					) );
				}
			}

			if ( WFP::get_option( 'recaptcha_v2_v3_warning' ) ) {
				WFP::update_option( 'recaptcha_v2_v3_warning', false );
			}

			wp_safe_redirect( $redirect_to );
			exit();
		}
	}


	public function admin_notice( $message = '' ) {
		if ( 'invalid' == $message ) {
			echo sprintf(
				'<div class="notice notice-error"><p><strong>%1$s</strong>: %2$s</p></div>',
				esc_html( __( "Error", 'wing-forms' ) ),
				esc_html( __( "Invalid key values.", 'wing-forms' ) ) );
		}

		if ( 'success' == $message ) {
			echo sprintf( '<div class="notice notice-success"><p>%s</p></div>',
				esc_html( __( 'Settings saved.', 'wing-forms' ) ) );
		}
	}


	public function display( $action = '' ) {
		echo '<p>' . sprintf(
			esc_html( __( 'reCAPTCHA protects you against spam and other types of automated abuse. With Wing Forms&#8217;s reCAPTCHA integration module, you can block abusive form submissions by spam bots. For details, see %s.', 'wing-forms' ) ),
			wfp_link(
				__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
				__( 'reCAPTCHA (v3)', 'wing-forms' )
			)
		) . '</p>';

		if ( $this->is_active() ) {
			echo sprintf(
				'<p class="dashicons-before dashicons-yes">%s</p>',
				esc_html( __( "reCAPTCHA is active on this site.", 'wing-forms' ) )
			);
		}

		if ( 'setup' == $action ) {
			$this->display_setup();
		} else {
			echo sprintf(
				'<p><a href="%1$s" class="button">%2$s</a></p>',
				esc_url( $this->menu_page_url( 'action=setup' ) ),
				esc_html( __( 'Setup Integration', 'wing-forms' ) )
			);
		}
	}


	private function display_setup() {
		$sitekey = $this->is_active() ? $this->get_sitekey() : '';
		$secret = $this->is_active() ? $this->get_secret( $sitekey ) : '';

?>
<form method="post" action="<?php echo esc_url( $this->menu_page_url( 'action=setup' ) ); ?>">
<?php wp_nonce_field( 'wfp-recaptcha-setup' ); ?>
<table class="form-table">
<tbody>
<tr>
	<th scope="row"><label for="sitekey"><?php echo esc_html( __( 'Site Key', 'wing-forms' ) ); ?></label></th>
	<td><?php
		if ( $this->is_active() ) {
			echo esc_html( $sitekey );
			echo sprintf(
				'<input type="hidden" value="%1$s" id="sitekey" name="sitekey" />',
				esc_attr( $sitekey )
			);
		} else {
			echo sprintf(
				'<input type="text" aria-required="true" value="%1$s" id="sitekey" name="sitekey" class="regular-text code" />',
				esc_attr( $sitekey )
			);
		}
	?></td>
</tr>
<tr>
	<th scope="row"><label for="secret"><?php echo esc_html( __( 'Secret Key', 'wing-forms' ) ); ?></label></th>
	<td><?php
		if ( $this->is_active() ) {
			echo esc_html( wfp_mask_password( $secret, 4, 4 ) );
			echo sprintf(
				'<input type="hidden" value="%1$s" id="secret" name="secret" />',
				esc_attr( $secret )
			);
		} else {
			echo sprintf(
				'<input type="text" aria-required="true" value="%1$s" id="secret" name="secret" class="regular-text code" />',
				esc_attr( $secret )
			);
		}
	?></td>
</tr>
</tbody>
</table>
<?php
		if ( $this->is_active() ) {
			if ( $this->get_global_sitekey() and $this->get_global_secret() ) {
				// nothing
			} else {
				submit_button(
					_x( 'Remove Keys', 'API keys', 'wing-forms' ),
					'small', 'reset'
				);
			}
		} else {
			submit_button( __( 'Save Changes', 'wing-forms' ) );
		}
?>
</form>
<?php
	}
}
