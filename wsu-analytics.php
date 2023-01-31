<?php
/*
Plugin Name: WSU Analytics
Version: 1.3.4
Plugin URI: https://web.wsu.edu/
Description: Provides tracking through Google Analytics for WSU WordPress sites.
Author: washingtonstateuniversity, jeremyfelt, jeremybass
Author URI: https://web.wsu.edu/
*/

class WSU_Analytics {

	/**
	 * @var string The current version of this plugin. Used to break script cache.
	 */
	var $version = '1.3.4';

	/**
	 * @var string Track the string used for the custom settings page we add.
	 */
	var $settings_page = '';

	/**
	 * @var array List of default values for the extended analytics option.
	 */
	var $extended_analytics_defaults = array(
		'tracker'         => 'tagmanager',
		'campus'          => 'none',
		'college'         => 'none',
		'unit_type'       => 'none',
		'unit'            => 'none',
		'subunit'         => 'none',
		'track_global'    => 'true',
		'track_app'       => 'true',
		'track_site'      => 'true',
	);

	/**
	 * Add our hooks.
	 */
	public function __construct() {
		add_action( 'wp_head', array( $this, 'display_site_verification' ), 1 );
		add_action( 'wp_head', array( $this, 'display_tag_manager' ), 2 );
		add_action( 'admin_head', array( $this, 'display_tag_manager' ), 100 );
		

		// Configure the settings page and sections provided by the plugin.
		add_action( 'admin_init', array( $this, 'register_settings_sections' ), 10 );
		add_action( 'admin_menu', array( $this, 'add_analytics_options_page' ), 10 );

		add_action( 'after_setup_theme', array( $this, 'display_body_tags' ) );
	}


	public function display_body_tags() {

		if ( defined( 'ISWDS' ) ) {

			add_action( 'wp_body_open', array( $this, 'display_noscript_tag_manager' ) );

		} else {

			add_action( 'wp_footer', array( $this, 'display_noscript_tag_manager' ) );

		}

	}

	/**
	 * Register the settings sections used to display the Analytics and Site Verification areas
	 * in our custom Analytics options page.
	 */
	public function register_settings_sections() {
		register_setting( 'wsuwp-analytics', 'wsuwp_google_verify', array( $this, 'sanitize_google_verify' ) );
		register_setting( 'wsuwp-analytics', 'wsuwp_bing_verify', array( $this, 'sanitize_bing_verify' ) );
		register_setting( 'wsuwp-analytics', 'wsuwp_facebook_verify', array( $this, 'sanitize_facebook_verify' ) );
		register_setting( 'wsuwp-analytics', 'wsuwp_ga_id', array( $this, 'sanitize_ga_id' ) );
		register_setting( 'wsuwp-analytics', 'wsuwp_ga4_id', 'sanitize_text_field' );
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
		add_settings_field( 'wsuwp-google-site-verify', 'Google Site Verification', array( $this, 'general_settings_google_site_verify' ), $this->settings_page, 'wsuwp-verification', array(
			'label_for' => 'wsuwp_google_verify',
		) );
		add_settings_field( 'wsuwp-bing-site-verify', 'Bing Site Verification', array( $this, 'general_settings_bing_site_verify' ), $this->settings_page, 'wsuwp-verification', array(
			'label_for' => 'wsuwp_bing_verify',
		) );
		add_settings_field( 'wsuwp-facebook-site-verify', 'Facebook Site Verification', array( $this, 'general_settings_facebook_site_verify' ), $this->settings_page, 'wsuwp-verification', array(
			'label_for' => 'wsuwp_facebook_verify',
		) );
	}

	/**
	 * Display the settings fields associated with general analytics.
	 */
	public function display_analytics_settings() {
		add_settings_field( 'wsuwp-ga-id', 'Google Analytics ID', array( $this, 'general_settings_ga_id' ), $this->settings_page, 'wsuwp-analytics', array(
			'label_for' => 'wsuwp_ga_id',
		) );
		add_settings_field( 'wsuwp-ga4-id', 'GA4 Analytics ID', array( $this, 'general_settings_ga4_id' ), $this->settings_page, 'wsuwp-analytics', array(
			'label_for' => 'wsuwp_ga4_id',
		) );
		add_settings_field( 'wsuwp-analytics-option-map', 'General Analytics Settings', array( $this, 'general_settings_inputs' ), $this->settings_page, 'wsuwp-analytics', array(
			'label_for' => 'wsuwp_analytics_option_map',
		) );
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

		if ( empty( $ga_id ) ) {
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
	 * Sanitize the saved value for the Facebook Site Verification meta.
	 *
	 * @param string $facebook_verify
	 *
	 * @return string
	 */
	public function sanitize_facebook_verify( $facebook_verify ) {
		return sanitize_text_field( $facebook_verify );
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
	 * Display a field to capture the site's Google Analytics ID.
	 */
	public function general_settings_ga4_id() {
		$google_analytics_id = get_option( 'wsuwp_ga4_id', false );

		?><input id="wsuwp_ga4_id" name="wsuwp_ga4_id" value="<?php echo esc_attr( $google_analytics_id ); ?>" type="text" class="regular-text" /><?php
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
	 * Provide an input in general settings for the entry of Bing Site Verification meta data.
	 */
	public function general_settings_facebook_site_verify() {
		$facebook_verification = get_option( 'wsuwp_facebook_verify', false );

		?><input id="wsuwp_facebook_verify" name="wsuwp_facebook_verify" value="<?php echo esc_attr( $facebook_verification ); ?>" type="text" class="regular-text" /><?php
	}


	/**
	 * Get extended analytics options for this site.
	 *
	 * @return array
	 * @access private
	 */
	private function get_analytics_options() {
		$option_object = get_option( 'wsuwp_analytics_option_map', array() );

		return wp_parse_args( $option_object, $this->extended_analytics_defaults );
	}

	/**
	 * Return the value of a single WSUWP Analytics option.
	 *
	 * @since 0.7.0
	 *
	 * @param string $key Option key.
	 *
	 * @return mixed Option value.
	 */
	public function get_analytics_option( $key ) {
		$option_object = $this->get_analytics_options();

		if ( isset( $option_object[ $key ] ) ) {
			return $option_object[ $key ];
		}

		return false;
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
		$units = array();

		?>
		<!-- campus -->
		<p><label class="wsu-analytics-label" for="wsu-analytics-campus">Campus:</label>
		<select id="wsu-analytics-campus" name="wsuwp_analytics_option_map[campus]">
			<option value="none" <?php selected( 'none', $option_object['campus'] ); ?>>None</option>
			<option value="all" <?php selected( 'all', $option_object['campus'] ); ?>>All</option>
			<?php foreach ( $campus as $key => $name ) : ?>
				<option value="<?php echo $key; ?>" <?php selected( $key, $option_object['campus'] ); ?>><?php echo $name; ?></option>
			<?php endforeach; ?>
		</select></p>
		<p class="description">Does this site represent a campus in location or association?</p><br/>

		<!-- college -->
		<p><label class="wsu-analytics-label" for="wsu-analytics-college">College:</label>
		<select id="wsu-analytics-college" name="wsuwp_analytics_option_map[college]">
			<option value="none" <?php selected( 'none', $option_object['college'] ); ?>>None</option>
			<option value="all" <?php selected( 'all', $option_object['college'] ); ?>>All</option>
			<?php foreach ( $college as $key => $name ) : ?>
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
			<?php foreach ( $units as $key => $group ) : ?>
				<optgroup label="<?php echo $key; ?>">
				<?php foreach ( $group as $item_key => $name ) : ?>
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
			<?php foreach ( $units as $key => $group ) : ?>
				<optgroup label="<?php echo $key; ?>">
				<?php foreach ( $group as $item_key => $name ) : ?>
					<option value="<?php echo $item_key; ?>" <?php selected( $item_key, $option_object['subunit'] ); ?>><?php echo $name; ?></option>
				<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select></p>
		<p class="description">Does this site represent an entity that is a unit? (e.g. department, office, school)</p><br/>

		<?php if ( ( function_exists( 'wsuwp_is_global_admin' ) && wsuwp_is_global_admin( wp_get_current_user()->ID ) ) || is_super_admin() ) : ?>
		<p><span class="wsu-analytics-label">Track Global Analytics</span></p>
		<label>Yes <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[track_global]" value="true" <?php checked( 'true', $option_object['track_global'] ); ?> /></label>
		<label>No  <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[track_global]" value="false" <?php checked( 'false', $option_object['track_global'] ); ?> /></label>
		<p class="description">Should global WSU analytics be tracked on this site? This should normally be on and only disabled for debugging.</p><br/>

		<p><span class="wsu-analytics-label">Track App Analytics</span></p>
		<label>Yes <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[track_app]" value="true" <?php checked( 'true', $option_object['track_app'] ); ?> /></label>
		<label>No  <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[track_app]" value="false" <?php checked( 'false', $option_object['track_app'] ); ?> /></label>
		<p class="description">Should WSUWP Platform analytics be tracked on this site? This should normally be on and only disabled for debugging.</p><br/>

		<p><span class="wsu-analytics-label">Track Site Analytics</span></p>
		<label>Yes <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[track_site]" value="true" <?php checked( 'true', $option_object['track_site'] ); ?> /></label>
		<label>No  <input type="radio" class="regular-radio" name="wsuwp_analytics_option_map[track_site]" value="false" <?php checked( 'false', $option_object['track_site'] ); ?> /></label>
		<p class="description">Should analytics be tracked on this site? A Google Analytics ID is still required if this is enabled.</p><br/>
		<?php endif; ?>

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
		$facebook_verification = get_option( 'wsuwp_facebook_verify', false );

		if ( $google_verification ) {
			echo '<meta name="google-site-verification" content="' . esc_attr( $google_verification ) . '">' . "\n";
		}

		if ( $bing_verification ) {
			echo '<meta name="msvalidate.01" content="' . esc_attr( $bing_verification ) . '" />' . "\n";
		}

		if ( $facebook_verification ) {
			echo '<meta name="facebook-domain-verification"  content="' . esc_attr( $facebook_verification ) . '" />' . "\n";
		}
	}

	/**
	 * Output the JavaScript used when Google Tag Manager is enabled.
	 *
	 * @since 0.7.0
	 */
	public function display_tag_manager() {
		$tracker_data = $this->get_tracker_data();

		?>
		<script>
			window.dataLayer = window.dataLayer || [];
		</script>
		<script type='text/javascript'>
			/* <![CDATA[ */
			var wsu_analytics = <?php echo wp_json_encode( $tracker_data ); ?>;
			/* ]]> */

			// Determine if this is a mobile view using the same definition as the WSU Spine - less than 990px.
			function wsa_spine_type() {
				if ( window.matchMedia ) {
					return window.matchMedia( "(max-width: 989px)" ).matches ? 'spine-mobile' : 'spine-full';
				}

				return 'spine-full';
			}

			wsu_analytics.app.spine_type = wsa_spine_type();
		</script>
		<!-- Google Tag Manager -->
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
				new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
				j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
				'//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
			})(window,document,'script','dataLayer','GTM-K5CHVG');</script>
		<!-- End Google Tag Manager -->
		<?php

		include __DIR__ . '/tags/ga4-tagmanager.php';
		include __DIR__ . '/tags/marketing-tagmanager.php';

	}

	/**
	 * Output the noscript iframe in the HTML body to track non-JS users if
	 * Google Tag Manager is enabled.
	 *
	 * @since 0.7.0
	 */
	public function display_noscript_tag_manager() {
		?>
		<noscript><iframe src="//www.googletagmanager.com/ns.html?id=GTM-K5CHVG" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
		<?php

		include __DIR__ . '/tags/ga4-tagmanager-body.php';
		include __DIR__ . '/tags/marketing-tagmanager-body.php';

	}

	/**
	 * Builds an array of tracker data that is attached to a jQuery or Google Tag Manager request.
	 *
	 * @since 0.8.0
	 *
	 * @return array
	 */
	public function get_tracker_data() {
		$option_object = $this->get_analytics_options();

		// Look for a site level Google Analytics ID
		$google_analytics_id = get_option( 'wsuwp_ga_id', false );

		// Look for a site level Google Analytics ID
		$ga4_google_analytics_id = get_option( 'wsuwp_ga4_id', false );

		// If a site level ID does not exist, look for a network level Google Analytics ID
		if ( ! $google_analytics_id ) {
			$google_analytics_id = get_site_option( 'wsuwp_network_ga_id', false );
		}

		// Provide this via filter in your instance of WordPress. In the WSUWP Platform, this will
		// be part of a must-use plugin.
		$app_analytics_id = apply_filters( 'wsu_analytics_app_analytics_id', '' );

		$spine_color = '';
		$spine_grid = '';
		$wsuwp_network = '';

		if ( function_exists( 'spine_get_option' ) ) {
			$spine_color = esc_js( spine_get_option( 'spine_color' ) );
			$spine_grid = esc_js( spine_get_option( 'grid_style' ) );
		}

		if ( is_multisite() ) {
			$wsuwp_network = get_network()->domain;
		}

		// Do not track site analytics on admin or preview views.
		if ( is_admin() || is_preview() ) {
			$option_object['track_site'] = false;
		}

		// Escaping of tracker data for output as JSON is handled via wp_localize_script().
		$tracker_data = array(
			'defaults' => array(
				'cookieDomain' => $this->get_cookie_domain(),
			),

			'wsuglobal' => array(
				'ga_code'            => 'true' === $option_object['track_global'] ? 'UA-55791317-1' : false, // Hard coded global analytics ID for WSU.
				'campus'             => $option_object['campus'],
				'college'            => $option_object['college'],
				'unit_type'          => $option_object['unit_type'],
				// Fallback to the subunit if a unit is not selected.
				'unit'               => 'none' === $option_object['unit'] && 'none' !== $option_object['subunit'] ? $option_object['subunit'] : $option_object['unit'],
				// If a subunit has been used as a fallback, output "none" as the subunit.
				'subunit'            => 'none' !== $option_object['unit'] ? $option_object['subunit'] : 'none',
				'is_editor'          => $this->is_editor() ? 'true' : 'false',
				'track_view'         => is_admin() ? 'no' : 'yes',
				'events'             => array(),
			),

			'app' => array(
				'ga_code'            => 'true' === $option_object['track_app'] ? $this->sanitize_ga_id( $app_analytics_id ) : false,
				'page_view_type'     => $this->get_page_view_type(),
				'authenticated_user' => $this->get_authenticated_user(),
				'user_id'            => ( is_admin() ) ? get_current_user_id() : 0,
				'server_protocol'    => $_SERVER['SERVER_PROTOCOL'],
				'wsuwp_network'      => $wsuwp_network,
				'spine_grid'         => $spine_grid,
				'spine_color'        => $spine_color,
				'events'             => array(),
			),

			'site' => array(
				'ga_code'            => 'true' === $option_object['track_site'] ? $google_analytics_id : false,
				'ga4_code'           => $ga4_google_analytics_id,
				'track_view'         => is_admin() ? 'no' : 'yes',
				'events'             => array(),
			),
		);

		return $tracker_data;
	}

	/**
	 * Break the requested host into a 2 part cookie domain.
	 *
	 * @return string
	 */
	private function get_cookie_domain() {
		$requested_domain_parts = explode( '.', $_SERVER['HTTP_HOST'] );
		$cookie_domain = array_pop( $requested_domain_parts );
		$cookie_domain = '.' . array_pop( $requested_domain_parts ) . '.' . $cookie_domain;

		return $cookie_domain;
	}

	/**
	 * State if the user is authenticated
	 *
	 * @return String
	 * @access private
	 */
	private function get_page_view_type() {
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
	private function get_authenticated_user() {
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
