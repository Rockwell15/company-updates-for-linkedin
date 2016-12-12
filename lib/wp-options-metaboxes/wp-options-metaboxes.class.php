<?php

	class Options_Metaboxes {

		protected $body    = '';
		protected $sidebar = '';
		protected $version = 0.1;

		public function __construct() {

			// enqueue script
			add_filter( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_script' ) );

		}

		public function admin_enqueue_script() {

			wp_enqueue_script( 'wp_options_metaboxes_script', plugins_url( 'script.js', __FILE__ ), null, $this->version );
			wp_enqueue_style( 'wp_options_metaboxes_style', plugins_url( 'style.css', __FILE__ ), null, $this->version );

		}

		/**
		 * Queues a metabox for the output
		 * @param string  $section Slug for the section
		 * @param string  $title   Title for the metabox titlebar
		 * @param boolean $sidebar Boolean to determine whether to put it in the body or sidebar
		 * @param string  $inside  HTML string that goes inside the metabox
		 */
		public function add_metabox( $section, $title, $sidebar, $inside ) {

			// strings
			$toggle = __('Toggle panel:');

			// buid the metabox HTML string
			$metabox = <<< HTML
				<div id="$section" class="postbox">
					<button type="button" class="handlediv button-link" aria-expanded="true">
						<span class="screen-reader-text">$toggle $title</span>
						<span class="toggle-indicator" aria-hidden="true"></span>
					</button>
					<h2 class="hndle ui-sortable-handle">
						<span>$title</span>
					</h2>
					<div class="inside">
						$inside
					</div>
				</div>
HTML;

			// if $sidebar is true add this metabox there
			if ( $sidebar ) {
				$this->sidebar .= $metabox;

			// otherwise add it to the body by default
			} else {
				$this->body .= $metabox;

			}

		}

		/**
		 * Adds a metabox with fields from the Settings API
		 * @param string  $section Slug for the section
		 * @param string  $title   Title for the metabox titlebar
		 * @param boolean $sidebar Boolean to determine whether to put it in the body or sidebar
		 */
		public function add_settings_metabox( $section, $title, $sidebar = false ) {

			// get the settings fields
			$inside = $this->get_settings_html( $section );

			// add the metabox
			$this->add_metabox( $section, $title, $sidebar, $inside );

		}

		public function get_settings_html( $section ) {

			// get the settings fields
			ob_start();
			settings_fields( $section );
			do_settings_sections( $section );
			return ob_get_clean();

		}

		/**
		 * Adds a "publish" style metabox to the sidebar
		 * @param string $title Title for the metabox titlebar
		 */
		public function add_publish_metabox( $title, $inside ) {

			// strings
			$save  = __('Save');

			// buid the metabox inside HTML
			$inside = <<< HTML
				<div class="submitbox" id="submitpost">
					<div id="minor-publishing">
						<div id="misc-publishing-actions">
							<div class="misc-pub-section">
								$inside
							</div>
							<div class="clear"></div>
						</div>

						<div id="major-publishing-actions">
							<div id="publishing-action">
								<span class="spinner"></span>
								<input name="original_publish" type= "hidden" id="original_publish" value="Publish">
								<input type="submit" name="submit" id='submit' class="button button-primary button-large" value="$save">
							</div>
							<div class="clear"></div>
						</div>
					</div>
				</div>
HTML;

			// add the metabox
			$this->add_metabox( 'submitdiv', $title, 1, $inside );

		}

		/**
		 * Outputs the metaboxes
		 * @return string
		 */
		public function output() {

			$output = <<< HTML
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						$this->body
					</div>
					<div id="postbox-container-1" class="postbox-container">
						<div id="side-sortables" class="meta-box-sortables ui-sortable" style="">
							$this->sidebar
						</div>
					</div>
				</div>
HTML;

			echo $output;

		}

	}
