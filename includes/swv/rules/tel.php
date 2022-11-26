<?php

class WFP_SWV_TelRule extends WFP_SWV_Rule {

	const rule_name = 'tel';

	public function matches( $context ) {
		if ( false === parent::matches( $context ) ) {
			return false;
		}

		if ( empty( $context['text'] ) ) {
			return false;
		}

		return true;
	}

	public function validate( $context ) {
		$field = $this->get_property( 'field' );
		$stellfield = sanitize_text_field($_POST[$field]);
		$input = isset( $stellfield ) ? $stellfield : '';
		$input = wfp_array_flatten( $input );
		$input = wfp_exclude_blank( $input );

		foreach ( $input as $i ) {
			if ( ! wfp_is_tel( $i ) ) {
				return new WP_Error( 'wfp_invalid_tel',
					$this->get_property( 'error' )
				);
			}
		}

		return true;
	}

	public function to_array() {
		return array( 'rule' => self::rule_name ) + (array) $this->properties;
	}
}
