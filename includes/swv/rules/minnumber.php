<?php

class WFP_SWV_MinNumberRule extends WFP_SWV_Rule {

	const rule_name = 'minnumber';

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
		$sminnumber = sanitize_text_field($_POST[$field]);
		$input = isset( $sminnumber ) ? $sminnumber : '';
		$input = wfp_array_flatten( $input );
		$input = wfp_exclude_blank( $input );

		$threshold = $this->get_property( 'threshold' );

		if ( ! wfp_is_number( $threshold ) ) {
			return true;
		}

		foreach ( $input as $i ) {
			if ( wfp_is_number( $i ) and (float) $i < (float) $threshold ) {
				return new WP_Error( 'wfp_invalid_minnumber',
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
