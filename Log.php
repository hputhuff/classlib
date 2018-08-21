<?php
/*---------------------------------------------------------------------*\
|	   Log class - Methods for using the System Log (syslog)	|
\*---------------------------------------------------------------------*/

class Log {

/**
 * ::entry method - log an entry to the SYSLOG log
 * @param mixed $message1			: int,string,array,object
 * @param mixed $message2			: (optional) int,string,array,object
 */
static function entry($message1,$message2=null) {
	$identity = "Medusa";
	openlog($identity,0,LOG_USER);
	if (is_array($message1) || is_object($message1)) $message1 = print_r($message1,true);
	$buffer = preg_replace('/[\x00-\x1F\x80-\xFF]/',' ',$message1);
	syslog(LOG_INFO,preg_replace('/\s+/',' ',$buffer));
	if ($message2) {
		if (is_array($message2) || is_object($message2)) $message2 = print_r($message2,true);
		$buffer = preg_replace('/[\x00-\x1F\x80-\xFF]/',' ',$message2);
		syslog(LOG_INFO,preg_replace('/\s+/',' ',$buffer));
		}
	closelog();
	}

}

?>