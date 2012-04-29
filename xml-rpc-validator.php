<?php

/*
Plugin Name: XML-RPC Validator
Version: 0.1
Description: 
Author: Danilo E
Author URI: 
Plugin URI:
*/

//TODO remove these lines in production
//ini_set("display_errors", TRUE); 
/*enabling logging of errors*/
ini_set("log_errors", TRUE);
ini_set('display_startup_errors',TRUE);
error_reporting(E_ALL);

global $wp_version;
$exit_msg='XML-RPC validator requires WordPress 3.0 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update!</a>';
if (version_compare($wp_version,"3.0","<"))
{
	exit ($exit_msg);
}

/**
 * global constant for the plugin directory
 */
define( 'XMLRPC_VALIDATOR_PLUGIN_DIR', dirname( __FILE__ ) );
define( 'XMLRPC_VALIDATOR__PLUGIN_URL', plugins_url() . '/' . wp_basename( dirname( __FILE__ ) ) );

require_once 'commons.php';

function get_custom_page_template($single_template) {
	$options = get_option('xml_rpc_validator');
	// get our pageId
	$pageId = $options['pageId'];
	if (get_the_ID() === $pageId) {
		$single_template = dirname( __FILE__ ) . '/page.php';
	}
	return $single_template;
}
add_filter( 'page_template', "get_custom_page_template" ) ;

register_activation_hook(__FILE__, 'xml_rpc_validator_activation');
register_deactivation_hook(__FILE__, 'xml_rpc_validator_deactivation');
function xml_rpc_validator_deactivation()
{
	$options = get_option('xml_rpc_validator');
	// get our  pageId
	$pageId=$options['pageId'];
	// check if the actual post exists
	$actual_post = get_post($pageId);
	// check if the page is already created
	if (!$pageId || !$actual_post || ($pageId!=$actual_post->ID))
	{
		//the page doesn't exists anymore
	} else {
		wp_update_post(array('ID' => $pageId, 'post_status' => 'draft'));
	}
}
function xml_rpc_validator_activation()
{
	$options = get_option('xml_rpc_validator');
	// get our  pageId
	$pageId=$options['pageId'];
	// check if the actual post exists
	$actual_post=get_post($pageId);
	// check if the page is already created
	if (!$pageId || !$actual_post || ($pageId!=$actual_post->ID))
	{
		// create the page and save it's ID
		$options['pageId'] = xml_rpc_validator_create_main_page();
		update_option('xml_rpc_validator', $options);
	} else {
		//update the page status to publish. just a check.
		wp_update_post(array('ID' => $pageId, 'post_status' => 'publish'));
	}
}

function xml_rpc_validator_create_main_page()
{
	// create post object
	class mypost
	{
		var $post_title;
		var $post_content;
		var $post_status;
		var $post_type; // can be 'page' or 'post'
		var $comment_status; // open or closed for commenting
	}
	// initialize the post object
	$mypost = new mypost();
	// fill it with data
	$mypost->post_title = 'XML-RPC Validator';
	$mypost->post_content = '';
	$mypost->post_status = 'publish';
	$mypost->post_type = 'page';
	$mypost->comment_status = 'closed';
	// insert the post and return it's ID
	return wp_insert_post($mypost);
}

?>