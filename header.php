<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
<title><?php echo wp_title(); ?></title>
<?php
$myStyleFile = constant( 'XMLRPC_VALIDATOR__PLUGIN_URL' ).'/xml-rpc-validator.css';
wp_register_style('xml_rpc_validator_stylesheet', $myStyleFile);
wp_enqueue_style( 'xml_rpc_validator_stylesheet');

// create a nonce
$nonce = wp_create_nonce('xml-rpc-ajax-nonce');
wp_enqueue_script('jquery');
wp_enqueue_script('xml-rpc-validator-script', constant( 'XMLRPC_VALIDATOR__PLUGIN_URL' ).'/xml-rpc-validator.js', array('jquery'));
// pass parameters to JavaScript
wp_localize_script('xml-rpc-validator-script', 'XML_RPC_Setting', array('plugin_url' => constant( 'XMLRPC_VALIDATOR__PLUGIN_URL' ).'/', 'nonce' => $nonce));

if ( $ua_info->is_blackbeberry() ){ ?>
<!-- detected a BB device -->
<meta name="HandheldFriendly" content="true" />
<?php
} elseif ( $ua_info->is_iphone_or_ipod() || $ua_info->is_android() || $ua_info->is_opera_mobile() ) { ?>
<!-- detected an HighEnd device -->
<?php 	} elseif ($ua_info->is_WindowsPhone7()) { ?>
<!-- detected a Win7 device -->
<?php } 
wp_head(); 
?>
</head>
<body <?php body_class(); ?>>
	<div id="banner">
		<h1 id="title">
			<a href="<?php echo get_permalink( get_the_ID() ); ?>"><span>WordPress XML-RPC Validation Service</span> </a>
		</h1>
		<p id="tagline">Check the XML-RPC Endpoint of your site</p>
	</div>
	<div id="content">