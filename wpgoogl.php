<?php
/*
Plugin Name: WP Goo.gl
Plugin URI: http://hameedullah.com/wordpress/wpgoogl
Description: Get goo.gl short links for your blog posts, supports authentication so you can track the history from your Google Account
Author: Hameedullah Khan
Author URI: http://hameedullah.com
Version: 1.1
*/


class WPGoogl {

	// authentication URLs
	private $google_auth_url = 'https://www.google.com/accounts/AuthSubRequest';
	private $google_session_url = 'https://www.google.com/accounts/AuthSubSessionToken';
	private $google_revoke_url = 'https://www.google.com/accounts/AuthSubRevokeToken';

	// Googl URL Shortener API URLs
	private $googl_scope_url = 'https://www.googleapis.com/auth/urlshortener';
	private	$googl_api_url = 'https://www.googleapis.com/urlshortener/v1/url';

	function WPGoogl() {
		add_action( 'admin_init', array( $this, 'register_setting_vars' ) );
		add_action( 'admin_menu', array( $this, 'add_menu' ) );

		add_filter( 'get_shortlink', array( 'WPGoogl', 'get_short_url' ), 10, 4 );

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}


	static function get_short_url( $short_url, $post_id, $context, $allow_slugs ) {
        	global $post;
        	if ( $post->post_status != 'publish' ) {
			// no point requesting short url if post is not published yet
                	return "";
		}

		if ( ! $post_id ) {
			$post_id = $post->ID;
		}

		$custom_key_name = get_option( "wpgoogl_custom_key_name" );
        	$shorturl = get_post_meta( $post_id, $custom_key_name, true );
        	if ( $shorturl ) {
			return $shorturl;
		}

        	$post_url = get_permalink( $post_id );

		// build the short url query
        	$headers = array( 
				'Content-Type' => 'application/json',
				'Authorization' => 'AuthSub token="' . get_option( 'wpgoogl_token' ) . '"' 
		);
		$body = '{ "longUrl": "'. $post_url . '"}';
		$params = array( 'method' => 'POST', 'body' => $body, 'headers' => $headers );

        	$http = new WP_Http();
        	$result = $http->request( $this->googl_api_url, $params );
        	$result = json_decode( $result[ 'body' ] );

        	$shorturl = $result->id;

        	if ($shorturl) {
			// alright we got the short url, lets save it and return
                	add_post_meta( $post_id, $custom_key_name, $shorturl, true );
                	return $shorturl;
        	}
        	else {
			// couldn't get the short url, lets return what we got as first param
                	return $short_url;
        	}
	}

	function activate() {
		add_option( 'wpgoogl_authenticated_requests', 0 );
		add_option( 'wpgoogl_token', '' );
		add_option( 'wpgoogl_custom_key_name', 'googl_short_url' );
	}

	function revoke_token($token) {
       		$headers = array( 
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Authorization' => 'AuthSub token="' . get_option( 'wpgoogl_token' ) . '"'
		);
		$body = '';
		$params = array( 'method' => 'GET', 'body' => $body, 'headers' => $headers );
        	$http = new WP_Http();
        	$result = $http->request( $this->google_revoke_url, $params );
		delete_option( 'wpgoogl_token' );
	}

	function deactivate() {
		// plugin deactivated get rid of all the variables
		$this->revoke_token();
		delete_option( 'wpgoogl_authenticated_requests' );
		delete_option( 'wpgoogl_custom_key_name' );
	}

	function add_menu() {
		if ( function_exists( 'add_options_page' ) ) {
			add_options_page( 'WP Goo.gl', 'WP Goo.gl', 'administrator', basename(__FILE__), array( $this, 'settings_page' ) );
		}
	}

	function register_setting_vars() {
		if ( function_exists( 'register_settings' ) ) {
			register_settings( 'wpgoogl-settings', 'wpgoogl_authenticated_requests' );
			register_settings( 'wpgoogl-settings', 'wpgoogl_token' );
			register_settings( 'wpgoogl-settings', 'wpgoogl_custom_key_name' );
		}
	}

	function settings_page() {
		// check the google autehntication token in db
		$wpgoogl_token = get_option( 'wpgoogl_token' );
		if (!$wpgoogl_token) {
			// only read token from url if there is not token already in db
			if ( isset( $_GET[ 'token' ] ) ) {
				// alright we are hot, got the token from google
				// this is one time token, so we have to request session token

				// build google session token request
				$one_time_token = $_GET[ 'token' ];
        			$headers = array( 
						'Content-Type' => 'application/x-www-form-urlencoded',
						'Authorization' => 'AuthSub token="' . $one_time_token . '"'
				);
				$body = '';
				$params = array( 'method' => 'GET', 'body' => $body, 'headers' => $headers );


				// lets get the session token
        			$http = new WP_Http();
        			$result = $http->request( $this->google_session_url, $params );

				$matches = array();
				preg_match( '/Token=(.+)/', $result[ 'body' ], $matches );
				if ( count( $matches ) > 1 ) {
					// alright we got the session token, lets save it
					$wpgoogl_token = $matches[ 1 ];
					update_option( 'wpgoogl_token', $wpgoogl_token );
				}
			} else {
				// if there is no token in db, we need to build the request token url
				$query_str = http_build_query(
					array(
						'next' => $this->settings_url(),
						'scope' => $this->googl_scope_url,
						'session' => 1
					)
				);
				$google_auth_url = $this->google_auth_url . '?' . $query_str;
			}
		}
		include( 'wpgoogl_settings.php' );
	}
	
	function settings_url() {
		return admin_url( 'options-general.php?page=' . basename(__FILE__) );
	}
}

$wpgoogl = new WPGoogl();

?>
