<?php

class WFP_SWV_RequiredFileRule extends WFP_SWV_Rule {

	const rule_name = 'requiredfile';

	public function matches( $context ) {
		if ( false === parent::matches( $context ) ) {
			return false;
		}

		if ( empty( $context['file'] ) ) {
			return false;
		}

		return true;
	}

	public function validate( $context ) {
		$field = $this->get_property( 'field' );

		$input = isset( $_FILES[$field]['tmp_name'] )
			? sanitize_text_field($_FILES[$field]['tmp_name']) : '';

		$input = wfp_array_flatten( $input );
		$input = wfp_exclude_blank( $input );

		if ( empty( $input ) ) {
			return new WP_Error( 'wfp_invalid_requiredfile',
				$this->get_property( 'error' )
			);
		}

		return true;
	}

	public function to_array() {
		return array( 'rule' => self::rule_name ) + (array) $this->properties;
	}
}
