<?php

trait WFP_SWV_SchemaHolder {

	protected $schema;


	/**
	 * Retrieves SWV schema for this holder object (wing form).
	 *
	 * @return WFP_SWV_Schema The schema object.
	 */
	public function get_schema() {
		if ( isset( $this->schema ) ) {
			return $this->schema;
		}

		$schema = new WFP_SWV_Schema( array(
			'locale' => isset( $this->locale ) ? $this->locale : '',
		) );

		do_action( 'wfp_swv_create_schema', $schema, $this );

		return $this->schema = $schema;
	}


	/**
	 * Validates form inputs based on the schema and given context.
	 */
	public function validate_schema( $context, WFP_Validation $validity ) {
		$callback = function ( $rule ) use ( &$callback, $context, $validity ) {
			if ( ! $rule->matches( $context ) ) {
				return;
			}

			if ( $rule instanceof WFP_SWV_CompositeRule ) {
				foreach ( $rule->rules() as $child_rule ) {
					call_user_func( $callback, $child_rule );
				}
			} else {
				$field = $rule->get_property( 'field' );

				if ( $validity->is_valid( $field ) ) {
					$result = $rule->validate( $context );

					if ( is_wp_error( $result ) ) {
						$validity->invalidate( $field, $result );
					}
				}
			}
		};

		call_user_func( $callback, $this->get_schema() );
	}

}
