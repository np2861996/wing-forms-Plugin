<?php
/**
 * All the functions and classes in this file are deprecated.
 * You should not use them. The functions and classes will be
 * removed in a later version.
 */

function wfp_add_shortcode( $tag, $callback, $has_name = false ) {
	wfp_deprecated_function( __FUNCTION__, '4.6', 'wfp_add_form_tag' );

	return wfp_add_form_tag( $tag, $callback, $has_name );
}

function wfp_remove_shortcode( $tag ) {
	wfp_deprecated_function( __FUNCTION__, '4.6', 'wfp_remove_form_tag' );

	return wfp_remove_form_tag( $tag );
}

function wfp_do_shortcode( $content ) {
	wfp_deprecated_function( __FUNCTION__, '4.6',
		'wfp_replace_all_form_tags' );

	return wfp_replace_all_form_tags( $content );
}

function wfp_scan_shortcode( $cond = null ) {
	wfp_deprecated_function( __FUNCTION__, '4.6', 'wfp_scan_form_tags' );

	return wfp_scan_form_tags( $cond );
}

class WFP_ShortcodeManager {

	private static $form_tags_manager;

	private function __construct() {}

	public static function get_instance() {
		wfp_deprecated_function( __METHOD__, '4.6',
			'WFP_FormTagsManager::get_instance' );

		self::$form_tags_manager = WFP_FormTagsManager::get_instance();
		return new self;
	}

	public function get_scanned_tags() {
		wfp_deprecated_function( __METHOD__, '4.6',
			'WFP_FormTagsManager::get_scanned_tags' );

		return self::$form_tags_manager->get_scanned_tags();
	}

	public function add_shortcode( $tag, $callback, $has_name = false ) {
		wfp_deprecated_function( __METHOD__, '4.6',
			'WFP_FormTagsManager::add' );

		return self::$form_tags_manager->add( $tag, $callback, $has_name );
	}

	public function remove_shortcode( $tag ) {
		wfp_deprecated_function( __METHOD__, '4.6',
			'WFP_FormTagsManager::remove' );

		return self::$form_tags_manager->remove( $tag );
	}

	public function normalize_shortcode( $content ) {
		wfp_deprecated_function( __METHOD__, '4.6',
			'WFP_FormTagsManager::normalize' );

		return self::$form_tags_manager->normalize( $content );
	}

	public function do_shortcode( $content, $exec = true ) {
		wfp_deprecated_function( __METHOD__, '4.6',
			'WFP_FormTagsManager::replace_all' );

		if ( $exec ) {
			return self::$form_tags_manager->replace_all( $content );
		} else {
			return self::$form_tags_manager->scan( $content );
		}
	}

	public function scan_shortcode( $content ) {
		wfp_deprecated_function( __METHOD__, '4.6',
			'WFP_FormTagsManager::scan' );

		return self::$form_tags_manager->scan( $content );
	}
}

class WFP_Shortcode extends WFP_FormTag {

	public function __construct( $tag ) {
		wfp_deprecated_function( 'WFP_Shortcode', '4.6', 'WFP_FormTag' );

		parent::__construct( $tag );
	}
}
