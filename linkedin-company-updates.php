<?php
/*
Plugin Name: LinkedIn Company Updates 
Plugin URI:  http://www.rockwellgrowth.com/linkedin-company-updates/
Description: Get your company\'s recent updates with PHP or [shortcodes]
Version:     1.4
Author:      Andrew Rockwell
Author URI:  http://www.rockwellgrowth.com/
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

if ( ! class_exists( 'LIupdates' ) ) {

	class LIupdates {

		//---- Set up variables
		protected $tag = 'linkedin_company_updates_options';
		protected $name = 'LIupdates';
		protected $version = '1.2';
		protected $options = array();
		protected $settings = array(
			'Client ID' => array(
				'description' => 'Your LinkedIn App Client ID.',
				'placeholder' => 'Client ID'
			),
			'Client Secret' => array(
				'description' => 'Your LinkedIn App Client Secret.',
				'placeholder' => 'Client Secret'
			),
			'Limit' => array(
				'description' => 'If no amount is specified in the shortcode, then this amount will be used.',
				'validator' => 'numeric',
				'placeholder' => 8
			),
			'Update Items Container Class' => array(
				'description' => 'This class will be added to the container of the update items. Leave a space between each class.',
				'placeholder' => 'li-updates-container'
			),
			'Update Item Class' => array(
				'description' => 'This class will be added to each update item. Leave a space between each class.',
				'placeholder' => 'li-updates-card'
			),
			'Include Default Styling' => array(
				'description' => 'Checking this will include the plugin\'s default styling for the feed.',
				'type' => 'checkbox'
			),
			'Redirect URL' => array(
				'description' => 'Fully qualified URLs to define valid OAuth 2.0 callback paths, as defined in your LinkedIn App.',
				'placeholder' => 'Redirect URL'
			),
			'Authorize Me' => array(
				'description' => 'Authorize Me.',
				'type' => 'authorize'
			),
			'Company ID' => array(
				'description' => 'Your LinkedIn Company ID.',
			)
		);

		//---- Initiate plugin
		public function __construct() {
			if ( $options = get_option( $this->tag ) ) {
				$this->options = $options;
			}
			if ( is_admin() ) {
				add_action( 'admin_init', array( &$this, 'settings' ) );
			}
		}

		//---- Make settings section for plugin fields
		public function settings() {
			$section = 'LinkedIn';
			add_settings_section(
				$this->tag . '_settings_section',
				null,
				function () {
					echo '<p>Configuration options for the LinkedIn Company Updates plugin.</p>';
				},
				$section
			);
			foreach ( $this->settings AS $id => $options ) {
				$options['id'] = $id;
				add_settings_field(
					$this->tag . '_' . $id . '_settings',
					$id,
					array( &$this, 'settings_field' ),
					$section,
					$this->tag . '_settings_section',
					$options
				);
			}
			register_setting(
				$section,
				$this->tag,
				array( &$this, 'settings_validate' )
			);
		}

		//---- Add input fields to the settings section
		public function settings_field( array $options = array() ) {

			// Set up variables
			$redirect_url = get_bloginfo( 'url' ) . '/wp-admin/options-general.php?page=linkedin_recent_updates';
			$db_options = get_option( 'linkedin_company_updates_options' );
			$lioauth_options = get_option( 'linkedin_company_updates_authkey' );

			$client_secret = $db_options['Client Secret'];
			$client_id = $db_options['Client ID'];
			$access_token = $lioauth_options['access_token'];
			$epire_date = $lioauth_options['expires_in'];

			//---- Output fields 
			if ( isset( $options['id'] ) && $options['id'] == 'Authorize Me' ) {

				$_SESSION['state'] = $state = substr(md5(rand()), 0, 7);
			    $params = array(
			        'response_type' => 'code',
			        'client_id' => $client_id,
			        'state' => $state,
			        'redirect_uri' => $redirect_url,
			    );

				if( $lioauth_options ) {
				    $startDate = date('Y-m-d H:m:s');
				    $endDate = $epire_date;
				    $dDiff = strtotime($endDate) - strtotime($startDate);
				    $datetime = new DateTime('@' . $dDiff, new DateTimeZone('UTC'));
				    $times = array('days' => $datetime->format('z'), 'hours' => $datetime->format('G'), 'minutes' => $datetime->format('i'), 'seconds' => $datetime->format('s'));

                	$date = new DateTime();
					$date->modify('+' . $times['days'] . ' days');
					$auth_expire_date = $date->format('m / d / Y');

					if( $dDiff > 0 ) {
						$authorize_string = 'Regenerate Access Token';
						$authorization_message = '<p style=" display: inline-block; margin-left: 10px; "> Authorization token expires in ' . $times['days'] . ' days, ' . $times['hours'] . ' hours ( <i>' . $auth_expire_date . '</i> ) </p>';
					} else {
						$authorize_string = 'Regenerate Access Token';
						$authorization_message = '<p style=" display: inline-block; margin-left: 10px; "> Authorization token has expired, please regenerate. </p>';
					}
				} else {
					$authorize_string = 'Authorize Me';
					$authorization_message = '<p style=" display: inline-block; margin-left: 10px; "> You must authorize first to create a shortcode. </p>';
				}

				echo '<a href="https://www.linkedin.com/uas/oauth2/authorization?' . http_build_query($params) . '" id="authorize-linkedin" class="button-secondary">' . $authorize_string . '</a>';
				echo $authorization_message;

			} elseif ( isset( $options['id'] ) && $options['id'] == 'Redirect URL' ) {
					echo '<p style=" display: inline-block; ">Add <a href="' . $redirect_url . '">' . $redirect_url . '</a> to the Authorized Redirect URLs in your LinkedIn Application.</p>';
			} else {

				$atts = array(
					'id' => $this->tag . '_' . str_replace(' ', '-', $options['id']),
					'name' => $this->tag . '[' . $options['id'] . ']',
					'type' => ( isset( $options['type'] ) ? $options['type'] : 'text' ),
					'class' => 'small-text',
					'value' => ( array_key_exists( 'default', $options ) ? $options['default'] : null )
				);
				if ( isset( $this->options[$options['id']] ) ) {
					$atts['value'] = $this->options[$options['id']];
				}
				if ( isset( $options['placeholder'] ) ) {
					$atts['placeholder'] = $options['placeholder'];
				}
				if ( isset( $options['type'] ) && $options['type'] == 'checkbox' ) {
					$stylings = 'style="width:16px;"';
					if ( $atts['value'] ) {
						$atts['checked'] = 'checked';
					}
					$atts['value'] = true;
				} else {
					$stylings = 'style="width:320px;"';
				}

				array_walk( $atts, function( &$item, $key ) {
					$item = esc_attr( $key ) . '="' . esc_attr( $item ) . '"';
				} );	
				?>
					<label>
						<input <?php echo $stylings; echo implode( ' ', $atts ); ?> />
						<?php if ( array_key_exists( 'description', $options ) ) : ?>
						<?php esc_html_e( $options['description'] ); ?>
						<?php endif; ?>
					</label>
				<?php
			}
		}

		// Validate saved settings
		public function settings_validate( $input ) {
			$errors = array();
			foreach ( $input AS $key => $value ) {
				if ( $value == '' ) {
					unset( $input[$key] );
					continue;
				}
				$validator = false;
				if ( isset( $this->settings[$key]['validator'] ) ) {
					$validator = $this->settings[$key]['validator'];
				}
				switch ( $validator ) {
					case 'numeric':
						if ( is_numeric( $value ) ) {
							$input[$key] = intval( $value );
						} else {
							$errors[] = $key . ' must be a numeric value.';
							unset( $input[$key] );
						}
					break;
					default:
						 $input[$key] = strip_tags( $value );
					break;
				}
			}
			if ( count( $errors ) > 0 ) {
				add_settings_error(
					$this->tag,
					$this->tag,
					implode( '<br />', $errors ),
					'error'
				);
			}
			return $input;
		}

	}
	new LIupdates;

	class options_page {
		function __construct() {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		}
		function admin_menu () {
			add_options_page( 'LinkedIn Company Updates','LinkedIn Company Updates','manage_options','linkedin_recent_updates', array( $this, 'settings_page' ) );
		}
		function  settings_page () {
			$redirect_url = get_bloginfo( 'url' ) . '/wp-admin/options-general.php?page=linkedin_recent_updates';
			$db_options = get_option( 'linkedin_company_updates_options' );
			if($db_options) {
				$client_id = $db_options['Client ID'];
				$client_secret = $db_options['Client Secret'];
			}

			if ( isset($_GET['code']) ) {
				$redirect_url = get_bloginfo( 'url' ) . '/wp-admin/options-general.php?page=linkedin_recent_updates';
			    $params = array(
			        'grant_type' => 'authorization_code',
			        'client_id' => $client_id,
			        'client_secret' => $client_secret,
			        'code' => $_GET['code'],
			        'redirect_uri' => $redirect_url,
			    );
			    $url = 'https://www.linkedin.com/uas/oauth2/accessToken?' . http_build_query($params);
			    $context = stream_context_create(
			        array('http' => 
			            array('method' => 'POST',
			            )
			        )
			    );

			    if ( function_exists('curl_version') ) {
			    	$curl = curl_init();
			    	curl_setopt($curl, CURLOPT_URL, $url);
			    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			    	$response = curl_exec($curl);
			    	curl_close($curl);
			    } else if ( file_get_contents(__FILE__) && ini_get('allow_url_fopen') ) {
			    	$response = file_get_contents($url, false, $context);
			    } else {
			    	echo '<div class="updated error"><p>You have neither cUrl installed nor allow_url_fopen activated. Please setup one of those!</p></div>';
			    	die();
			    }
			    $token = json_decode($response, 1);

			    $end_date = date('Y-m-d H:m:s', time() + 86400 * 60);
			    $auth_options = array( 'access_token' => $token["access_token"], 'expires_in' => $end_date );
			    if(isset($token["access_token"]) && $token["access_token"] != '' && strlen($token["access_token"]) > 5) {
					update_option( 'linkedin_company_updates_authkey', $auth_options );

			        echo '<div class="updated"><p>Your LinkedIn authorization token has been successfully updated!</p></div>'; 

					$_SESSION['access_token'] = $token->access_token;
					$_SESSION['expires_in']   = $token->expires_in;
					$_SESSION['expires_at']   = time() + $_SESSION['expires_in'];
				}

				// update_option( 'linkedin_company_updates_company', 'nope' );
				if( $_GET['select-company'] ) {
					update_option( 'linkedin_company_updates_company', 'GET' );
				}
				if( $_POST['select-company'] ) {
					update_option( 'linkedin_company_updates_company', 'POST' );
				}

			    echo "<script>";
				echo "	window.location.replace('" . get_bloginfo( 'url' ) . "/wp-admin/options-general.php?page=linkedin_recent_updates');";
			    echo "</script>";

			}
		    ?>
		     <div class='wrap'>
		          <h2>LinkedIn Company Updates</h2>
		          <form method='post' action='options.php'>
		          <?php 
		               settings_fields( 'LinkedIn' );
		               do_settings_sections( 'LinkedIn' );
		          ?>
		               <p class='submit'>
		                    <input name='submit' type='submit' id='submit' class='button-primary' value='<?php _e("Save Changes") ?>' />
		               </p>
		          </form>
		          <?php

					$lioauth_options = get_option( 'linkedin_company_updates_authkey' );

					$access_token = $lioauth_options['access_token'];
					$startDate = date('Y-m-d H:m:s');
					$endDate = $lioauth_options['expires_in'];
					$dDiff = strtotime($endDate) - strtotime($startDate);

					if( isset($access_token) && $access_token != '' && $dDiff > 0 ) {
					    $array = file_get_contents('https://api.linkedin.com/v1/companies?format=json&is-company-admin=true&oauth2_access_token='.$access_token, false);
					    $array = json_decode($array, 1);
					    $array_companies = $array['values'];
					    // echo '<span style="display:inline-block; width: 300px;">Choose a company <i>*defaults to first in list*</i> : </span><select id="select-company" name="select-company" value="select-company">';
					    echo '<span style="display:inline-block; width: 300px;">Find Your Company ID : </span><select id="select-company" name="select-company" value="select-company">';
					    $i = 1;
					    foreach($array_companies as $company) {
					    	if($i == 1) {
					    		$company_id = $company['id'];
					    		$i++;
					    	}
					    	echo '<option value="' . $company['id'] . '" ' . selected( $company['id'] == $options['slider-posts'] ) . '>' . $company['name'] . ' - ' . $company['id'] . '</option>';
						}
					    echo '</select>';
				        echo '<p><span style="display:inline-block; width: 300px;"><b>Use this shortcode: </b></span><input style="display:inline-block; width: 300px;" type="text" id="linkedin_company_updates_shortcode" value="[li-company-updates limit=\'5\' company=\'' . $company_id . '\']"></p>';
				        echo '<p><span>Use shortcode [li-company-updates] to put the feed into content. For further documentation of shortcodes, go <a href="#">Here.</a></span></p>';
					} else {
						echo '<p style=" display: inline-block; "> Need to authorize first </p>';
					}
					?>

		     </div>
		     <script>
		     jQuery(document).ready(function ($) {
		     	// Update Auth Button
		     	$('#linkedin_company_updates_options_Client-ID').on('input', function () {
		     		var linkedin_authorize_url = 'https://www.linkedin.com/uas/oauth2/authorization?response_type=code&client_id=' + $('#linkedin_company_updates_options_Client-ID').val() + '&state=<?php echo substr(md5(rand()), 0, 7); ?>&redirect_uri=<?php echo urlencode($redirect_url); ?>';
		     	});

		     	// Update Shortcode
		     	window.linkedin_shortcode = 'li-company-updates';
		     	if($('#select-company').find('option:selected').val()) { 
		     		window.linkedin_company_id = ' company="' + $('#select-company').find('option:selected').val() + '"'; 
		     	} else {
		     		window.linkedin_company_id = '';
		     	}
		     	if($('#linkedin_company_updates_options_Limit').val()) { 
		     		window.linkedin_post_num = ' limit="' + $('#linkedin_company_updates_options_Limit').val() + '"'; 
		     	} else {
		     		window.linkedin_post_num = '';
		     	}
		     	if($('#linkedin_company_updates_options_Update-Items-Container-Class').val()) { 
		     		window.linkedin_conclass = ' con_class="' + $('#linkedin_company_updates_options_Update-Items-Container-Class').val() + '"'; 
		     	} else {
		     		window.linkedin_conclass = '';
		     	}
		     	if($('#linkedin_company_updates_options_Update-Item-Class').val()) { 
		     		window.linkedin_itemclass = ' item_class="' + $('#linkedin_company_updates_options_Update-Item-Class').val() + '"'; 
		     	} else {
		     		window.linkedin_itemclass = '';
		     	}
     			$('#linkedin_company_updates_shortcode').val( '[' + window.linkedin_shortcode + window.linkedin_post_num + window.linkedin_company_id + window.linkedin_conclass + window.linkedin_itemclass + ']' );

		     	$('#select-company').live('change', function () {
		     		window.linkedin_company_id = $('#select-company').find('option:selected').val();
	     			$('#linkedin_company_updates_shortcode').val( '[' + window.linkedin_shortcode + window.linkedin_post_num + window.linkedin_company_id + window.linkedin_conclass + window.linkedin_itemclass + '"]' );
		     	});
		     	$('#linkedin_company_updates_options_Update-Items-Container-Class, #linkedin_company_updates_options_Update-Item-Class, #linkedin_company_updates_options_Limit').on('input', function () {
		     		if( $('#linkedin_company_updates_options_Update-Items-Container-Class').val() ) {
		     			window.linkedin_conclass = ' con_class="' + $('#linkedin_company_updates_options_Update-Items-Container-Class').val() + '"';
		     		}
		     		if( $('#linkedin_company_updates_options_Update-Item-Class').val() ) {
		     			window.linkedin_itemclass = ' item_class="' + $('#linkedin_company_updates_options_Update-Item-Class').val() + '"';
		     		}
		     		if( $('#linkedin_company_updates_options_Limit').val() ) {
		     			window.linkedin_post_num = ' limit="' + $('#linkedin_company_updates_options_Limit').val() + '"';
		     		}
	     			$('#linkedin_company_updates_shortcode').val( '[' + window.linkedin_shortcode + window.linkedin_post_num + window.linkedin_company_id + window.linkedin_conclass + window.linkedin_itemclass + ']' );
		     	});
		     });
		     </script>
		     <?php
		}
	}
	new options_page;

	class company_updates_shortcode {
		function __construct() {
			// do stuffs
		}
	}
	new company_updates_shortcode;


	function get_linkedin_company_updates( $atts ){

		//---- Set up shortcode attributes
		$args = shortcode_atts( array(
				'con_class' => '',
				'item_class' => '',
				'company' => '',
				'limit' => '',
			), $atts );

		//---- Set up options
		$db_options = get_option( 'linkedin_company_updates_options' );
		$lioauth_options = get_option( 'linkedin_company_updates_authkey' );

		if( isset($args['con_class']) && $args['con_class'] != '' ) {
			$container_class = $args['con_class'];
		} elseif( isset($db_options['Update Items Container Class']) ) {
			$container_class = $db_options['Update Items Container Class'];
		} else {
			$container_class = '';
		}

		if( isset($args['item_class']) && $args['item_class'] != '' ) {
			$card_class = $args['item_class'];
		} elseif( isset($db_options['Update Item Class']) ) {
			$card_class = $db_options['Update Item Class'];
		} else {
			$card_class = '';
		}

		if( isset($args['company']) && $args['company'] != '' ) {
			$linkedin_company_id = $args['company'];
		} elseif( isset($db_options['Company ID']) && isset($db_options['Company ID']) != '' ) {
			$linkedin_company_id = $db_options['Company ID'];
		} else {
			echo 'Company ID not set';
			return;
		}

		if( isset($args['limit']) && $args['limit'] != '' ) {
			$linkedin_limit = $args['limit'];
		} elseif( isset($db_options['Limit']) ) {
			$linkedin_limit = $db_options['Limit'];
		} else {
			$linkedin_limit = 5;
		}

		$epire_date = $lioauth_options['expires_in'];
	    $startDate = date('Y-m-d H:m:s');
	    $endDate = $epire_date;
	    $dDiff = strtotime($endDate) - strtotime($startDate);
	    if( $dDiff > 0 ) {
			$access_token = $lioauth_options['access_token'];
			$array = file_get_contents('https://api.linkedin.com/v1/companies/' . $linkedin_company_id . '/updates?count=' . $linkedin_limit . '&format=json&oauth2_access_token='.$access_token, false);
			$array = json_decode($array, 1);
			$array_updates = $array['values'];

			$company_info = file_get_contents('https://api.linkedin.com/v1/companies/' . $linkedin_company_id . ':(id,name,ticker,description,square-logo-url)?format=json&oauth2_access_token='.$access_token, false);
			$company_info = json_decode($company_info, 1);
			$logo_url = $company_info['squareLogoUrl'];

			$company_updates = '<ul id="linkedin-con" class="' . $container_class . '">';
			$company_updates .= '	<h2><img src="' . get_bloginfo('url') . '/wp-content/plugins/company-updates-for-linkedin/linkedin-logo.gif" />LinkedIn Company Updates</h2>';
			foreach ($array_updates as $update) {
				$d1 = new DateTime( date('m/d/Y', time() ) );
				$d2 = new DateTime( date('m/d/Y', $update['updateContent']['companyStatusUpdate']['share']['timestamp'] / 1000 ) );

				$months = $d1->diff($d2)->m;
				$month_string = $months . ' Months';

				$days = $d1->diff($d2)->d;
				$days_string = $days . ' Days';

				if( isset($months) && $months > 0 ) {
					$time_ago = $month_string;
					if( isset($days) && $days > 0 ) {
						$time_ago .= ', ' . $days_string;
					}
					$time_ago .= ' Ago';
				} else {
					$time_ago = $days_string . ' Ago';
				}

				$company_updates .= '<li id="linkedin-item" class="' . $card_class . '">';
				$company_updates .= 	'<img src="' . $logo_url . '" />';
				$company_updates .= 	'<span>';
				$company_updates .= 		'<i>' . $time_ago . '</i>';
				$company_updates .= 		'<a href="https://www.linkedin.com/company/' . $linkedin_company_id . '">View on LinkedIn</a>';
				$company_updates .= 	'</span>';
				$company_updates .= 	'<div>';
				$company_updates .= 		'<h3>' . $update['updateContent']['company']['name'] . '</h3>';
				$company_updates .= 		'<p>' . $update['updateContent']['companyStatusUpdate']['share']['comment'] . '</p>';
				$company_updates .= 	'</div>';
				$company_updates .= '</li>';
			}
			$company_updates .= '</ul>';

			return $company_updates;
		} else {
			return "Authorization has expired";
		}
	}
	add_shortcode('li-company-updates', 'get_linkedin_company_updates');

	function add_company_updates_plugin_action_links ( $links ) {
		$links[] = '<a href="' . admin_url( 'options-general.php?page=linkedin_recent_updates' ) . '">Settings</a>';
   		$links[] = '<a href="http://www.rockwellgrowth.com" target="_blank">More from Rockwell Growth</a>';
   		return $links;
	}
	add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_company_updates_plugin_action_links' );

	function add_company_updates_stylesheet() {
		wp_enqueue_style( 'company_updates_style', plugins_url( 'company-updates-for-linkedin/style.css' ) );
	}
	$stylingBool = get_option( 'linkedin_company_updates_options' );
	if( $stylingBool['Include Default Styling'] ) {
		add_action( 'wp_enqueue_scripts', 'add_company_updates_stylesheet' );
	}



	function linkedin_company_updates($args) {

		//---- Set up options
		$db_options = get_option( 'linkedin_company_updates_options' );
		$lioauth_options = get_option( 'linkedin_company_updates_authkey' );

		if( isset($args['con_class']) && $args['con_class'] != '' ) {
			$container_class = $args['con_class'];
		} elseif( isset($db_options['Update Items Container Class']) ) {
			$container_class = $db_options['Update Items Container Class'];
		} else {
			$container_class = '';
		}

		if( isset($args['item_class']) && $args['item_class'] != '' ) {
			$card_class = $args['item_class'];
		} elseif( isset($db_options['Update Item Class']) ) {
			$card_class = $db_options['Update Item Class'];
		} else {
			$card_class = '';
		}

		if( isset($args['company']) && $args['company'] != '' ) {
			$linkedin_company_id = $args['company'];
		} elseif( isset($db_options['Company ID']) ) {
			$linkedin_company_id = $db_options['Company ID'];
		} else {
			echo 'Company ID not set';
			return;
		}

		if( isset($args['limit']) && $args['limit'] != '' ) {
			$linkedin_limit = $args['limit'];
		} elseif( isset($db_options['Limit']) ) {
			$linkedin_limit = $db_options['Limit'];
		} else {
			$linkedin_limit = 5;
		}

		$epire_date = $lioauth_options['expires_in'];
	    $startDate = date('Y-m-d H:m:s');
	    $endDate = $epire_date;
	    $dDiff = strtotime($endDate) - strtotime($startDate);
	    if( $dDiff > 0 ) {
			$access_token = $lioauth_options['access_token'];
			$array = file_get_contents('https://api.linkedin.com/v1/companies/' . $linkedin_company_id . '/updates?count=' . $linkedin_limit . '&format=json&oauth2_access_token=' . $access_token, false);
			$array = json_decode($array, 1);
			$array_updates = $array['values'];

			$company_info = file_get_contents('https://api.linkedin.com/v1/companies/' . $linkedin_company_id . ':(id,name,ticker,description,square-logo-url)?format=json&oauth2_access_token='.$access_token, false);
			$company_info = json_decode($company_info, 1);
			$logo_url = $company_info['squareLogoUrl'];

			$company_updates = '<ul id="linkedin-con" class="' . $container_class . '">';
			$company_updates .= '	<h2><img src="' . get_bloginfo('url') . '/wp-content/plugins/company-updates-for-linkedin/linkedin-logo.gif" />LinkedIn Company Updates</h2>';
			foreach ($array_updates as $update) {
				$d1 = new DateTime( date('m/d/Y', time() ) );
				$d2 = new DateTime( date('m/d/Y', $update['updateContent']['companyStatusUpdate']['share']['timestamp'] / 1000 ) );

				$months = $d1->diff($d2)->m;
				$month_string = $months . ' Months';

				$days = $d1->diff($d2)->d;
				$days_string = $days . ' Days';

				if( isset($months) && $months > 0 ) {
					$time_ago = $month_string;
					if( isset($days) && $days > 0 ) {
						$time_ago .= ', ' . $days_string;
					}
					$time_ago .= ' Ago';
				} else {
					$time_ago = $days_string . ' Ago';
				}

				$company_updates .= '<li id="linkedin-item" class="' . $card_class . '">';
				$company_updates .= 	'<img src="' . $logo_url . '" />';
				$company_updates .= 	'<span>';
				$company_updates .= 		'<i>' . $time_ago . '</i>';
				$company_updates .= 		'<a href="https://www.linkedin.com/company/' . $linkedin_company_id . '">View on LinkedIn</a>';
				$company_updates .= 	'</span>';
				$company_updates .= 	'<div>';
				$company_updates .= 		'<h3>' . $update['updateContent']['company']['name'] . '</h3>';
				$company_updates .= 		'<p>' . $update['updateContent']['companyStatusUpdate']['share']['comment'] . '</p>';
				$company_updates .= 	'</div>';
				$company_updates .= '</li>';
			}
			$company_updates .= '</ul>';

			return $company_updates;
		} else {
			return "Authorization has expired";
		}
	}

}