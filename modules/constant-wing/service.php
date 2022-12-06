<?php

if ( ! class_exists( 'WFP_Service_OAuth2' ) ) {
	return;
}

class WFP_ConstantWing extends WFP_Service_OAuth2 {

	const service_name = 'constant_wing';

	const authorization_endpoint
		= 'https://authz.constantwing.com/oauth2/default/v1/authorize';

	const token_endpoint
		= 'https://authz.constantwing.com/oauth2/default/v1/token';

	private static $instance;
	protected $wing_lists = array();

	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function __construct() {
		$this->authorization_endpoint = self::authorization_endpoint;
		$this->token_endpoint = self::token_endpoint;

		$option = (array) WFP::get_option( self::service_name );

		if ( isset( $option['client_id'] ) ) {
			$this->client_id = $option['client_id'];
		}

		if ( isset( $option['client_secret'] ) ) {
			$this->client_secret = $option['client_secret'];
		}

		if ( isset( $option['access_token'] ) ) {
			$this->access_token = $option['access_token'];
		}

		if ( isset( $option['refresh_token'] ) ) {
			$this->refresh_token = $option['refresh_token'];
		}

		if ( $this->is_active() ) {
			if ( isset( $option['wing_lists'] ) ) {
				$this->wing_lists = $option['wing_lists'];
			}
		}

		add_action( 'wfp_admin_init', array( $this, 'auth_redirect' ) );
	}

	public function auth_redirect() {
		if(isset( $_GET['auth'])){
			$sauth = sanitize_text_field($_GET['auth']); 
		}
		$auth = isset( $sauth ) ? trim( $sauth ) : '';
		
		if ( self::service_name === $auth
		and current_user_can( 'wfp_manage_integration' ) ) {

			$scode = sanitize_text_field($_GET['code']);
			$sstate = sanitize_text_field($_GET['state']);
			$redirect_to = add_query_arg(
				array(
					'service' => self::service_name,
					'action' => 'auth_redirect',
					'code' => isset( $scode ) ? trim( $scode ) : '',
					'state' => isset( $sstate ) ? trim( $sstate ) : '',
				),
				menu_page_url( 'wfp-integration', false )
			);

			wp_safe_redirect( $redirect_to );
			exit();
		}
	}

	protected function save_data() {
		$option = array_merge(
			(array) WFP::get_option( self::service_name ),
			array(
				'client_id' => $this->client_id,
				'client_secret' => $this->client_secret,
				'access_token' => $this->access_token,
				'refresh_token' => $this->refresh_token,
				'wing_lists' => $this->wing_lists,
			)
		);

		WFP::update_option( self::service_name, $option );
	}

	protected function reset_data() {
		$this->client_id = '';
		$this->client_secret = '';
		$this->access_token = '';
		$this->refresh_token = '';
		$this->wing_lists = array();

		$this->save_data();
	}

	public function get_title() {
		return __( 'Constant Wing', 'wing-forms' );
	}

	public function get_categories() {
		return array( 'email_marketing' );
	}

	public function icon() {
	}

	public function link() {
		echo sprintf( '<a href="%1$s">%2$s</a>',
			'https://constant-wing.evyy.net/c/1293104/205991/3411',
			'constantwing.com'
		);
	}

	protected function get_redirect_uri() {
		return admin_url( '/?auth=' . self::service_name );
	}

	protected function menu_page_url( $args = '' ) {
		$args = wp_parse_args( $args, array() );

		$url = menu_page_url( 'wfp-integration', false );
		$url = add_query_arg( array( 'service' => self::service_name ), $url );

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}

	public function load( $action = '' ) {
		if ( 'auth_redirect' == $action ) {

			if(isset($_GET['code']))
			{
				$code = sanitize_text_field($_GET['code']);
			}

			if(isset($_GET['state']))
			{
				$state  = sanitize_text_field($_GET['state']);
			}
			

			$code = isset( $code ) ? urldecode( $code ) : '';
			$state = isset( $state ) ? urldecode( $state ) : '';

			if ( $code and $state
			and wfp_verify_nonce( $state, 'wfp_constant_wing_authorize' ) ) {
				$response = $this->request_token( $code );
			}

			if ( ! empty( $this->access_token ) ) {
				$message = 'success';
			} else {
				$message = 'failed';
			}

			wp_safe_redirect( $this->menu_page_url(
				array(
					'action' => 'setup',
					'message' => $message,
				)
			) );

			exit();
		}

		if ( 'setup' == $action and 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			check_admin_referer( 'wfp-constant-wing-setup' );


			if ( ! empty( $_POST['reset'] ) ) {
				$this->reset_data();
			} else {

				$sclient_id = sanitize_text_field($_POST['client_id']);
				$this->client_id = isset( $sclient_id )
					? trim( $sclient_id ) : '';

				$sclient_secret = sanitize_text_field($_POST['client_secret']);
				$this->client_secret = isset( $sclient_secret )
					? trim( $sclient_secret ) : '';

				$this->save_data();
				$this->authorize( 'wing_data offline_access' );
			}

			wp_safe_redirect( $this->menu_page_url( 'action=setup' ) );
			exit();
		}
	}

	protected function authorize( $scope = '' ) {
		$endpoint = add_query_arg(
			array_map( 'urlencode', array(
				'response_type' => 'code',
				'client_id' => $this->client_id,
				'redirect_uri' => $this->get_redirect_uri(),
				'scope' => $scope,
				'state' => wfp_create_nonce( 'wfp_constant_wing_authorize' ),
			) ),
			$this->authorization_endpoint
		);

		if ( wp_redirect( esc_url_raw( $endpoint ) ) ) {
			exit();
		}
	}

	public function email_exists( $email ) {
		$endpoint = add_query_arg(
			array(
				'email' => $email,
				'status' => 'all',
			),
			'https://api.cc.email/v3/wings'
		);

		$request = array(
			'method' => 'GET',
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json; charset=utf-8',
			),
		);

		$response = $this->remote_request( $endpoint, $request );

		if ( 400 <= (int) wp_remote_retrieve_response_code( $response ) ) {
			if ( WP_DEBUG ) {
				$this->log( $endpoint, $request, $response );
			}

			return false;
		}

		$response_body = wp_remote_retrieve_body( $response );

		if ( empty( $response_body ) ) {
			return false;
		}

		$response_body = json_decode( $response_body, true );

		return ! empty( $response_body['wings'] );
	}

	public function create_wing( $properties ) {
		$endpoint = 'https://api.cc.email/v3/wings';

		$request = array(
			'method' => 'POST',
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json; charset=utf-8',
			),
			'body' => json_encode( $properties ),
		);

		$response = $this->remote_request( $endpoint, $request );

		if ( 400 <= (int) wp_remote_retrieve_response_code( $response ) ) {
			if ( WP_DEBUG ) {
				$this->log( $endpoint, $request, $response );
			}

			return false;
		}
	}

	public function get_wing_lists() {
		$endpoint = 'https://api.cc.email/v3/wing_lists';

		$request = array(
			'method' => 'GET',
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json; charset=utf-8',
			),
		);

		$response = $this->remote_request( $endpoint, $request );

		if ( 400 <= (int) wp_remote_retrieve_response_code( $response ) ) {
			if ( WP_DEBUG ) {
				$this->log( $endpoint, $request, $response );
			}

			return false;
		}

		$response_body = wp_remote_retrieve_body( $response );

		if ( empty( $response_body ) ) {
			return false;
		}

		$response_body = json_decode( $response_body, true );

		if ( ! empty( $response_body['lists'] ) ) {
			return (array) $response_body['lists'];
		} else {
			return array();
		}
	}

	public function update_wing_lists( $selection = array() ) {
		$wing_lists = array();
		$wing_lists_on_api = $this->get_wing_lists();

		if ( false !== $wing_lists_on_api ) {
			foreach ( (array) $wing_lists_on_api as $list ) {
				if ( isset( $list['list_id'] ) ) {
					$list_id = trim( $list['list_id'] );
				} else {
					continue;
				}

				if ( isset( $this->wing_lists[$list_id]['selected'] ) ) {
					$list['selected'] = $this->wing_lists[$list_id]['selected'];
				} else {
					$list['selected'] = array();
				}

				$wing_lists[$list_id] = $list;
			}
		} else {
			$wing_lists = $this->wing_lists;
		}

		foreach ( (array) $selection as $key => $ids_or_names ) {
			foreach( $wing_lists as $list_id => $list ) {
				if ( in_array( $list['list_id'], (array) $ids_or_names, true )
				or in_array( $list['name'], (array) $ids_or_names, true ) ) {
					$wing_lists[$list_id]['selected'][$key] = true;
				} else {
					unset( $wing_lists[$list_id]['selected'][$key] );
				}
			}
		}

		$this->wing_lists = $wing_lists;

		if ( $selection ) {
			$this->save_data();
		}

		return $this->wing_lists;
	}

	public function admin_notice( $message = '' ) {
		switch ( $message ) {
			case 'success':
				echo sprintf(
					'<div class="notice notice-success"><p>%s</p></div>',
					esc_html( __( "Connection established.", 'wing-forms' ) )
				);
				break;
			case 'failed':
				echo sprintf(
					'<div class="notice notice-error"><p><strong>%1$s</strong>: %2$s</p></div>',
					esc_html( __( "Error", 'wing-forms' ) ),
					esc_html( __( "Failed to establish connection. Please double-check your configuration.", 'wing-forms' ) )
				);
				break;
			case 'updated':
				echo sprintf(
					'<div class="notice notice-success"><p>%s</p></div>',
					esc_html( __( "Configuration updated.", 'wing-forms' ) )
				);
				break;
		}
	}

	public function display( $action = '' ) {
		echo '<p>' . sprintf(
			esc_html( __( 'The Constant Wing integration module allows you to send wing data collected through your wing forms to the Constant Wing API. You can create reliable email subscription services in a few easy steps. For details, see %s.', 'wing-forms' ) ),
			wfp_link(
				__(
					'https://github.com/np2861996/wing-forms-Plugin',
					'wing-forms'
				),
				__( 'Constant Wing integration', 'wing-forms' )
			)
		) . '</p>';

		if ( $this->is_active() ) {
			echo sprintf(
				'<p class="dashicons-before dashicons-yes">%s</p>',
				esc_html( __( "This site is connected to the Constant Wing API.", 'wing-forms' ) )
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
?>
<form method="post" action="<?php echo esc_url( $this->menu_page_url( 'action=setup' ) ); ?>">
<?php wp_nonce_field( 'wfp-constant-wing-setup' ); ?>
<table class="form-table">
<tbody>
<tr>
	<th scope="row"><label for="client_id"><?php echo esc_html( __( 'API Key', 'wing-forms' ) ); ?></label></th>
	<td><?php
		if ( $this->is_active() ) {
			echo esc_html( $this->client_id );
			echo sprintf(
				'<input type="hidden" value="%1$s" id="client_id" name="client_id" />',
				esc_attr( $this->client_id )
			);
		} else {
			echo sprintf(
				'<input type="text" aria-required="true" value="%1$s" id="client_id" name="client_id" class="regular-text code" />',
				esc_attr( $this->client_id )
			);
		}
	?></td>
</tr>
<tr>
	<th scope="row"><label for="client_secret"><?php echo esc_html( __( 'App Secret', 'wing-forms' ) ); ?></label></th>
	<td><?php
		if ( $this->is_active() ) {
			echo esc_html( wfp_mask_password( $this->client_secret, 4, 4 ) );
			echo sprintf(
				'<input type="hidden" value="%1$s" id="client_secret" name="client_secret" />',
				esc_attr( $this->client_secret )
			);
		} else {
			echo sprintf(
				'<input type="text" aria-required="true" value="%1$s" id="client_secret" name="client_secret" class="regular-text code" />',
				esc_attr( $this->client_secret )
			);
		}
	?></td>
</tr>
<tr>
	<th scope="row"><label for="redirect_uri"><?php echo esc_html( __( 'Redirect URI', 'wing-forms' ) ); ?></label></th>
	<td><?php
		echo sprintf(
			'<input type="text" value="%1$s" id="redirect_uri" name="redirect_uri" class="large-text code" readonly="readonly" onfocus="this.select();" style="font-size: 11px;" />',
			$this->get_redirect_uri()
		);
	?>
	<p class="description"><?php echo esc_html( __( "Set this URL as the redirect URI.", 'wing-forms' ) ); ?></p>
	</td>
</tr>
</tbody>
</table>
<?php
		if ( $this->is_active() ) {
			submit_button(
				_x( 'Reset Keys', 'API keys', 'wing-forms' ),
				'small', 'reset'
			);
		} else {
			submit_button(
				__( 'Connect to the Constant Wing API', 'wing-forms' )
			);
		}
?>
</form>
<?php
	}

}
