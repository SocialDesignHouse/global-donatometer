<?php
/*
Plugin Name: Social Donatometer
Plugin URI: https://github.com/SocialDesignHouse/global-donatometer
Description: A global donation thermometer for WordPress sites
Version: 0.0.1
Author: Eric Allen of Social Design House
Author URI: http://socialdesignhouse.com/
License: MIT
*/

	// GLOBAL PATHS

	//this is the plug-in directory name
	if(!defined("SOCIAL_DONATOMETER")) {
		define("SOCIAL_DONATOMETER", trim(dirname(plugin_basename(__FILE__)), '/'));
	}

	//this is the path to the plug-in's directory
	if(!defined("SOCIAL_DONATOMETER_DIR")) {
		define("SOCIAL_DONATOMETER_DIR", WP_PLUGIN_DIR . '/' . SOCIAL_DONATOMETER);
	}

	//this is the url to the plug-in's directory
	if(!defined("SOCIAL_DONATOMETER_URL")) {
		define("SOCIAL_DONATOMETER_URL", WP_PLUGIN_URL . '/' . SOCIAL_DONATOMETER);
	}

	// CLASSES

	//options
	include_once(SOCIAL_DONATOMETER_DIR . '/assets/classes/Social_Donatometer_Options.class.php');
	//logic
	include_once(SOCIAL_DONATOMETER_DIR . '/assets/classes/Social_Donatometer.class.php');

	//if classes exist
	if(class_exists('Social_Donatometer_Options') && class_exists('Social_Donatometer')) {
		$donatometer_options = new Social_Donatometer_Options();

		$donatometer = new Social_Donatometer($donatometer_options);

		register_activation_hook(__FILE__, array($donatometer, 'activate'));

		register_deactivation_hook(__FILE__, array($donatometer, 'deactivate'));

		add_action('wp_dashboard_setup', array($donatometer, 'widget'));

		add_action('admin_enqueue_scripts', array($donatometer, 'widget_scripts'));

		add_action('wp_enqueue_scripts', array($donatometer, 'styles'));

		add_action('wp_footer', array($donatometer, 'show'), 10);

		add_action('admin_menu', array($donatometer, 'admin_page'));
	}
