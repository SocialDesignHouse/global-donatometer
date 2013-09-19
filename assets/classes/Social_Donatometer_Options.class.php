<?php

	if(!class_exists('Social_Donatometer_Options')) {

		//options for our plug-in
		class Social_Donatometer_Options {

			//IMPORTANT: Update the version number here whenever you release a new version
			public $v_num = '0.0.1';

			//prefix for option names, table names, and capability names
			public $prefix = 'social_donatometer_';

			//namespace for any Debug messages
			public $namespace = 'SOCIAL DONATOMETER';

			//initialize vars for options, and capabilities
			public $opts;
			public $caps;

			//initialize options
			public function __construct() {
				$this->set_options();
				$this->set_capabilities();
			}

			//set up options array
			private function set_options() {
				$this->opts = array(
					$this->prefix . 'version' => $this->v_num,
					$this->prefix . 'options' => array(
						'goal' => '0.00',
						'amount' => '0.00',
						'start_date' => '0000-00-00',
						'end_date' => '0000-00-00',
						'success' => 'Thanks for your support!',
						'active' => 0,
						'last_update' => 0000-00-00,
						'link' => '/donate/',
						'button' => 'Donate'
					),
					$this->prefix . 'display' => array(
						'css' => 1,
						'position' => 'bottom',
						'raised_template' => '$%raised_format% of $%goal_format% goal',
						'success_template' => 'Thanks to your support we raised %raised_format%!',
						'show' => 'none',
						'tickmarks' => 0
					)
				);
			}

			//set up capability array
			private function set_capabilities() {
				//add capabilities to this array as 'required_capability' => 'capability_to_grant'
				$this->caps = array(
					'manage_options' => array(
						$this->prefix . 'options',
						$this->prefix . 'display'
					)
				);
			}

		}

	}
