<?php
// Change these
define('client_id','66db69907daac8e63073');
define('client_secret','f736422ca3f5540fd602167e3bb97eb208cdaabb');
define('redirect_uri','http://localhost/oauth');
define('scope','read:user_id read:teachers read:students');

// You'll probably use a database
session_name('clever');
session_start();

// OAuth 2 Control Flow
if (isset($_GET['error'])) {
    // OAuth server returned an error
    print $_GET['error'] . ': ' . $_GET['error_description'];
    exit;
} 
elseif (isset($_GET['code'])) {
    // User authorized your application
    if ($_SESSION['state'] == $_GET['state']) {
        // Get token so you can make API calls
        //print "User authorized the application. Need to get token in order to make API calls";
        //exit;
        getAccessToken();
    } else {
        // CSRF attack? Or did you mix up your states?
        print "what happened?";
        exit;
    }
} 
else {
    if ((empty($_SESSION['expires_at'])) || (time() > $_SESSION['expires_at'])) {
        // Token has expired, clear the state
        $_SESSION = array();
        //print "Token has expired, clearing the state";
        //exit;
    }
    if (empty($_SESSION['access_token'])) {
        // Start authorization process
        //print "No token. Need to start authorization process";
        //exit;
        getAuthorizationCode();
    }
}

// Congratulations! You have a valid token. Now fetch a profile
//echo "getting user info";
$user = fetch('GET', '/me');
echo $user;
//echo "user: ".var_dump($user);
//echo $user->data;
exit;
//print "Hello $user->firstName.\n";
echo("<p>Here's some information about the user:</p>");
echo("<ul>");
$fields = array('type' => 'User type', 'id' => 'User ID', 'district' => 'District ID');
foreach($fields as $key => $label) {
	echo("<li>{$label}: {$user['data'][$key]}");
}
echo("</ul>");
exit;

function getAuthorizationCode() {
	$params = array('response_type' => 'code',
			'client_id' => client_id,
			'scope' => scope,
			'redirect_uri' => redirect_uri,
			'state' => uniqid('', true), // unique long string
			'district_id' => '575706126afc1801000000ea'
	);
	//'state' => uniqid('', true), // unique long string

	// Authentication request
	//$url = 'https://www.linkedin.com/uas/oauth2/authorization?' .
	$url = 'https://clever.com/oauth/authorize?' .
			http_build_query($params);
	
	$sign_in_link = "<a href='" . $url . "'><img src='http://assets.clever.com/sign-in-with-clever/sign-in-with-clever-small.png'/></a>";

	// Needed to identify request when it returns to us
	$_SESSION['state'] = $params['state'];
	//$_SESSION['state'] = uniqid('', true);
	
	//echo("<h1>clever_oauth_examples: Login!</h1>");
	//echo('<p>' . $sign_in_link . '</p>');
	//echo("<p>Ready to handle OAuth 2.0 client redirects on " .redirect_uri. ".</p>");

	// Redirect user to authenticate
	header("Location: $url");
	exit;
}

function getAccessToken() {
	$params = array('grant_type' => 'authorization_code',
			//'client_id' => client_id,
			//'client_secret' => client_secret,
			'code' => $_GET['code'],
			'redirect_uri' => redirect_uri,
	);

	// Access Token request
	//$url = 'https://clever.com/oauth/tokens?' .
	//		http_build_query($params);
	$url = 'https://clever.com/oauth/tokens';
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_USERPWD, client_id . ':' . client_secret);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	curl_setopt($ch, CURLOPT_HTTPHEADER, 'Accept: application/json');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
	$response = curl_exec($ch);

	// Tell streams to make a POST request
	//$context = stream_context_create(
	//		array('http' =>
	//				array('method' => 'POST',
	//				)
	//		)
	//);
	//print "url: ".$url."\n";
	// Retrieve access token information
	//$response = file_get_contents($url, false, $context);
	//print "response: ".$response."\n";
	// Native PHP object, please
	$token = json_decode($response);
	//print "access_token: ".$token->access_token;
	//exit;
	// Store access token and expiration time
	$_SESSION['access_token'] = $token->access_token; // guard this!
	$_SESSION['expires_in']   = $token->expires_in; // relative time (in seconds)
	$_SESSION['expires_at']   = time() + $_SESSION['expires_in']; //absolute time
	//print "access_token: ".$_SESSION['access_token'];
	//exit;
	return true;
}

function fetch($method, $resource, $body = '') {
	//$params = array('oauth2_access_token' => $_SESSION['access_token'],
	//		'format' => 'json',
	//);
	$auth_header = 'Authorization: Bearer ' . $_SESSION['access_token'];
	$request_headers[] = $auth_header;

	// Need to use HTTPS
	//$url = 'https://api.clever.com' . $resource . '?' .
	//		http_build_query($params);
	$url = 'https://api.clever.com'.$resource;
	
	$ch = curl_init($url);
	//curl_setopt($ch, CURLOPT_USERPWD, client_id . ':' . client_secret);
	//curl_setopt($ch, CURLOPT_POST, 1);
	//curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
	$response = curl_exec($ch);
	echo "response: ".$response;
	exit;
	// Tell streams to make a (GET, POST, PUT, or DELETE) request
	//$context = stream_context_create(
	//		array('http' =>
	//				array('method' => $method,
	//				)
	//		)
	//);

	// Hocus Pocus
	//$response = file_get_contents($url, false, $context);
	//print "url: ".$url."\n";
	//print "we got a response back: ".$response;
	//print json_decode($response);
	//exit;

	// Native PHP object, please
	//$json_response = json_decode($response);
	//$normalized_response = array('response_code' => $response_code, 'response' => $parsed_response, 'raw_response' => $raw_response, 'curl_info' => $curl_info);
	//$normalized_response = array('response' => $json_responce);
	//return $normalized_response;
	return json_decode($response);
}
?>