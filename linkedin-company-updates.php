<?php
/*
Plugin Name: LinkedIn Company Updates 
Plugin URI:  http://www.rockwellgrowth.com/linkedin-company-updates/
Description: Get your company's recent updates with PHP or [shortcodes]
Version:     1.5
Author:      Andrew Rockwell
Author URI:  http://www.rockwellgrowth.com/
Text Domain: linkedin-company-updates
License:     GPL2v2
 
LinkedIn Company Updates is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
LinkedIn Company Updates is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with LinkedIn Company Updates. If not, see https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html.
*/

defined( 'ABSPATH' ) or die( 'Plugin file cannot be accessed directly.' );

if ( ! class_exists( 'Linkedin_Company_Updates' ) ) {

	include_once('lib/wp-options-metaboxes/wp-options-metaboxes.class.php');

	class Linkedin_Company_Updates {

		//---- Set up variables
		protected $client_id     = '';
		protected $redirect_url  = '';
		protected $admin_url     = '';
		protected $token_life    = 0;
		protected $tag           = 'linkedin_company_updates';
		protected $version       = '1.5';
		protected $options       = array();
		protected $meta_boxes    = array();

		public function __construct() {

			$this->options      = get_option( $this->tag );
			$this->auth_options = get_option( $this->tag . '_authkey' );
			add_action( 'init', array( &$this, 'token_life' ) );

			// do admin stuff
			if ( is_admin() ) {

				// define admin url
				$this->admin_url = admin_url( 'options-general.php?page=' . $this->tag );

				// language support
				add_action( 'plugins_loaded', array( &$this, 'load_textdomain') );

				// add plugin page
				add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

				// add plugin sections
				add_action( 'admin_init', array( &$this, 'settings_sections' ) );

				$help_image = '<a href="' . plugins_url( 'img/help_image.png', __FILE__ ) . '" target="_blank">help</a>';

				// meta boxes config
				$this->meta_boxes = array(

					'config' => array(

						'title' => __('Config') . $help_image,

						'settings' => array(

							// Linkedin setup
							'client-id' => array(
								'type'        => 'text',
								'title'       => __( 'Client ID', 'linkedin-company-updates' ),
								'description' => __( 'Your LinkedIn App Client ID.', 'linkedin-company-updates' ),
								'placeholder' => __( 'Client ID', 'linkedin-company-updates' )
							),
							'client-secret' => array(
								'type'        => 'text',
								'title'       => __( 'Client Secret', 'linkedin-company-updates' ),
								'description' => __( 'Your LinkedIn App Client Secret.', 'linkedin-company-updates' ),
								'placeholder' => __( 'Client Secret', 'linkedin-company-updates' )
							),
							'redirect-url' => array(
								'type'        => 'redirect',
								'title'       => __( 'Redirect URL', 'linkedin-company-updates' ),
								'description' => __( 'Fully qualified URLs to define valid OAuth 2.0 callback paths, as defined in your LinkedIn App.', 'linkedin-company-updates' ),
								'placeholder' => __( 'Redirect URL', 'linkedin-company-updates' )
							),
							'access-token' => array(
								'type'        => 'authorize',
								'title'       => __( 'Access Token', 'linkedin-company-updates' ),
							),

						),

					),

					'feed' => array(

						'title' => __('Feed Settings'),

						'settings' => array(

							// plugin options
							'company-id' => array(
								'type'        => 'text',
								'title'       => __( 'Company ID', 'linkedin-company-updates' ),
								'description' => __( 'Your Default LinkedIn Company ID.', 'linkedin-company-updates' ),
								'placeholder' => '',
							),
							'limit' => array(
								'type'        => 'number',
								'title'       => __( 'Limit', 'linkedin-company-updates' ),
								'description' => __( 'If no amount is specified in the shortcode, then this amount will be used.', 'linkedin-company-updates' ),
								'validator'   => 'numeric',
								'placeholder' => 8,
							),
							'include-default-styling' => array(
								'type'        => 'checkbox',
								'title'       => __( 'Include Default Styling', 'linkedin-company-updates' ),
								'description' => __( 'Checking this will include the plugin\'s default styling for the feed.', 'linkedin-company-updates' ),
							),
							'update-items-container-class' => array(
								'type'        => 'text',
								'title'       => __( 'Update Items Container Class', 'linkedin-company-updates' ),
								'description' => __( 'This class will be added to the container of the update items. Leave a space between each class.', 'linkedin-company-updates' ),
								'placeholder' => 'li-updates-container',
							),
							'update-item-class' => array(
								'type'        => 'text',
								'title'       => __( 'Update Item Class', 'linkedin-company-updates' ),
								'description' => __( 'This class will be added to each update item. Leave a space between each class.', 'linkedin-company-updates' ),
								'placeholder' => 'li-updates-card',
							),

						),

					),

					'publish' => array(

						'title' => __('Save Settings'),

						'settings' => array(

							// plugin options
							'email-when-expired' => array(
								'type'        => 'checkbox',
								'title'       => __( 'Email when Expired', 'linkedin-company-updates' ),
								'description' => __( 'Send email when auth code expires.', 'linkedin-company-updates' ),
							),

						),

					),

				);

				// if we're on the plugin's settings page
				if ( false !== strpos( $_SERVER['QUERY_STRING'], 'page=linkedin_company_updates' ) ) {

					// 1.5 naming fix
					$db_version = get_option( $this->tag . '_version' );
					if ( ! $db_version ) {

						$old_options = get_option( $this->tag . '_options' );
						$new_options = array();
						foreach ( $old_options as $key => $value ) {
							$new_key = str_replace( ' ', '-', strtolower( $key ) );
							$new_options[ $new_key ] = $value;
						}
						$this->options = $new_options;
						update_option( $this->tag, $new_options );
						update_option( $this->tag . '_version', $this->version );

					}

					$this->Options_Metaboxes = new Options_Metaboxes;

					// setup variables
					$this->redirect_url = $this->admin_url;

					// add links to plugin page
					add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( &$this, 'add_plugin_links' ) );

					// enqueue script
					add_filter( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_script' ) );

				}

			// if the shortcode is being used
			} elseif ( ! is_admin() ) {

				// add stylesheet if user wants one
				add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_style' ) );

				// add plugin shortcode
				add_shortcode( 'li-company-updates', array( &$this, 'get_updates' ) );

			}

		}


		public function load_textdomain() {

			load_plugin_textdomain( 'linkedin-company-updates', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );

		}

		/**
		 * Add the handy settings link to the plugins page
		 * @param   array Links for the plugin page
		 */
		public function add_plugin_links ( $links ) {
			$links[] = '<a href="' . $this->admin_url . '">' . __( 'Settings', 'linkedin-company-updates' ) . '</a>';
			return $links;
		}

		/**
		 * Setup the settings page for the plugin
		 */
		public function admin_menu() {
			$title = __( 'Linkedin Company Updates', 'linkedin-company-updates' );
			add_options_page( $title, $title, 'manage_options', $this->tag, array( $this, 'settings_page' ) );
		}

		/**
		 * Enqueue javascript
		 */
		public function admin_enqueue_script() {
			wp_enqueue_script( $this->tag . '_script', plugins_url( 'company-updates-for-linkedin/js/script.js' ), null, $this->version );
			wp_enqueue_style( $this->tag . '_style', plugins_url( 'company-updates-for-linkedin/css/style-admin.css' ), null, $this->version );
		}

		/**
		 * Constructs a string detailing how long the access token remains valid for
		 * @return number Difference between access token & the current time in milliseconds
		 */
		public function token_life() {

			$life = intval( $this->auth_options['expires_in'] ) - strtotime( date('Y-m-d H:m:s') );

			// if the token is past expiration, deal with it
			if ( 0 > $life ) {

				$life = false;

				// check if we should send an email
				if ( isset( $this->options['email-when-expired'] ) && $this->options['email-when-expired'] && ! get_option( $this->tag . '_emailed' ) ) {

					// store that we already emailed an admin
					update_option( $this->tag . '_emailed', 1 );

					// email
					$this->email_admin();

				}

				// add an admin notification
				add_action( 'admin_notices', array( &$this, 'regenerate_notification') );

			}

			$this->token_life = $life;
			return;

		}

		/**
		 * Setup the settings page for the plugin
		 */
		public function settings_sections() {

			foreach ( $this->meta_boxes as $section => $meta_box ) {

				$section_id = $this->tag . '_' . $section;

				// add the plugin settings section
				add_settings_section(
					$section_id,
					null,
					null,
					$section
				);

				// build the settings fields
				foreach ( $meta_box['settings'] as $id => $options ) {
					$options['id'] = $id;
					add_settings_field(
						$this->tag . '_' . $id . '_settings',
						$options['title'],
						array( &$this, 'settings_field' ),
						$section,
						$section_id,
						$options
					);
				}

				// register validation
				register_setting(
					$section,
					$this->tag,
					array( &$this, 'settings_validate' )
				);

			}



		}

		/**
		 * Validate settings
		 * @param  array $input Form fields to be evaluated
		 * @return mixed        Returns the input iff it is valid
		 */
		public function settings_validate( $input ) {
			return $input;
		}

		/**
		 * Add input fields to the settings section
		 * @param  array  $options Options regarding how to build the HTML
		 * @return string          HTML for each settings field
		 */
		public function settings_field( array $options = array() ) {

			$type = $options['type'];

			switch ( $type ) {

				// authorize button
				case 'authorize':
					$this->echo_auth_html();
					break;

				// redirect url
				case 'redirect':
					$string = __('Add this URL to your LinkedIn App\'s <i>Authorized Redirect URLs</i>:');

					echo <<< HTML
						<p>
							$string
							<br />
							<input class="lcu-fullwidth" type="text" readonly onclick="this.select()" value="$this->redirect_url" />
						</p>
HTML;
break;

				// text / number / checkbox
				default:
					$title       = $options['title'];
					$type        = $options['type'];
					$id          = $options['id'];
					$id_string   = $this->tag . '_' . str_replace( ' ', '-', $id );
					$name        = $this->tag . '[' . $id . ']';
					$placeholder = isset( $options['placeholder'] ) ? 'placeholder="' . $options['placeholder'] . '"' : '';
					$description = isset( $options['description'] ) ? $options['description'] : '';

					// set the value
					if ( isset( $this->options[ $id ] ) && $this->options[ $id ] ) {

						$value = 'checkbox' === $type ? '1" checked="checked' : $this->options[ $id ];

					} else {

						$value = 'checkbox' === $type ? 1 : '';

					}

					echo <<< HTML
					<label>
						<input type="$type" name="$name" value="$value" id="$id_string" $placeholder />
						$description
					</label>
HTML;
break;

			}

		}

		/**
		 * echos out the reauthorize button for the access token
		 */
		public function echo_auth_html() {

			// Build parameters for the authorize link
			$_SESSION['state'] = $state = substr(md5(rand()), 0, 7);
		    $params = array(
				'response_type' => 'code',
				'client_id'     => $this->client_id,
				'state'         => $state,
				'redirect_uri'  => $this->redirect_url,
		    );

		    // if re-authorizing
			if( $this->auth_options ) {

				$authorize_string      = 'Regenerate Access Token';
				$authorization_message = '<p>' . $this->get_auth_expiration_string( $this->auth_options['expires_in'] ) . '</p>';

			// if authorizing for the first time
			} else {

				$authorize_string      = 'Authorize Me';
				$authorization_message = '<p>' . __('You must authorize first to create a shortcode.') . '</p>';

			}

			// Output all the things
			echo '<a href="https://www.linkedin.com/uas/oauth2/authorization?' . http_build_query( $params ) . '" id="authorize-linkedin" class="button-secondary">' . $authorize_string . '</a>';
			echo $authorization_message;

		}

		/**
		 * Generates a string reflecting when a token will expire
		 * @param  number $time Unix Timestamp
		 * @return string
		 */
		private function get_auth_expiration_string( $time ) {

			if ( $this->token_life ) {

				$datetime = new DateTime( '@' . $this->token_life, new DateTimeZone('UTC') );
				$date     = new DateTime();
				$times    = array(
					'days'    => $datetime->format('z'),
					'hours'   => $datetime->format('G'),
				);
				$date->modify( '+' . $times['days'] . ' days' );

				return sprintf(
					__('Expires in %s days, %s hours ( <i>%s</i> ) '),
					$times['days'],
					$times['hours'],
					$date->format('m / d / Y')
				);

			} else {

				return __('Authorization token has expired, please regenerate.');

			}

		}

		/**
		 * Fetches the API access token
		 * @param  string $code Linkedin API authentication code
		 * @return array | false
		 */
		private function get_access_token( $code ) {

			// build the url
			$params = array(
				'grant_type'    => 'authorization_code',
				'client_id'     => $this->client_id,
				'client_secret' => isset( $this->options['client-secret'] ) ? $this->options['client-secret'] : '',
				'code'          => $code,
				'redirect_uri'  => $this->redirect_url,
			);
			$url = 'https://www.linkedin.com/uas/oauth2/accessToken?' . http_build_query( $params );

			// get the json
			$json = $this->get_remote_json( $url, 'Failed to make request for access token.' );

			// if there's something wrong with the access token, say so
			if( ! isset( $json['access_token'] ) || 5 >= strlen( $json['access_token'] ) ) {

				$this->notification( __( 'Did not recieve an access token.', 'linkedin-company-updates' ) );
				return false;

			}

			return $json;

		}

		/**
		 * Handles get parameters in the URL
		 */
		private function handle_params() {

			// if we have a `code` GET param we got some work to do
			if ( isset( $_GET['code'] ) ) {

				// get the access token
			    $token = $this->get_access_token( $_GET['code'] );
			    if ( false === $token ) {
			    	return;
			    }

				// calculate when the token will expire
				$end_date = time() + $token['expires_in'];

				// set session variables
				$_SESSION['access_token'] = $token['access_token'];
				$_SESSION['expires_in']   = $token['expires_in'];
				$_SESSION['expires_at']   = $end_date;

				// update the `authkey` option
				$auth_options = array(
					'access_token' => $token["access_token"],
					'expires_in'   => $end_date
				);
				$this->auth_options = $auth_options;
				update_option( $this->tag . '_authkey', $auth_options );
				update_option( $this->tag . '_emailed', 0 );

				echo '<script>window.location = "' . $this->admin_url . '&new_token=true' . '";</script>';

			} else if ( isset( $_GET['new_token'] ) ) {

				$this->notification( __( 'Your LinkedIn authorization token has been successfully updated!', 'linkedin-company-updates' ), 1 );

			}

		}

		/**
		 * HTML for the 'shortcode info' metabox
		 * @return string HTML for the 'shortcode info' metabox
		 */
		private function helper_info() {

			ob_start();

			// only attempt to display shortcode info if we have valid creds for the Linkedin app
			if ( $this->auth_options && $this->token_life ) {

				// get the user's companies
				$array_companies = $this->linkedin_api_call( '', 'values', array( 'is-company-admin' => 'true' ) );
				if ( $array_companies ) {

					echo '<span>' . __( 'Find Your Company ID : ', 'linkedin-company-updates' ) . '</span>';
					echo '<select id="select-company" name="select-company" value="select-company">';

					// add each company to the dropdown
					foreach ( $array_companies as $company ) {
						echo '<option value="' . $company['id'] . '" ' . selected( $company['id'] == $options['slider-posts'] ) . '>' . $company['name'] . ' - ' . $company['id'] . '</option>';
					}

					echo '</select>';
					echo '<p><span><b>' . __( 'Use this shortcode: ', 'linkedin-company-updates' ) . '</b></span><input onClick="this.select();" type="text" id="' . $this->tag . '_shortcode" value="[li-company-updates limit=\'5\' company=\'' . $this->options['company-id'] . '\']"></p>';
					echo sprintf(
						__( '<p><span>Use shortcode %s to put the feed into content. For further documentation of shortcodes, go <a target="_blank" href="%s">Here.</a></span></p>', 'linkedin-company-updates' ),
						'[li-company-updates]',
						'http://www.rockwellgrowth.com/linkedin-company-updates/'
					);

				// tell the user if no companies were retrieved
				} else {
					echo '<b>' . __( 'No companies retrieved! Make sure you\'re the owner of the company via LinkedIn', 'linkedin-company-updates' ) . '</b>';

				}

			// notify unauthorized users
			} else {
				echo '<p>' . __( 'Need to authorize first', 'linkedin-company-updates' ) . '</p>';

			}

			return ob_get_clean();

		}

		/**
		 * Builds the settings page
		 */
		public function settings_page() {

			// load these options
			if( $this->options ) {
				$this->client_id     = isset( $this->options['client-id'] ) ? $this->options['client-id'] : '';
			}

			// handle if the request has an authentication code
			$this->handle_params();

			// setup meta boxes
			foreach ( $this->meta_boxes as $slug => $meta_box ) {
				if ( 'publish' === $slug ) {
					continue;
				}
				$this->Options_Metaboxes->add_settings_metabox( $slug, $meta_box['title'], false );
			}
			$inside = '<input type="checkbox" />Send email when auth code expires';
			$this->Options_Metaboxes->add_publish_metabox( __( 'Save Settings', 'linkedin-company-updates' ), $this->Options_Metaboxes->get_settings_html('publish') );
			$this->Options_Metaboxes->add_metabox( 'shortcode-info', __( 'Shortcode Info', 'linkedin-company-updates' ), true, $this->helper_info() );

		    ?>

				<div class='wrap'>
					<h2><?php _e( 'LinkedIn Company Updates', 'linkedin-company-updates' ); ?></h2>
					<form method='post' id="poststuff" action='options.php'>
						<?php $this->Options_Metaboxes->output(); ?>
					</form>
				</div>

			<?php
		}

		/**
		 * Email notifcation reminding admin to reauthenticate
		 */
		public function email_admin() {

			$email   = get_bloginfo('admin_email');
			$subject = __( 'Linkedin Company Updates - Authorization code expired', 'linkedin-company-updates' );
			$message = sprintf(
				__( 'Please regenerate your authorization code <a href="%s">Here</a>', 'linkedin-company-updates' ),
				$this->admin_url
			);

			wp_mail( $email, $subject, $message );

		}

		/**
		 * Adds a notification to the dashboard
		 */
		public function notification( $text, $update = 0 ) {

			$class = $update ? 'updated' : 'error';

			echo <<< HTML
				<div class="notice is-dismissible $class">
					<p>$text</p>
				</div>
HTML;

		}

		/**
		 * Dashboard notifcation reminding admin to reauthenticate
		 */
		public function regenerate_notification() {

			$string = sprintf(
				__( '<b>Linkedin Company Updates</b> - no valid access token found, your Linkedin feed will not display. Generate a new one <a href="%s">here</a>', 'linkedin-company-updates' ),
				$this->admin_url
			);

			$this->notification( $string );

		}

		/**
		 * GETs remote json data
		 * @param  string $url           url from which to fetch the data
		 * @param  string $error_message Text error message
		 * @return array | false
		 */
		private function get_remote_json( $url, $error_message ) {

			// make the GET request
			$response = wp_remote_get( $url );

			// try to parse it
			try {

				$json = json_decode( $response['body'], 1 );

				// check for errors
				if ( isset( $json['error'] ) ) {

					throw new Error( $json['error_description'] );

				}

				return $json;

			// handle errors
			} catch ( Exception $ex ) {

				$this->notification( $ex->getMessage() );
				return false;

			}

		}

		/**
		 * Grabs data from the Linkedin API
		 * @param  string $path   Path to the resources relative to /companies
		 * @param  string $key    Array key to use for the json response
		 * @param  array  $params URL parameters
		 * @return array | false
		 */
		private function linkedin_api_call( $path, $key, $params = array() ) {

			// build the url
			$default_params = array(
				'format'              => 'json',
				'oauth2_access_token' => $this->auth_options['access_token']
			);
	 		$url = 'https://api.linkedin.com/v1/companies/' . $path . '?' . http_build_query( array_merge( $default_params, $params ) );

	 		// get the json
			$json = $this->get_remote_json( $url, __( 'Failed to make request for access token.', 'linkedin-company-updates' ) );

			// check for undesirable results
			if ( false === $json || ! isset( $json[ $key ] ) || empty( $json[ $key ] ) ) {
				return false;
			}

			return $json[ $key ];

		}

		/**
		 * Enqueue stylesheet
		 */
		public function enqueue_style() {

			// only enqueue if the user wants it to be
			if ( isset( $this->options['include-default-styling'] ) && $this->options['include-default-styling'] ) {

				wp_enqueue_style( 'company_updates_style', plugins_url( 'company-updates-for-linkedin/css/style.css' ), null, $this->version );

			}

		}

		/**
		 * Transforms a timestamp in to a formatted string, reflecting how long ago the timestamp represents
		 * @param  number $time Unix Timestamp
		 * @return string
		 */
		private function time_ago( $time ) {

			$d1           = new DateTime( date('m/d/Y', time() ) );
			$d2           = new DateTime( date('m/d/Y', $time / 1000 ) );
			$months       = $d1->diff( $d2 )->m;
			$days         = $d1->diff( $d2 )->d;

			$month_string = $months . ' ' . __('Months');
			$days_string  = ( 1 === $days ) ? $days . ' ' . __('Day') : $days . ' ' . __('Days');

			if ( 0 == $d1->diff( $d2 )->days ) {
				$time_ago = __('Today');
			} elseif ( isset( $months ) && $months > 0 ) {
				$time_ago = $month_string;
				if ( isset( $days ) && $days > 0 ) {
					$time_ago .= ', ' . $days_string;
				}
			} else {
				$time_ago = $days_string;
			}

			return $time_ago . ' ' . __('Ago');

		}

		/**
		 * Shortcode
		 * @param  array $atts User arguments
		 * @return string      HTML
		 */
		public function get_updates( $atts ) {

			// Set up shortcode attributes
			$args = shortcode_atts( array(
				'con_class'  => isset( $this->options['update-items-container-class'] ) ? $this->options['update-items-container-class'] : '',
				'item_class' => isset( $this->options['update-item-class'] ) ? $this->options['update-item-class'] : '',
				'company'    => isset( $this->options['company-id'] ) ? $this->options['company-id'] : '',
				'limit'      => isset( $this->options['limit'] ) ? $this->options['limit'] : '',
			), $atts );

			// Set up options
			$company_id = $args['company'];

			// Make sure auth token isn't expired
		    if ( $this->token_life ) {

		    	// make some linkedin api calls
				$array_updates = $this->linkedin_api_call( $company_id . '/updates', 'values', array( 'count' => $args['limit'] ) );
				$logo_url      = $this->linkedin_api_call( $company_id . ':(id,name,ticker,description,square-logo-url)', 'squareLogoUrl', array() );

				// Build the list of updates
				$company_updates = '<ul id="linkedin-con" class="' . $args['con_class'] . '">';
				$company_updates .= '	<h2><img src="' . get_bloginfo('url') . '/wp-content/plugins/company-updates-for-linkedin/img/linkedin-logo.gif" />' . __( 'LinkedIn Company Updates', 'linkedin-company-updates' ) . '</h2>';
				if ( $array_updates ) {
					foreach ($array_updates as $update) {

						// Set up the time ago strings
						$update_share = $update['updateContent']['companyStatusUpdate']['share'];
						$time_ago = $this->time_ago( $update_share['timestamp'] );

						// Add picture if there is one
						$img = '';
						if( array_key_exists( 'content', $update_share ) ) {
							$shared_content = $update_share['content'];

							if( array_key_exists( 'submittedImageUrl', $shared_content ) 
								&& 'https://static.licdn.com/scds/common/u/img/spacer.gif' !== $shared_content['submittedImageUrl'] ) {

									$update_image_url = $shared_content['submittedImageUrl'];
									$update_image_link = $shared_content['submittedUrl'];

									$img = '<a target="_blank" href="' . $update_image_link . '"><img alt="" class="linkedin-update-image" src="' . $update_image_url . '" /></a>';

							}

						}

						// Filter the content for links
						$update_content = preg_replace('!(((f|ht)tp(s)?://)[-a-zA-Zа-яА-Я()0-9@:%_+.~#?&;//=]+)!i', '<a target="_blank" href="$1">$1</a>', $update_share['comment']);

						// Create the link to the post
						$update_pieces = explode( '-', $update['updateKey'] );
						$update_id = end( $update_pieces );
						$update_url = 'https://www.linkedin.com/nhome/updates?topic=' . $update_id;

						// Add this item to the update string
						$company_updates .= '<li id="linkedin-item" class="' . $args['item_class'] . '">';
						$company_updates .= 	'<img class="linkedin-update-logo" src="' . $logo_url . '" />';
						$company_updates .= 	'<span>';
						$company_updates .= 		'<i>' . $time_ago . '</i>';
						$company_updates .= 		'<a target="_blank" href="' . $update_url . '">' . __( 'view on linkedin', 'linkedin-company-updates' ) . '</a>';
						$company_updates .= 	'</span>';
						$company_updates .= 	'<div>';
						$company_updates .= 		$img;
						$company_updates .= 		'<h3><a target="_blank" href="https://www.linkedin.com/company/' . $this->options['company-id'] . '">' . $update['updateContent']['company']['name'] . '</a></h3>';
						$company_updates .= 		'<p>' . $update_content . '</p>';
						$company_updates .= 	'</div>';
						$company_updates .= '</li>';
					}
				} else {
					$company_updates .= '<li>' . __( 'Sorry, no posts were received from LinkedIn!', 'linkedin-company-updates' ) . '</li>';
				}
				$company_updates .= '</ul>';

				return $company_updates;
			}

		}

	}
	new Linkedin_Company_Updates;

	/**
	 * Echos the feed
	 * @param  array $atts Info to build the feed
	 */
	function linkedin_company_updates( $atts ) {
		echo Linkedin_Company_Updates::get_updates( $atts );
	}

}
