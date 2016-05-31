<?php
if( !class_exists( 'WP_Http' ) )
include_once( ABSPATH . WPINC. '/class-http.php' );

if( !class_exists( 'UserAgentInfo' ) )
require_once( 'UserAgentInfo.php' );

include_once(ABSPATH . WPINC . '/class-IXR.php');

//Remove the generator tag
function rm_generator_filter() { return '<meta name="generator" content="Eritreo v0.1" />'; }
add_filter('the_generator', 'rm_generator_filter');

//Remove WLW and RSD Link
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rsd_link');

/**
 * disable feed
 */
function fb_disable_feed() {
	wp_die( __('No feed available, please visit our <a href="'. get_bloginfo('url') .'">homepage</a>!') );
}
add_action('do_feed', 'fb_disable_feed', 1);
add_action('do_feed_rdf', 'fb_disable_feed', 1);
add_action('do_feed_rss', 'fb_disable_feed', 1);
add_action('do_feed_rss2', 'fb_disable_feed', 1);
add_action('do_feed_atom', 'fb_disable_feed', 1);
remove_action( 'wp_head', 'feed_links_extra', 3 );
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'rsd_link' );

//Remove link rel=’prev’ and link rel=’next’ from Head
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );


define("USER_AGENT", 'WordPress XML-RPC Client');
define("REQUEST_HTTP_TIMEOUT", 30); //30 secs timeout for HTTP request

//creates the instances of common classes used later
$ua_info = new UserAgentInfo();
$xml_rpc_validator_utils = new xml_rpc_validator_utils();

$xml_rpc_validator_errors = $xml_rpc_validator_utils->xml_rpc_validator_errors; //TODO change this


class xml_rpc_validator_utils {
		
	var $validator_logging = 1;
	var $logging_on_file = 0;
	var $logging_buffer = '';
	var $xml_rpc_validator_errors = null;
	var $xml_rpc_server_errors = array(
		'401'	=>  'link to a support page, sticky forum post with steps to fix it',
		'405'	=>  'link to a support page, sticky forum post with steps to fix it',
		'412'	=>	'link to a support page, sticky forum post with steps to fix it'
	);

	function __construct() {
	$this->xml_rpc_validator_errors = array(
		'NO_RSD_FOUND'		=> array(
			'code'			=> 1000000,
			'message'		=> __('Sorry, we cannot find the RSD Endpoint link in the src code of the page. The RSD document contains the URL to the XML-RPC endpoint.'),
			'workaround'	=> 'https://apps.wordpress.org/support/#faq-ios-3'
		),
		'EMPTY_RSD'			=> array(
			'code'			=> 1000001,
			'message'		=> __('The RSD document is empty.'),
			'workaround'	=> 'https://apps.wordpress.org/support/#faq-ios-3'
		),
		'MALFORMED_RSD'		=> array(
			'code'			=> 1000002,
			'message'		=> __('The RSD document is not well formed. There are characters before the xml preamble.'),
			'workaround'	=> 'https://apps.wordpress.org/support/#faq-ios-3'
		),
		'NO_XMLRPC_IN_RSD_FOUND'		=> array(
			'code'			=> 1000004,
			'message'		=> __('We cannot find the XML-RPC Endpoint within the RSD document.'),
			'workaround'	=> 'https://apps.wordpress.org/support/#faq-ios-3'
		),
		'MISSING_XMLRPC_METHODS'		=> array(
			'code'			=> 1000005,
			'message'		=> __('The following XML-RPC methods are missing from your site: '),
			'workaround'	=> 'Please upgrade your installation of WordPress on your site.'
		),
		'XMLRPC_RESPONSE_EMPTY'		=> array(
			'code'			=> 1000006,
			'message'		=> __('The XML-RPC response document is empty.'),
			'workaround'	=> 'https://apps.wordpress.org/support/#faq-ios-4'
		),
		'XMLRPC_RESPONSE_MALFORMED_1'		=> array(
			'code'			=> 1000007,
			'message'		=> __('The XML-RPC response document is not well formed. There are characters before the xml preamble'),
			'workaround'	=> 'https://apps.wordpress.org/support/#faq-ios-4'
		),
		'XMLRPC_RESPONSE_MALFORMED_2'		=> array(
			'code'			=> 1000008,
			'message'		=> __('Parse error. The XML-RPC response document is not well formed'),
			'workaround'	=> 'https://apps.wordpress.org/support/#faq-ios-4'
		),
		'XMLRPC_RESPONSE_CONTAINS_INVALID_CHARACTERS'		=> array(
			'code'			=> 1000009,
			'message'		=> __('The XML-RPC response document contains characters outside the XML range'),
			'workaround'	=> 'https://apps.wordpress.org/support/#faq-ios-4'
		),
		'CANNOT_CHECK_WP_VERSION'	=> array(
			'code'			=> 1000010,
			'message'		=> __('Can\'t check your WordPress version'),
			'workaround'	=> 'link to a support page, sticky forum post with steps to fix it'
		),
		'NEW_WP_VERSION_AVAILABLE'	=> array(
			'code'			=> 1000011,
			'message'		=> __('<a href="http://codex.wordpress.org/Version_%1$s">WordPress %1$s</a> is available! Please update now.'),
			'workaround'	=> ''
		),
	);

	}
	
	/**
	 * xml_rpc_validator_logIO() - Writes logging info to file/screen.
	 *
	 * @uses $xmlrpc_logging
	 * @package WordPress
	 * @subpackage Logging
	 *
	 * @param string $io Whether input or output
	 * @param string $msg Information describing logging reason.
	 * @return bool Always return true
	 */
	function logIO($io,$msg) {
		if ($this->validator_logging) {
			$date = gmdate("Y-m-d H:i:s ");
			$iot = ($io == "I") ? " Input: " : " Output: ";
			
			$this->logging_buffer.= '<tr><td>'.$date.'</td><td>'.esc_html($msg).'</td></tr>';
			
			if ($this->logging_on_file) {
				$fp = fopen( constant('XMLRPC_VALIDATOR_PLUGIN_DIR').'/validator.log',"a+" );
				fwrite($fp, "\n\n".$date.$iot.$msg);
				fclose($fp);
			}
		}
		return true;
	}

	function logXML($io,$msg) {
		if ($this->validator_logging) {
			$date = gmdate("Y-m-d H:i:s ");
			$iot = ($io == "I") ? " Input: " : " Output: ";

			$this->logging_buffer.= '<tr><td>'.$date.'</td><td><pre>'.$this->xmlpp($msg, true).'</pre></td></tr>';

			if ($this->logging_on_file) {
				$fp = fopen( constant('XMLRPC_VALIDATOR_PLUGIN_DIR').'/validator.log',"a+" );
				fwrite($fp, "\n\n".$date.$iot.$msg);
				fclose($fp);
			}
		}
		return true;
	}

	/** Prettifies an XML string into a human-readable and indented work of art
	 *  @param string $xml The XML as a string
	 *  @param boolean $html_output True if the output should be escaped (for use in HTML)
	 */
	function xmlpp($xml, $html_output=false) {
		try {
			$xml_obj = new SimpleXMLElement($xml);
			$level = 4;
			$indent = 0; // current indentation level
			$pretty = array();

			// get an array containing each XML element
			$xml = explode("\n", preg_replace('/>\s*</', ">\n<", $xml_obj->asXML()));

			// shift off opening XML tag if present
			if (count($xml) && preg_match('/^<\?\s*xml/', $xml[0])) {
				$pretty[] = array_shift($xml);
			}

			foreach ($xml as $el) {
				if (preg_match('/^<([\w])+[^>\/]*>$/U', $el)) {
					// opening tag, increase indent
					$pretty[] = str_repeat(' ', $indent) . $el;
					$indent += $level;
				} else {
					if (preg_match('/^<\/.+>$/', $el)) {
						$indent -= $level;  // closing tag, decrease indent
					}
					if ($indent < 0) {
						$indent += $level;
					}
					$pretty[] = str_repeat(' ', $indent) . $el;
				}
			}
			$xml = implode("\n", $pretty);
			return ($html_output) ? htmlentities($xml) : $xml;
		} catch (Exception $e) {
				return esc_html($xml);
		}
	}

	function printErrors($wp_error = ''){
	
		if ( empty($wp_error) )
		$wp_error = new WP_Error();
	
		if ( $wp_error->get_error_code() ) {
			$errors_html = '';
	
			foreach ( $wp_error->get_error_codes() as $code ) {
				$errors_html .= '<tr>';
				$errors_html .= '<td>'.$code.'</td>';
				
				$errors_html .= '<td>';
				foreach ( $wp_error->get_error_messages($code) as $error_msgs ) {
					$errors_html .= $error_msgs.'<br/>';
				}
				$errors_html .= '</td>';
				
				$error_data = $wp_error->get_error_data($code);
				if ( !empty($error_data) )
					$errors_html .= '<td>'.$error_data.'</td>';
				else
					$errors_html .= '<td></td>';	
					
				//print the workaround
				$errors_html .= '<td>';
				$workorund_html = null;
				$is_validator_error = false;
				//read the workaround for this error
				foreach ( $this->xml_rpc_validator_errors as $error_obj ) {
					if( $error_obj['code'] == $code ){
						//print the workaround on screen
						$workorund_html = $error_obj['workaround'];
						$is_validator_error = true;
						break;
					}
				}
					
				if ( ! $is_validator_error ) {
					foreach ( $this->xml_rpc_server_errors as $server_code => $server_workaround ) {
						if( $server_code == $code ) {
							$workorund_html =  $server_workaround;
						}
					}
				}
				
				if ( !empty($workorund_html) )
					$errors_html .= $workorund_html;
				else 
					$errors_html .= 'Sorry, there isn\'t a FAQ for this issue. Please seach within the forum or post a new thread.
					If you aready solved this issue you can propose a fix by clicking on this link';
				
				$errors_html .= '</td>';
				$errors_html .= '</tr>';
			}
	
			if ( !empty($errors_html) ) {
				return '<table><tr><th>Code</th><th>Description</th><th>Additional Info</th><th>Workaround</th></tr>'
				.$errors_html.'</table>';
			}
	
			else return '';
		}
	}
		 
	function show_log_on_video( ) {
		$content = '<table><tr><th>Date</th><th>Message</th></tr>'.$this->logging_buffer.'</table>';

/*		$content .= 'array POST: <br/>';
		while (list($chiave, $valore) = each($_POST)) {
			$content .= "$chiave => $valore";
			$content .= '<br/>';
		}
		$content .= '<br/>';
		$content .= 'array GET: <br/>';
		while (list($chiave, $valore) = each($_GET)) {
			$content .= "$chiave => $valore";
			$content .= '<br/>';
		}
		$content .= '<br/>';

		if(isset($_SESSION)) {
			$content .= 'array SESSION:</br>';
			while (list($chiave, $valore) = each($_SESSION)) {
				$content .= "$chiave => $valore";
				$content .= '<br/>';
			}
			$content .= '<br/>';
		}
*/
		return $content;
	}
}

/**
 * xml_rpc_validator_logIO() - Writes logging info to file/screen.
 *
 * @uses $xmlrpc_logging
 * @package WordPress
 * @subpackage Logging
 *
 * @param string $io Whether input or output
 * @param string $msg Information describing logging reason.
 * @return bool Always return true
 */
function xml_rpc_validator_logIO($io,$msg) {
	global $xml_rpc_validator_utils;
	$xml_rpc_validator_utils->logIO($io,$msg);
}

function xml_rpc_validator_logXML($io, $msg) {
	global $xml_rpc_validator_utils;
	$xml_rpc_validator_utils->logXML($io,$msg);
}


class Blog_Validator {
	var $site_URL;
	var $xmlrpc_endpoint_URL;

	var $userBlogs; //array of blogs
	var $userBlogsErrors = Array(); //array of wp_errors got during testing the xml-rpc calls

	//used when tests private calls
	var	$user_login;
	var	$user_pass;

	//for blogs behind HTTP basic Auth
	var	$HTTP_auth_user_login;
	var	$HTTP_auth_user_pass;

	//The user agent to set on requestes
	var $user_agent = USER_AGENT;


	function __construct($URL) {
		$this->site_URL = $URL;
	}

	function setWPCredential ($user, $pass) {
		$this->user_login= $user;
		$this->user_pass = $pass;
	}

	function setHTTPCredential ($user, $pass) {
		$this->HTTP_auth_user_login = $user;
		$this->HTTP_auth_user_pass = $pass;
	}

	function setUserAgent ( $ua ) {
		$this->user_agent= $ua;
	}
	
	function getUsersBlogs() { 

		if( ! empty( $this->user_login ) ) {

			//starts with real xmlrpc calls
			$client = new wp_xmlrpc_client( $this->xmlrpc_endpoint_URL, false, $this->user_agent );

			if(! empty($this->HTTP_auth_user_login)) {
				$client->setHTTPCredential($this->HTTP_auth_user_login, $this->HTTP_auth_user_pass);
			}
			$this->userBlogs = $client->open('wp.getUsersBlogs', $this->user_login, $this->user_pass);
			if( is_wp_error( $this->userBlogs ) ) {
				return $this->userBlogs;
			}
			//xml_rpc_validator_logIO("O", print_r ($this->userBlogs, TRUE));

		}//end xmlrpc calls

		return true;
	}
	
	function execute_call( $method_name, $parameters = NULL ) { 

		if(! empty($this->user_login)) {
			$client = new wp_xmlrpc_client( $this->xmlrpc_endpoint_URL, false, $this->user_agent );

			if(! empty($this->HTTP_auth_user_login)) {
				$client->setHTTPCredential($this->HTTP_auth_user_login, $this->HTTP_auth_user_pass);
			}

			if ( $parameters != NULL )
				$result = $client->open($method_name, '', $this->user_login, $this->user_pass, $parameters);
			else 
				$result = $client->open($method_name, '', $this->user_login, $this->user_pass);
		
			return $result;
		}//end xmlrpc calls
		return NULL;
	}
	
	/*
	 * This function discorver and validate the XML-RPC endpoint
	 * 1.Discover the XML-RPC Endpoint
	 * 2.Verify the response from the endpoint
	 * 3.Verify that the XML-RPC methods are all available
	 * 4.Verify that the XML-RPC service is active
	 */
	function find_and_validate_xmlrpc_endpoint() {
		global $xml_rpc_validator_errors;
		/*check the string inserted by user */
		$pos = strripos($this->site_URL, 'xmlrpc.php');
		if ($pos !== false) {
			//use the url as-is
			$this->xmlrpc_endpoint_URL = $this->site_URL;
			xml_rpc_validator_logIO("O", "The string 'xmlrpc.php' found in the input URL '" .  $this->site_URL. "'. The validator will use the input URL as-is, without launching the XML-RPC discovery process.");
		} else {
			//try to guess the endpoint by appending the xmlrpc.php prefix
			xml_rpc_validator_logIO("O", "The validator will start the XML-RPC discovery process by using the URL '". $this->site_URL."' as base URL.");
			$client = new wp_xmlrpc_client( rtrim($this->site_URL,' /').'/xmlrpc.php', false, $this->user_agent );
			xml_rpc_validator_logIO("O", "The validator is going to the test following URL: ". $client->URL . ' by doing the XML-RPC call system.listMethods on it.');
			if( ! empty( $this->HTTP_auth_user_login ) ) {
				$client->setHTTPCredential($this->HTTP_auth_user_login, $this->HTTP_auth_user_pass);
			}
			$xmlArray = $client->open('system.listMethods', '');
			
			if( is_wp_error( $xmlArray ) ) {
				xml_rpc_validator_logIO("O", "The validator hasn't found the XML-RPC Endpoint at the URL: " . $client->URL );
				//start the discovery process
				$RSD_link = $this->find_rsd_document_url( );
				if( ! is_wp_error( $RSD_link ) ) {
					$discoverResult = $this->findXMLRPCEndpointFromRSDlink($RSD_link);
					if( is_wp_error( $discoverResult ) ) {
						return $discoverResult;
					} else {
						$this->xmlrpc_endpoint_URL = $discoverResult;
					}
				} else {
					return $RSD_link;
				}
			} else {
				// found the xmlrpc endpoint at the first tentative!!! hurraaa!
				xml_rpc_validator_logIO("O", "The validator has found the XML-RPC Endpoint at the URL: " . $client->URL );
				$this->xmlrpc_endpoint_URL = rtrim($this->site_URL,' /').'/xmlrpc.php' ;
			}
		}

		if( empty( $this->xmlrpc_endpoint_URL ) ) {
			//never empty. but just in case...
			$error_obj = $xml_rpc_validator_errors['NO_XMLRPC_IN_RSD_FOUND'];
			return new WP_Error( $error_obj['code'], $error_obj['message'] );
		}
		
		xml_rpc_validator_logIO("O", "Checking the available XML-RPC methods available at ".$this->xmlrpc_endpoint_URL);
		$allMethodsAreAvailable = $this->checkAvailableMethods();
		if( is_wp_error( $allMethodsAreAvailable ) ) {
			return $allMethodsAreAvailable;
		}
		
		xml_rpc_validator_logIO("O", "Starting a dummy XML-RPC call using test/test as credentials");
		$client = new wp_xmlrpc_client( $this->xmlrpc_endpoint_URL, false, $this->user_agent );

		if( ! empty( $this->HTTP_auth_user_login ) ) {
			$client->setHTTPCredential($this->HTTP_auth_user_login, $this->HTTP_auth_user_pass);
		}

		$this->userBlogs = $client->open('wp.getUsersBlogs', 'test', 'test');
		//xml_rpc_validator_logIO("O", print_r ($this->userBlogs, TRUE));
		if( is_wp_error( $this->userBlogs ) ) {
			if($this->userBlogs->get_error_code() != '403')
			return $this->userBlogs;
		}

		xml_rpc_validator_logIO("O", "Dummy call finished");
		return $this->xmlrpc_endpoint_URL;
	}

	/*
	 * find the RSD endpoint URL in the header
	 */
	private function find_rsd_document_url() {
		global $xml_rpc_validator_errors;
		$rsdURL;
		xml_rpc_validator_logIO("O", "The validator is going to downloading the HTML page available at the URL " .$this->site_URL.' Inside the HTML code should be available the link to the RSD document.' );
		//download the HTML code
		$headers = array( 'Accept' => 'text/html');
		$response = $this->downloadContent($this->site_URL, $headers);
		if( is_wp_error( $response ) ) {
			return $response;
		} else {
			xml_rpc_validator_logIO("O", "Parsing the HTML response document trying to match the RSD Endpoint declaration...");
			//find the RSD endpoint URL
			$match = array();
			$dom = new DOMDocument();
			@$dom->loadHTML($response['body']);
			$xpath = new DOMXPath($dom);
			$hrefs = $xpath->evaluate('/html/head/link[@rel = "EditURI" and @type="application/rsd+xml" and @title="RSD"]');
			for ($i = 0; $i < $hrefs->length; $i++) {
				$href = $hrefs->item($i);
				$rsdURL = $href->getAttribute('href');
			}
		}

		if(!isset($rsdURL)){
			$error_obj = $xml_rpc_validator_errors['NO_RSD_FOUND'];
			xml_rpc_validator_logIO("O", "RSD document NOT found!!");
			return new WP_Error( $error_obj['code'], $error_obj['message'] );
		}

		xml_rpc_validator_logIO("O", "RSD document found at:". print_r ($rsdURL, TRUE));

		return $rsdURL;
	}

	private function findXMLRPCEndpointFromRSDlink($rsdURL) {
		global $xml_rpc_validator_errors;
		xml_rpc_validator_logIO("O", "The RSD document was found at the following URL ".$rsdURL." Downloading the RSD document content. Inside the RSD document there is the link to the XML-RPC endpoint.");
		$xmlrpcURL;
		$headers = array( 'Accept' => 'text/xml');
		$response = $this->downloadContent($rsdURL, $headers);
		if( is_wp_error( $response ) ) {
			return $response;
		} else {

			if(empty($response['body'])){
				$error_obj = $xml_rpc_validator_errors['EMPTY_RSD'];
				return new WP_Error( $error_obj['code'], $error_obj['message'] );
			} else {
				//check the first character
				if($response['body'][0] !== '<') {
					$error_obj = $xml_rpc_validator_errors['MALFORMED_RSD'];
					return new WP_Error( $error_obj['code'], $error_obj['message'] );
				}
			}

			//find the XMLRPC endpoint URL
			xml_rpc_validator_logIO("O", "Parsing the RSD document...");
			$match = array();
			$dom = new DOMDocument();
			@$dom->loadXML($response['body']);
			$xpath = new DOMXPath($dom);
			$xpath->registerNamespace("lib", "http://archipelago.phrasewise.com/rsd");
			$hrefs = $xpath->evaluate('/lib:rsd/lib:service/lib:apis/lib:api[@name = "WordPress"]');
			for ($i = 0; $i < $hrefs->length; $i++) {
				$href = $hrefs->item($i);
				$xmlrpcURL = $href->getAttribute('apiLink');
			}
		}

		if(!isset($xmlrpcURL)){
			$error_obj = $xml_rpc_validator_errors['NO_XMLRPC_IN_RSD_FOUND'];
			xml_rpc_validator_logIO("O", "NO  XML-RPC endpoint found!!");
			return new WP_Error( $error_obj['code'], $error_obj['message'] );
		}
		xml_rpc_validator_logIO("O", "Found the XML-RPC endpoint at:". print_r ($xmlrpcURL, TRUE));
		return $xmlrpcURL;
	}

	//ensures we are not dl big file
	private function check_download_size( $url, $args = array() ) {
		
		xml_rpc_validator_logIO("I", "HTTP HEAD Request: ". print_r ($args, TRUE));
		$response = wp_remote_head ( $url, $args );
		if( is_wp_error( $response ) ) {
			return $response;
		} else {
			xml_rpc_validator_logIO("O", "HTTP Response Header: " .print_r ($response['headers'], TRUE));
			xml_rpc_validator_logIO("O", "HTTP Response Code: " .print_r ($response['response'], TRUE));
			return true;
		} 
	}
	
	private function downloadContent($URL, $args = array()) {
		global $xml_rpc_validator_errors;
		xml_rpc_validator_logIO("I", 'Doing a simple HTTP GET request on the following URL '.$URL);

		$headers = array();
		$headers['User-Agent']	= $this->user_agent;
		if(! empty($this->HTTP_auth_user_login)) {
			xml_rpc_validator_logIO("I", "HTTP auth header set ".$this->HTTP_auth_user_login.':'.$this->HTTP_auth_user_pass);
			$headers['Authorization'] = 'Basic '.base64_encode($this->HTTP_auth_user_login.':'.$this->HTTP_auth_user_pass);
		}

		$r = wp_parse_args($headers, $args);

		/* checking the document size b4 downloading it */
		//$this->check_download_size ( $URL, $r );
		
		xml_rpc_validator_logIO("I", "HTTP Request: ". print_r ($r, TRUE));

		$request = new WP_Http;
		$requestParameter = array('headers' => $r, 'timeout' => REQUEST_HTTP_TIMEOUT);
		$response = $request->request( $URL, $requestParameter );
			
		/*
		xml_rpc_validator_logIO("O", "Start logging of the HTTP Response");
		foreach ($response as $tmpResponseitemKey => $tmpResponseitemValue ) {
			if($tmpResponseitemKey !== 'body')
				xml_rpc_validator_logIO("O", $tmpResponseitemKey.' -> ' .print_r ($tmpResponseitemValue, TRUE) );
			else
			xml_rpc_validator_logIO("O", 'body -> skipped... ');
		}
		xml_rpc_validator_logIO("O", "End logging of the HTTP Response");
		*/
		
		// Handle error here.
		if( is_wp_error( $response ) ) {
			return $response;
		} 
		
		xml_rpc_validator_logIO("O", "HTTP Response Headers: " .print_r ($response['headers'], TRUE));
		xml_rpc_validator_logIO("O", "HTTP Response Codes: " .print_r ($response['response'], TRUE));
		
		if ( strcmp( $response['response']['code'], '200' ) != 0 ) {
			return  new WP_Error($response['response']['code'], $response['response']['message']);
		} else {
			return $response;
		}
	}


	private function  checkAvailableMethods() {
		global $xml_rpc_validator_errors;
		
		$client = new wp_xmlrpc_client($this->xmlrpc_endpoint_URL, false, $this->user_agent );

		if(! empty($this->HTTP_auth_user_login)) {
			$client->setHTTPCredential($this->HTTP_auth_user_login, $this->HTTP_auth_user_pass);
		}

		xml_rpc_validator_logIO("O", "Retrieving the XML-RPC methods list (system.listMethods)");

		$xmlArray = $client->open('system.listMethods', '');
		if( is_wp_error( $xmlArray ) ) {
			return $xmlArray;
		} else {

		// validate xmlrpc methods
			$standardCall = array('wp.getUsersBlogs', 'wp.getPage', 'wp.getCommentStatusList', 'wp.newComment',
			'wp.editComment', 'wp.deleteComment', 'wp.getComments',	'wp.getComment', 'wp.setOptions',
			'wp.getOptions', 'wp.getPageTemplates', 'wp.getPageStatusList', 'wp.getPostStatusList',
			'wp.getCommentCount', 'wp.uploadFile', 'wp.suggestCategories', 'wp.deleteCategory', 'wp.newCategory',
			'wp.getTags', 'wp.getCategories', 'wp.getAuthors', 'wp.getPageList', 'wp.editPage', 'wp.deletePage',
			'wp.newPage', 'wp.getPages', 'mt.publishPost', 'mt.getTrackbackPings',
			'mt.supportedTextFilters', 'mt.supportedMethods', 'mt.setPostCategories', 'mt.getPostCategories',
			'mt.getRecentPostTitles', 'mt.getCategoryList', 'metaWeblog.getUsersBlogs',
			'metaWeblog.deletePost', 'metaWeblog.newMediaObject', 'metaWeblog.getCategories',
			'metaWeblog.getRecentPosts', 'metaWeblog.getPost', 'metaWeblog.editPost', 'metaWeblog.newPost',
			'blogger.deletePost', 'blogger.editPost', 'blogger.newPost',
			'blogger.getRecentPosts', 'blogger.getPost', 'blogger.getUserInfo', 'blogger.getUsersBlogs');

			$result = array_diff($standardCall, $xmlArray); //Returns an array containing all the entries from array1 that are not present in any of the other arrays.
			if(!empty($result)) {
				xml_rpc_validator_logIO("O", "The following XML-RPC methods are missing on the server:");
				xml_rpc_validator_logIO("O", print_r ($result, TRUE));
			} else 
				xml_rpc_validator_logIO("O", "All XML-RPC methods are available on the server");
				
			if(!empty($result)) {
				$errorData = '';
				foreach ($result as $missingMethod) {
					$errorData.= $missingMethod.' - ';
				}
				$error_obj = $xml_rpc_validator_errors['MISSING_XMLRPC_METHODS'];
				return new WP_Error( $error_obj['code'], $error_obj['message'], $errorData );
			}
		}
		return true;
	}

/**
 * Checks that the WordPress version installed on the remote server is latest version
 * @param array $args Method parameters (The response of wp.getOptions)
 * @return bool|WP_Error 
 */
	function check_wp_version( $args ) {
		global $xml_rpc_validator_errors;

		if(  is_wp_error( $args ) ) {
			$error_obj = $xml_rpc_validator_errors['CANNOT_CHECK_WP_VERSION'];
			return new WP_Error( $error_obj['code'], $error_obj['message'], 'Can\'t download the site options the latest' );
		}

		//reads the $args WP version
		if( !isset( $args['software_version'] ) || !isset( $args['software_version']['value'] )  ) {
			$error_obj = $xml_rpc_validator_errors['CANNOT_CHECK_WP_VERSION'];
			return new WP_Error( $error_obj['code'], $error_obj['message'], 'Can\'t read the site WordPress software version' );
		}
		$remote_wp_version = $args['software_version']['value'];

		//read the latest version of WP
		$from_api = get_site_transient( 'update_core' );
		if ( empty($from_api) || !isset( $from_api->updates ) || !is_array( $from_api->updates ) ) {
			$error_obj = $xml_rpc_validator_errors['CANNOT_CHECK_WP_VERSION'];
			return new WP_Error( $error_obj['code'], $error_obj['message'], 'Can\'t read the latest WordPress.org software version' );
		}

		$updates = $from_api->updates[0];
		if( ! isset( $updates ) || ! isset( $updates->current ) ) {
			$error_obj = $xml_rpc_validator_errors['CANNOT_CHECK_WP_VERSION'];
			return new WP_Error( $error_obj['code'], $error_obj['message'], 'Can\'t read the latest WordPress.org software version' );
		}

		$latest_wp_version = $updates->current;
		if (version_compare($remote_wp_version, $latest_wp_version,"<")) {
			$error_obj = $xml_rpc_validator_errors['NEW_WP_VERSION_AVAILABLE'];
			$msg = sprintf( $error_obj['message'] , $latest_wp_version );
			return new WP_Error( $error_obj['code'], $msg );
		}

		return true;
	}
}


class wp_xmlrpc_client  {

	var $URL;
    var $useragent;
	var $headers;
    var $response;
    var $message = false;
    var $timeout;
    // Storage place for an error message
    var $error = false;

	//for blogs behind HTTP basic Auth
	var	$HTTP_auth_user_login;
	var	$HTTP_auth_user_pass;

	function setHTTPCredential ($user, $pass) {
		$this->HTTP_auth_user_login = $user;
		$this->HTTP_auth_user_pass = $pass;
	}

	function __construct($URL, $timeout = false, $useragent = false) {
		$this->URL = $URL;
		$this->timeout = $timeout;

		if ( $timeout === false ) {
			$this->timeout = REQUEST_HTTP_TIMEOUT;
		} else
			$this->timeout = $timeout;

		if ( $useragent === false ) {
			$this->useragent = USER_AGENT;
		} else
			$this->useragent = $useragent;
	}

	function open() {
		global $xml_rpc_validator_errors;

		$args = func_get_args();
		$method = array_shift($args);
		$request = new IXR_Request($method, $args);
		$length = $request->getLength();
		$xml = $request->getXml();

		$this->headers['Content-Type']	= 'text/xml';
		$this->headers['User-Agent']	= $this->useragent;
		$this->headers['Content-Length']= $length;
		$this->headers['Accept'] = '*/*';

		if(! empty($this->HTTP_auth_user_login)) {
			xml_rpc_validator_logIO("I", "HTTP auth header set ".$this->HTTP_auth_user_login.':'.$this->HTTP_auth_user_pass);
			$this->headers['Authorization'] = 'Basic '.base64_encode($this->HTTP_auth_user_login.':'.$this->HTTP_auth_user_pass) ;
		}

		$requestParameter = array('headers' => $this->headers);
		$requestParameter['method'] = 'POST';
		$requestParameter['body'] = $xml;
		$requestParameter['timeout'] = REQUEST_HTTP_TIMEOUT;

		xml_rpc_validator_logIO("I", "HTTP Request headers: ". print_r ( $this->headers, TRUE));

		xml_rpc_validator_logIO("I", "XML-RPC Request: ");
		if ( strpos($method, 'metaWeblog.newMediaObject') === false ) //do not log the whole picture upload request document
			xml_rpc_validator_logXML("I", $xml);
		else
			xml_rpc_validator_logXML("I", substr($xml, 0, 100) );

		$xmlrpc_request = new WP_Http;
		$this->response = $xmlrpc_request->request( $this->URL, $requestParameter);

		//xml_rpc_validator_logIO("O", "RAW response:     ". print_r ($this->response, TRUE));

		// Handle error here.
		if( is_wp_error( $this->response ) ) {
			return $this->response;
		}

		xml_rpc_validator_logIO("O", "Response details below ->");
		xml_rpc_validator_logIO("O", "HTTP Response code: ". print_r ($this->response['response']['code']. ' - '. $this->response['response']['message'], TRUE));
		xml_rpc_validator_logIO("O", "HTTP Response headers: ". print_r ( $this->response['headers'], TRUE));

		if ( strcmp($this->response['response']['code'], '200') != 0 ) {
			return new WP_Error($this->response['response']['code'], $this->response['response']['message']);
		}

		xml_rpc_validator_logIO("O", "HTTP Response Body:", TRUE);
		$contents = trim($this->response['body']);
		xml_rpc_validator_logXML("O", $contents);

		if(empty($contents)){
			$error_obj = $xml_rpc_validator_errors['MISSING_XMLRPC_METHODS'];
			$this->error = new WP_Error( $error_obj['code'], $error_obj['message'] );
			return $this->error;
		} else {
			//check the first character
			if($contents[0] !== '<') {
				$error_obj = $xml_rpc_validator_errors['XMLRPC_RESPONSE_MALFORMED_1'];
				$this->error = new WP_Error( $error_obj['code'], $error_obj['message'] );
				return $this->error;
			}
		}

		//check the characters within the response		
		if ( $this->check_UTF8( $contents ) !== true ) {
			$error_obj = $xml_rpc_validator_errors['XMLRPC_RESPONSE_CONTAINS_INVALID_CHARACTERS'];
			$this->error = new WP_Error( $error_obj['code'], $error_obj['message'] );
			return $this->error;
		}
		
		// Now parse what we've got back
		$this->message = new IXR_Message($contents);
		if (!$this->message->parse()) {
			// XML error
			$error_obj = $xml_rpc_validator_errors['XMLRPC_RESPONSE_MALFORMED_2'];
			$this->error = new WP_Error( $error_obj['code'], $error_obj['message'] );
			return $this->error;
		}
		// Is the message a fault?
		if ($this->message->messageType == 'fault') {
			$this->error = new WP_Error($this->message->faultCode, $this->message->faultString);
			return $this->error;
		}
		return $this->message->params[0];
	}

    function getResponse() {
        // methodResponses can only have one param - return that
        return $this->message->params[0];
    }
    function isError() {
        return (is_object($this->error));
    }
    function getError() {
        return $this->error;
    }
	private function check_UTF8($textToCheck) {
	//reject overly long 2 byte sequences, as well as characters above U+10000
	$some_string = preg_match('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]'.
 	'|[\x00-\x7F][\x80-\xBF]+'.
 	'|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.
 	'|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'.
 	'|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S', $textToCheck );
	if( $some_string == 1 )
		return false;
		
	//reject overly long 3 byte sequences and UTF-16 surrogates
	$some_string = preg_match('/\xE0[\x80-\x9F][\x80-\xBF]|\xED[\xA0-\xBF][\x80-\xBF]/S', $textToCheck );
	if($some_string == 1 )
		return false;
	
	return true;
	}
}
?>