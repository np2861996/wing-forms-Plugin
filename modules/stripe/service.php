<?php

if ( ! class_exists( 'WFP_Service' ) ) {
	return;
}

class WFP_Stripe extends WFP_Service {

	private static $instance;
	private $api_keys;


	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}


	private function __construct() {
		$option = WFP::get_option( 'stripe' );

		if ( isset( $option['api_keys']['publishable'] )
		and isset( $option['api_keys']['secret'] ) ) {
			$this->api_keys = array(
				'publishable' => $option['api_keys']['publishable'],
				'secret' => $option['api_keys']['secret'],
			);
		}
	}


	public function get_title() {
		return __( 'Stripe', 'wing-forms' );
	}


	public function is_active() {
		return (bool) $this->get_api_keys();
	}


	public function api() {
		if ( $this->is_active() ) {
			$api = new WFP_Stripe_API( $this->api_keys['secret'] );
			return $api;
		}
	}


	public function get_api_keys() {
		return $this->api_keys;
	}


	public function get_categories() {
		return array( 'payments' );
	}


	public function icon() {
	}


	public function link() {
		echo wfp_link(
			'https://stripe.com/',
			'stripe.com'
		);
	}


	protected function menu_page_url( $args = '' ) {
		$args = wp_parse_args( $args, array() );

		$url = menu_page_url( 'wfp-integration', false );
		$url = add_query_arg( array( 'service' => 'stripe' ), $url );

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}


	protected function save_data() {
		WFP::update_option( 'stripe', array(
			'api_keys' => $this->api_keys,
		) );
	}


	protected function reset_data() {
		$this->api_keys = null;
		$this->save_data();
	}


	public function load( $action = '' ) {
		if ( 'setup' == $action and 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			check_admin_referer( 'wfp-stripe-setup' );

			if ( ! empty( $_POST['reset'] ) ) {
				$this->reset_data();
				$redirect_to = $this->menu_page_url( 'action=setup' );
			} else {

				$spublishable = sanitize_text_field($_POST['publishable']);
				$publishable = isset( $spublishable ) ?
					trim( $spublishable ) : '';

				$ssecret = sanitize_text_field($_POST['secret']);
				$secret = isset( $ssecret ) ? trim( $ssecret ) : '';

				if ( $publishable and $secret ) {
					$this->api_keys = array(
						'publishable' => $publishable,
						'secret' => $secret,
					);
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

			wp_safe_redirect( $redirect_to );
			exit();
		}
	}


	public function admin_notice( $message = '' ) {
		if ( 'invalid' == $message ) {
			echo sprintf(
				'<div class="notice notice-error"><p><strong>%1$s</strong>: %2$s</p></div>',
				esc_html( __( "Error", 'wing-forms' ) ),
				esc_html( __( "Invalid key values.", 'wing-forms' ) )
			);
		}

		if ( 'success' == $message ) {
			echo sprintf(
				'<div class="notice notice-success"><p>%s</p></div>',
				esc_html( __( 'Settings saved.', 'wing-forms' ) )
			);
		}
	}


	public function display( $action = '' ) {
		// https://stripe.com/docs/partners/support#intro
		echo '<p>' . sprintf(
			esc_html( __( 'Stripe is a simple and powerful way to accept payments online. Stripe has no setup fees, no monthly fees, and no hidden costs. Millions of businesses rely on Stripe’s software tools to accept payments securely and expand globally. For details, see %s.', 'wing-forms' ) ),
			wfp_link(
				__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
				__( 'Stripe integration', 'wing-forms' )
			)
		) . '</p>';

		if ( $this->is_active() ) {
			echo sprintf(
				'<p class="dashicons-before dashicons-yes">%s</p>',
				esc_html( __( "Stripe is active on this site.", 'wing-forms' ) )
			);
		}

		if ( 'setup' == $action ) {
			$this->display_setup();
		} elseif ( is_ssl() or WP_DEBUG ) {
			echo sprintf(
				'<p><a href="%1$s" class="button">%2$s</a></p>',
				esc_url( $this->menu_page_url( 'action=setup' ) ),
				esc_html( __( 'Setup Integration', 'wing-forms' ) )
			);
		} else {
			echo sprintf(
				'<p class="dashicons-before dashicons-warning">%s</p>',
				esc_html( __( "Stripe is not available on this site. It requires an HTTPS-enabled site.", 'wing-forms' ) )
			);
		}
	}


	private function display_setup() {
		$api_keys = $this->get_api_keys();

		if ( $api_keys ) {
			$publishable = $api_keys['publishable'];
			$secret = $api_keys['secret'];
		} else {
			$publishable = '';
			$secret = '';
		}

?>
<form method="post" action="<?php echo esc_url( $this->menu_page_url( 'action=setup' ) ); ?>">
<?php wp_nonce_field( 'wfp-stripe-setup' ); ?>
<table class="form-table">
<tbody>
<tr>
	<th scope="row"><label for="publishable"><?php echo esc_html( __( 'Publishable Key', 'wing-forms' ) ); ?></label></th>
	<td><?php
		if ( $this->is_active() ) {
			echo esc_html( $publishable );
			echo sprintf(
				'<input type="hidden" value="%s" id="publishable" name="publishable" />',
				esc_attr( $publishable )
			);
		} else {
			echo sprintf(
				'<input type="text" aria-required="true" value="%s" id="publishable" name="publishable" class="regular-text code" />',
				esc_attr( $publishable )
			);
		}
	?></td>
</tr>
<tr>
	<th scope="row"><label for="secret"><?php echo esc_html( __( 'Secret Key', 'wing-forms' ) ); ?></label></th>
	<td><?php
		if ( $this->is_active() ) {
			echo esc_html( wfp_mask_password( $secret ) );
			echo sprintf(
				'<input type="hidden" value="%s" id="secret" name="secret" />',
				esc_attr( $secret )
			);
		} else {
			echo sprintf(
				'<input type="text" aria-required="true" value="%s" id="secret" name="secret" class="regular-text code" />',
				esc_attr( $secret )
			);
		}
	?></td>
</tr>
</tbody>
</table>
<?php
		if ( $this->is_active() ) {
			submit_button(
				_x( 'Remove Keys', 'API keys', 'wing-forms' ),
				'small', 'reset'
			);
		} else {
			submit_button( __( 'Save Changes', 'wing-forms' ) );
		}
?>
</form>
<?php
	}
}
