<?php

function curl_post_request($url, $data)
{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$content = curl_exec($ch);
	curl_close($ch);
	return $content;
}

//function uses WebPurify
function check_for_profanity_WebPurify($message) {
	global $DEBUG,$pluginSettings,$API_USER_ID, $API_KEY;
	
	$checkurl = "http://api1.webpurify.com/services/rest/?method=webpurify.live.check&api_key=".$API_KEY."&text=".urlencode($message);
	
	
	if($DEBUG)
		logEntry("Inside Web Purify profanity checker");
	
	if($DEBUG)
		logEntry("Checkurl: ".$checkurl);

	$response = simplexml_load_file($checkurl,'SimpleXMLElement', LIBXML_NOCDATA);

	if($DEBUG)
		print_r($response);
	
	//if($DEBUG)
	//echo $response->found;
	
	if($response->found != 0) {
		return true;
	} else {
		return false;
	}
	
	//return $response->found;
}

//function to check if it is profanity
//this uses function: https://neutrinoapi.com/bad-word-filter
function check_for_profanity_neutrinoapi($message) {
global $DEBUG,$pluginSettings,$API_USER_ID, $API_KEY;

	if($DEBUG)
	logEntry("inside profanity checker");
	
	logEntry("checking for profanity inside message: ".$message);

//	$API_USER_ID = urldecode($pluginSettings['API_USER_ID']);
//	$API_KEY = urldecode($pluginSettings['API_KEY']);


	logEntry("API USER: ".$API_USER_ID);
	logEntry("API KEY: ".$API_KEY);	
	$postData = array(
		"user-id" => $API_USER_ID,
		"api-key" => $API_KEY,
		"content" => $message
);

$json = curl_post_request("https://neutrinoapi.com/bad-word-filter", $postData);
//$json = curl_post_request("https://neutrinoapi.com/ip-info", $postData);
$result = json_decode($json, true);
//logEntry("profanty result: is bad: ".$result['is-bad']);
//logEntry("profanity result: total bad words: ".$result['bad-words-total']);



logEntry("is bad: ".$result['is-bad']);
logEntry("is bad total: ".$result['bad-words-total']);
//echo print_r($result['bad-words-list'])."\n";
logEntry("censored content: ".$result['censored-content']);

if($result['is-bad']>0 || $result['bad-words-total'] > 0)
	return true;
else {
	return false;
}

}

//basic checker only single words
function is_profanity($q,$json=0) {
	$q=urlencode(preg_replace('/[\W+]/',' ',$q));
	$p=file_get_contents('http://www.wdyl.com/profanity?q='.$q);
	if ($json) { return $p; }
	$p=json_decode($p);
	return ($p->response=='true')?1:0;
}

//$q=isset($_REQUEST['q'])?$_REQUEST['q']:'';
//$q="butt";
//$q="shoe";


?>
