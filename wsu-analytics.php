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
	 * @var string Track the string used for the custom settings page we add.
	 */
	var $settings_page = '';

	/**
	 * Add our hooks.
	 */
	public function __construct() {
		//set up the trackers for both the front and admin
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
		
		//account for the code media  elements, but treated as after dom load in the browser
		add_filter( 'wp_video_shortcode_library', array( $this, 'mediaelement_scripts' ), 11 );
		add_filter( 'wp_audio_shortcode_library', array( $this, 'mediaelement_scripts' ), 11 );
		
		add_action( 'wp_head', array( $this, 'display_site_verification' ), 99 );

		// Configure the settings page and sections provided by the plugin.
		add_action( 'admin_init', array( $this, 'register_settings_sections' ), 10 );
		add_action( 'admin_menu', array( $this, 'add_analytics_options_page' ), 10 );

		add_action( 'admin_footer', array( $this, 'enqueue_scripts' ), 10 );
	}

	/**
	 * Register the settings sections used to display the Analytics and Site Verification areas
	 * in our custom Analytics options page.
	 */
	public function register_settings_sections() {
		register_setting( 'wsuwp-analytics', 'wsuwp_google_verify', array( $this, 'sanitize_google_verify' ) );
		register_setting( 'wsuwp-analytics', 'wsuwp_bing_verify', array( $this, 'sanitize_bing_verify' ) );
		register_setting( 'wsuwp-analytics', 'wsuwp_ga_id', array( $this, 'sanitize_ga_id' ) );
		register_setting( 'wsuwp-analytics', 'wsuwp_analytics_option_map', array( $this, 'sanitize_wsuwp_analytics_option_map' ) );
	}

	/**
	 * Add a new settings page as "Analytics" in the menu. Only administrators and higher will
	 * be able to view this by default.
	 */
	public function add_analytics_options_page() {
		$this->settings_page = add_options_page( 'WSU Analytics', 'Analytics', 'manage_options', 'wsuwp-analytics', array( $this, 'display_analytics_options_page' ) );

		add_settings_section( 'wsuwp-verification', 'Site Verification', array( $this, 'display_verification_settings' ), $this->settings_page );
		add_settings_section( 'wsuwp-analytics', 'WSU Analytics', array( $this, 'display_analytics_settings' ), $this->settings_page );
	}

	/**
	 * Provide the HTML output for the analytics options page.
	 */
	public function display_analytics_options_page() {
		?>
		<div class="wrap">
			<h2>WSU Analytics Settings</h2>
			<form method="post" action="options.php">
		<?php

		wp_nonce_field('wsuwp-analytics-options');
		do_settings_sections( $this->settings_page );

		submit_button();

		?>
				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="option_page" value="wsuwp-analytics" />
			</form>
		</div>
        <?php
	}

	/**
	 * Display the settings fields associated with site verification.
	 */
	public function display_verification_settings() {
		add_settings_field( 'wsuwp-google-site-verify', 'Google Site Verification', array( $this, 'general_settings_google_site_verify' ), $this->settings_page, 'wsuwp-verification', array( 'label_for' => 'wsuwp_google_verify' ) );
		add_settings_field( 'wsuwp-bing-site-verify', 'Bing Site Verification', array( $this, 'general_settings_bing_site_verify' ), $this->settings_page, 'wsuwp-verification', array( 'label_for' => 'wsuwp_bing_verify' ) );
	}

	/**
	 * Display the settings fields associated with general analytics.
	 */
	public function display_analytics_settings() {
		add_settings_field( 'wsuwp-ga-id', 'Google Analytics ID', array( $this, 'general_settings_ga_id'), $this->settings_page, 'wsuwp-analytics', array( 'label_for' => 'wsuwp_ga_id' ) );
		add_settings_field( 'wsuwp-analytics-option-map', 'General Analytics Settings', array( $this, 'general_settings_inputs' ), $this->settings_page, 'wsuwp-analytics', array( 'label_for' => 'wsuwp_analytics_option_map' ) );
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
	 * Sanitize the saved value for analytics.
	 *
	 * @param $analytics_settings
	 *
	 * @return string
	 */
	public function sanitize_wsuwp_analytics_option_map( $analytics_settings ) {
		return $analytics_settings;
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
	 * Get the site analytics options with no fallback to the network
	 *
	 * @return array
	 * @access private
	 */
	private function get_analytics_options(){
		$defaults = array(
			'campus' => 'none',
			'college' => 'none',
			'unit' => 'none',
			'subunit' => 'none',
			'extend_defaults' => true,
			'use_jquery_ui' => true,
			'is_debug' => false,
		);
		$option_object = get_option( 'wsuwp_analytics_option_map', array() );

		return wp_parse_args( $option_object, $defaults );
	}

	/**
	 * Provide inputs and selects in general settings
	 *
	 * @todo the stubs should be pulled not hardcoded
	 */
	public function general_settings_inputs() {
		$option_object = $this->get_analytics_options();
		
		//stubs for now untill it's pulled form a more central source
		$campus=array(
			"pullman"=>"Pullman"
		);
		$college=array(
			"art_and_science"=>"Arts & Science",
			"cahnrs"=>"CAHNRS & Extension",
			"carson"=>"Carson",
			"education"=>"Education",
			"honors"=>"Honors College",
			"medicine"=>"Medicine",
			"murrow"=>"Murrow",
			"nursing"=>"Nursing",
			"pharmacy"=>"Pharmacy",
			"vetmed"=>"VetMed",
			"voiland"=>"Voiland"
		);
		$units=array(
			"school"=>array(
				"food_sciences"=>"Food Sciences"
			),
			"departments"=>array(
			),
			"offices"=>array(
			),
			"unit"=>array(
				"ucomm"=>"University Communications"
			)
		);
		
		?>
		<hr/>
		
		<!-- campus -->
		<p><b>Campus</b></p>
		<select name="wsuwp_analytics_option_map[campus]">
			<option value="none" <?=selected( "none", $option_object["campus"] )?>>None</option>
			<option value="all" <?=selected( "all", $option_object["campus"] )?>>All</option>
			<?php foreach($campus as $key=>$name):?>
				<option value="<?=$key?>" <?=selected( $key, $option_object["campus"] )?>><?=$name?></option>
			<?php endforeach;?>
		</select>
		<p class="description">Does this site represent a campus in either location or association?</p><br/>
		
		<!-- college -->
		<p><b>College</b></p>
		<select name="wsuwp_analytics_option_map[college]">
			<option value="none" <?=selected( "none", $option_object["college"] )?>>None</option>
			<option value="all" <?=selected( "all", $option_object["college"] )?>>All</option>
			<?php foreach($college as $key=>$name):?>
				<option value="<?=$key?>" <?=selected( $key, $option_object["college"] )?>><?=$name?></option>
			<?php endforeach;?>
		</select>
		<p class="description">Does this site represent a College either in totality or as an association?</p><br/>
		
		<!-- units -->
		<p><b>Parent Unit</b></p>
		<select name="wsuwp_analytics_option_map[unit]">
			<option value="none" <?=selected( "none", $option_object["unit"] )?>>None</option>
			<?php foreach($units as $key=>$group):?>
				<optgroup label="<?=$key?>">
				<?php foreach($group as $item_key=>$name):?>
					<option value="<?=$item_key?>" <?=selected( $item_key, $option_object["unit"] )?>><?=$name?></option>
				<?php endforeach;?>
				</optgroup>
			<?php endforeach;?>
		</select>
		<p class="description">Does this site represent an entiy that has a parent unit/department/office/school?</p><br/>
		
		<!-- units -->
		<p><b>Unit</b></p>
		<select name="wsuwp_analytics_option_map[subunit]">
			<option value="none" <?=selected( "none", $option_object["subunit"] )?>>None</option>
			<?php foreach($units as $key=>$group):?>
				<optgroup label="<?=$key?>">
				<?php foreach($group as $item_key=>$name):?>
					<option value="<?=$item_key?>" <?=selected( $item_key, $option_object["subunit"] )?>><?=$name?></option>
				<?php endforeach;?>
				</optgroup>
			<?php endforeach;?>
		</select>
		<p class="description">Does this site represent an entiy that is some form of a unit/department/office/school?</p><br/>
			
		<!-- extend_defaults -->
		<p><b>Extend Defaults</b></p>
		<label>Yes <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[extend_defaults]" value="true" <?=checked( "true", $option_object["extend_defaults"] )?> /></label>
		<label>No <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[extend_defaults]" value="false" <?=checked( "false", $option_object["extend_defaults"] )?> /></label>
		<p class="description">When using a theme js file to define your custom events, should, "Yes", it be extending the defaults provided with the plugin, or should, "No", it be replacing the defaults. </p><br/>

		<!-- use_jquery_ui -->
		<p><b>Use jQuery UI</b></p>
		<label>Yes <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[use_jquery_ui]" value="true" <?=checked( "true", $option_object["use_jquery_ui"] )?> /></label>
		<label>No <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[use_jquery_ui]" value="false" <?=checked( "false", $option_object["use_jquery_ui"] )?> /></label>
		<p class="description">Load default jQuery UI events.  Note: When using a theme js file, the jQuery UI will follow the same `Extend Defaults` selection. </p><br/>

		<!-- debug -->
		<p><b>Turn on debug</b></p>
		<label>Yes <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[is_debug]" value="true" <?=checked( "true", $option_object["is_debug"] )?> /></label>
		<label>No <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[is_debug]" value="false" <?=checked( "false", $option_object["is_debug"] )?> /></label>
		<p class="description">Normally used for local development</p><br/>		
		
		
		<hr/>
		
		
		<p class="description">Instructions on how to set up your Google analytics to best use this plugin can be <a href="#" class="ajax_info" target="_blank">found here</a>.</p>


		<?php
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
	}

	/**
	 * Enqueue the scripts used for analytics on the platform.
	 */
	public function enqueue_scripts() {

		$option_object = $this->get_analytics_options();

		if ( !$option_object["is_debug"] && defined( 'WSU_LOCAL_CONFIG' ) && WSU_LOCAL_CONFIG ) {
			return;
		}


		// Look for a site level Google Analytics ID
		$google_analytics_id = get_option( 'wsuwp_ga_id', false );

		// If a site level ID does not exist, look for a network level Google Analytics ID
		if ( ! $google_analytics_id ) {
			$google_analytics_id = get_site_option( 'wsuwp_network_ga_id', false );
		}

		/*// If no GA ID exists, we can't reliably track visitors.
		if ( ! $google_analytics_id ) {
			return;
		}*/ //there still maybe app level information to get so we will proceed
		// @todo remove befor publish
		

		//$site_details = get_blog_details();

		wp_enqueue_script( 'jquery-jtrack', '//repo.wsu.edu/jtrack/1/jtrack.js', array( 'jquery' ), $this->script_version(), true );


		wp_register_script( 'wsu-analytics-events', plugins_url( 'js/default_events.js', __FILE__ ), array( 'jquery-jtrack', 'jquery' ), $this->script_version(), true );


		$using_jquery_ui = wp_script_is('jquery-ui-core','registered') || wp_script_is('jquery-ui-core','enqueued') || wp_script_is('jquery-ui-core','done');
		if( $using_jquery_ui && $option_object['use_jquery_ui'] ){
			wp_register_script( 'wsu-analytics-ui-events', plugins_url( 'js/default_ui-events.js', __FILE__ ), array( 'jquery-jtrack', 'jquery' ), $this->script_version(), true );
		}
		
		wp_register_script( 'wsu-analytics-main', plugins_url( 'js/analytics.min.js', __FILE__ ), array( 'jquery-jtrack', 'jquery' ), $this->script_version(), true );

		$tracker_data = array(
			"wsuglobal"=>array(
				"ga_code"           => "UA-55791317-1", // this is hardcode for now
				"campus"            => $option_object["campus"],
				"college"           => $option_object["college"],
				// note that the use may think that "parent unit" is them or not.  
				// When that happens we need to detect it by noting that the subunit is filled but the 
				// unit is not.  This accounts for the other way to look at the two drop downs
				"unit"              => $option_object["unit"]=="none" && $option_object["subunit"]!="none" ? $option_object["subunit"] : $option_object["unit"],
				"subunit"           => $option_object["unit"]!="none" ? $option_object["subunit"] : $option_object["unit"],
				"events"            => array() //ns placholder
			),
			"app"=>array(
				"ga_code"           => "UA-52133513-1", // this is hardcode for now
				"page_view_type"    => $this->get_page_view_type(),
				"authenticated_user"=> $this->get_authenticated_user(),
				"is_editor"         => $this->is_editor(),
				"events"            => array() //ns placholder
			),
			"site"=>array(
				"ga_code"           => $google_analytics_id,
				"events"            => array() //ns placholder
			)
		);
		
		// output the inline settings for the plugin
		wp_localize_script( 'wsu-analytics-events', 'wsu_analytics', $tracker_data );
		
		//figure out what set of events are to be used for the site
		$hascustom_events = file_exists(get_stylesheet_directory() . '/wsu-analytics/events.js');
		if($hascustom_events){
			if($option_object['extend_defaults'] == true){
				wp_enqueue_script( 'wsu-analytics-events' );
			}
			wp_enqueue_script( 'custom-events', get_stylesheet_directory_uri() . '/wsu-analytics/events.js', array( 'jquery-jtrack' ), false, true );
		}else{
			wp_enqueue_script( 'wsu-analytics-events' );
		}

		//figure out what set of jQuery UI events are to be used for the site
		if( wp_script_is('wsu-analytics-ui-events','registered') ){
			$hascustom_ui_events = file_exists(get_stylesheet_directory() . '/wsu-analytics/ui-events.js');
			if($hascustom_ui_events){
				if($option_object['extend_defaults'] == true){
					wp_enqueue_script( 'wsu-analytics-ui-events' );
				}
				wp_enqueue_script( 'custome-ui-events', get_stylesheet_directory_uri() . '/wsu-analytics/ui-events.js', array( 'jquery-jtrack' ), false, true );
			}else{
				wp_enqueue_script( 'wsu-analytics-ui-events' );
			}
		}
		//start up the tracking script
		wp_enqueue_script( 'wsu-analytics-main' );
	}
	
	/**
	 * Enqueues the events when the core media is loaded.
	 */
	public function mediaelement_scripts() {
		wp_enqueue_script( 'wsu-mediaelement-events', plugins_url( '/js/mediaelement-events.js', __FILE__ ), array( 'mediaelement' ), false, true );
	}

	/**
	 * State if the user is authenticated
	 *
	 * @return String
	 * @access private
	 */
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

	/**
	 * State if the user is authenticated
	 *
	 * @return String
	 * @access private
	 */
	private function get_authenticated_user(){
		if ( is_user_logged_in() ) {
			$authenticated_user = 'Authenticated';
		} else {
			$authenticated_user = 'Not Authenticated';
		}
		return $authenticated_user;
	}

	/**
	 * Is the user do more then just suggest content for the site?
	 *
	 * @return boolean
	 * @access private
	 */
	private function is_editor(){
		if(is_user_logged_in()){
			if(is_super_admin()){
				return true;	
			}
			$user = wp_get_current_user();
			$allowed_roles = array('editor', 'administrator', 'author');
			if( array_intersect($allowed_roles, $user->roles ) ) {
				return true;	
			}
		}
		return false;
	}

	/**
	 * Compile a script version and include WSUWP Platform if possible.
	 *
	 * @return string Version to be attached to scripts.
	 * @access private
	 */
	private function script_version() {
		if ( function_exists( 'wsuwp_global_version' ) ) {
			return wsuwp_global_version() . '-' . $this->version;
		}

		return $this->version;
	}

}
$wsu_analytics = new WSU_Analytics();
