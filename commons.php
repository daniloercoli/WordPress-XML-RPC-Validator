<?php
if( !class_exists( 'WP_Http' ) )
include_once( ABSPATH . WPINC. '/class-http.php' );

if( !class_exists( 'UserAgentInfo' ) )
require_once( 'UserAgentInfo.php' );

include_once(ABSPATH . WPINC . '/class-IXR.php');

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
		
	function xml_rpc_validator_utils()
	{
	$this->xml_rpc_validator_errors = array(
		'NO_RSD_FOUND'		=> array(
			'code'			=> 1000000,
			'message'		=> __('Sorry, we cannot find your RSD Endpoint'),
			'workaround'	=> 'link to a support page, sticky forum post with steps to fix it'
		),
		'EMPTY_RSD'			=> array(
			'code'			=> 1000001,
			'message'		=> __('The RSD document is empty.'),
			'workaround'	=> 'link to a support page, sticky forum post with steps to fix it'
		),
		'MALFORMED_RSD'		=> array(
			'code'			=> 1000002,
			'message'		=> __('The RSD document is not well formed. There are characters before the xml preamble.'),
			'workaround'	=> 'link to a support page, sticky forum post with steps to fix it'
		),
		'NO_XMLRPC_IN_RSD_FOUND'		=> array(
			'code'			=> 1000004,
			'message'		=> __('We cannot find the XML-RPC Endpoint within the RSD document.'),
			'workaround'	=> 'link to a support page, sticky forum post with steps to fix it'
		),
		'MISSING_XMLRPC_METHODS'		=> array(
			'code'			=> 1000005,
			'message'		=> __('The following XML-RPC methods are missing from your site: '),
			'workaround'	=> 'link to a support page, sticky forum post with steps to fix it'
		),
		'XMLRPC_RESPONSE_EMPTY'		=> array(
			'code'			=> 1000006,
			'message'		=> __('The XML-RPC response document is empty.'),
			'workaround'	=> 'link to a support page, sticky forum post with steps to fix it'
		),
		'XMLRPC_RESPONSE_MALFORMED_1'		=> array(
			'code'			=> 1000007,
			'message'		=> __('The XML-RPC response document is not well formed. There are characters before the xml preamble'),
			'workaround'	=> 'link to a support page, sticky forum post with steps to fix it'
		),
		'XMLRPC_RESPONSE_MALFORMED_2'		=> array(
			'code'			=> 1000008,
			'message'		=> __('Parse error. The XML-RPC response document is not well formed'),
			'workaround'	=> 'link to a support page, sticky forum post with steps to fix it'
		),
		'XMLRPC_RESPONSE_CONTAINS_INVALID_CHARACTERS'		=> array(
			'code'			=> 1000009,
			'message'		=> __('The XML-RPC response document contains characters outside the XML range'),
			'workaround'	=> 'link to a support page, sticky forum post with steps to fix it'
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
			
			$this->logging_buffer.= $date.$iot.esc_html($msg).'<br/><br/>';
			
			if ($this->logging_on_file) {
				$fp = fopen( constant('XMLRPC_VALIDATOR_PLUGIN_DIR').'/validator.log',"a+" );
				fwrite($fp, "\n\n".$date.$iot.$msg);
				fclose($fp);
			}
		}
		return true;
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
				return '<table><tr><th>Code</th><th>Description</th><th>Addional Info</th><th>Workaround</th></tr>'
				.$errors_html.'</table>';
			}
	
			else return '';
		}
	}
		 
	function show_log_on_video( ) {
		$content = $this->logging_buffer;

		$content .= 'array POST: <br/>';
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


	function Blog_Validator ($URL) {
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

	function getUsersBlogs() { 

		if( ! empty( $this->user_login ) ) {

			//starts with real xmlrpc calls
			$client = new wp_xmlrpc_client($this->xmlrpc_endpoint_URL);

			if(! empty($this->HTTP_auth_user_login)) {
				$client->setHTTPCredential($this->HTTP_auth_user_login, $this->HTTP_auth_user_pass);
			}
			$this->userBlogs = $client->open('wp.getUsersBlogs', $this->user_login, $this->user_pass);
			if( is_wp_error( $this->userBlogs ) ) {
				return $this->userBlogs;
			}
			xml_rpc_validator_logIO("O", print_r ($this->userBlogs, TRUE));

		}//end xmlrpc calls

		return true;
	}
	
	function execute_call( $method_name, $parameters = NULL ) { 

		if(! empty($this->user_login)) {
			$client = new wp_xmlrpc_client($this->xmlrpc_endpoint_URL);

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
		} else {
			//try to guess the endpoint by appending the xmlrpc.php prefix
			xml_rpc_validator_logIO("O", "try to guess the endpoint by appending the xmlrpc.php prefix");
			$client = new wp_xmlrpc_client( rtrim($this->site_URL,' /').'/xmlrpc.php' );
			if( ! empty( $this->HTTP_auth_user_login ) ) {
				$client->setHTTPCredential($this->HTTP_auth_user_login, $this->HTTP_auth_user_pass);
			}
			$xmlArray = $client->open('system.listMethods', '');
			
			if( is_wp_error( $xmlArray ) ) {
				xml_rpc_validator_logIO("O", "the validator haven't found the XML-RPC Endpoint by appending the xmlrpc.php prefix.");
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
				xml_rpc_validator_logIO("O", "the validator found the XML-RPC Endpoint by appending the xmlrpc.php prefix. GOOALL!!!");
				$this->xmlrpc_endpoint_URL = rtrim($this->site_URL,' /').'/xmlrpc.php' ;
			}
		}

		if( empty( $this->xmlrpc_endpoint_URL ) ) {
			//never empty. but just in case...
			$error_obj = $xml_rpc_validator_errors['NO_XMLRPC_IN_RSD_FOUND'];
			return new WP_Error( $error_obj['code'], $error_obj['message'] );
		}
		
		xml_rpc_validator_logIO("O", "Checking the available XML-RPC methods...");
		$allMethodsAreAvailable = $this->checkAvailableMethods();
		if( is_wp_error( $allMethodsAreAvailable ) ) {
			return $allMethodsAreAvailable;
		}
		
		xml_rpc_validator_logIO("O", "Starting a dummy XML-RPC call using test/test as credentials");
		$client = new wp_xmlrpc_client( $this->xmlrpc_endpoint_URL );

		if( ! empty( $this->HTTP_auth_user_login ) ) {
			$client->setHTTPCredential($this->HTTP_auth_user_login, $this->HTTP_auth_user_pass);
		}

		$this->userBlogs = $client->open('wp.getUsersBlogs', 'test', 'test');
		xml_rpc_validator_logIO("O", print_r ($this->userBlogs, TRUE));
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
		xml_rpc_validator_logIO("O", "Downloading the HTML of the page...");
		//download the HTML code
		$headers = array( 'Accept' => 'text/html');
		$response = $this->downloadContent($this->site_URL, $headers);
		if( is_wp_error( $response ) ) {
			return $response;
		} else {
			xml_rpc_validator_logIO("O", "Parsing the HTML document trying to match the RSD Endpoint declatation...");
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
			return new WP_Error( $error_obj['code'], $error_obj['message'] );
		}

		xml_rpc_validator_logIO("O", "RSD document found at:". print_r ($rsdURL, TRUE));

		return $rsdURL;
	}

	private function findXMLRPCEndpointFromRSDlink($rsdURL) {
		global $xml_rpc_validator_errors;
		xml_rpc_validator_logIO("O", "Downloading the RSD document...");
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
			return new WP_Error( $error_obj['code'], $error_obj['message'] );
		}
		xml_rpc_validator_logIO("O", "Found the XML-RPC endpoint URL at:". print_r ($xmlrpcURL, TRUE));
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
		xml_rpc_validator_logIO("I", 'Opening URL '.$URL);

		$headers = array();
		$headers['User-Agent']	= USER_AGENT;
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
		
		xml_rpc_validator_logIO("O", "HTTP Response Header: " .print_r ($response['headers'], TRUE));
		xml_rpc_validator_logIO("O", "HTTP Response Code: " .print_r ($response['response'], TRUE));
		
		if ( strcmp( $response['response']['code'], '200' ) != 0 ) {
			return  new WP_Error($response['response']['code'], $response['response']['message']);
		} else {
			return $response;
		}
	}


	private function  checkAvailableMethods() {
		global $xml_rpc_validator_errors;
		
		$client = new wp_xmlrpc_client($this->xmlrpc_endpoint_URL);

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
			'mt.getRecentPostTitles', 'mt.getCategoryList', 'metaWeblog.getUsersBlogs', 'metaWeblog.setTemplate',
			'metaWeblog.getTemplate', 'metaWeblog.deletePost', 'metaWeblog.newMediaObject', 'metaWeblog.getCategories',
			'metaWeblog.getRecentPosts', 'metaWeblog.getPost', 'metaWeblog.editPost', 'metaWeblog.newPost',
			'blogger.deletePost', 'blogger.editPost', 'blogger.newPost', 'blogger.setTemplate', 'blogger.getTemplate',
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

	function wp_xmlrpc_client($URL, $timeout = false, $useragent = false) {
		$this->URL = $URL;
		$this->timeout = $timeout;
		if (!$useragent) {
			 $this->useragent = USER_AGENT;
		}
	}

	function open() {
		global $xml_rpc_validator_errors;

		$args = func_get_args();
		$method = array_shift($args);
		$request = new IXR_Request($method, $args);
		$length = $request->getLength();
		$xml = $request->getXml();

		$this->headers['Content-Type']	= 'application/xml';
		$this->headers['User-Agent']	= $this->useragent;
		$this->headers['Content-Length']= $length;
		$this->headers['Accept'] = 'text/xml';

		if(! empty($this->HTTP_auth_user_login)) {
			xml_rpc_validator_logIO("I", "HTTP auth header set ".$this->HTTP_auth_user_login.':'.$this->HTTP_auth_user_pass);
			$this->headers['Authorization'] = 'Basic '.base64_encode($this->HTTP_auth_user_login.':'.$this->HTTP_auth_user_pass) ;
		}

		$requestParameter = array();
		$requestParameter = array('headers' => $this->headers);
		$requestParameter['method'] = 'POST';
		$requestParameter['body'] = $xml;
		$requestParameter['timeout'] = REQUEST_HTTP_TIMEOUT;

		xml_rpc_validator_logIO("I", "xmlrpc request: ". print_r ($requestParameter, TRUE));

		$xmlrpc_request = new WP_Http;
		$this->response = $xmlrpc_request->request( $this->URL, $requestParameter);

		xml_rpc_validator_logIO("O", "Download response:\n". print_r ($this->response, TRUE));

		// Handle error here.
		if( is_wp_error( $this->response ) ) {
			return $this->response;
		} elseif ( strcmp($this->response['response']['code'], '200') != 0 ) {
			return  new WP_Error($this->response['response']['code'], $this->response['response']['message']);
		}

		$contents = trim($this->response['body']);
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