<?php
/**
 *   ____                      _      
 *  / ___|___  _ __  ___  ___ | | ___ 
 * | |   / _ \| '_ \/ __|/ _ \| |/ _ \
 * | |__| (_) | | | \__ \ (_) | |  __/
 *  \____\___/|_| |_|___/\___/|_|\___|
 *
 * Console.php - Console (STDOUT) handler
 * Sep 2018 by Harley H. Puthuff
 * Copyright 2016-18, Your Showcase on the Internet
 */

class Console {

	const DEFAULT_PREFIX = '>';				// default line prefix
	const LABEL_SIZE = 20;					// max length of value label
	const DATE_FORMAT = "D M j G:i:s Y";	// output dates

/**
 * Constructor:
 * 
 *	@param string $prefix		: (optional) prefix to output
 */
public function __construct($prefix=null) {
	$this->prefix = $prefix ? $prefix : self::DEFAULT_PREFIX;
	$this->prefix .= " "; // append a space
	$script = $_SERVER['PHP_SELF'];
	$path = pathinfo($script);
	$this->script =
		basename($script,(array_key_exists('extension',$path) ?
			('.'.$path['extension']) : null));
	$this->bold = rtrim(`tput bold`);
	$this->normal = rtrim(`tput sgr0`);
	}

public function __destruct() {}

/**
 * write to STDOUT
 * 
 *	@param string $msg			: one or more strings to write
 */
public function write() {
	foreach (func_get_args() as $msg)
		echo $this->prefix,$msg,"\n";
	}

##
# read from STDIN
#
#	@param string				: (optional) prompt text
#	@param string				: (optional) default value
#	@return string				: input string or undef
#
public function read($prompt=null,$default=null) {
	$pretext = $this->prefix;
	if ($prompt) {
		$pretext .= $prompt;
		if ($default) $pretext .= " [$default]";
		$pretext .= ": ";
		}
	$buffer = readline($pretext);
	if ($default && !$buffer) $buffer = $default;
	return $buffer;
	}

/**
 * confirm a decision or action
 *
 *	@param string				: prompt text
 *	@return boolean				: 0=false, 1=true
 */
public function confirm($prompt) {
	$result = $this->read($prompt." [Y,n]");
	return (preg_match('/n/i',$result)) ? 0 : 1;
	}
	
/**
 * display a header line followed by underscores
 * 
 * @param string $title			: (optional) a title string
 */
public function header($title=null) {
	if (! $title) {
		$ltime = date(self::DATE_FORMAT);
		$title = sprintf("%s start: %s",$this->script,$ltime);
		}
	echo "\n";
	$this->write($title,str_repeat('-',strlen($title)));
	}

/**
 * display a footer line preceeded by underscores
 * 
 * @param string $title			: (optional) a title string
 */
public function footer($title=null) {
	if (! $title) {
		$ltime = date(self::DATE_FORMAT);
		$title = sprintf("%s ended: %s",$this->script,$ltime);
		}
	$this->write(str_repeat('-',strlen($title)),$title);
	echo "\n";
	}

/**
 * Exhibit a label (and value)
 * 
 * @param string $label			: the label or description of value
 * @param string $value			: the actual value or null
 */
public function exhibit($label,$value=null) {
	$labelSize = strlen($label);
	$trailer = ($labelSize >= self::LABEL_SIZE) ? "" :
		str_repeat(' ',(self::LABEL_SIZE - $labelSize));
	if (substr($label,-1) == ':') { #subheading
		$this->write($this->bold.$label.$this->normal);
		}
	else { #label & value
		$value = preg_replace('/[^\x20-\x7E]/','', $value);
		$value = preg_replace('/\s{2,}/',' ',$value);
		$value = rtrim($value);
		if (substr($value,0,1) == ' ')
			$this->write(" ".$label.$trailer." ".$value);
		else
			$this->write(" ".$label.$trailer." ".$this->bold.$value.$this->normal);
		}	
	}	

}
?>