<?php
/*
Plugin Name: WSU Analytics
Version: 0.3.3
Plugin URI: http://web.wsu.edu
Description: Manages analytics for sites on the WSUWP Platform
Author: washingtonstateuniversity, jeremyfelt
Author URI: http://web.wsu.edu
*/

class WSU_Analytics {

	/**
	 * @var string The current version of this plugin, or used to break script cache.
	 */
	var $version = '0.3.3';

	/**
	 * Add our hooks.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
		add_filter( 'wp_video_shortcode_library', array( $this, 'mediaelement_scripts' ), 11 );
		add_filter( 'wp_audio_shortcode_library', array( $this, 'mediaelement_scripts' ), 11 );
		
		add_action( 'wp_head', array( $this, 'display_site_verification' ), 99 );
		add_action( 'wp_footer', array( $this, 'global_tracker' ), 999 );
		
		add_action( 'admin_init', array( $this, 'display_settings' ), 99);
		add_action( 'admin_footer', array( $this, 'global_tracker' ), 999 );
	}

	/**
	 * Register the settings fields that will be output for this plugin.
	 */
	public function display_settings() {
		register_setting( 'general', 'wsuwp_ga_id', array( $this, 'sanitize_ga_id' ) );
		register_setting( 'general', 'wsuwp_google_verify', array( $this, 'sanitize_google_verify' ) );
		register_setting( 'general', 'wsuwp_bing_verify', array( $this, 'sanitize_bing_verify' ) );
		add_settings_field( 'wsuwp-ga-id', 'Google Analytics ID', array( $this, 'general_settings_ga_id'), 'general', 'default', array( 'label_for' => 'wsuwp_ga_id' ) );
		add_settings_field( 'wsuwp-google-site-verify', 'Google Site Verification', array( $this, 'general_settings_google_site_verify' ), 'general', 'default', array( 'label_for' => 'wsuwp_google_verify' ) );
		add_settings_field( 'wsuwp-bing-site-verify', 'Bing Site Verification', array( $this, 'general_settings_bing_site_verify' ), 'general', 'default', array( 'label_for' => 'wsuwp_bing_verify' ) );
	}

	/**
	 * Make sure what we're seeing looks like a Google Analytics tracking ID.
	 *
	 * @param string $ga_id The inputted Google Analytics ID.
	 *
	 * @return string Sanitized Google Analytics ID.
	 */
	public function sanitize_ga_id( $ga_id ) {
		// trim spaces, uppercase UA, explode to 3 piece array
		$ga_id = explode( '-', trim( strtoupper( $ga_id ) ) );

		if ( empty( $ga_id ) || 'UA' !== $ga_id[0] ) {
			return false;
		}

		if ( isset( $ga_id[1] ) ) {
			$ga_id[1] = preg_replace( '/[^0-9]/', '', $ga_id[1] );
		}

		if ( isset( $ga_id[2] ) ) {
			$ga_id[2] = preg_replace( '/[^0-9]/', '', $ga_id[2] );
		}

		$ga_id = implode( '-', $ga_id );

		return $ga_id;
	}

	/**
	 * Sanitize the saved value for the Google Site Verification meta.
	 *
	 * @param string $google_verify
	 *
	 * @return string
	 */
	public function sanitize_google_verify( $google_verify ) {
		return sanitize_text_field( $google_verify );
	}

	/**
	 * Sanitize the saved value for the Bing Site Verification meta.
	 *
	 * @param $bing_verify
	 *
	 * @return string
	 */
	public function sanitize_bing_verify( $bing_verify ) {
		return sanitize_text_field( $bing_verify );
	}

	/**
	 * Display a field to capture the site's Google Analytics ID.
	 */
	public function general_settings_ga_id() {
		$google_analytics_id = get_option( 'wsuwp_ga_id', false );

		?><input id="wsuwp_ga_id" name="wsuwp_ga_id" value="<?php echo esc_attr( $google_analytics_id ); ?>" type="text" class="regular-text" /><?php
	}

	/**
	 * Provide an input in general settings for the entry of Google Site Verification meta data.
	 */
	public function general_settings_google_site_verify() {
		$google_verification = get_option( 'wsuwp_google_verify', false );

		?><input id="wsuwp_google_verify" name="wsuwp_google_verify" value="<?php echo esc_attr( $google_verification ); ?>" type="text" class="regular-text" /><?php
	}

	/**
	 * Provide an input in general settings for the entry of Bing Site Verification meta data.
	 */
	public function general_settings_bing_site_verify() {
		$bing_verification = get_option( 'wsuwp_bing_verify', false );

		?><input id="wsuwp_bing_verify" name="wsuwp_bing_verify" value="<?php echo esc_attr( $bing_verification ); ?>" type="text" class="regular-text" /><?php
	}

	/**
	 * Output the verification tags used by Google and Bing to verify a site.
	 */
	public function display_site_verification() {
		$google_verification = get_option( 'wsuwp_google_verify', false );
		$bing_verification = get_option( 'wsuwp_bing_verify', false );

		if ( $google_verification ) {
			echo '<meta name="google-site-verification" content="' . esc_attr( $google_verification ) . '">' . "\n";
		}

		if ( $bing_verification ) {
			echo '<meta name="msvalidate.01" content="' . esc_attr( $bing_verification ) . '" />' . "\n";
		}
		return;
	}

	/**
	 * Enqueue the scripts used for analytics on the platform.
	 */
	public function enqueue_scripts() {
		// Look for a site level Google Analytics ID
		$google_analytics_id = get_option( 'wsuwp_ga_id', false );

		// If a site level ID does not exist, look for a network level Google Analytics ID
		if ( ! $google_analytics_id ) {
			$google_analytics_id = get_site_option( 'wsuwp_network_ga_id', false );
		}

		// If no GA ID exists, we can't reliably track visitors.
		if ( ! $google_analytics_id ) {
			return;
		}

		//$site_details = get_blog_details();

		wp_enqueue_script( 'jquery-jtrack', '//repo.wsu.edu/jtrack/1/jtrack.js', array( 'jquery' ), $this->script_version(), true );
		
		//if blaa blaa then build else then use default
		wp_register_script( 'wsu-analytics-events', plugins_url( 'js/default_events.js', __FILE__ ), array( 'jquery-jtrack', 'jquery' ), $this->script_version(), true );
		
		
		wp_register_script( 'wsu-analytics-main', plugins_url( 'js/analytics.min.js', __FILE__ ), array( 'jquery-jtrack', 'jquery' ), $this->script_version(), true );

		/*$tracker_data = array(
			'tracker_id' => $google_analytics_id,
			'domain' => $site_details->domain,
		);*/

		$tracker_data = array(
			"wsuglobal"=>array(
				"ga_code"=>"UA-55791317-1",
				"campus"=>"none",
				"college"=>"none",
				"unit"=>"none",
				"subunit"=>"none",
				"events"=>array() //placholder // get and build from the default
			),
			"app"=>array(
				"ga_code"=>"UA-52133513-1",
				"page_view_type"=>$this->get_page_view_type(),
				"authenticated_user"=>$this->get_authenticated_user(),
				"is_authenticated"=>is_user_logged_in(),
				"events"=>array() //placholder // get and build from the default
			),
			"site"=>array(
				"ga_code"=>$google_analytics_id,
				"events"=>array() //placholder // get and build from the default
			)
		);

		wp_localize_script( 'wsu-analytics-events', 'wsu_analytics', $tracker_data );
		wp_enqueue_script( 'wsu-analytics-events' );
		wp_enqueue_script( 'wsu-analytics-main' );
		return;
	}

	public function mediaelement_scripts() {
		wp_enqueue_script( 'wsu-mediaelement-events', plugins_url( '/js/mediaelement-events.js', __FILE__ ), array( 'mediaelement' ), false, true );

		return 'mediaelement';
	}

	private function get_page_view_type(){
		if ( is_blog_admin() ) {
			$page_view_type = 'Site Admin';
		} elseif ( is_network_admin() ) {
			$page_view_type = 'Network Admin';
		} elseif ( ! is_admin() ) {
			$page_view_type = 'Front End';
		} else {
			$page_view_type = 'Unknown';
		}
		return $page_view_type;
	}

	private function get_authenticated_user(){
		if ( is_user_logged_in() ) {
			$authenticated_user = 'Authenticated';
		} else {
			$authenticated_user = 'Not Authenticated';
		}
		return $authenticated_user;
	}


	/**
	 * Compile a script version and include WSUWP Platform if possible.
	 *
	 * @return string Version to be attached to scripts.
	 */
	private function script_version() {
		if ( function_exists( 'wsuwp_global_version' ) ) {
			return wsuwp_global_version() . '-' . $this->version;
		}

		return $this->version;
	}

	/**
	 * Set a global tracker for the wsu.edu root domain. This tracker will not work for
	 * any domains outside of the wsu.edu root at this time.
	 */
	public function global_tracker() {
		if ( defined( 'WSU_LOCAL_CONFIG' ) && WSU_LOCAL_CONFIG ) {
			return;
		}

		// The cookie domain is always wp.wsu.edu, but this can be filtered.
		$cookie_domain = apply_filters( 'wsu_analytics_cookie_domain', 'wsu.edu' );

		// The GA ID is ours by default, but can be filtered.
		$global_id = apply_filters( 'wsu_analytics_ga_id', 'UA-52133513-1' );

		if ( is_blog_admin() ) {
			$page_view_type = 'Site Admin';
		} elseif ( is_network_admin() ) {
			$page_view_type = 'Network Admin';
		} elseif ( ! is_admin() ) {
			$page_view_type = 'Front End';
		} else {
			$page_view_type = 'Unknown';
		}

		if ( is_user_logged_in() ) {
			$authenticated_user = 'Authenticated';
		} else {
			$authenticated_user = 'Not Authenticated';
		}
		
		return;
	}
}
$wsu_analytics = new WSU_Analytics();
