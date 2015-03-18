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
				
	$site_url =  isset( $_REQUEST['site_url'] ) ? esc_url( $_REQUEST['site_url'] ) : 'http://';
?>
	<form name="loginform" id="loginform" action="#" method="post" onSubmit="return xml_rpc_validator.check_url();">
		<?php echo $nonce_content; ?>
		<div class="tipcontainer"><span class="errortipwrap"><span class="errortiptext">Please Try Again</span></span></div>
		<span id="url-loading" class="loading" style="display: none;"></span>
		<p>
			<label for="site_url" title="Address of site to Validate">Address:</label>
			<input type='text' name='site_url' id='site_url' class='input' value='<?php echo $site_url; ?>' size='30' tabindex='10'/>		
		</p>
		<br/>
		<!-- a href="" id="xmlrpc_validator_advanced_settings_switcher" onclick="xml_rpc_validator.toggle_advanced_settings( ); return false;">More Options</a -->
		<fieldset id="xmlrpc_validator_advanced_settings" style="margin-top:10px;">
			<p>
			<label for="user_agent_selection"><?php _e('User Agent'); ?></label>
				<select id="user_agent_selection">
				  <?php if (!isset($_REQUEST['user_agent'])) { ?>
				  <option value="custom">Custom User Agent</option>
				  <option selected="selected" value="WordPress XML-RPC Client">WordPress XML-RPC Client</option>
				  <?php } else { ?>
				  <option selected="selected" value="">Custom User Agent</option>
				  <option value="WordPress XML-RPC Client">WordPress XML-RPC Client</option>
                  <?php } ?>
				  <option value="wp-android/2.6.4 (Android 4.3; en_US; samsung GT-I9505/jfltezh)">WordPress for Android</option>
				  <option value="wp-iphone/4.8.1 (iPhone OS 8.1.3, iPad) Mobile">WordPress for iOS</option>
				  <option value="wp-blackberry/1.6">WordPress for BlackBerry</option>
				  <option value="wp-nokia/1.0">WordPress for Nokia</option>
				  <option value="wp-windowsphone/1.5">WordPress for Windows Phone7</option>
				</select>
				<?php if (!isset($_REQUEST['user_agent'])) { ?>
					<input type='text' name='user_agent' id='user_agent' class='input' value='WordPress XML-RPC Client/1.1' size='70' tabindex='10'/>
				<?php } else { ?>
					<input type='text' name='user_agent' id='user_agent' class='input' value='<?php echo esc_attr($_REQUEST['user_agent']); ?>' size='70' tabindex='10'/>
				<?php } ?>
			</p>
			<br/>
			<p>
				<label for="enable_401_auth"><?php _e('Enable HTTP Auth'); ?></label>
				<input type="checkbox" name="enable_401_auth" value="yes" />
			</p>
			<p style="margin-top:10px">
				<label for="HTTP_auth_user_login"><?php _e('Username'); ?></label>
				<input type="text" name="HTTP_auth_user_login" id="HTTP_auth_user_login" class="input" value="" size="20" tabindex="200" />
			</p>
			<p style="margin-top:10px;margin-bottom:10px;">
				<label for="HTTP_auth_user_pass"><?php _e('Password'); ?></label>
				<input type="password" name="HTTP_auth_user_pass" id="HTTP_auth_user_pass" class="input" value="" size="20" tabindex="210" />
			</p>
		</fieldset>
		
		<p class="submit_button">
			<input type="submit" name="wp-submit" id="wp-submit" value="<?php esc_attr_e('Check');?>" />
		</p>
		
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
			//Set the UserAgent
			$user_agent_selected = esc_attr( $_REQUEST['user_agent'] );
			$client -> setUserAgent($user_agent_selected);
			//Enable the HTTP Auth if selected
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
				?><p class="tick"style="margin-bottom:10px;"><b>Congratulation! Your site passed the first check.</b>
				<br /><br />You can add the blog within the mobile app using the following URL: <em><?php echo ($xmlrpcEndpointURL); ?></em>
				</p>
				<form name="credentialInfoform" id="credentialInfoform" action="#" method="post">
					<p>Please insert your credentials below to start a deep test of the blog. (Credentials will not be stored or sent to 3rd party sites)</p>
					<?php if ( function_exists('wp_nonce_field') )	echo wp_nonce_field('checkstep2', 'name_of_nonce_field_checkstep2',true, false); ?>
					<p style="margin-top:10px">
						<label for="user_login"><?php _e('Username'); ?></label>
						<input type="text" name="user_login" id="user_login" class="input" value="" size="20" tabindex="20" />
					</p>
					<p style="margin-top:10px;margin-bottom:10px;">
						<label for="user_pass"><?php _e('Password'); ?></label>
						<input type="password" name="user_pass" id="user_pass" class="input" value="" size="20" tabindex="30" />
					</p>
					<p class="submit_button">
						<input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="<?php esc_attr_e('Check'); ?>" onClick="return xml_rpc_validator.check_credentials();" tabindex="1000" />
					</p>

					<input type="hidden" name="site_url" value="<?php echo $site_url; ?>"/>
					<input type="hidden" name="xmlrpc_url" value="<?php echo ($xmlrpcEndpointURL); ?>"/>
					<input type="hidden" name="action" value="check_step2"/>
					<input type="hidden" name="user_agent" value="<?php echo ($user_agent_selected); ?>"/>
		
				<?php if ( $enable_401_auth ) { ?>
					<input type="hidden" name="enable_401_auth" value="yes" />
					<input type="hidden" name="HTTP_auth_user_login" value="<?php esc_attr_e($_REQUEST['HTTP_auth_user_login']); ?>" />
					<input type="hidden" name="HTTP_auth_user_pass" value="<?php esc_attr_e($_REQUEST['HTTP_auth_user_pass']); ?>" />
				<?php } ?>
				</form>
			<?php } ?>
		<?php 
		endif; 
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
		//Set the UserAgent
		$user_agent_selected = esc_attr( $_REQUEST['user_agent'] );
		$client -> setUserAgent($user_agent_selected);
		//Enable HTTP Auth if selected
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
					echo '<p style="margin-top:10px"><input type="radio" name="single_site_xmlrpc_url" value="'.$blog['xmlrpc'].'"> '.$blog['blogName'].' - '.$blog['xmlrpc'].'</input></p>';
				}//end foreach
				?>
				<input type="hidden" name="user_login" id="user_login" value="<?php esc_attr_e($_REQUEST['user_login']); ?>"/>
				<input type="hidden" name="user_pass" id="user_pass" value="<?php esc_attr_e($_REQUEST['user_pass']); ?>"/>
				<input type="hidden" name="site_url" value="<?php echo esc_url( $site_url ); ?>"/>
				<input type="hidden" name="xmlrpc_url" value="<?php echo esc_url($xmlrpc_url); ?>"/>
				<input type="hidden" name="user_agent" id="user_agent" value="<?php echo ($user_agent_selected); ?>"/>
				
				<?php if ($enable_401_auth){ ?>
					<input type="hidden" id="enable_401_auth" name="enable_401_auth" value="yes" />
					<input type="hidden" id="HTTP_auth_user_login" name="HTTP_auth_user_login" value="<?php esc_attr_e($_REQUEST['HTTP_auth_user_login']); ?>" />
					<input type="hidden" id="HTTP_auth_user_pass" name="HTTP_auth_user_pass" value="<?php esc_attr_e($_REQUEST['HTTP_auth_user_pass']) ?>" />
				<?php } ?>
				<p class="submit_button">
				<input type="submit" name="xml_rpc_single_site_form-submit" id="xml_rpc_single_site_form-submit" class="button-primary" value="<?php esc_attr_e('Check') ?>" tabindex="1000"/>
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
						
					<!-- just to test 
					<p>Another Round? <a href="#" onclick="jq('#xml_rpc_single_site_form').fadeIn('fast'); jq('#xmlrpc_validator_ajax_calls_response').fadeOut('fast');">yes</a>
					-->	
				</div>
			<?php 
			}
		}
	endif;
	
endif; ?>
</div> 
<?php include( dirname(__FILE__) . '/footer.php' );  ?>