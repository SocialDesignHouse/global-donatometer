<?php

	//uninstallation
	if(!defined('WP_UNINSTALL_PLUGIN')) {
		exit();
	} else {
		if(!class_exists('Social_Donatometer_Options')) {
			include_once('./assets/classes/Social_Donatometer_Options.class.php');
		}

		if(!class_exists('Social_Donatometer')) {
			include_once('./assets/classes/Social_Donatometer.class.php');
		}

		$donatometer_options = new Social_Donatometer_Options();

		$donatometer = new Social_Donatometer($donatometer_options);

		$donatometer->uninstall();
	}