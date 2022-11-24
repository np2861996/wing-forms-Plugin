<?php

add_action(
	'rest_api_init',
	function () {
		$controller = new WFP_REST_Controller;
		$controller->register_routes();
	},
	10, 0
);


class WFP_REST_Controller {

	const route_namespace = 'wing-forms/v1';

	public function register_routes() {

		register_rest_route( self::route_namespace,
			'/wing-forms',
			array(
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_wing_forms' ),
					'permission_callback' => function () {
						if ( current_user_can( 'wfp_read_wing_forms' ) ) {
							return true;
						} else {
							return new WP_Error( 'wfp_forbidden',
								__( "You are not allowed to access wing forms.", 'wing-forms' ),
								array( 'status' => 403 )
							);
						}
					},
				),
				array(
					'methods' => WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'create_wing_form' ),
					'permission_callback' => function () {
						if ( current_user_can( 'wfp_edit_wing_forms' ) ) {
							return true;
						} else {
							return new WP_Error( 'wfp_forbidden',
								__( "You are not allowed to create a wing form.", 'wing-forms' ),
								array( 'status' => 403 )
							);
						}
					},
				),
			)
		);

		register_rest_route( self::route_namespace,
			'/wing-forms/(?P<id>\d+)',
			array(
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_wing_form' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						$id = (int) $request->get_param( 'id' );

						if ( current_user_can( 'wfp_edit_wing_form', $id ) ) {
							return true;
						} else {
							return new WP_Error( 'wfp_forbidden',
								__( "You are not allowed to access the requested wing form.", 'wing-forms' ),
								array( 'status' => 403 )
							);
						}
					},
				),
				array(
					'methods' => WP_REST_Server::EDITABLE,
					'callback' => array( $this, 'update_wing_form' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						$id = (int) $request->get_param( 'id' );

						if ( current_user_can( 'wfp_edit_wing_form', $id ) ) {
							return true;
						} else {
							return new WP_Error( 'wfp_forbidden',
								__( "You are not allowed to access the requested wing form.", 'wing-forms' ),
								array( 'status' => 403 )
							);
						}
					},
				),
				array(
					'methods' => WP_REST_Server::DELETABLE,
					'callback' => array( $this, 'delete_wing_form' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						$id = (int) $request->get_param( 'id' );

						if ( current_user_can( 'wfp_delete_wing_form', $id ) ) {
							return true;
						} else {
							return new WP_Error( 'wfp_forbidden',
								__( "You are not allowed to access the requested wing form.", 'wing-forms' ),
								array( 'status' => 403 )
							);
						}
					},
				),
			)
		);

		register_rest_route( self::route_namespace,
			'/wing-forms/(?P<id>\d+)/feedback',
			array(
				array(
					'methods' => WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'create_feedback' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route( self::route_namespace,
			'/wing-forms/(?P<id>\d+)/feedback/schema',
			array(
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_schema' ),
					'permission_callback' => '__return_true',
				),
				'schema' => 'wfp_swv_get_meta_schema',
			)
		);

		register_rest_route( self::route_namespace,
			'/wing-forms/(?P<id>\d+)/refill',
			array(
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_refill' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	public function get_wing_forms( WP_REST_Request $request ) {
		$args = array();

		$per_page = $request->get_param( 'per_page' );

		if ( null !== $per_page ) {
			$args['posts_per_page'] = (int) $per_page;
		}

		$offset = $request->get_param( 'offset' );

		if ( null !== $offset ) {
			$args['offset'] = (int) $offset;
		}

		$order = $request->get_param( 'order' );

		if ( null !== $order ) {
			$args['order'] = (string) $order;
		}

		$orderby = $request->get_param( 'orderby' );

		if ( null !== $orderby ) {
			$args['orderby'] = (string) $orderby;
		}

		$search = $request->get_param( 'search' );

		if ( null !== $search ) {
			$args['s'] = (string) $search;
		}

		$items = WFP_WingForm::find( $args );

		$response = array();

		foreach ( $items as $item ) {
			$response[] = array(
				'id' => $item->id(),
				'slug' => $item->name(),
				'title' => $item->title(),
				'locale' => $item->locale(),
			);
		}

		return rest_ensure_response( $response );
	}

	public function create_wing_form( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );

		if ( $id ) {
			return new WP_Error( 'wfp_post_exists',
				__( "Cannot create existing wing form.", 'wing-forms' ),
				array( 'status' => 400 )
			);
		}

		$args = $request->get_params();
		$args['id'] = -1; // Create
		$context = $request->get_param( 'context' );
		$item = wfp_save_wing_form( $args, $context );

		if ( ! $item ) {
			return new WP_Error( 'wfp_cannot_save',
				__( "There was an error saving the wing form.", 'wing-forms' ),
				array( 'status' => 500 )
			);
		}

		$response = array(
			'id' => $item->id(),
			'slug' => $item->name(),
			'title' => $item->title(),
			'locale' => $item->locale(),
			'properties' => $this->get_properties( $item ),
			'config_errors' => array(),
		);

		if ( wfp_validate_configuration() ) {
			$config_validator = new WFP_ConfigValidator( $item );
			$config_validator->validate();

			$response['config_errors'] = $config_validator->collect_error_messages();

			if ( 'save' == $context ) {
				$config_validator->save();
			}
		}

		return rest_ensure_response( $response );
	}

	public function get_wing_form( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );
		$item = wfp_wing_form( $id );

		if ( ! $item ) {
			return new WP_Error( 'wfp_not_found',
				__( "The requested wing form was not found.", 'wing-forms' ),
				array( 'status' => 404 )
			);
		}

		$response = array(
			'id' => $item->id(),
			'slug' => $item->name(),
			'title' => $item->title(),
			'locale' => $item->locale(),
			'properties' => $this->get_properties( $item ),
		);

		return rest_ensure_response( $response );
	}

	public function update_wing_form( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );
		$item = wfp_wing_form( $id );

		if ( ! $item ) {
			return new WP_Error( 'wfp_not_found',
				__( "The requested wing form was not found.", 'wing-forms' ),
				array( 'status' => 404 )
			);
		}

		$args = $request->get_params();
		$context = $request->get_param( 'context' );
		$item = wfp_save_wing_form( $args, $context );

		if ( ! $item ) {
			return new WP_Error( 'wfp_cannot_save',
				__( "There was an error saving the wing form.", 'wing-forms' ),
				array( 'status' => 500 )
			);
		}

		$response = array(
			'id' => $item->id(),
			'slug' => $item->name(),
			'title' => $item->title(),
			'locale' => $item->locale(),
			'properties' => $this->get_properties( $item ),
			'config_errors' => array(),
		);

		if ( wfp_validate_configuration() ) {
			$config_validator = new WFP_ConfigValidator( $item );
			$config_validator->validate();

			$response['config_errors'] = $config_validator->collect_error_messages();

			if ( 'save' == $context ) {
				$config_validator->save();
			}
		}

		return rest_ensure_response( $response );
	}

	public function delete_wing_form( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );
		$item = wfp_wing_form( $id );

		if ( ! $item ) {
			return new WP_Error( 'wfp_not_found',
				__( "The requested wing form was not found.", 'wing-forms' ),
				array( 'status' => 404 )
			);
		}

		$result = $item->delete();

		if ( ! $result ) {
			return new WP_Error( 'wfp_cannot_delete',
				__( "There was an error deleting the wing form.", 'wing-forms' ),
				array( 'status' => 500 )
			);
		}

		$response = array( 'deleted' => true );

		return rest_ensure_response( $response );
	}

	public function create_feedback( WP_REST_Request $request ) {
		$content_type = $request->get_header( 'Content-Type' );

		if ( ! str_starts_with( $content_type, 'multipart/form-data' ) ) {
			return new WP_Error( 'wfp_unsupported_media_type',
				__( "The request payload format is not supported.", 'wing-forms' ),
				array( 'status' => 415 )
			);
		}

		$url_params = $request->get_url_params();

		$item = null;

		if ( ! empty( $url_params['id'] ) ) {
			$item = wfp_wing_form( $url_params['id'] );
		}

		if ( ! $item ) {
			return new WP_Error( 'wfp_not_found',
				__( "The requested wing form was not found.", 'wing-forms' ),
				array( 'status' => 404 )
			);
		}

		$unit_tag = wfp_sanitize_unit_tag(
			$request->get_param( '_wfp_unit_tag' )
		);

		$result = $item->submit();

		$response = array_merge( $result, array(
			'into' => sprintf( '#%s', $unit_tag ),
			'invalid_fields' => array(),
		) );

		if ( ! empty( $result['invalid_fields'] ) ) {
			$invalid_fields = array();

			foreach ( (array) $result['invalid_fields'] as $name => $field ) {
				if ( ! wfp_is_name( $name ) ) {
					continue;
				}

				$name = strtr( $name, '.', '_' );

				$invalid_fields[] = array(
					'field' => $name,
					'message' => $field['reason'],
					'idref' => $field['idref'],
					'error_id' => sprintf(
						'%1$s-ve-%2$s',
						$unit_tag,
						$name
					),
				);
			}

			$response['invalid_fields'] = $invalid_fields;
		}

		$response = wfp_apply_filters_deprecated(
			'wfp_ajax_json_echo',
			array( $response, $result ),
			'5.2',
			'wfp_feedback_response'
		);

		$response = apply_filters( 'wfp_feedback_response', $response, $result );

		return rest_ensure_response( $response );
	}


	public function get_schema( WP_REST_Request $request ) {
		$url_params = $request->get_url_params();

		$item = null;

		if ( ! empty( $url_params['id'] ) ) {
			$item = wfp_wing_form( $url_params['id'] );
		}

		if ( ! $item ) {
			return new WP_Error( 'wfp_not_found',
				__( "The requested wing form was not found.", 'wing-forms' ),
				array( 'status' => 404 )
			);
		}

		$schema = $item->get_schema();

		$response = isset( $schema ) ? $schema->to_array() : array();

		return rest_ensure_response( $response );
	}


	public function get_refill( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );
		$item = wfp_wing_form( $id );

		if ( ! $item ) {
			return new WP_Error( 'wfp_not_found',
				__( "The requested wing form was not found.", 'wing-forms' ),
				array( 'status' => 404 )
			);
		}

		$response = wfp_apply_filters_deprecated(
			'wfp_ajax_onload',
			array( array() ),
			'5.2',
			'wfp_refill_response'
		);

		$response = apply_filters( 'wfp_refill_response', array() );

		return rest_ensure_response( $response );
	}

	private function get_properties( WFP_WingForm $wing_form ) {
		$properties = $wing_form->get_properties();

		$properties['form'] = array(
			'content' => (string) $properties['form'],
			'fields' => array_map(
				function ( WFP_FormTag $form_tag ) {
					return array(
						'type' => $form_tag->type,
						'basetype' => $form_tag->basetype,
						'name' => $form_tag->name,
						'options' => $form_tag->options,
						'raw_values' => $form_tag->raw_values,
						'labels' => $form_tag->labels,
						'values' => $form_tag->values,
						'pipes' => $form_tag->pipes instanceof WFP_Pipes
							? $form_tag->pipes->to_array()
							: $form_tag->pipes,
						'content' => $form_tag->content,
					);
				},
				$wing_form->scan_form_tags()
			),
		);

		$properties['additional_settings'] = array(
			'content' => (string) $properties['additional_settings'],
			'settings' => array_filter( array_map(
				function ( $setting ) {
					$pattern = '/^([a-zA-Z0-9_]+)[\t ]*:(.*)$/';

					if ( preg_match( $pattern, $setting, $matches ) ) {
						$name = trim( $matches[1] );
						$value = trim( $matches[2] );

						if ( in_array( $value, array( 'on', 'true' ), true ) ) {
							$value = true;
						} elseif ( in_array( $value, array( 'off', 'false' ), true ) ) {
							$value = false;
						}

						return array( $name, $value );
					}

					return false;
				},
				explode( "\n", $properties['additional_settings'] )
			) ),
		);

		return $properties;
	}

	private function get_argument_schema() {
		return array(
			'id' => array(
				'description' => __( "Unique identifier for the wing form.", 'wing-forms' ),
				'type' => 'integer',
				'required' => true,
			),
		);
	}

}
