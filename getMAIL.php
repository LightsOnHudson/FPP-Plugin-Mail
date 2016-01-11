#!/usr/bin/php
<?php

//error_reporting(0);

$pluginName ="MailControl";
$myPid = getmypid();

$messageQueue_Plugin = "MessageQueue";
$MESSAGE_QUEUE_PLUGIN_ENABLED=false;

$DEBUG=false;
$LOG_LEVEL=0;

$NEW_MESSAGE=false;

$RESPONSE_METHOD = "EMAIL";

$skipJSsettings = 1;
$fppWWWPath = '/opt/fpp/www/';
set_include_path(get_include_path() . PATH_SEPARATOR . $fppWWWPath);

require("common.php");

include_once("functions.inc.php");
include_once("commonFunctions.inc.php");
include_once("profanity.inc.php");
//include_once ("GoogleVoice.php");
require 'PHPMailer/PHPMailerAutoload.php';
require ("lock.helper.php");

$logFile = $settings['logDirectory']."/".$pluginName.".log";

$messageQueuePluginPath = $pluginDirectory."/".$messageQueue_Plugin."/";

$messageQueueFile = urldecode(ReadSettingFromFile("MESSAGE_FILE",$messageQueue_Plugin));

if(file_exists($messageQueuePluginPath."functions.inc.php"))
        {
                include $messageQueuePluginPath."functions.inc.php";
                $MESSAGE_QUEUE_PLUGIN_ENABLED=true;

        } else {
                logEntry("Message Queue Plugin not installed, some features will be disabled");
        }



define('LOCK_DIR', '/tmp/');
define('LOCK_SUFFIX', '.lock');

$pluginConfigFile = $settings['configDirectory'] . "/plugin." .$pluginName;
if (file_exists($pluginConfigFile))
        $pluginSettings = parse_ini_file($pluginConfigFile);

        


$MATRIX_MESSAGE_PLUGIN_NAME = "MatrixMessage";
//page name to run the matrix code to output to matrix (remote or local);
$MATRIX_EXEC_PAGE_NAME = "matrix.php";

	
	$PLAYLIST_NAME = $pluginSettings['PLAYLIST_NAME'];
	$WHITELIST_NUMBERS = urldecode($pluginSettings['WHITELIST_NUMBERS']);
	$CONTROL_NUMBERS = urldecode($pluginSettings['CONTROL_NUMBERS']);
	$REPLY_TEXT = urldecode($pluginSettings['REPLY_TEXT']);
	$VALID_COMMANDS = urldecode($pluginSettings['VALID_COMMANDS']);
	$EMAIL = urldecode($pluginSettings['EMAIL']);
	$PASSWORD = $pluginSettings['PASSWORD'];
	$LAST_READ = $pluginSettings['LAST_READ'];
	$API_USER_ID = urldecode($pluginSettings['API_USER_ID']);
	$API_KEY = urldecode($pluginSettings['API_KEY']);
	$IMMEDIATE_OUTPUT = $pluginSettings['IMMEDIATE_OUTPUT'];
	$MATRIX_LOCATION = $pluginSettings['MATRIX_LOCATION'];
	$MAIL_TYPE = $pluginSettings['MAIL_TYPE'];
	$MAIL_HOST = urldecode($pluginSettings['MAIL_HOST']);
	$MAIL_PORT = urldecode($pluginSettings['MAIL_PORT']);
	$MAIL_LAST_TIMESTAMP= urldecode($pluginSettings['MAIL_LAST_TIMESTAMP']);
	$ENABLED = $pluginSettings['ENABLED'];
	$DEBUG = $pluginSettings['DEBUG'];
	$READ_MESSAGE_MARK = $pluginSettings['READ_MESSAGE_MARK'];
	$PROFANITY_ENGINE = urldecode($pluginSettings['PROFANITY_ENGINE']);

$LOG_LEVEL = getFPPLogLevel();
logEntry("Log level in translated from fpp settings file: ".$LOG_LEVEL);

if(urldecode($pluginSettings['DEBUG'] != "" || urldecode($pluginSettings['DEBUG'] != 0))) {
        $DEBUG=urldecode($pluginSettings['DEBUG']);
}



$COMMAND_ARRAY = explode(",",trim(strtoupper($VALID_COMMANDS)));
$CONTROL_NUMBER_ARRAY = explode(",",$CONTROL_NUMBERS);


$WHITELIST_NUMBER_ARRAY = explode(",",$WHITELIST_NUMBERS);

$logFile = $settings['logDirectory']."/".$pluginName.".log";


//give google voice time to sleep
$GVSleepTime = 5;

$ENABLED="";

$ENABLED = trim(urldecode(ReadSettingFromFile("ENABLED",$pluginName)));



if(($pid = lockHelper::lock()) === FALSE) {
        exit(0);

}

if($ENABLED != "on" && $ENABLED != "1") {
        logEntry("Plugin Status: DISABLED Please enable in Plugin Setup to use & Restart FPPD Daemon");
        lockHelper::unlock();
        exit(0);
}

if($DEBUG){
	logEntry("________________________");
	logEntry("Plugin Settings");
	logEntry("________________________");
	
	while (list($key, $val) = each($pluginSettings)) {
		logEntry("$key => $val");
	}
	logEntry("________________________");
	logEntry("COMMAND ARRAY Settings");
	logEntry("________________________");
	while (list($key, $val) = each($COMMAND_ARRAY)) {
		logEntry("$key => $val");
	}
	logEntry("________________________");
	logEntry("CONTROL NUMBER ARRAY Settings");
	logEntry("________________________");
	while (list($key, $val) = each($CONTROL_NUMBER_ARRAY)) {
		logEntry("$key => $val");
	}
	
	logEntry("________________________");
	logEntry("WHITELIST ARRAY Settings");
	logEntry("________________________");
	while (list($key, $val) = each($WHITELIST_NUMBER_ARRAY)) {
		logEntry("$key => $val");
	}
}

logEntry("Log Level: ".$LOG_LEVEL);


switch (trim(strtoupper($MAIL_HOST))) {
	
	
	case "IMAP.GMAIL.COM":
		//$hostname = '{imap.gmail.com:993/imap/ssl}ALL';
		$path="INBOX";
		
		//$imap_search ='SUBJECT "SMS from"';
		
		$hostname = "{imap.gmail.com:993/imap/ssl/novalidate-cert}$path";
		//$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
		
		/* try to connect */
		
		$USERNAME = substr($EMAIL,0,strpos($EMAIL,"@"));
		if($DEBUG)
			logEntry("Username extracted from email: ".$USERNAME);
		$mbox = imap_open($hostname,$EMAIL,$PASSWORD) or die('Cannot connect to Gmail: ' . imap_last_error());
		
		break;
		
		
	default:
		//$hostname = '{imap.gmail.com:993/imap/ssl}ALL';
		$path="INBOX";
		
		//$imap_search ='SUBJECT "SMS from"';
		$hostname = "{".$MAIL_HOST.":".$MAIL_PORT."/".strtolower($MAIL_TYPE)."/ssl/novalidate-cert}";
	//	$hostname = "{".$MAIL_HOST.":".$MAIL_PORT."}$path";
		//$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
		
		/* try to connect */

		$USERNAME = substr($EMAIL,0,strpos($EMAIL,"@"));
		if($DEBUG)
			logEntry("Username extracted from email: ".$USERNAME);
		$mbox = imap_open($hostname,$USERNAME,$PASSWORD) or die('Cannot connect to Imap Server: ' . imap_last_error());

		$imap_search = "ALL";		
		
}


//Message body Format: 
$messageFormat = 1.1;

    $sorted_mbox = imap_sort($mbox, SORTARRIVAL, 1);
    $totalrows = imap_num_msg($mbox);

	logEntry("total mesages: ".$totalrows);

        $emails = imap_search($mbox,$imap_search);
        //$emails = imap_search($mbox,'SUBJECT "SMS from"');
        //$emails = imap_search($mbox,'ALL');
        //$emails = imap_search($mbox,'UNSEEN');
      //  $emails = imap_search($inbox,'ALL', SE_UID);


if($emails) {
	if($DEBUG)
 	 logEntry( "got emails");
  /* begin output var */
  $output = '';

  /* put the newest emails on top */
  //rsort($emails);


  /* for every email... */
  foreach($emails as $email_number) {


	    /* get information specific to this email */
	    $overview = imap_fetch_overview($mbox,$email_number,0);
	
	//get message body
	        $message = (imap_fetchbody($mbox,$email_number,1.1)); 
	        if($message == '')
	        {
	            $message = (imap_fetchbody($mbox,$email_number,1));
	        }
		$mailUID = $overview[0]->uid;
	
	
	    /* output the email header information */
	
		$subject = $overview[0]->subject." ";
		$subject = trim($subject);

		$from =  $overview[0]->from;

		$from =  get_string_between($from,"<",">");
		//if($DEBUG)	
		//echo "from: ".$from."\n";
	
		//the to is the first one and the from is the second
	
	
		$from = trim($from);
	
		$mailUID = $overview[0]->uid;
	
		$messageDate =  $overview[0]->date."\n  ";
	
	
	    /* output the email body */
		
	if($DEBUG)
			logEntry("SUBJECT: ".$subject);
	
	if($DEBUG)
			logEntry("BODY: ".$message);
	
	if($DEBUG)
			logEntry("message uid: ".$mailUID);
		
			$messageTimestamp = $overview[0]->udate;
	
			if($MAIL_LAST_TIMESTAMP < $messageTimestamp) {
				logEntry("We have a new message");
				$NEW_MESSAGE = true;
			logEntry("Message: ".$message);
		
	
			} else {
	
			//	logEntry("this message is not new");
				continue;
			}
	
			logEntry("updating message last download to: ".$messageTimestamp);
			WriteSettingToFile("MAIL_LAST_TIMESTAMP",$messageTimestamp,$pluginName);
			
			switch ($READ_MESSAGE_MARK) {
				
				case "DELETED":
					$status = imap_setflag_full($mbox, $mailUID, "\\Deleted", ST_UID);
					
					break;
					
				case "READ":
					 $status = imap_setflag_full($mbox, $mailUID, "\\Seen \\Flagged", ST_UID);
					
					break;
					
			}
			
		// $status = imap_setflag_full($mbox, $mailUID, "\\Seen \\Flagged", ST_UID);
		//	$status = imap_setflag_full($mbox, $mailUID, "\\Deleted", ST_UID);
		 $MESSAGE_USED=false;
	     
	//	 $messageText = $message;

		if($subject != "") {
			$messageText = $subject;
		}

		if($message != "") {
			$messageText .= $message;
		}
		//not from a white listed or a control number so just a regular user
		//need to check for profanity
		//profanity checker API
		switch($PROFANITY_ENGINE) {
				
			case "NEUTRINO":
				$profanityCheck = check_for_profanity_neutrinoapi($messageText);
				break;
		
			case "WEBPURIFY":
				$profanityCheck = check_for_profanity_WebPurify($messageText);
				break;
		
			default:
				//default turn off profanity check
				$profanityCheck == false;
				break;
		}
		if(!$profanityCheck) {
		
			logEntry("Message: ".$messageText. " PASSED");
		 	// $gv->sendSMS($from,$REPLY_TEXT);
		 	$subject="";
		 	processMessage($from,$messageText);
		 	sendResponse($from,$REPLY_TEXT,$from,$subject);
		 	
		 	sleep(1);
		
		} else {
		$subject="";
		 	logEntry("message: ".$messageText." FAILED");
		 	$PROFANITY_REPLY_TEXT = "Your message contains profanity, sorry. More messages like these will ban your phone number";
		 	$subject="";
		 	sendResponse($from,$PROFANITY_REPLY_TEXT,$from,$subject);
		 	sleep(1);
	        }
		
		

	}
}

/* close the connection */
imap_close($mbox);

lockHelper::unlock();


?>
