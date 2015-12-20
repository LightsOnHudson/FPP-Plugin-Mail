<?php
//$DEBUG=true;

include_once "/opt/fpp/www/common.php";
include_once 'functions.inc.php';
include_once 'commonFunctions.inc.php';
$pluginName = "MailControl";

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


$gitURL = "https://github.com/LightsOnHudson/FPP-Plugin-SMS.git";




$pluginMessages = getPluginMessages($pluginName, 0);

//print_r($pluginMessages);
$messageCount = count($pluginMessages);


echo "<center><h1><b>".$pluginName." Message Management</b></h1></center> <br/> \n";

//echo "<textarea class=\"FormElement\" name=\"messages\" id=\"messages\" cols=\"40\" rows=\"".$messageCount."\">\n";
echo "<table cellspacing=\"3\" cellpadding=\"3\" border=\"1\"> \n";

for($i=0;$i<=$messageCount-1;$i++ ) {

	echo "<tr> \n";
	
	$messageQueueParts = explode("|",$pluginMessages[$i]);
	
	//unix timestamp
	echo "<td> \n";
	
	echo date('d M Y H:i:s',$messageQueueParts[0]);
	echo "</td> \n";
	
	echo "<td> \n";
	//message data
	echo urldecode($messageQueueParts[1]);
	echo "</td> \n";
	
	//plugin Subscription
	//echo "<td> \n";
	
	//echo $messageQueueParts[2];
	//echo "</td> \n";
	
	echo "</tr> \n";
	//echo $pluginMessages[$i];
}

echo "</table> \n";
//echo "</textarea> \n";


?>
