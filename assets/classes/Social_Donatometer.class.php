<?php

	//class for our plug-in logic
	if(!class_exists('Social_Donatometer')) {

		class Social_Donatometer {

			public $display = array();
			public $donatometer = array();

			public function __construct($opts = null) {
				global $wpdb;

				//store a reference to $wpdb as $this->db
				//so we don't have to keep retyping 'global $wpdb;'
				$this->db = $wpdb;

				//if options were provided
				if(is_object($opts)) {
					//store the options for reference
					$this->options = $opts;
				//if they weren't provided
				} else {
					//check if the Plugin_Name_Options class exists
					if(class_exists('Social_Donatometer_Options')) {
						//store the Social_Donatometer_Options object
						//for reference to capabilties, tables, and options
						$this->options = new Social_Donatometer_Options();
					}
				}

				//get current options and create $this->donatometer and $this->display
				$this->get_options();
			}

		//ACTIVATION, DEACTIVATION, UNINSTALLATION

			//our plug-in activation
			public function activate() {
				//call methods to initialize plug-in functionality
				$this->set_options();
				$this->add_caps();
			}

			//our plug-in deactivation
			public function deactivate() {
				//call methods to remove options and capabilities
				//we don't remove the tables here, they are removed in uninstall.php
				$this->remove_caps();
			}

			//our plug-in uninstall
			public function uninstall() {
				//call methods to remove tables and unset version number
				//other plugin data should have been removed on deactivation
				$this->unset_options();
			}

		//GET CURRENT SETTINGS

			//get current options
			public function get_options() {
				//get options, use defaults from plugin-options.php if they aren't found
				$opts = get_option($this->fix_name('options'), $this->options->opts[$this->fix_name('options')]);

				//decode the JSON string into an array and save it to $this->donatometer
				$this->donatometer = json_decode($opts, true);

				//get display, use defaults from plugin-options.php if they aren't found
				$display = get_option($this->fix_name('display'), $this->options->opts[$this->fix_name('display')]);

				//decode teh JSON string into an array and save it over to $this->display
				$this->display = json_decode($display, true);
			}

		//DASHBOARD WIDGET

			//adds the dashboard widget
			public function widget() {
				wp_add_dashboard_widget('donatometer_widget', 'Donatometer', array($this, 'create_widget'), array($this, 'save_widget'));
			}

			//add widget scripts so we can display date picker if browser doesn't support it
			public function widget_scripts($page) {
				if($page == 'index.php') {
					wp_register_script('modernizr-input', SOCIAL_DONATOMETER_URL . '/assets/js/modernizr.min.js', '', '', true);
					wp_register_script('donatometer-js', SOCIAL_DONATOMETER_URL . '/assets/js/widget.min.js', array('jquery', 'modernizr-input'), $this->options->opts[$this->prefix . 'version'], true);

					wp_enqueue_script('donatometer-js');
				}
			}

			//create the default widget
			public function create_widget() {
				if($donatometer['active']) {
					$body = '<p>We have raised <strong>$' . $this->donatometer['amount'] . '</strong> of our <strong>$' . $this->donatometer['goal'] . '</strong> goal with <strong>' . $this->get_days_left($this->donatometer['end_date'], date('Y-m-d')) . ' days</strong> to go.</p>' .
						'<br /><br />' .
						'<p>Last Updated: ' . date('M d, Y', strtotime($this->donatometer['last_update'])) . '</p>'
					;
				} else {
					$body = '<p>No active Donatometers.</p>';
				}

				echo $body;
			}

			//save widget configuration
			public function save_widget($id) {
				//check if user has our required capability
				if(current_user_can($this->options->caps['manage_options'][0])) {
					//see if the configuration options for this widget are being saved
					if('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['donatometer_save'])) {
						//if active was unchecked
						if(!$_POST['active']) {
							//set active to 0
							$this->donatometer['active'] = 0;
						}

						//iterate through form fields
						foreach($_POST as $name => $val) {
							//if the input is an actual option
							if($name != 'donatometer_save') {
								//set donatometer option
								$this->donatometer[$name] = $val;
							}
						}

						$this->save_options();
					}

					//check for active state so we can set checkbox checked state
					if($this->donatometer['active']) {
						$active = ' checked';
					} else {
						$active = '';
					}

					//build widget body
					$body .= '<div id="donatometer_widget">' .
							'<label for="donatometer_success">Success Message (optional): ' . 
								'<input type="text" name="success" id="donatometer_success" value="' . $this->donatometer['success'] . '" />' .
							'</label><br />' .
							'<label for="donatometer_goal">Goal: $' . 
								'<input type="text" name="goal" id="donatometer_goal" value="' . $this->donatometer['goal'] . '" />' .
							'</label><br />' .
							'<label for="donatometer_amount">Raised: $' . 
								'<input type="text" name="amount" id="donatometer_amount" value="' . $this->donatometer['amount'] . '" />' .
							'</label><br />' .
							'<label for="donatometer_start">Starts: ' .
								'<input type="date" name="start_date" id="donatometer_start" value="' . $this->donatometer['start_date'] . '" />' .
							'</label><br />' .
							'<label for="donatometer_end">Ends: ' .
								'<input type="date" name="end_date" id="donatometer_end" value="' . $this->donatometer['end_date'] . '" />' .
							'</label><br />' .
							'<label for="donatometer_active">' .
								'<input type="checkbox" name="active" id="donatometer_active" value="1"' . $active . ' />' .
								' Active' .
							'</label><br />' .
							'<input type="hidden" name="last_update" value="' . date('Y-m-d') . '" />' .
							'<input type="hidden" name="donatometer_save" value="1" />' .
						'</div>'
					;

					echo $body;
				} else {
					echo '<p>Insufficent Access to edit the Donatometer.</p>';
				}
			}

		//ADMIN PAGE

			//add donatometer admin page
			public function admin_page() {
				add_theme_page('Donatometer', 'Donatometer', $this->options->caps['manage_options'][1], $this->fix_name('display'), array($this, 'create_page'));
			}

			//create donatometer admin page
			public function display_page() {
				//if form was submitted and user can edit the donatometer display options
				if($_REQUEST['submit'] && current_user_can($this->options->caps['manage_options'][1]) {
					//save display options
					$this->save_admin($_REQUEST);
				}

				//see if we should check the css checkbox
				if($this->display['css']) {
					$css_active = ' checked';
				} else {
					$css_active = '';
				}

				//array for position choices
				$position_options = array(
					'none' => 'Do not add position class'
					'bottom' => 'Fix to Bottom - Adds class "bottom" to template',
					'top' => 'Fix to Top - Adds class "top" to template'
				);

				//build page body
				$body .= '<div id="donatometer_page" class="wrap">' .
						'<h2>Donatometer Display Options</h2>' .
						'<form id="donatometer_admin_form" method="post">' .
							'<label for="donatometer_css">CSS<br />' .
								'<input type="checkbox" id="donatometer_css" name="css" value="1"' . $css_active . ' />' .
								' Embed Donatometer Default CSS' .
							'</label><br />' .
							'<label for="donatometer_position">' .
								'<select name="position" id="donatometer_position">';

									//iterate through position choices and populate dropdown
									foreach($position_options as $val => $label) {
										//check for selected position
										if($val == $this->display['position']) {
											$selected = ' selected';
										} else {
											$selected = '';
										}

										//add position to dropdown
										$body .= '<option value="' . $val . '"' . $selected . '>' . $label . '</label>';
									}

								//build rest of body
								$body .= '</select>' .
							'</label><br />' .
							'<label for="donatometer_success_template">' .
								'<input type="text" name="success_template" id="donatometer_success_template" value="' . $this->display['success_template'] . '" />' .
							'</label><br />' .
							'<label for="donatometer_raised_template">' .
								'<inpyt type="text" name="success_template" id="donatomter_raised_template" value="' . $this->display['raised_template'] . '" />' .
							'</label><br /><br />' .
							'<input type="submit" id="donatometer_submit" name="submit" value="Save Settings" />' .
						'</form>' .
					'</div>'
				;

				//display template
				echo $body;
			}

			//save settings from the admin display page
			private function save_admin($vals) {
				//remove submit from fields
				unset($vals['submit']);

				//iterate through values and update $this->display
				foreach($vals as $name => $val) {
					$this->display[$name] = $val;
				}

				$this->save_display();
			}

		//FRONT END DISPLAY

			//add donatometer css and js
			public function styles() {
				//if donatometer is active and end_date hasn't passed
				if($this->check($this->donatometer)) {
					//if we should embed donatometer css
					if($this->display['css']) {
						//register and enqueue donatometer css
						wp_register_style('donatometer', DONATOMETER_URL . '/assets/css/donatometer.css', '', $this->options->opts[$this->prefix . 'version'], 'screen');
						wp_enqueue_style('donatometer');
					}

					//register and enqueue donatomter js
					wp_register_script('donatometer', DONATOMETER_URL . '/assets/js/donatometer.min.js', array('jquery'), $this->options->opts[$this->prefix . 'version'], true);
					wp_enqueue_script('donatometer');
				}
			}

			//show donatometer
			public function show() {
				//if donatometer should be displayed
				if($this->check()) {
					//display donatometer
					echo $this->get_template();
				}
			}

			//check display options and end date
			private function check() {
				//if the donatometer is active
				if($this->donatometer['active']) {
					//check page display and date
					if($this->check_page() && $this->check_date()) {
						return true;
					} else {
						return false;
					}
				} else {
					return false;
				}
			}

			//check if this is the correct page to display the donatometer
			private function check_page($donatometer) {
				//check the display['show'] option
				switch($this->display['show']) {
					//if show is set to homepage
					case 'home':
						//check if this is the front page
						if(is_front_page()) {
							return true;
						}
						break;
					//if show is set to all pages
					case 'all':
						return true;
						break;
					//if show is set to no pages
					case 'none':
					default:
						return false;
						break;
				}
			}

			//check if the date falls within the donatometers display dates
			private function check_date($donatometer) {
				//don't check dates by default
				$check_start = false;
				$check_end = false;

				//check against this strtotime to see if dates were set
				$default_date = strtotime(date('Y-m-d', strtotime('0000-00-00')));

				//set times for start and end
				$end = strtotime($donatometer['end_date']);
				$start = strtotime($donatometer['start_date']);

				//see if we need to check start date
				if($start != $default_date) {
					$check_start = true;
				}

				//see if we need to check end date
				if($end != $default_date) {
					$check_end = true;
				}

				//if we need to check dates
				if($check_end || $check_start) {
					//store today's time string
					$today = strtotime(date('Y-m-d'));

					//if we need to check both
					if($check_start && $check_end) {
						//if today is after the start date and before the end date  
						if($start >= $today && $today <= $end) {
							return false;
						} else {
							return true;
						}
					//if we only need to check the end date
					} elseif($check_end && $check_start == false) {
						//if today is before the end date
						if($today <= $end) {
							return true;
						} else {
							return false;
						}
					//if we only need to check the start date
					} elseif($check_start && $check_end == false) {
						//if today is after the start date
						if($today >= $start) {
							return true;
						} else {
							return false;
						}
					}
				//if we don't need to check dates
				} else {
					return true;
				}
			}

			//get donatometer template
			private function get_template() {
				//make sure donatometer is active
				if($this->donatometer['active']) {
					//format the %tags% in the display options
					$this->format_tags();

					//check for donatometer button to know if we need to add the no-button class to our progress bar
					if($this->donatometer['link'] && $this->donatometer['button']) {
						//showing button
						$show_button = true;
						$progrss_class = '';
					} else {
						//not showing button
						$show_button = false;
						$progress_class = ' no-button';
					}

					//check for display position setting
					if($this->display['position'] != 'none') {
						$position = ' ' . $this->display['position'];
					} else {
						$position = '';
					}

					//build the donatometer HTML
					$body = '<div id="social-donatometer" class="donatometer-container' . $position . '">' .
						'<div class="donatometer">' .
							'<div class="progress-bar' . $progress_class . '">' .
								'<p class="progress" data-goal="' . $this->donatometer['goal'] . '" data-amount="' . $this->donatometer['amount'] . '" data-success="' . $this->display['success_template'] . '"></p>' .
								'<p class="progress-msg">' . $this->display['raised_template'] . '</p>' .
							'</div>'
					;

					//if there is a donatometer button
					if($show_button) {
						//add the donatometer markup to the template body
						$body .= '<div class="donatometer-button">' .
								'<a href="' . $this->donatometer['link'] . '">' . $this->donatometer['button'] . '</a>' .
							'</div>'
						;
					}

					//close out the template body
					$body .= '</div>' .
						'</div>'
					;

					//return template
					return $body;
				}
			}

		//FORMAT TEMPLATE TAGS

			//swap %tags% with actual values
			private function format_tags() {
				//these are the templates we need to check
				$format_array = array(
					$this->display['raised_template'],
					$this->display['success_template']
				);

				//these are the various pre-defined tags, aside from the date tags which we will deal with later
				$tags_array = array(
					'%raised%' => $this->donatometer['amount'],
					'%raised_format%' => number_format($this->donatometer['amount'], 0, '.', ','),
					'%raised_dec%' => number_format($this->donatometer['amount'], 2, '.', ','),
					'%raised_eu_format' => number_format($this->donatometer['amount'], 0, ',', '.'),
					'%raised_eu_dec%' => number_format($this->donatometer['amount'], 2, ',', '.'),
					'%goal%' => $this->donatometer['goal'],
					'%goal_format%' => number_format($this->donatometer['goal'], 0, '.', ','),
					'%goal_dec%' => number_format($this->donatometer['goal'], 2, '.', ','),
					'%goal_eu_format' => number_format($this->donatometer['goal'], 0, ',', '.'),
					'%goal_eu_dec%' => number_format($this->donatometer['goal'], 2, ',', '.'),
					'%days_left%' => $this->days_left($this->donatometer['end_date'])
				);

				//iterate through templates to format
				foreach($format_array as $key => &$val) {
					//check for date tags and replace them with the specified format
					$val = preg_replace_calback(
						'\%end.*\%|\%start.*\%',
						function($dates) {
							//iterate through matched date tags
							foreach($dates as $key => $format) {
								//create array from the tag to see if there is a format specified
								//this also makes it easy to know if we are using the start or end date
								$format_array = explode('_', $format);

								//if there was a format
								if(count($format_array) > 1) {
									//get the format
									$date_format = $format_array[1];

									//replace date tag based on the provided format
									$dates[$key] = date($date_format, strtotime($this->donatometer[$format_array[0] . '_date']));
								//if there wasn't a format
								} else {
									//replace date tag with the specified date
									$dates[$key] = $this->donatometer[$format_array[0] . '_date'];
								}
							}

							//return formatted dates
							return $dates;
						},
						$val
					);

					//iterate through tags array
					foreach($tags_array as $tag => $replace) {
						//replace any tags found with their match
						$val = str_replace($tag, $replace, $val);
					}
				}
			}

		//CAPABILITIES

			//add capabilities
			private function add_caps() {
				//get roles object
				global $wp_roles;

				//iterate through all roles and add the capabilities
				foreach($wp_roles->role_names as $role => $info) {
					//get the role
					$role_obj = get_role($role);

					//iterate through capabilities in the options
					//this gives us an array of capabilities and the capability they require
					foreach($this->options->caps as $req => $caps) {
						//iterate through our capabilities
						foreach($caps as $key => $cap) {
							//if this role has the required capability
							//but not the capability we want to add
							if(!$role_obj->has_cap($cap) && $role_obj->has_cap($req)) {
								//add capability
								$role_obj->add_cap($cap, true);
							}
						}
					}
				}
			}

			//remove capabilities
			private function remove_caps() {
				//get roles object
				global $wp_roles;

				//iterate through all roles and remove the capabilities
				foreach($wp_roles->roles as $role => $info) {
					//get the role
					$role_obj = get_role($role);

					//iterate through capabilities in the options
					//this gives us an array of capabilities and the capability they require
					foreach($this->options->caps as $req => $caps) {
						//iterate through our capabilities
						foreach($caps as $key => $cap) {
							//if this role has our capability
							if($role_obj->has_cap($cap)) {
								//remove the capability
								$role_obj->remove_cap($cap);
							}
						}
					}
				}
			}

		//OPTIONS

			//save options
			private function save_options() {
				//json_encode the array for storage
				$options = json_encode($this->donatometer);

				//store donatometer options
				update_option($this->fix_name('options'), $options);
			}

			//save display
			private function save_display() {
				//json_encode the array for storage
				$display = json_encode($this->display);

				//store display options
				update_option($this->fix_name('display'), $display);
			}

			//this method sets any necessary options
			private function set_options() {
				//iterate through our options
				foreach($this->options->opts as $name => $val) {
					if($name == $this->fix_name('options')) {
						$val = json_encode($val);
					}
					//run the option through our update method
					$this->update_option($name, $val);
				}
			}

			//this method removes any necessary options
			public function unset_options() {
				//iterate through our options
				foreach($this->options->opts as $name => $val) {
					//don't remove the version number so we can still check versions on updates
					//we'll remove it in uninstall.php
					if($name != $this->fix_name('version')) {
						//remove the option
						delete_option($name);
					}
				}
			}

			//this method allows us to run some checks when updating versions and changing options
			private function update_option($option, $value) {
				//if the option exists
				if($curr_value = get_option($option)) {
					//if the current value isn't what we want
					if($curr_value !== $value) {
						//check with the pre_update_option method which lets us perform any necessary actions when updating our options
						if($this->pre_update_option($option, $curr_value, $value)) {
							//update the option value
							update_option($option, $value);
						}
					}
				//if it doesn't add it
				} else {
					add_option($option, $value);
				}
			}

			//this method performs checks against specific option names to run update functions prior to saving the option
			private function pre_update_option($name, $old, $new) {
				//we'll make this true when the option is safe to update
				$good_to_go = false;

				//if this is our version number
				if($name === $this->options->opts[$this->fix_name('version')]) {

					//IMPORTANT: call necessary update functions for each version here

					$good_to_go = true;
				//otherwise
				} else {
					//if we've got some values in there, we're good
					if($old || $new) {
						$good_to_go = true;
					}
				}

				return $good_to_go;
			}

		//UTILITY

			//get the days left for the donatometer
			private function get_days_left($end, $from = null) {
				//if from wasn't specified, use the current date
				if(!$from) {
					$from = date('Y-m-d');
				}

				//format the date strings so we can find the difference
				$from_str = strtotime($from);
				$end_str = strtotime($end);

				//if the end date specified is before the start date
				if($end_str < $from_str) {
					//just return 0
					return 0;
				//otherwise
				} else {
					//convert the differnce into days
					$diff = ($end_str - $from_str) / (60 * 60 * 24);

					//return the difference
					return $diff;
				}
			}

			//create a prefixed version of a table name or option name
			private function fix_name($short_name = null) {
				//see if short_name was provided
				if(isset($short_name)) {
					//if short_name doesn't start with _ and prefix doesn't end with _
					if(substr(0, -1, $this->options->prefix) != '_' && substr(0, 1, $short_name) != '_') {
						//add an _ between prefix and short_name
						$name = $this->options->prefix . '_' . $short_name;
					//if short_name starts with _ and prefix ends with _
					} elseif(substr(0, -1, $this->options->prefix) == '_' && substr(0, 1, $short_name) == '_') {
						//remove _ from short_name and prepend prefix
						$name = $this->options->prefix . substr(0, 1, $short_name);
					//if only one has an _
					} else {
						//concatenate the prefix and short_name
						$name = $this->options->prefix . $short_name;
					}

					//return the newly generated name
					return $name;
				}
			}

			//WP_DEBUG logging method
			public function log($message, $namespace = null) {
				//if debugging is enabled
				if(WP_DEBUG) {
					//if we weren't given a namespace
					if(!is_string($namespace)) {
						//use the one defined in the class initialization
						$namespace = $this->options->namespace;
					//if we were
					} else {
						//convert it to caps so it's easily recognizable in the debug.log
						$namespace = strtoupper($namespace);
					}

					//append a colon and a space
					$namespace .= ': ';

					//if the message is an object or an array
					if(is_array($message) || is_object($message)) {
						//print out the object or array structure
						error_log($namespace . print_r($message, true));
					//if it isn't
					} else {
						//just echo out the message
						error_log($namespace . $message);
					}
				}
			}

		}

	}
