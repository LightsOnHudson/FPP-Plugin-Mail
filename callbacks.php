#!/usr/bin/php
<?
//error_reporting(0);

$skipJSsettings = 1;
include_once '/opt/fpp/www/config.php';
include_once '/opt/fpp/www/common.php';

include_once "functions.inc.php";

include_once ("GoogleVoice.php");
require ("lock.helper.php");

define('LOCK_DIR', '/tmp/');
define('LOCK_SUFFIX', '.lock');
$pluginName = "SMS";


$myPid = getmypid();

//arg0 is  the program
//arg1 is the first argument in the registration this will be --list
//$DEBUG=true;
$logFile = $settings['logDirectory']."/".$pluginName.".log";

$ENABLED="";

$ENABLED = trim(urldecode(ReadSettingFromFile("ENABLED",$pluginName)));

if($ENABLED != "on" && $ENABLED != "1") {
	logEntry("Plugin Status: DISABLED Please enable in Plugin Setup to use & Restart FPPD Daemon");

	exit(0);
}


$EMAIL = urldecode(ReadSettingFromFile("EMAIL",$pluginName));
$PASSWORD = urldecode(ReadSettingFromFile("PASSWORD",$pluginName));
$PLAYLIST_NAME = urldecode(ReadSettingFromFile("PLAYLIST_NAME",$pluginName));
$WHITELIST_NUMBERS = urldecode(ReadSettingFromFile("WHITELIST_NUMBERS",$pluginName));
$CONTROL_NUMBERS = urldecode(ReadSettingFromFile("CONTROL_NUMBERS",$pluginName));
$REPLY_TEXT = urldecode(ReadSettingFromFile("REPLY_TEXT",$pluginName));
$VALID_COMMANDS = urldecode(ReadSettingFromFile("VALID_COMMANDS",$pluginName));

$COMMAND_ARRAY = explode(",",trim(strtoupper($VALID_COMMANDS)));
$CONTROL_NUMBER_ARRAY = explode(",",$CONTROL_NUMBERS);

$PLAYLIST_NAME = getRunningPlaylist();

//none at this time
$callbackRegisters = "media";
//$callbackRegisters = "playlist,media";
//var_dump($argv);

$FPPD_COMMAND = $argv[1];

//echo "FPPD Command: ".$FPPD_COMMAND."<br/> \n";

if($FPPD_COMMAND == "--list") {

	echo $callbackRegisters;
	logEntry("FPPD List Registration request: responded:". $callbackRegisters);
	exit(0);
}

if($FPPD_COMMAND == "--type") {
	logEntry("type callback requested");
	//we got a register request message from the daemon
	processCallback($argv);
	exit(0);
}

logEntry($argv[0]." called with no parameteres");
exit(0);

?>
