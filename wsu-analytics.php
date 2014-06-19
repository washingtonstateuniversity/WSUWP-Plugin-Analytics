<?php
/*
Plugin Name: WSU Analytics
Version: 0.0.1
Plugin URI: http://web.wsu.edu
Description: Manages analytics for sites on the WSUWP Platform
Author: washingtonstateuniversity, jeremyfelt
Author URI: http://web.wsu.edu
*/

class WSU_Analytics {

	/**
	 * Add our hooks.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue the scripts used for analytics on the platform.
	 */
	public function enqueue_scripts() {
		wp_register_script( 'wsu-analytics-main', plugins_url( 'js/analytics.js', __FILE__ ), array( 'jquery' ), false, true );

		$tracker_data = array(
			'tracker_id' => 12345,
			'domain' => 'wsu.edu',
		);

		wp_localize_script( 'wsu-analytics-main', 'wsu_analytics', $tracker_data );
		wp_enqueue_script( 'wsu-analytics-main' );
	}
}
new WSU_Analytics();