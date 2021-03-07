<?php

/**
 * Plugin Name: Custom search for custom post type
 * Description: A simple plugin providing custom search features for a given custom post type
 * Version: 0.9
 * Author: Davide Longo
 * Author URI: https://davidelongo.net/
 */

require_once('Default.class.php');
add_action('plugins_loaded', array('david1\CustomSearchCustomPostType', 'init'));