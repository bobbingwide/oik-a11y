<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2024
 * @package oik-ai
 *
 * A new version of bobbset.inc for use by a standalone .php file inside a WordPress plugin being run outside of WordPress.
 * Yes, it may be insecure but I'm not bothered about that at the moment since the code's only intended for running in a local development environment.
 * For now (18th Jan 2024) it seems that just including bobbset.inc does the job.
 *
 * From 24th Jan it attempts to use oik shared libraries outside of WordPress.
 * Let's see how far we get!
 */


if ( file_exists( "C:/apache/htdocs/ai") && is_dir( "C:/apache/htdocs/ai" )) {
	//require_once('C:/apache/htdocs/bw/bobbset.inc');
	oik_ai_boot_libs();

	oik_require_lib( "bwtrace" );
	$loaded = oik_require_lib( "bwtrace_boot" );
	oik_require_lib( "bobbfunc" );
	oik_require_lib( "class-BW-" );
	//oik_require_lib( 'bobbcomp');
	oik_require( "inc/bobbcomp.php", 'oik-ai' );


	/**
	 * In case we're not in WordPress we need some functions from general-template for BW_::select to work
	 */
	if ( !function_exists( 'selected') ) {
		require_once 'c:/apache/htdocs/wordpress/wp-includes' .'/general-template.php';
		// We can't do this.
		//require_once 'c:/apache/htdocs/wordpress/wp-includes' . '/plugin.php';
		/*
		function selected( $key, $value, $echo ) {
			return __checked_selected_helper( $selected, $current, $echo, 'selected' );
		}
		*/
	}
} else {
	// Not quite a 401 or 403. More a 404.
	echo "Sorry you can't do that!";
	die();
}

/**
 * Boot up process for shared libraries
 *
 * ... if not already performed
 */
function oik_ai_boot_libs() {
	if ( ! function_exists( 'oik_require' ) ) {
		$oik_boot_file = dirname( __DIR__ ) . '/libs/oik_boot.php';
		$loaded        = include_once( $oik_boot_file );
	}
	oik_lib_fallback( dirname( __DIR__ ) . '/libs' );
}