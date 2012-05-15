var jq = jQuery;

// setup everything when document is ready
jq(document).ready(function($) {
	jq('#xml_rpc_single_site_form-submit').click( xml_rpc_validator.start_ajax_calls );
});


var xml_rpc_validator = {
		request: false,
		xml_rpc_test_calls : [ 
		    {'div_id': "check_wp_version", 'xmlrpc_call':"check_wp_version"},
			{'div_id': "get_options", 'xmlrpc_call':"wp.getOptions"},
			{'div_id': "get_post_formats", 'xmlrpc_call':"wp.getPostFormats"},
			{'div_id': "get_categories", 'xmlrpc_call':"wp.getCategories"}, 
			{'div_id': "get_tags", 'xmlrpc_call':"wp.getTags"},
			{'div_id': "get_post_status_list", 'xmlrpc_call':"wp.getPostStatusList"},
			{'div_id': "get_comments", 'xmlrpc_call':"wp.getComments"},
			{'div_id': "get_page_list", 'xmlrpc_call':"wp.getPageList"},
			{'div_id': "upload_picture", 'xmlrpc_call':"metaWeblog.newMediaObject"},
		],
		current_call_index : 0,
		
		init: function() {
			this.unload(); // Unbind any previous bindings.
		},
		unload: function() {
			
		},	
		show_log : function( ) {
			jq( '#xmlrpc_validator_main' ).hide( );
			jq( '#xmlrpc_validator_log' ).show( );
			jq( '#action-main' ).removeClass('active');
			jq( '#action-log' ).addClass('active');	
		},
		show_main : function( ) {
			jq( '#xmlrpc_validator_main' ).show( );
			jq( '#xmlrpc_validator_log' ).hide( );
			jq( '#action-main' ).addClass('active');
			jq( '#action-log' ).removeClass('active');			
		},
		toggle_advanced_settings : function( ) {
			jq('#xmlrpc_validator_advanced_settings' ).fadeToggle(function() {
				if( jq('#xmlrpc_validator_advanced_settings').is(':visible') ) {
				    // it's visible, do something
					jq('#xmlrpc_validator_advanced_settings_switcher' ).text( 'Hide Connection Settings');
				}
				else {
				    // it's not visible so do something else
					jq('#xmlrpc_validator_advanced_settings_switcher' ).text( 'Show Connection Settings');
				}
		  }
			);
		},
		check_url : function ( ) {
			var myVariable = jq('#site_url').val();
			if(/^([a-z]([a-z]|\d|\+|-|\.)*):(\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?((\[(|(v[\da-f]{1,}\.(([a-z]|\d|-|\.|_|~)|[!\$&'\(\)\*\+,;=]|:)+))\])|((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=])*)(:\d*)?)(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*|(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)|((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)|((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)){0})(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(myVariable)) {
			  {
				  jq( '#url-loading' ).show(  );
				  jq('span.errortipwrap').fadeOut(150);
				  return true;
			  }
			} else {
				jq( '.errortiptext' ).text( 'Invalid URL' );
				jq( 'span.errortipwrap' ).fadeIn( 'fast' );
				return false;
			}
		},
		check_credentials : function (  ) {
			var user_login = jq('#user_login').val();
			var user_pass = jq('#user_pass').val();
			if ( user_pass.length > 0 && user_pass.trim() != "" &&  user_login.length > 0 && user_login.trim() != "")
				return true;
			else {
				alert('Please, insert the Credentials...');
				return false;
			}
		},
		start_ajax_calls : function( ) {
			jq('.running').removeClass('running').addClass('wait');
			jq('.tick').removeClass('tick').addClass('wait');
			jq('.cross').removeClass('cross').addClass('wait');
			jq('.xml_rpc_error').hide().text('');
			
			//clean the log div
			jq('#xmlrpc_validator_log').text('');
			 
			if ( typeof xml_rpc_validator.request == 'object' )
				xml_rpc_validator.request.abort();
			
			jq('#xml_rpc_single_site_form').fadeOut('fast');
			jq('#xmlrpc_validator_ajax_calls_response').fadeIn('fast');
			xml_rpc_validator.current_call_index = 0;
			xml_rpc_validator.make_ajax_call( );
			},
			
		make_ajax_call : function( ) {

			var arrayAssoc = xml_rpc_validator.xml_rpc_test_calls;
			if(xml_rpc_validator.current_call_index >= arrayAssoc.length) 
				return;
			
			var call_obj = arrayAssoc[xml_rpc_validator.current_call_index];
			jq('#'+call_obj['div_id']).removeClass('wait').addClass('running');
			var url = jq('input:radio[name=single_site_xmlrpc_url]:checked').val();
			
			xml_rpc_validator.request = jq.ajax({
				    type: "POST",
				    url: XML_RPC_Setting.plugin_url + 'xml-rpc-validator-ajax.php',
				    timeout: 30000,				    
				    data: {
				    	xmlrpc_url: url,
				        method_name : call_obj['xmlrpc_call'],
				        _ajax_nonce: XML_RPC_Setting.nonce,
				        user_login: jq( '#user_login').val(),
						user_pass: jq( '#user_pass').val(),
						enable_401_auth: jq( '#enable_401_auth').val(),
						HTTP_auth_user_login: jq( '#HTTP_auth_user_login').val(),
						HTTP_auth_user_pass: jq( '#HTTP_auth_user_pass').val()
				    },
				    success: function(msg) {
				    	var obj = jQuery.parseJSON(msg);
				    	$old_log = jq('#xmlrpc_validator_log').html();
				    	var call_obj = arrayAssoc[xml_rpc_validator.current_call_index];
				    	$current_log_msg = '';
				    	
				    	if( 'ok' == obj[0] ) {
							jq('#'+call_obj['div_id']).removeClass('running').addClass('tick');
							$current_log_msg = obj[1];
				        } else {
							jq('#'+call_obj['div_id']).removeClass('running').addClass('cross');
							jq('#xml_rpc_error_'+call_obj['div_id']).html(obj[1]).show();
							$current_log_msg = obj[2];
				        }
				    	
				    	jq('#xmlrpc_validator_log').html( $old_log + $current_log_msg ); //writes the full log
				        xml_rpc_validator.current_call_index++;
				        xml_rpc_validator.make_ajax_call( );
				    },
				    error: function(msg) {
				    	//console.log('Error: ' + msg.responseText);
				    	var call_obj = arrayAssoc[xml_rpc_validator.current_call_index];
						jq('#'+call_obj['div_id']).removeClass('running').addClass('cross');
						
						jq('#xml_rpc_error_'+call_obj['div_id']).html(msg).show();

						$old_log = jq('#xmlrpc_validator_log').html();
						jq('#xmlrpc_validator_log').html( $old_log + $current_log_msg ); //writes the full log
						
						xml_rpc_validator.current_call_index++;
						xml_rpc_validator.make_ajax_call( );
				    }
				});		
			},
}


/*
<script type="text/javascript" >
jQuery(document).ready(function($) {

	var data = {
		action: 'my_special_action',
		whatever: 1234
	};

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery.post(ajaxurl, data, function(response) {
		alert('Got this from the server: ' + response);
	});
});
</script>
*/
		