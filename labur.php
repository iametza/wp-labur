<?php
/*
Plugin Name: Labur Wordpress Plugin
Plugin URI: https://wordpress.org/plugins/wp-labur
Description: Labur is a quick, modern, and open-source link shortener. This plugin allows you to use https://labur.eus service in Wordpress.
Version: 1.0.1
Author: Egoitz Gonzalez
Author URI: http://www.egoitzgonzalez.com
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl.html
Text Domain: wp-labur
Domain Path: /languages
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// === INCLUDES === //

include( plugin_dir_path( __FILE__ ) . 'admin/labur-metabox.php');
include( plugin_dir_path( __FILE__ ) . 'admin/admin-all-posts-page.php');
include( plugin_dir_path( __FILE__ ) . 'admin/admin-menu.php');
include( plugin_dir_path( __FILE__ ) . 'admin/plugins-page.php');
include( plugin_dir_path( __FILE__ ) . 'admin/settings-page.php');

// === //



// Set up to internationalize the plugin
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'labur_plugin_add_settings_link' ); // admin/plugins-page.php


/**
  * @wp-hook plugins_loaded
	* Set to internationalize the plugin
	*/

function labur_plugin_load_textdomain() {

	$text_domain	= 'wp-labur';
	$path_languages = basename(dirname(__FILE__)).'/languages/'; // basename(dirname(__FILE__)) relative url. This function extracts the name of the plugin directory. In this case, Labur.

 	load_plugin_textdomain($text_domain, false, $path_languages );
}
add_action('plugins_loaded', 'labur_plugin_load_textdomain'); // init hook is valid too



/**
  * @wp-hook add_meta_boxes
	* Load external files
	* JQuery
	* labur.js
	*/

function labur_init() {
    // registers plugin's js file. Jquery is a requirement for this script, so we specify it
		// labur-js is the name to reference polar.js file as identifile. Jquery is a requirement for labur.js file

    wp_register_script( 'labur-js', plugins_url( '/labur.js', __FILE__ ), array('jquery') );

    //load scripts
    wp_enqueue_script('jquery'); // Load jquery for labur.js
    wp_enqueue_script('labur-js'); // Load labur.js file. This file is registered as labur-js

    global $post;
    $post_id = $post->ID;
		$ajax_path = esc_url(admin_url('admin-ajax.php'));
    wp_localize_script('labur-js', 'MyAjax', array(
      'post_id' => $post_id,
      'action'=> 'labur_get_url', // labur_get_url is the action, but without the wp_ajax prefix. This acction launches labur_get_url_process function
			'ajax_path'=> $ajax_path
    ));
}
add_action( 'add_meta_boxes', 'labur_init' ); // wp hook is valid too



/**
  * @wp-hook wp_ajax_labur_get_url
	* Function to process the Ajax
	* Get post url, service api key, host url and create shortened url
	*/

function labur_get_url_process() {
    if (isset($_POST['postID']))
    {
      $post_id = intval( $_POST['postID'] );
      $post_url = esc_url(get_permalink($post_id));

      $result = laburtu($post_url);
      if ($result['status'] == 201) {
        $shortenedurl = 'https://' . $result['data']->domeinua . '/' . $result['data']->laburdura;

        // Gorde labur esteka
        update_post_meta($post_id, 'labur_shortened_url', esc_url_raw($shortenedurl));
         
        wp_send_json_success($shortenedurl);

      } else {
        wp_send_json_error($result['data']->message);

      }
    }

    exit();
}
add_action('wp_ajax_labur_get_url', 'labur_get_url_process'); // Defined labur_get_url action. This is called in labur.js file . wp_ajax is a standard prefix in Wordpress
add_action('wp_ajax_nopriv_labur_get_url', 'unauthorized');

function unauthorized() {
  wp_send_json_error('Unauthorized.', 401);
}

function laburtu($url) {
  $api_url = esc_url('https://labur.eus/api/v1/laburtu');
  $api_key = esc_attr(get_option('labur_settings_api_key'));

  $headers = [
    'Accept: application/json',
    'Authorization: Bearer ' . $api_key,
  ]; 
 
  $data = [
    'url' => $url
  ];

  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => $api_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POSTFIELDS => $data
  ));

  $response = curl_exec($curl);
  $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

  $response = json_decode($response);
  
  return [
    'status' => $httpcode,
    'data' => $response
  ];
}

?>
