<?php
//$DEBUG=true;

include_once "/opt/fpp/www/common.php";
include_once 'functions.inc.php';
include_once 'commonFunctions.inc.php';
$pluginName = "MailControl";

$PLAYLIST_NAME="";
$MAJOR = "98";
$MINOR = "01";
$eventExtension = ".fevt";
//arg0 is  the program
//arg1 is the first argument in the registration this will be --list
//$DEBUG=true;

$SMSEventFile = $eventDirectory."/".$MAJOR."_".$MINOR.$eventExtension;
$SMSGETScriptFilename = $scriptDirectory."/".$pluginName."_GET.sh";

$messageQueue_Plugin = "MessageQueue";
$MESSAGE_QUEUE_PLUGIN_ENABLED=false;


$logFile = $settings['logDirectory']."/".$pluginName.".log";



$messageQueuePluginPath = $settings['pluginDirectory']."/".$messageQueue_Plugin."/";

$messageQueueFile = urldecode(ReadSettingFromFile("MESSAGE_FILE",$messageQueue_Plugin));

if(file_exists($messageQueuePluginPath."functions.inc.php"))
{
	include $messageQueuePluginPath."functions.inc.php";
	$MESSAGE_QUEUE_PLUGIN_ENABLED=true;

} else {
	logEntry("Message Queue Plugin not installed, some features will be disabled");
}


$gitURL = "https://github.com/LightsOnHudson/FPP-Plugin-Mail.git";


$pluginUpdateFile = $settings['pluginDirectory']."/".$pluginName."/"."pluginUpdate.inc";


createSMSSequenceFiles();


logEntry("plugin update file: ".$pluginUpdateFile);


if(isset($_POST['updatePlugin']))
{
	$updateResult = updatePluginFromGitHub($gitURL, $branch="master", $pluginName);

	echo $updateResult."<br/> \n";
}


if(isset($_POST['submit']))
{
	


//	echo "Writring config fie <br/> \n";
	WriteSettingToFile("EMAIL",urlencode($_POST["EMAIL"]),$pluginName);
	WriteSettingToFile("PASSWORD",urlencode($_POST["PASSWORD"]),$pluginName);
	WriteSettingToFile("PLAYLIST_NAME",urlencode($_POST["PLAYLIST_NAME"]),$pluginName);
	WriteSettingToFile("WHITELIST_NUMBERS",urlencode($_POST["WHITELIST_NUMBERS"]),$pluginName);
	WriteSettingToFile("CONTROL_NUMBERS",urlencode($_POST["CONTROL_NUMBERS"]),$pluginName);
	WriteSettingToFile("REPLY_TEXT",urlencode($_POST["REPLY_TEXT"]),$pluginName);
	WriteSettingToFile("VALID_COMMANDS",urlencode($_POST["VALID_COMMANDS"]),$pluginName);
	WriteSettingToFile("ENABLED",urlencode($_POST["ENABLED"]),$pluginName);
	WriteSettingToFile("LAST_READ",urlencode($_POST["LAST_READ"]),$pluginName);
	WriteSettingToFile("API_USER_ID",urlencode($_POST["API_USER_ID"]),$pluginName);
	WriteSettingToFile("API_KEY",urlencode($_POST["API_KEY"]),$pluginName);
	WriteSettingToFile("IMMEDIATE_OUTPUT",urlencode($_POST["IMMEDIATE_OUTPUT"]),$pluginName);
	WriteSettingToFile("MATRIX_LOCATION",urlencode($_POST["MATRIX_LOCATION"]),$pluginName);
	WriteSettingToFile("MAIL_TYPE",urlencode($_POST["MAIL_TYPE"]),$pluginName);
	WriteSettingToFile("MAIL_HOST",urlencode($_POST["MAIL_HOST"]),$pluginName);
	WriteSettingToFile("MAIL_PORT",urlencode($_POST["MAIL_PORT"]),$pluginName);

}

	
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

if($REPLY_TEXT == "") {
	$REPLY_TEXT = "Thank you for your message, it has been added to the Queue";
}
if($VALID_COMMANDS == "") {

	//populate with default valid commands
	$VALID_COMMANDS = "play,stop,repeat,status";
}
	
	//crate the event file
	function createSMSEventFile() {
		
		global $SMSEventFile,$pluginName,$MAJOR,$MINOR,$SMSGETScriptFilename;
		
		
		logEntry("Creating  event file: ".$SMSEventFile);
		
		$data = "";
		$data .= "majorID=".$MAJOR."\n";
		$data .= "minorID=".$MINOR."\n";
		
		$data .= "name='".$pluginName."_GET"."'\n";
			
		$data .= "effect=''\n";
		$data .="startChannel=\n";
		$data .= "script='".pathinfo($SMSGETScriptFilename,PATHINFO_BASENAME)."'\n";
		
		
		
		$fs = fopen($SMSEventFile,"w");
		fputs($fs, $data);
		fclose($fs);
	}
	
	if((int)$LAST_READ == 0 || $LAST_READ == "") {
		$LAST_READ=0;
	}

?>

<html>
<head>
</head>

<div id="MailControl" class="settings">
<fieldset>
<legend>Mail Control Support Instructions</legend>

<p>Known Issues:
<ul>
<li>the fpp daemon doesn't return an active playlist if the command is currently loading a function (i.e. starting a playlist or transitioning to events</li>
<li>Thus you may get a No playlist active at this time</li>
</ul>

<p>Configuration:
<ul>
<li>Configure your whitelist of numbers, and your control number</li>
<li>Your control numbers, and white list numbers should be comma separated</li>
<li>Control numbers can send valid commands to be processed</li>
<li>ALL control numbers will get status commands when including the SMS-STATUS-SEND.FSEQ sequence in a playlist</li>
</ul>
<ul>
<li>Add the crontabAdd options to your crontab to have the sms run every X minutes to process commands</li>
<li>The Writeplaylist script writes the current running playlist (if any) to a tmp file on /tmp</li>
</ul>
<ul>
<li>This uses the profanity checker located at: https://www.neutrinoapi.com/api/bad-word-filter/</li>
<li>You will need to visit this site and generate a userid and API Key</li>
<li>NOTE: it has limited checks on FREE accounts</li>



<form method="post" action="http://<? echo $_SERVER['SERVER_NAME']?>/plugin.php?plugin=<?echo $pluginName;?>&page=plugin_setup.php">


<?
//will add a 'reset' to this later

echo "<input type=\"hidden\" name=\"LAST_READ\" value=\"".$LAST_READ."\"> \n";


$restart=0;
$reboot=0;

echo "ENABLE PLUGIN: ";

if($ENABLED== 1 || $ENABLED == "on") {
		echo "<input type=\"checkbox\" checked name=\"ENABLED\"> \n";
//PrintSettingCheckbox("Radio Station", "ENABLED", $restart = 0, $reboot = 0, "ON", "OFF", $pluginName = $pluginName, $callbackName = "");
	} else {
		echo "<input type=\"checkbox\"  name=\"ENABLED\"> \n";
}

echo "<p/> \n";
echo "Immediately output to Matrix (Run MATRIX plugin): ";

if($IMMEDIATE_OUTPUT == "on" || $IMMEDIATE_OUTPUT == 1) {
	echo "<input type=\"checkbox\" checked name=\"IMMEDIATE_OUTPUT\"> \n";
	//PrintSettingCheckbox("Radio Station", "ENABLED", $restart = 0, $reboot = 0, "ON", "OFF", $pluginName = $pluginName, $callbackName = "");
} else {
	echo "<input type=\"checkbox\"  name=\"IMMEDIATE_OUTPUT\"> \n";
}
echo "<p/> \n";
?>
MATRIX Message Plugin Location: (IP Address. default 127.0.0.1);
<input type="text" size="15" value="<? if($MATRIX_LOCATION !="" ) { echo $MATRIX_LOCATION; } else { echo "127.0.0.1";}?>" name="MATRIX_LOCATION" id="MATRIX_LOCATION"></input>
<p/>
<?


echo "<p/> \n";

echo "Playlist Name: ";
PrintMediaOptions();

 function PrintMediaOptions()
  {
	  global $playlistDirectory;

		echo "<select name=\"PLAYLIST_NAME\">";

	$playlistEntries = scandir($playlistDirectory);
	sort($playlistEntries);
	
    foreach($playlistEntries as $playlist) 
    {
      if($playlist != '.' && $playlist != '..')
      {
        echo "<option value=\"" . $playlist . "\">" . $playlist . "</option>";
      }
	}
  echo "</select>";
  }

echo "<p/> \n";

echo "Email Address: \n";
  
echo "<input type=\"text\" name=\"EMAIL\" size=\"16\" value=\"".$EMAIL."\"> \n";
 
echo "<p/> \n";

echo "Password: \n";

echo "<input type=\"password\" name=\"PASSWORD\" size=\"16\" value=\"".$PASSWORD."\"> \n";


echo "<p/> \n";

echo "Mail Type: \n";
echo "<select name=\"MAIL_TYPE\"> \n";
	if($MAIL_TYPE !="" ) {
              switch ($MAIL_TYPE)
				{
					case "IMAP":
                                		echo "<option selected value=\"".$RESPONSE_METHOD."\">".$RESPONSE_METHOD."</option> \n";
                                		echo "<option value=\"POP3\">POP3</option> \n";
                                		break;
					case "POP3":
                                		echo "<option selected value=\"".$RESPONSE_METHOD."\">".$RESPONSE_METHOD."</option> \n";
                                		echo "<option value=\"IMAP\">IMAP</option> \n";
                        			break;
			
				
	
				}
	
			} else {

                                echo "<option value=\"IMAP\">IMAP</option> \n";
                                echo "<option value=\"POP3\">POP3</option> \n";
			}
               
			echo "</select> \n";
echo "<p/> \n";


echo "Valid Commands: \n";

echo "<input type=\"text\" name=\"VALID_COMMANDS\" size=\"16\" value=\"".$VALID_COMMANDS."\"> \n";


echo "<p/> \n";

echo "Reply Text: \n";

echo "<input type=\"text\" name=\"REPLY_TEXT\" size=\"64\" value=\"".$REPLY_TEXT."\"> \n";
echo "<p/> \n";

echo "White List Numbers(comma separated): \n";

echo "<input type=\"text\" name=\"WHITELIST_NUMBERS\" size=\"64\" value=\"".$WHITELIST_NUMBERS."\"> \n";


echo "<p/> \n";

echo "CONTROL NUMBER: \n";

echo "<input type=\"text\" name=\"CONTROL_NUMBERS\" size=\"16\" value=\"".$CONTROL_NUMBERS."\"> \n";


echo "<p/> \n";

echo "Profanity API User ID: \n";

echo "<input type=\"text\" name=\"API_USER_ID\" size=\"32\" value=\"".$API_USER_ID."\"> \n";


echo "<p/> \n";

echo "Profanity API KEY: \n";

echo "<input type=\"text\" name=\"API_KEY\" size=\"64\" value=\"".$API_KEY."\"> \n";

?>
<p/>
<input id="submit_button" name="submit" type="submit" class="buttons" value="Save Config">
<?
 if(file_exists($pluginUpdateFile))
 {
 	//echo "updating plugin included";
	include $pluginUpdateFile;
}
?>
</form>


<form method="post" action="http://<? echo $_SERVER['SERVER_NAME']?>/plugin.php?plugin=<?echo $pluginName;?>&page=messageManagement.php">
<input id="MessageManagementButton" name="Message Management" type="submit" value="Message Management">
</form>




<p>To report a bug, please file it against the sms Control plugin project on Git:<? echo $gitURL;?> 
</fieldset>
</div>
<br />
</html>
