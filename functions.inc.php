<?php

//get the fpp log level
function getFPPLogLevel() {
	
	$FPP_LOG_LEVEL_FILE = "/home/fpp/media/settings";
	if (file_exists($FPP_LOG_LEVEL_FILE)) {
		$FPP_SETTINGS_DATA = parse_ini_file($FPP_LOG_LEVEL_FILE);
	} else {
		//return log level 0
		return 0;
	}
	
		logEntry("FPP Settings file: ".$FPP_LOG_LEVEL_FILE);
		
		$logLevelString = trim($FPP_SETTINGS_DATA['LogLevel']);
		logEntry("Log level in fpp settings file: ".$logLevelString);
		
		switch($logLevelString) {
			
			
			case "info":
				$logLevel=0;
				
			//	break;
				
			case "warn":
				$logLevel=1;
				
			//	break;
				
			case "debug":
				
				$logLevel=2;
				
			//	break;
				
			case "excess":
				
				$logLevel=3;
				
				//break;
				
			 default:
				$logLevel = 0;
				
				
		}
		
		
	
	return $logLevel;
	
}
function processSMSMessage($from,$messageText) {
        global $pluginName,$MESSAGE_QUEUE_PLUGIN_ENABLED;


      //  logEntry("Adding message from: ".$from. ": ".$messageText. " to message queue");
        if($MESSAGE_QUEUE_PLUGIN_ENABLED) {
                addNewMessage($messageText,$pluginName,$from);
        } else {
                logEntry("MessageQueue plugin is not enabled/installed: Cannot add message: ".$messageText);
        }

        return;


}

function processMessage($from, $messageText) {
	global $DEBUG;
	
	if($DEBUG)
		logEntry("inside process message");
	
	processSMSMessage($from, $messageText);
	
	if($DEBUG)
		logEntry("leaving process message");
	
	return;
}
//old profanity checkers
function profanityChecker($messageText) {

        $profanityCheck = false;

        logEntry("Checking:  ".$messageText." for profanity");

        return $profanityCheck;

}

//process the SMS commnadn coming in from a control number
function processSMSCommand($from,$SMSCommand="",$playlistName="") {

        global $gv,$DEBUG;
        $FPPDStatus=false;
        $output="";


     //   if($playlistName != "") {
                $PLAYLIST_NAME = trim($playlistName);
      //  } else {
     //           logEntry("No playlist name specified, using Plugin defined playlist: ".$PLAYLIST_NAME);
     //   }

        logEntry("Processing command: ".$SMSCommand." for playlist: ".$PLAYLIST_NAME);

        $FPPDStatus = isFPPDRunning();

        logEntry("FPPD status: ".$FPPDStatus);
        if($FPPDStatus != "RUNNING") {
                logEntry("FPPD NOT RUNNING: Sending message to : ".$from. " that FPPD status: ".$FPPDStatus);
                //send a message that the daemon is not running and cannot execute the command
                $gv->sendSMS($from, "FPPD is not running, cannot execute cmd: ".$SMSCommand);
                sleep(1);
                processReadSentMessages();
                return;
        } else {
                logEntry("Sending message to : ".$from. " that FPPD status: ".$FPPDStatus);
                $gv->sendSMS($from,"FPPD is running, I will execute command: ".$SMSCommand);
                sleep(1);
                //if sending a message.. need to clear it as it may hose up the next queue of messages
                processReadSentMessages();
        } 
       $cmd = "/opt/fpp/bin/fpp ";

        switch (trim(strtoupper($SMSCommand))) {

                case "PLAY":
                         $cmd .= "-P \"".$PLAYLIST_NAME."\"";
                        break;

                case "STOP":
                        $cmd .= "-c stop";

                        break;

                case "REPEAT":

                        $cmd .= "-p \"".$PLAYLIST_NAME."\"";
                        break;

                case "STATUS":
                        $playlistName = getRunningPlaylist();
                        if($playlistName == null) {
                                $playlistName = " No current playlist active or FPPD starting, please try your command again in a few";
                        }
                        logEntry("Sending SMS to : ".$from. " playlist: ".$playlistName);
                        $gv->sendSMS($from,"Playlist STATUS: ".$playlistName);
                        break;

                default:

                        $cmd = "";
                        break;
        }

        if($cmd !="" ) {
                logEntry("Executing SMS command: ".$cmd);
                exec($cmd,$output);
                //system($cmd,$output);

        }
logEntry("Processing command: ".$cmd);

}
//is fppd running?????
function isFPPDRunning() {
	$FPPDStatus=null;
	logEntry("Checking to see if fpp is running...");
        exec("if ps cax | grep -i fppd; then echo \"True\"; else echo \"False\"; fi",$output);

        if($output[1] == "True" || $output[1] == 1 || $output[1] == "1") {
                $FPPDStatus = "RUNNING";
        }
	//print_r($output);

	return $FPPDStatus;
        //interate over the results and see if avahi is running?

}
//get current running playlist
function getRunningPlaylist() {

	global $sequenceDirectory;
	$playlistName = null;
	$i=0;
	//can we sleep here????

	//sleep(10);
	//FPPD is running and we shoud expect something back from it with the -s status query
	// #,#,#,Playlist name
	// #,1,# = running

	$currentFPP = file_get_contents("/tmp/FPP.playlist");
	logEntry("Reading /tmp/FPP.playlist : ".$currentFPP);
	if($currentFPP == "false") {
		logEntry("We got a FALSE status from fpp -s status file.. we should not really get this, the daemon is locked??");
	}
	$fppParts="";
	$fppParts = explode(",",$currentFPP);
//	logEntry("FPP Parts 1 = ".$fppParts[1]);

	//check to see the second variable is 1 - meaning playing
	if($fppParts[1] == 1 || $fppParts[1] == "1") {
		//we are playing

		$playlistParts = pathinfo($fppParts[3]);
		$playlistName = $playlistParts['basename'];
		logEntry("We are playing a playlist...: ".$playlistName);
		
	} else {

		logEntry("FPPD Daemon is starting up or no active playlist.. please try again");
	}
	
	
	//now we should have had something
	return $playlistName;
}
//create sequence files
function createSMSSequenceFiles() {
        global $sequenceDirectory;
        $SMSStartSendSequence= $sequenceDirectory."/"."SMS-STATUS-SEND.FSEQ";

        $tmpFile = fopen($SMSStartSendSequence, "w") or die("Unable to open file for writing SMS SequencesFile!");
        fclose($tmpFile);

}
function processSequenceName($sequenceName,$sequenceAction="NONE RECEIVED") {

	global $CONTROL_NUMBER_ARRAY,$PLAYLIST_NAME,$EMAIL,$PASSWORD,$pluginDirectory,$pluginName;
        logEntry("Sequence name: ".$sequenceName);

        $sequenceName = strtoupper($sequenceName);
	//$PLAYLIST_NAME= getRunningPlaylist();

	if($PLAYLIST_NAME == null) {
		$PLAYLIST_NAME = "FPPD Did not return a playlist name in time, please try again later";
	}
//        switch ($sequenceName) {

 //               case "SMS-STATUS-SEND.FSEQ":

                $messageToSend="";
		$gv = new GoogleVoice($EMAIL, $PASSWORD);

		//send a message to all numbers in control array and then delete them from new messages
		for($i=0;$i<=count($CONTROL_NUMBER_ARRAY)-1;$i++) {
			logEntry("Sending message to : ".$CONTROL_NUMBER_ARRAY[$i]. " that playlist: ".$PLAYLIST_NAME." is ACTION:".$sequenceAction);
			//get the current running playlist name! :)	

				//$gv->sendSMS($CONTROL_NUMBER_ARRAY[$i], "PLAYLIST EVENT: ".$PLAYLIST_NAME." Action: ".$sequenceAction);
				$gv->sendSMS($CONTROL_NUMBER_ARRAY[$i], "PLAYLIST EVENT: Action: ".$sequenceAction);
		
		}		
		logEntry("Plugin Directory: ".$pluginDirectory);
		//run the sms processor outside of cron
		$cmd = $pluginDirectory."/".$pluginName."/getSMS.php";

		exec($cmd,$output); 

//		break;
//                exit(0);

 //               default:
  //                      logEntry("We do not support sequence name: ".$sequenceName." at this time");

   //                     exit(0);

    //    }

}
//process new messages
function processNewMessages() {

	global $gv,$EMAIL,$DEBUG;
	logEntry("processing new entries in SMS queue - if any");
	$messageQueue = array();
	$newmsgIDs = array();
	$sms = $gv->getUnreadSMS();
	logEntry("SMS COUNT: ".count($sms)." ----");

	$newMessageCount=0;
	foreach($sms as $s) {
	
		logEntry("NEW Message from: ".$s->phoneNumber." on ".$s->displayStartDateTime.": ".$s->messageText);
	
		$from = $s->phoneNumber;
		$msgText = $s->messageText;
		
		if($DEBUG) {
			logEntry("From: ".$from." MsgText: ".$msgText);
		}
	
		//strip the +1 from the phone number
		if(substr($from,0,2) == "+1")
		{
			$from=substr($from,2);
		}
	
		$messageQueue[$newMessageCount]=array($from,$msgText);
		
		if($DEBUG){
			print_r($messageQueue);
		}
	
		$newMessageCount++;
	
		if(!in_array($s->id, $newmsgIDs)) {
			// Mark the message as read in your Google Voice Inbox.
			//	$gv->markMessageRead($s->id);
			//sleep(1);
		//	$gv->deleteMessage($s->id);
		//	$newmsgIDs[] = $s->id;
		}
	}
	
	if($newMessageCount > 0) {
		logEntry ("Received : ".$newMessageCount. " Messages in Queue");
	} else {
		logEntry("No messages in queue: ".$EMAIL." to process");
			//exit here : dec 9
			lockHelper::unlock();
			exit(0);
		//return null;
	
	}
	return $messageQueue;
}
//process read/sent messages

function processReadSentMessages() {

	global $gv;
	
	logEntry("Processing read/sent messages in queue - if any");
	$readmsgIDs = array();
	
	//clean up old READ messages
	$smsRead = $gv->getReadSMS();
	$readMessageCount=0;
	
	foreach($smsRead as $s) {
		//	logEntry("Erasing Message: ".$readMessageCount);
		logEntry("Erasing Message from: ".$s->phoneNumber." on ".$s->displayStartDateTime.": ".$s->messageText);
		if(!in_array($s->id, $readmsgIDs)) {
		$readMessageCount++;
	
			$gv->deleteMessage($s->id);//
			$readmsgIDs[] = $s->id;
		}
	}
	
	if($readMessageCount>0 ) {
		logEntry("Erased ".$readMessageCount." Messages from READ queue");
	} else {
		logEntry(" No READ messages to purge from queue");
	}
	
	
}
function logEntry($data,$logLevel=1) {

	global $logFile,$myPid, $LOG_LEVEL;

	
	if($logLevel <= $LOG_LEVEL) 
		return
		
		$data = $_SERVER['PHP_SELF']." : [".$myPid."] ".$data;
		
		$logWrite= fopen($logFile, "a") or die("Unable to open file!");
		fwrite($logWrite, date('Y-m-d h:i:s A',time()).": ".$data."\n");
		fclose($logWrite);


}



function processCallback($argv) {
	global $DEBUG,$pluginName;
	
	
	if($DEBUG)
		print_r($argv);
	//argv0 = program
	
	//argv2 should equal our registration // need to process all the rgistrations we may have, array??
	//argv3 should be --data
	//argv4 should be json data
	
	$registrationType = $argv[2];
	$data =  $argv[4];
	
	logEntry("PROCESSING CALLBACK: ".$registrationType);
	$clearMessage=FALSE;
	
	switch ($registrationType)
	{
		case "media":
			if($argv[3] == "--data")
			{
				$data=trim($data);
				logEntry("DATA: ".$data);
				$obj = json_decode($data);
	
				$type = $obj->{'type'};
				logEntry("Type: ".$type);	
				switch ($type) {
						
					case "sequence":
						logEntry("media sequence name received: ");	
						processSequenceName($obj->{'Sequence'},"STATUS");
							
						break;
					case "media":
							
						logEntry("We do not support type media at this time");
							
						//$songTitle = $obj->{'title'};
						//$songArtist = $obj->{'artist'};
	
	
						//sendMessage($songTitle, $songArtist);
						//exit(0);
	
						break;
						
						case "both":
								
						logEntry("We do not support type media/both at this time");
						//	logEntry("MEDIA ENTRY: EXTRACTING TITLE AND ARTIST");
								
						//	$songTitle = $obj->{'title'};
						//	$songArtist = $obj->{'artist'};
							//	if($songArtist != "") {
						
						
						//	sendMessage($songTitle, $songArtist);
							//exit(0);
						
							break;
	
					default:
						logEntry("We do not understand: type: ".$obj->{'type'}. " at this time");
						exit(0);
						break;
	
				}
	
	
			}
	
			break;
			exit(0);
	
		case "playlist":

			logEntry("playlist type received");
			if($argv[3] == "--data")
                        {
                                $data=trim($data);
                                logEntry("DATA: ".$data);
                                $obj = json_decode($data);
				$sequenceName = $obj->{'sequence0'}->{'Sequence'};	
				$sequenceAction = $obj->{'Action'};	
                                                processSequenceName($sequenceName,$sequenceAction);
                                                //logEntry("We do not understand: type: ".$obj->{'type'}. " at this time");
                                        //      logEntry("We do not understand: type: ".$obj->{'type'}. " at this time");
			}

			break;
			exit(0);			
		default:
			exit(0);
	
	}
	

}
?>
