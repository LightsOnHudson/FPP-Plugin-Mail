#!/usr/bin/php
<?php

error_reporting(0);

$pluginName ="SMS";
$myPid = getmypid();

$messageQueue_Plugin = "MessageQueue";
$MESSAGE_QUEUE_PLUGIN_ENABLED=false;

$DEBUG=false;
$LOG_LEVEL=0;

$NEW_MESSAGE=false;

$skipJSsettings = 1;
include_once("/opt/fpp/www/config.php");
include_once("/opt/fpp/www/common.php");
include_once("functions.inc.php");
include_once("commonFunctions.inc.php");
include_once("profanity.inc.php");
//include_once ("GoogleVoice.php");
require 'PHPMailer/PHPMailerAutoload.php';

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

require ("lock.helper.php");

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
	$MAIL_PORT = urldecode($pluginSettings['$MAIL_PORT']);
	$ENABLED = $pluginSettings['ENABLED'];

$LOG_LEVEL = getFPPLogLevel();
logEntry("Log level in translated from fpp settings file: ".$LOG_LEVEL);

if(urldecode($pluginSettings['DEBUG'] != "")) {
        $DEBUG=urldecode($pluginSettings['DEBUG']);
}

if($DEBUG)
        print_r($pluginSettings);


$COMMAND_ARRAY = explode(",",trim(strtoupper($VALID_COMMANDS)));
$CONTROL_NUMBER_ARRAY = explode(",",$CONTROL_NUMBERS);


$WHITELIST_NUMBER_ARRAY = explode(",",$WHITELIST_NUMBERS);

$logFile = $settings['logDirectory']."/".$pluginName.".log";
if($DEBUG)
print_r($COMMAND_ARRAY);

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
        logEntry("user: ".$EMAIL);
        logEntry("pass: ".$PASSWORD);
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
		$mbox = imap_open($hostname,$EMAIL,$PASSWORD) or die('Cannot connect to Gmail: ' . imap_last_error());
		
		break;
		
		
	default:
		//$hostname = '{imap.gmail.com:993/imap/ssl}ALL';
		$path="INBOX";
		
		//$imap_search ='SUBJECT "SMS from"';
		
		$hostname = "{imap.gmail.com:993/imap/ssl/novalidate-cert}$path";
		//$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
		
		/* try to connect */
		$mbox = imap_open($hostname,$EMAIL,$PASSWORD) or die('Cannot connect to Gmail: ' . imap_last_error());
		
		
}



//Message body Format: 
$messageFormat = 1;

    $sorted_mbox = imap_sort($mbox, SORTARRIVAL, 1);
    $totalrows = imap_num_msg($mbox);

	logEntry("total mesages: ".$totalrows);

        $emails = imap_search($mbox,$imap_search);
        //$emails = imap_search($mbox,'SUBJECT "SMS from"');
        //$emails = imap_search($mbox,'ALL');
        //$emails = imap_search($mbox,'UNSEEN');
      //  $emails = imap_search($inbox,'ALL', SE_UID);


if($emails) {
  //echo "got emails";
  /* begin output var */
  $output = '';

  /* put the newest emails on top */
  //rsort($emails);


$max = 20;
$i=0;
  /* for every email... */
  foreach($emails as $email_number) {


    /* get information specific to this email */
    $overview = imap_fetch_overview($mbox,$email_number,0);
    $message = imap_fetchbody($mbox,$email_number,$messageFormat);

	$mailUID = $overview[0]->uid;


    /* output the email header information */

	$subject = $overview[0]->subject." ";
	$from =  $overview[0]->from;

	//echo "From: ".$from."\n";
	$from =  get_string_between($from,"<","@");
	//echo "from: ".$from."\n";

	//the to is the first one and the from is the second

	$to = substr($from,1,strpos($from,".")-1);

	//echo "To: ".$to."\n";

	$from = substr($from,strpos($from,".")+1);
		//echo "From: ".$from."\n";

	$from = substr($from,1,strpos($from,".")-1);

	$from = trim($from);

	//$from = $phoneNumber;
	//echo "from: ".$from."\n";

	$mailUID = $overview[0]->uid;

	$messageDate =  $overview[0]->date."\n  ";


    /* output the email body */
	

	//$subject = "abcdef";
	$pattern = "/SMS from/i";
	//echo "Looking for: ".$pattern." in message: ".$subject."\n";
	
	preg_match($pattern, $subject, $matches);
	//print_r($matches);

//	echo "subject: ".$subject." \n";

	$pos = strpos($subject, "SMS From");

	if($matches[0] !="" ) {

		if($DEBUG) {
		//logEntry("Message number: ".$email_number);
	//	logEntry("message uid: ".$mailUID);
	//	logEntry( "we got a match");
		}
		$phoneNumber = get_string_between ($subject,"[","]");
		$phoneNumber = preg_replace('/(\W*)/', '', $phoneNumber);
	//	logEntry("Phone number: ".$from);

		//get the message up to the first carriage return???
	
		$message = substr($message,0,strpos($message,"\n"));
		//logEntry("messagedate: ".$messageDate." UDate: ".$overview[0]->udate);

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
		$GMAIL_ADDRESS = $overview[0]->from;
		$GMAIL_ADDRESS = get_string_between($GMAIL_ADDRESS,"<",">");
//		$from =  $overview[0]->from;
		//echo "from: ".$from."\n";
//		$from = get_string_between($from,"<",".");
	
		//echo "from: ".$from."\n";

		//US based numbers, strip the 1 in the front
//		$from = substr($from,1);
		logEntry( "from: ".$from);

	 $status = imap_setflag_full($mbox, $mailUID, "\\Seen \\Flagged", ST_UID);
 $MESSAGE_USED=false;
        $messageText = $message;

        logEntry("processing message: ".$mailUID." from: ".$from." Message: ".$messageText);

                $messageText= preg_replace('/\s+/', ' ', $messageText);
                $messageParts = explode(" ",$messageText);

        if(in_array($from,$CONTROL_NUMBER_ARRAY))
        {
                ///message used is to make sure that we do not process a message twice if it is from a number that is both a whitelist AND control numbers
                $MESSAGE_USED=true;
                logEntry("Control number found: ".$from);
                //process the command see if it is in the valid commands

                //see if they sent in a playlist name???
                //that would mean there is a space in the command.

                //if(count($messageParts) > 1) {
                //      logEntry("did we get a command with playlist");
                //      logEntry("Command: ".$messageParts[0]);
                //      logEntry("playlist: ".$messageParts[1]);
                //}

                if(in_array(trim(strtoupper($messageParts[0])),$COMMAND_ARRAY)) {
                        logEntry("Command request: ".$messageText. " in uppercase is in control array");
                        //do we have a playlist name?
                        if($messageParts[1] != "") {

                                processSMSCommand($from,$messageParts[0],$messageParts[1]);
                        } else {

                                //play the configured playlist@!!!! from the plugin
                                processSMSCommand($from,$messageParts[0],$PLAYLIST_NAME);
                        }

                } else {
                                //generic message to display from control number just like a regular user
                                processSMSMessage($from,$messageText);
                                $subject="";
                                sendResponse($from,$REPLY_TEXT,$GMAIL_ADDRESS,$subject);
                                
                             
                                sleep(1);

                                //processReadSentMessages();
                        }

                }

 if(in_array($from,$WHITELIST_NUMBER_ARRAY) && !$MESSAGE_USED)

                        {
                                $MESSAGE_USED=true;
                                logEntry($messageText. " is from a white listed number");
                                processSMSMessage($from,$messageText);
                                 
                                $subject="";
                                
                                sendResponse($from,$REPLY_TEXT,$GMAIL_ADDRESS,$subject);
                                sleep(1);

        } else if(!$MESSAGE_USED){

                                //not from a white listed or a control number so just a regular user
                                //need to check for profanity
                                //profanity checker API
                                $profanityCheck = check_for_profanity($messageText);

                                //returns a list of array,
                                if($profanityCheck['is-bad'] == 0 && $profanityCheck['bad-words-total'] == 0) {

                                        logEntry("Message: ".$messageText. " PASSED");
                                         // $gv->sendSMS($from,$REPLY_TEXT);
                                $subject="";
                             
                                sendResponse($from,$REPLY_TEXT,$GMAIL_ADDRESS,$subject);
                                        processSMSMessage($from,$messageText);
                                        sleep(1);

                                } else {
                                	$subject="";
                                        logEntry("message: ".$messageText." FAILED");
                                        $REPLY_TEXT = "Your message contains profanity, sorry. More messages like these will ban your phone number";
                                         // $gv->sendSMS($from,$REPLY_TEXT);
                               $subject="";
                                sendResponse($from,$REPLY_TEXT,$GMAIL_ADDRESS,$subject);
                                sleep(1);

                                }
        }


        }



	
	}

 
} 




/* close the connection */
imap_close($mbox);
if(!$NEW_MESSAGE){
	logEntry("No New messages to process exiting",0);
	lockHelper::unlock();
	exit(0);
}
if($IMMEDIATE_OUTPUT != "on" && $IMMEDIATE_OUTPUT != "1") {
	logEntry("NOT immediately outputting to matrix");
} else {
	logEntry("IMMEDIATE OUTPUT ENABLED");
	logEntry("Forking Matrix command");
	
	logEntry("Matrix location: ".$MATRIX_LOCATION);
	logEntry("Matrix Exec page: ".$MATRIX_EXEC_PAGE_NAME);

	if($MATRIX_LOCATION != "127.0.0.1") {
		$remoteCMD = "/usr/bin/curl -s --basic 'http://".$MATRIX_LOCATION."/plugin.php?plugin=".$MATRIX_MESSAGE_PLUGIN_NAME."&page=".$MATRIX_EXEC_PAGE_NAME."&nopage=1' > /dev/null";
		logEntry("REMOTE MATRIX TRIGGER: ".$remoteCMD);
		
		$forkResult = forkExec($remoteCMD);
		if($DEBUG)
			logEntry("DEBUG: Fork Result: ".$forkResult);
		
		//exec($remoteCMD);
	} else {
		$IMMEDIATE_CMD = $settings['pluginDirectory']."/".$MATRIX_MESSAGE_PLUGIN_NAME."/matrix.php";
		logEntry("LOCAL command: ".$IMMEDIATE_CMD);
		$forkResult = forkExec($IMMEDIATE_CMD);
		if($DEBUG)
			logEntry("DEBUG: Fork Result: ".$forkResult);
		
		//exec($IMMEDIATE_CMD);
	}
}

lockHelper::unlock();



?>
