#!/usr/bin/php
<?php
error_reporting(0);
$pluginName ="SMS";

$DEBUG=false;
$myPid = getmypid();

$skipJSsettings = 1;
include_once("/opt/fpp/www/config.php");
include_once("/opt/fpp/www/common.php");
include_once("functions.inc.php");
$logFile = $settings['logDirectory']."/".$pluginName.".log";

$FPPDStatus = isFPPDRunning();

do{
$cmd = "sudo /opt/fpp/bin/fpp -s > /tmp/FPP.playlist";
exec($cmd,$tmp);
sleep(1);
$playlistName = file_get_contents ("/tmp/FPP.playlist");
logEntry("Playlist name = ".$playlistName);
if($playlistName == "false" && $FPPDStatus == "RUNNING") {
	logEntry("looping... playlist name should not be false..is FPPD running?");
}

if($playlistName == "false" && $FPPDStatus != "RUNNING") {
	logEntry("FPPD Daemon is not running..exiting");
	exit(0);
}

}while($playlistName == "false");
$playlistName = getRunningPlaylist();

logEntry("We got a valid playlist status from fpp -s ");

?>
