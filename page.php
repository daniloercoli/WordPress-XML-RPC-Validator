<?php
include( dirname(__FILE__) . '/header.php' );

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'home';
// validate action so as to default input screen
if ( ! in_array( $action, array( 'home', 'check_step1', 'check_step2' ), true ) )
	$action = 'home';

?> <div id="xmlrpc_validator_main"> <?php
 	
if ( 'home' == $action ) :

	if ( function_exists('wp_nonce_field') )
		$nonce_content = wp_nonce_field('checkstep1', 'name_of_nonce_field_checkstep1',true, false);
	else
		$nonce_content = '';
				
	$site_url =  isset( $_REQUEST['site_url'] ) ? esc_url ( $_REQUEST['site_url'] ) : 'Enter a blog URL';	
?>
	<form name="loginform" id="loginform" action="#" method="post" onSubmit="return xml_rpc_validator.check_url();">
		<?php echo $nonce_content; ?>
		<div class="tipcontainer"><span class="errortipwrap"><span class="errortiptext">Please Try Again</span></span></div>
		<span id="url-loading" class="loading" style="display: none;"></span>
		<input type='text' name='site_url' id='site_url' class='input' value='<?php echo /*$site_url;*/ 'http://localhost/wordpress31rc3/' ?>' size='30' tabindex='10' onclick="if ( this.value == 'Enter a blog URL' ) { this.value = 'http://'; }" onblur="if ( this.value == '' || this.value == 'http://' ) { this.value = 'Enter a blog URL'; }" />		
		<input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="<?php esc_attr_e('Check');?>" />
		<br />
		<a href="" id="xmlrpc_validator_advanced_settings_switcher" onclick="xml_rpc_validator.toggle_advanced_settings( ); return false;">Show Connection Settings</a>
		<fieldset id="xmlrpc_validator_advanced_settings" style="display:none"><legend>Http Authentication</legend>
			<p><label><?php _e('Enable HTTP Auth'); ?>
			<input type="checkbox" name="enable_401_auth" value="yes" /></label></p>
			<p><label><?php _e('Username'); ?><br />
			<input type="text" name="HTTP_auth_user_login" id="HTTP_auth_user_login" class="input" value="" size="20" tabindex="200" />
			</label>
			</p>
			<p><label><?php _e('Password'); ?><br />
			<input type="password" name="HTTP_auth_user_pass" id="HTTP_auth_user_pass" class="input" value="" size="20" tabindex="210" />
			</label>
			</p>
		</fieldset>
		<input type="hidden" name="action" value="check_step1"/>
	</form>
<?php 
elseif ( 'check_step1' == $action ) : //2nd page
		$permalink = get_permalink( get_the_ID() );
		$pre_check_error_message = null;
		if ( !wp_verify_nonce($_REQUEST['name_of_nonce_field_checkstep1'], 'checkstep1') ) {
			$pre_check_error_message = "Sorry, your nonce did not verify.";
		} else {
			//check the URL here
			$unescaped_site_url = isset( $_REQUEST['site_url'] ) ? $_REQUEST['site_url'] : '';
			
			if ( empty( $unescaped_site_url ) ) {
				$pre_check_error_message = __( 'Please enter a blog URL' );
			} elseif ( 'Enter a blog URL' == $unescaped_site_url || 'http://' == $unescaped_site_url ) {
				$pre_check_error_message = __( 'Please enter a blog URL' );
			} elseif ( ! empty( $unescaped_site_url ) ) {
				$base_url = preg_replace( '|^(?:https?://)?([^/]+).*$|i', '$1', $unescaped_site_url );
				/* Check this is a valid URL */
				if ( ! preg_match( '/^[-.a-z0-9]+$/i', $base_url ) ) {
					$pre_check_error_message = __( 'invalid_url' );
				}
			}
		}

		if ( ! empty( $pre_check_error_message ) ) :
			?><p class="cross"><?php echo $pre_check_error_message; ?></p>
			<?php 
		else :
			$client = new Blog_Validator( esc_url_raw( $_REQUEST['site_url'] ) );
			$site_url = esc_url($unescaped_site_url);
		
			$enable_401_auth= ! empty( $_REQUEST['enable_401_auth'] );
			if($enable_401_auth) {
				xml_rpc_validator_logIO("O", "HTTP auth enabled");
				$HTTP_auth_user_login = stripslashes( $_REQUEST['HTTP_auth_user_login'] );
				$HTTP_auth_user_pass = stripslashes( $_REQUEST['HTTP_auth_user_pass'] );
				$client -> setHTTPCredential( $HTTP_auth_user_login, $HTTP_auth_user_pass );
			}
		
			$xmlrpcEndpointURL = $client->find_and_validate_xmlrpc_endpoint();
				
			if( is_wp_error( $xmlrpcEndpointURL ) ) {
				?><p class="cross">Failed to check your site at <?php echo $site_url; ?> because of the following error: </p>
				<?php echo $xml_rpc_validator_utils->printErrors($xmlrpcEndpointURL); 
			} else {
				$xmlrpcEndpointURL = esc_url($xmlrpcEndpointURL);
				?><p class="tick">Congratulation! Your site passed the first check.
				<br />You can add the blog within the mobile app using the following URL: <em><?php echo ($xmlrpcEndpointURL); ?></em></p>
				<form name="credentialInfoform" id="credentialInfoform" action="#" method="post">
					<p>Please insert your credentials below to start a deep test on the blog. (Credentials will not be stored or sent to 3rd party sites)</p>
					<?php if ( function_exists('wp_nonce_field') )	echo wp_nonce_field('checkstep2', 'name_of_nonce_field_checkstep2',true, false); ?>
					<label><?php _e('Username'); ?>
					<input type="text" name="user_login" id="user_login" class="input" value="" size="20" tabindex="20" /></label>
					<br />
					<label><?php _e('Password'); ?>
					<input type="password" name="user_pass" id="user_pass" class="input" value="" size="20" tabindex="30" /></label>
					
					<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="<?php esc_attr_e('Check'); ?>" tabindex="1000" /></p>
					<input type="hidden" name="site_url" value="<?php echo $site_url; ?>"/>
					<input type="hidden" name="xmlrpc_url" value="<?php echo ($xmlrpcEndpointURL); ?>"/>
					<input type="hidden" name="action" value="check_step2"/>
		
				<?php if ( $enable_401_auth ) { ?>
					<input type="hidden" name="enable_401_auth" value="yes" />
					<input type="hidden" name="HTTP_auth_user_login" value="<?php esc_attr_e($_REQUEST['HTTP_auth_user_login']); ?>" />
					<input type="hidden" name="HTTP_auth_user_pass" value="<?php esc_attr_e($_REQUEST['HTTP_auth_user_pass']); ?>" />
				<?php } ?>
				</form>
			<?php } ?>
		<?php 
		endif; 
		?><a href="<?php echo $permalink; ?>">Home</a> <?php 
		
elseif ( 'check_step2' == $action ) : //3rd page 
	$permalink = get_permalink( get_the_ID() );
	
	if ( !wp_verify_nonce($_REQUEST['name_of_nonce_field_checkstep2'], 'checkstep2') ) :
		echo ('Sorry, your nonce did not verify.');
	else :	
		$site_url = isset($_REQUEST['site_url']) ? $_REQUEST['site_url'] : '';
		$xmlrpc_url = isset($_REQUEST['xmlrpc_url']) ? $_REQUEST['xmlrpc_url'] : '';
	
		$client = new Blog_Validator( esc_url_raw ( $site_url ) );
		$client->xmlrpc_endpoint_URL = esc_url_raw ( $xmlrpc_url );
		$client->setWPCredential( $_REQUEST['user_login'], $_REQUEST['user_pass'] );
	
		$enable_401_auth = ! empty( $_REQUEST['enable_401_auth'] );
		if($enable_401_auth) {
			xml_rpc_validator_logIO("O", "HTTP auth enabled");
			$client -> setHTTPCredential( $_REQUEST['HTTP_auth_user_login'], $_REQUEST['HTTP_auth_user_pass'] );
		}
	
		$basicCallsRes = $client->getUsersBlogs();
	
		if( is_wp_error( $basicCallsRes ) ) {
			echo $xml_rpc_validator_utils->printErrors($basicCallsRes);
		} else {
			if( ! empty($client->userBlogs)) {
				?>
				<form name="xml_rpc_single_site_form" id="xml_rpc_single_site_form" action="#" method="post" onsubmit="return false;">
				<p>Please select the blog you wanna test:</p>	
				<?php foreach ($client->userBlogs as $blog) {
					echo '<input type="radio" name="single_site_xmlrpc_url" value="'.$blog['xmlrpc'].'">'.$blog['blogName'].' - '.$blog['xmlrpc'].'</input></br>';
				}//end foreach
				?>
				<input type="hidden" name="user_login" id="user_login" value="<?php esc_attr_e($_REQUEST['user_login']); ?>"/>
				<input type="hidden" name="user_pass" id="user_pass" value="<?php esc_attr_e($_REQUEST['user_pass']); ?>"/>
				<input type="hidden" name="site_url" value="<?php echo esc_url( $site_url ); ?>"/>
				<input type="hidden" name="xmlrpc_url" value="<?php echo esc_url($xmlrpc_url); ?>"/>
				<?php if ($enable_401_auth){ ?>
					<input type="hidden" id="enable_401_auth" name="enable_401_auth" value="yes" />
					<input type="hidden" id="HTTP_auth_user_login" name="HTTP_auth_user_login" value="<?php esc_attr_e($_REQUEST['HTTP_auth_user_login']); ?>" />
					<input type="hidden" id="HTTP_auth_user_pass" name="HTTP_auth_user_pass" value="<?php esc_attr_e($_REQUEST['HTTP_auth_user_pass']) ?>" />
				<?php } ?>
				<p class="submit">
				<input type="submit" name="xml_rpc_single_site_form-submit" id="xml_rpc_single_site_form-submit" 
				class="button-primary" value="<?php esc_attr_e('Check') ?>" tabindex="1000"/>
				</p>
				</form>
				<div id="xmlrpc_validator_ajax_calls_response" style="display:none">
						<p id="check_wp_version" class="wait">WordPress Version</p>
						<p id="xml_rpc_error_check_wp_version" class="xml_rpc_error" style="display:none"></p>
				
						<p id="get_options" class="wait">wp.getOptions</p>
						<p id="xml_rpc_error_get_options" class="xml_rpc_error" style="display:none"></p>
						
						<p id="get_post_formats" class="wait">wp.getPostFormats</p>
						<p id="xml_rpc_error_get_post_formats" class="xml_rpc_error" style="display:none"></p>
						
						<p id="get_categories" class="wait">wp.getCategories</p>
						<p id="xml_rpc_error_get_categories" class="xml_rpc_error" style="display:none"></p>
						
						<p id="get_tags" class="wait">wp.getTags</p>
						<p id="xml_rpc_error_get_tags" class="xml_rpc_error" style="display:none"></p>
						
						<p id="get_post_status_list" class="wait">wp.getPostStatusList</p>
						<p id="xml_rpc_error_get_post_status_list" class="xml_rpc_error" style="display:none"></p>
						
						<p id="get_comments" class="wait">wp.getComments</p>
						<p id="xml_rpc_error_get_comments" class="xml_rpc_error" style="display:none"></p>
						
						<p id="get_page_list" class="wait">wp.getPageList</p>
						<p id="xml_rpc_error_get_page_list" class="xml_rpc_error" style="display:none"></p>
						
						<p id="upload_picture" class="wait">metaWeblog.newMediaObject</p>
						<p id="xml_rpc_error_upload_picture" class="xml_rpc_error" style="display:none"></p>
						
					<!-- just to test -->
					<p>Another Round? <a href="#" onclick="jq('#xml_rpc_single_site_form').fadeIn('fast'); jq('#xmlrpc_validator_ajax_calls_response').fadeOut('fast');">yes</a>	
				</div>
			<?php 
			}
		}
		?><a href="<?php echo $permalink; ?>">Home</a> <?php 
	endif;
	
endif; ?>
</div> 
<div id="xmlrpc_validator_log" style="display: none">
<?php echo $xml_rpc_validator_utils->show_log_on_video( ); ?>
</div>
<?php include( dirname(__FILE__) . '/footer.php' );  ?>