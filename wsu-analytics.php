<?php
/*
Plugin Name: WSU Analytics
Version: 0.4.1
Plugin URI: https://web.wsu.edu/
Description: Provides tracking through Google Analytics for WSU WordPress sites using WSU's jTrack.
Author: washingtonstateuniversity, jeremyfelt, jeremybass
Author URI: https://web.wsu.edu/
*/

class WSU_Analytics {

	/**
	 * @var string The current version of this plugin. Used to break script cache.
	 */
	var $version = '0.4.3';

	/**
	 * @var string Track the string used for the custom settings page we add.
	 */
	var $settings_page = '';

	/**
	 * @var array List of default values for the extended analytics option.
	 */
	var $extended_analytics_defaults = array(
		'campus'          => 'none',
		'college'         => 'none',
		'unit_type'       => 'none',
		'unit'            => 'none',
		'subunit'         => 'none',
		'extend_defaults' => 'true',
		'use_jquery_ui'   => 'true',
		'track_global'    => 'true',
		'track_app'       => 'true',
		'track_site'      => 'true',
	);

	/**
	 * Add our hooks.
	 */
	public function __construct() {
		// Tracking scripts are enqueued on the front end and admin views.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
		add_action( 'admin_footer', array( $this, 'enqueue_scripts' ), 10 );

		// Hack an enqueue of mediaelement when required to add custom events.
		add_filter( 'wp_video_shortcode_library', array( $this, 'mediaelement_scripts' ), 11 );
		add_filter( 'wp_audio_shortcode_library', array( $this, 'mediaelement_scripts' ), 11 );

		add_action( 'wp_head', array( $this, 'display_site_verification' ), 99 );

		// Configure the settings page and sections provided by the plugin.
		add_action( 'admin_init', array( $this, 'register_settings_sections' ), 10 );
		add_action( 'admin_menu', array( $this, 'add_analytics_options_page' ), 10 );
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
		wp_nonce_field( 'wsuwp-analytics-options' );
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
	 * Sanitize the saved values for extended analytics settings.
	 *
	 * @todo Properly clean these once the taxonomy is implemented.
	 *
	 * @param array $analytics_settings Array of settings being saved.
	 *
	 * @return array Clean array of settings to save.
	 */
	public function sanitize_wsuwp_analytics_option_map( $analytics_settings ) {
		$clean_settings = $this->extended_analytics_defaults;
		wp_parse_args( $analytics_settings, $clean_settings );

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
	 * Get extended analytics options for this site.
	 *
	 * @return array
	 * @access private
	 */
	private function get_analytics_options(){
		$option_object = get_option( 'wsuwp_analytics_option_map', array() );

		return wp_parse_args( $option_object, $this->extended_analytics_defaults );
	}

	/**
	 * Provide inputs and selects in general settings
	 *
	 * @todo the stubs should be pulled not hardcoded
	 */
	public function general_settings_inputs() {
		$option_object = $this->get_analytics_options();

		$campus = array(
			'pullman'      => 'Pullman',
			'spokane'      => 'Spokane',
			'vancouver'    => 'Vancouver',
			'tri-cities'   => 'Tri-Cities',
			'globalcampus' => 'Global Campus',
			'everett'      => 'Everett',
		);

		$college = array(
			'arts-and-sciences' => 'Arts & Sciences',
			'cahnrs' => 'CAHNRS & Extension',
			'carson' => 'Carson',
			'education' => 'Education',
			'honors' => 'Honors',
			'medicine' => 'Medicine',
			'murrow' => 'Murrow',
			'nursing' => 'Nursing',
			'pharmacy' => 'Pharmacy',
			'vetmed' => 'VetMed',
			'voiland' => 'Voiland',
		);

		$unit_type = array(
			'center'     => 'Center',
			'department' => 'Department',
			'laboratory' => 'Laboratory',
			'office'     => 'Office',
			'program'    => 'Program',
			'school'     => 'School',
			'unit'       => 'Unit',
		);

		// @todo complete units taxonomy.
		$units = array ();

		?>
		<!-- campus -->
		<p><label class="wsu-analytics-label" for="wsu-analytics-campus">Campus:</label>
		<select id="wsu-analytics-campus" name="wsuwp_analytics_option_map[campus]">
			<option value="none" <?php selected( 'none', $option_object['campus'] ); ?>>None</option>
			<option value="all" <?php selected( 'all', $option_object['campus'] ); ?>>All</option>
			<?php foreach( $campus as $key => $name ) : ?>
				<option value="<?php echo $key; ?>" <?php selected( $key, $option_object['campus'] )?>><?php echo $name; ?></option>
			<?php endforeach; ?>
		</select></p>
		<p class="description">Does this site represent a campus in location or association?</p><br/>

		<!-- college -->
		<p><label class="wsu-analytics-label" for="wsu-analytics-college">College:</label>
		<select id="wsu-analytics-college" name="wsuwp_analytics_option_map[college]">
			<option value="none" <?php selected( 'none', $option_object['college'] ); ?>>None</option>
			<option value="all" <?php selected( 'all', $option_object['college'] ); ?>>All</option>
			<?php foreach( $college as $key => $name ) : ?>
				<option value="<?php echo $key; ?>" <?php selected( $key, $option_object['college'] ); ?>><?php echo $name; ?></option>
			<?php endforeach; ?>
		</select></p>
		<p class="description">Does this site represent a college as a whole or by association?</p><br/>

		<p><label class="wsu-analytics-label" for="wsu-analytics-unit-type">Unit Type:</label>
		<select id="wsu-analytics-unit-type" name="wsuwp_analytics_option_map[unit_type]">
			<option value="none" <?php selected( 'none', $option_object['unit_type'] ); ?>>None</option>
			<?php foreach ( $unit_type as $k => $v ) : ?>
				<option value="<?php echo $k; ?>" <?php selected( $k, $option_object['unit_type'] ); ?>><?php echo $v; ?></option>
			<?php endforeach; ?>
		</select></p>
		<p class="description">What type of unit does this site represent?</p><br/>

		<!-- units -->
		<p><label class="wsu-analytics-label" for="wsu-analytics-parent-unit">Parent Unit:</label>
		<select id="wsu-analytics-parent-unit" name="wsuwp_analytics_option_map[unit]">
			<option value="none" <?php selected( 'none', $option_object['unit'] ); ?>>None</option>
			<?php foreach( $units as $key => $group ) : ?>
				<optgroup label="<?php echo $key; ?>">
				<?php foreach( $group as $item_key => $name ) : ?>
					<option value="<?php echo $item_key; ?>" <?php selected( $item_key, $option_object['unit'] ); ?>><?php echo $name; ?></option>
				<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select></p>
		<p class="description">Does this site represent an entity that has a parent unit? (e.g department, office, school)</p><br/>

		<!-- units -->
		<p><label class="wsu-analytics-label" for="wsu-analytics-unit">Unit:</label>
		<select id="wsu-analytics-unit" name="wsuwp_analytics_option_map[subunit]">
			<option value="none" <?php selected( 'none', $option_object['subunit'] ); ?>>None</option>
			<?php foreach( $units as $key => $group ) : ?>
				<optgroup label="<?php echo $key; ?>">
				<?php foreach( $group as $item_key => $name ) : ?>
					<option value="<?php echo $item_key; ?>" <?php selected( $item_key, $option_object['subunit'] ); ?>><?php echo $name; ?></option>
				<?php endforeach;?>
				</optgroup>
			<?php endforeach;?>
		</select></p>
		<p class="description">Does this site represent an entity that is a unit? (e.g. department, office, school)</p><br/>

		<?php if ( apply_filters( 'wsu_analytics_events_override', false ) || apply_filters( 'wsu_analytics_ui_events_override', false ) ) : ?>
		<!-- extend_defaults -->
		<p><span class="wsu-analytics-label">Custom Events Tracking:</span>
		<label>Extend <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[extend_defaults]" value="true" <?=checked( "true", $option_object["extend_defaults"] )?> /></label>
		<label>Override <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[extend_defaults]" value="false" <?=checked( "false", $option_object["extend_defaults"] )?> /></label>
		<p class="description">Should your theme's custom events file(s) extend or override the default events provided by WSU Analytics?</p><br/>
		<?php endif; ?>

		<!-- use_jquery_ui -->
		<p><span class="wsu-analytics-label">Track jQuery UI Events:</span>
		<label>Yes <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[use_jquery_ui]" value="true" <?=checked( "true", $option_object["use_jquery_ui"] )?> /></label>
		<label>No <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[use_jquery_ui]" value="false" <?=checked( "false", $option_object["use_jquery_ui"] )?> /></label>
		<p class="description">Should WSU Analytics track default jQuery UI events for the site?</p><br/>

		<?php if ( ( function_exists( 'wsuwp_is_network_admin' ) && wsuwp_is_network_admin( wsuwp_get_current_network() ) ) || is_super_admin() ) : ?>
		<p><span class="wsu-analytics-label">Track Global Analytics</span></p>
		<label>Yes <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[track_global]" value="true" <?php checked( 'true', $option_object['track_global'] ); ?> /></label>
		<label>No  <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[track_global]" value="false" <?php checked( 'false', $option_object['track_global'] ); ?> /></label>
		<p class="description">Should global WSU analytics be tracked on this site? This should normally be on and only disabled for debugging.</p><br/>

		<p><span class="wsu-analytics-label">Track App Analytics</span></p>
		<label>Yes <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[track_app]" value="true" <?php checked( 'true', $option_object['track_app'] ); ?> /></label>
		<label>No  <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[track_app]" value="false" <?php checked( 'false', $option_object['track_app'] ); ?> /></label>
		<p class="description">Should WSUWP Platform analytics be tracked on this site? This should normally be on and only disabled for debugging.</p><br/>
		<?php endif; ?>

		<p><span class="wsu-analytics-label">Track Site Analytics</span></p>
		<label>Yes <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[track_site]" value="true" <?php checked( 'true', $option_object['track_site'] ); ?> /></label>
		<label>No  <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[track_site]" value="false" <?php checked( 'false', $option_object['track_site'] ); ?> /></label>
		<p class="description">Should analytics be tracked on this site? A Google Analytics ID is still required if this is enabled.</p><br/>

		<hr/>
		<p class="description">Instructions on how to set up your Google analytics to best use this plugin can be <a href="https://web.wsu.edu/wordpress/plugins/wsu-analytics/">found here</a>.</p>

		<style>
			.wsu-analytics-label {
				display: inline-block;
				width: 185px;
				font-weight: 700;
			}
			.form-table td p.description {
				font-size: 13px;
			}
		</style>
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

		if ( defined( 'WSU_LOCAL_CONFIG' ) && WSU_LOCAL_CONFIG && false === apply_filters( 'wsu_analytics_local_debug', false ) ) {
			return;
		}

		// Look for a site level Google Analytics ID
		$google_analytics_id = get_option( 'wsuwp_ga_id', false );

		// If a site level ID does not exist, look for a network level Google Analytics ID
		if ( ! $google_analytics_id ) {
			$google_analytics_id = get_site_option( 'wsuwp_network_ga_id', false );
		}

		// Provide this via filter in your instance of WordPress. In the WSUWP Platform, this will
		// be part of a must-use plugin.
		$app_analytics_id = apply_filters( 'wsu_analytics_app_analytics_id', '' );

		wp_enqueue_script( 'jquery-jtrack', '//repo.wsu.edu/jtrack/1/jtrack.js', array( 'jquery' ), $this->script_version(), true );

		wp_register_script( 'wsu-analytics-main', plugins_url( 'js/analytics.min.js', __FILE__ ), array( 'jquery-jtrack', 'jquery' ), $this->script_version(), true );

		// Escaping of tracker data for output as JSON is handled via wp_localize_script().
		$tracker_data = array(
			'wsuglobal' => array(
				'ga_code'            => 'true' === $option_object['track_global'] ? 'UA-55791317-1' : false, // Hard coded global analytics ID for WSU.
				'campus'             => $option_object['campus'],
				'college'            => $option_object['college'],
				'unit_type'          => $option_object['unit_type'],
				// Fallback to the subunit if a unit is not selected.
				'unit'               => 'none' === $option_object['unit'] && 'none' !== $option_object['subunit'] ? $option_object['subunit'] : $option_object['unit'],
				// If a subunit has been used as a fallback, output "none" as the subunit.
				'subunit'            => 'none' !== $option_object['unit'] ? $option_object['subunit'] : 'none',
				'events'             => array(),
			),

			'app' => array(
				'ga_code'            => 'true' === $option_object['track_app'] ? $this->sanitize_ga_id( $app_analytics_id ) : false,
				'page_view_type'     => $this->get_page_view_type(),
				'authenticated_user' => $this->get_authenticated_user(),
				'is_editor'          => $this->is_editor(),
				'events'             => array(),
			),

			'site' => array(
				'ga_code'           => 'true' === $option_object['track_site'] ? $google_analytics_id : false,
				'events'            => array(),
			),
		);

		// Allow a theme to override or extend default events.
		if ( apply_filters( 'wsu_analytics_events_override', false ) ) {
			if ( 'true' === $option_object['extend_defaults'] ) {
				wp_enqueue_script( 'wsu-analytics-events', plugins_url( 'js/default_events.js', __FILE__ ), array( 'jquery-jtrack', 'jquery' ), $this->script_version(), true );
				$custom_slug = 'wsu-analytics-extended-events';
			} else {
				$custom_slug = 'wsu-analytics-events';
			}
			wp_enqueue_script( $custom_slug, get_stylesheet_directory_uri() . '/wsu-analytics/events.js', array( 'jquery-jtrack' ), $this->script_version(), true );
		} else {
			wp_enqueue_script( 'wsu-analytics-events', plugins_url( 'js/default_events.js', __FILE__ ), array( 'jquery-jtrack', 'jquery' ), $this->script_version(), true );
		}

		// Output tracker data as a JSON object in the document.
		wp_localize_script( 'wsu-analytics-events', 'wsu_analytics', $tracker_data );

		// Allow a theme to override or extend default UI events.
		if( 'true' === $option_object['use_jquery_ui'] ) {
			if ( apply_filters( 'wsu_analytics_ui_events_override', false ) ) {
				if ( 'true' === $option_object['extend_defaults'] ) {
					wp_enqueue_script( 'wsu-analytics-ui-events', plugins_url( 'js/default_ui-events.js', __FILE__ ), array( 'jquery-jtrack', 'jquery' ), $this->script_version(), true );
					$custom_slug = 'wsu-analytics-extended-ui-events';
				} else {
					$custom_slug = 'wsu-analytics-ui-events';
				}
				wp_enqueue_script( $custom_slug, get_stylesheet_directory_uri() . '/wsu-analytics/ui-events.js', array( 'jquery-jtrack' ), $this->script_version(), true );
			} else {
				wp_enqueue_script( 'wsu-analytics-ui-events', plugins_url( 'js/default_ui-events.js', __FILE__ ), array( 'jquery-jtrack', 'jquery' ), $this->script_version(), true );
			}
		}

		// Fire the primary analytics script after all tracker data and events data is available.
		wp_enqueue_script( 'wsu-analytics-main' );
	}
	
	/**
	 * Enqueues the events when the core media is loaded.
	 */
	public function mediaelement_scripts() {
		wp_enqueue_script( 'wsu-mediaelement-events', plugins_url( '/js/mediaelement-events.js', __FILE__ ), array( 'mediaelement' ), false, true );
		return "mediaelement";
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
	 * Determine if the user has the ability to change the site in some fashion. This
	 * can be through options that affect the front end or through content.
	 *
	 * @return boolean True if the user is a modifier of things. False if not.
	 * @access private
	 */
	private function is_editor() {
		if ( is_user_logged_in() ) {
			// A global admin can edit content or change options anywhere.
			if ( is_super_admin() ) {
				return true;
			}

			$user = wp_get_current_user();

			// On the WSUWP Platform, a network admin can edit content or change options
			// anywhere on an individual network and may not have a role assigned.
			if ( function_exists( 'wsuwp_is_network_admin' ) ) {
				if ( wsuwp_is_network_admin( $user->user_login ) ) {
					return true;
				}
			}

			// Authors and above have (at least) the ability to publish content or delete
			// published content at some level.
			$allowed_roles = array( 'editor', 'administrator', 'author' );
			if ( array_intersect( $allowed_roles, $user->roles ) ) {
				return true;
			}
		}

		return false;
	}
}
$wsu_analytics = new WSU_Analytics();