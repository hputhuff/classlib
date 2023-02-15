<?php
/**
 * Simplicity - A simple PHP framework
 * v1.1, March 2023 by Harley H. Puthuff
 * Copyright 2023, Your Showcase on the Internet
 */

/**
 * Global default variables
 */
class Simplicity {
	public static $database = [		// database defaults
			'hostname'	=> "localhost",
			'username'	=> "databoss",
			'password'	=> "dbpasswd"
			];
}

/**
 * Data - class methods to manipulate data
 */
class Data {

// Find first non-null value in the arguments

public static function sift() {
	foreach (func_get_args() as $arg) if ($arg) return $arg;
	return null;
	}
	
// return a space-separated string for the arguments

public static function combine() {
	$provided = func_get_args(); // get all arguments
	$valued = array();
	foreach ($provided as $arg) if ($arg) $valued[] = $arg;
	$values = count($valued);
	if (! $values) return null;
	if ($values == 1) return $valued[0];
	return join(' ',$valued);
	}

// render a string as displayable (such as db column name)

public static function toDisplayName($name) {
  $interim = preg_replace('/([a-z0-9])([A-Z])/','$1_$2',$name);
	$parts = preg_split('/[^0-9a-zA-Z]+/',$interim);
	foreach ($parts as &$part) $part = ucfirst($part);
	return join(' ',$parts);
	}

// render a phone number as a displayable string

public static function toDisplayPhone($phone=null) {
	if (! $phone) return null;
	$number = preg_replace('/[^0-9]/','',$phone);
	if (preg_match('/^1?(\d{3})(\d{3})(\d{4})$/',$number,$parts))
		// US number
		return "({$parts[1]}) {$parts[2]}-{$parts[3]}";
	else
		// non-US number
		return "+{$number}";
	}

/**
 * convert a string to hex notation
 * 
 * @param string $string		: ascii string
 * @return string				: string of hex digits
 */
public static function toHex($string) {
    $hex='';
    for ($i=0; $i < strlen($string); $i++) {
        $hex .= dechex(ord($string[$i]));
        }
    return strtoupper($hex);
	}

/**
 * convert a string of hex digits to ascii
 * 
 * @param string $hex			: string of hex digits
 * @return string				: ascii string
 */
public static function toAscii($hex) {
    $string='';
    for ($i=0; $i < strlen($hex)-1; $i+=2) {
        $string .= chr(hexdec($hex[$i].$hex[$i+1]));
       }
    return $string;
	}

/**
 * mask all but nn digits of a credit card number
 * 
 * @param string $number		: full number
 * @param int $visible			: (optional) digits to be visible [4]
 * @return string				: masked card number
 */
public static function maskCardNumber($number,$visible=4) {
	$fullsize = strlen($number);
	if ($visible < $fullsize)
		$mask = str_repeat('*',$fullsize-$visible) . substr($number,-$visible);
	else
		$mask = str_repeat('*',$fullsize);
	return $mask;
	}

/**
 * print breakout of variable
 * 
 * @param mixed $thing			: string/array/object
 */
public static function breakout(&$thing) {
	if ($_SERVER['DOCUMENT_ROOT'])
		echo "<pre style='font:10pt monospace;color:#000;".
			 "background:#fff;text-align:left;'>\n";
	print_r($thing);
	if ($_SERVER['DOCUMENT_ROOT']) echo "</pre>\n";
	}

/**
 * exhibit variable with types
 * 
 * @param mixed $thing			: int/string/etc.
 */
public static function exhibit(&$thing) {
	echo "<pre>\n";
	foreach ($thing as $key=>$data) {
		switch (gettype($data)) {
			case "boolean":
				$show = "[boolean]={$data}";
				break;
			case "integer":
				$show = "[integer]={$data}";
				break;
			case "double":
				$show = "[double]={$data}";
				break;
			case "string":
				$show = "[string]={$data}";
				break;
			case "array":
				$show = "[array]";
				break;
			case "object":
				$show = "[object]";
				break;
			case "resource":
				$show = "[resource handle]";
				break;
			case "NULL":
				$show = "NULL";
				break;
			default:
				$show = "-unknown-";
			}
		echo "\t$key: $show\n";
		}
	echo "</pre>\n\n";
	}

/**
 * return a random string of nn characters
 * 
 * @param int $length			: size of returned string
 * @return string				: random string
 */
public static function randomString($length) {
    $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $string = "";
    for ($p = 0; $p < $length; $p++) {
        $string .= $characters[mt_rand(0,strlen($characters))];
		}
    return $string;
	}

/**
 * return a Skype-proof string that won't be rendered as a link
 * 
 * @param string $text			: plain number or text
 * @return string				: skype-proof text
 */
public static function skypeProof($text) {
	$half = (int)(strlen($text)/2);
	return substr($text,0,$half)."<span style='display:none;'>_</span>".substr($text,$half);
	}

/**
 * limit a string to no more than nn characters
 * 
 * @param string $str			: original string
 * @param int $limit			: (optional) max size [40]
 * @return string				: limited string
 */
public static function limit($str,$limit=40) {
	$string = trim($str);
	$max = 0 + $limit - 3;
	$size = strlen($string);
	if (($max < 1) || ($size <= $limit)) return $string;
	return substr($string,0,$max).'...';
	}

/**
 * Return an array converted to a string w/quoted elements
 * 
 * @param array $list				: array of elements
 * @return string					: string as: 'aaa','bbb'...'zzz'
 */
public static function arrayToString($list) {
	return "'".join("','",$list)."'";
	}

/**
 * Produce a text summary table of key=>value lines
 * @param array $hash
 * @return string
 */
public static function summaryTable($hash) {
	$len = 0;
	foreach (array_keys($hash) as $key) {
		$klen = strlen($key);
		if ($klen > $len) $len = $klen;
		}
	$format = "%-{$len}s : %s\n";
	$summary = "";
	foreach ($hash as $key=>$value)
		$summary .= sprintf($format,$key,$value);
	return $summary;
	}

}

/**
 * Databoss SQL database services
 */
class Databoss {

	public static $lastDataboss;
	private static $reportingEnabled = false;
	
	public $database;
	public $hostname;
	public $username;
	public $password;
	public $db;				// connection to mysqli
	public $tables;		// hash of table definitions

// constructor

public function __construct($name,$host=null,$user=null,$pass=null) {
	$this->database = $name;
	$this->hostname = $host ? $host : Simplicity::$database['hostname'];
	$this->username = $user ? $user : Simplicity::$database['username'];
	$this->password = $pass ? $pass : Simplicity::$database['password'];
	if (! self::$reportingEnabled) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		self::$reportingEnabled = true;
		}
	if (!$this->database ||
			!($this->db = new mysqli(
					$this->hostname,
					$this->username,
					$this->password,
					$this->database)
				)
			) {
		die("Databoss: cannot obtain database connection for: $this->database !");
		}
	$this->tables = array();
	$results = $this->db->query("SHOW TABLES");
	while (list($table) = $results->fetch_row()) $this->tables[$table] = null;
	$results->close();
	self::$lastDataboss = $this;
	}

// destructor

public function __destruct() {
	if ($this->db) $this->db->close();
	}

// obtain the last error that occured as a string

public function error() {
	$errno = $this->db->errno;
	$error = $this->db->error;
	return $errno ? "$error ($errno)" : null;
	}

// Log database/SQL errors

public function logErrors($query) {
	if (! ($message = $this->error())) return;
	Log::entry($message,$query);
	return false;
	}

// obtain the structure for a table on the database
//	returns ref to table array entry or false

public function &structure($table=null) {
	if (!$table || !array_key_exists($table,$this->tables)) return false;
	$focus =& $this->tables[$table];
	if (is_array($focus)) return $focus;
	$focus = array();
	$focus['table'] = $table;
	$focus['primarykey'] = null;
	$focus['primarykeycolumn'] = null;
	$focus['autoincrement'] = null;
	$focus['properties'] = array();
	$focus['formats'] = array();
	$focus['defaults'] = array();
	$query = "DESCRIBE $table";
	$results = $this->db->query($query);
	if (!$results || $this->db->errno) {
		$this->logErrors($query);
		return false;
		}
	while ($column = $results->fetch_object()) {
		array_push($focus['properties'],$column->Field);
		array_push($focus['formats'],$column->Type);
		array_push($focus['defaults'],$column->Default);
		if (preg_match('/pri/i',$column->Key)) {
			if ($focus['primarykey']) {
				$focus['primarykey'] .= ",{$column->Field}";
				}
			else {
				$focus['primarykey'] = $column->Field;
				$focus['primarykeycolumn'] = sizeof($focus['properties']);
				if (preg_match('/auto/i',$column->Extra))
					$focus['autoincrement'] = $focus['primarykeycolumn'];
				}
			}
		}
	$results->close();
	return $focus;
	}

// obtain a list of properties/columns for a table

public function properties($table=null) {
	$structure =& $this->structure($table);
	return $structure['properties'];
	}

// return a string properly escaped for use in a query

public function escape($string) {
	return $this->db->escape_string($string);
	}

// return the last inserted id for autoincrement tables

public function lastInsertId() {
	return $this->db->insert_id;
	}

// perform a query against the database & return affected rows

public function query($sql=null) {
	if (! $sql) return 0;
	$result = $this->db->query($sql);
	if ($result===false) return 0;
	if ($result===true) return $this->db->affected_rows;
	if (!is_object($result)) return 1;
	$rows = $result->num_rows;
	$result->close();
	return $rows;
	}

// fetch record(s) from the database and return them as a list of object(s)
//	if a class name is provided, each of the objects is of that class

public function fetch($sql,$class=null) {
	$objects = array();
	if (! ($result = $this->db->query($sql))) return $this->logErrors($sql);
	while ($obj = $result->fetch_object()) {
		if ($class) {
			$objects[] = new $class();
			$objects[count($objects)-1]->merge($obj);
			}
		else
			$objects[] = $obj;
			}
	$result->close();
	return $objects;
	}

// fetch a hash of choices (key=>value)

public function fetchChoices($sql) {
	$choices = array();
	if (!$result = $this->db->query($sql)) return null;
	while (list($key,$value) = $result->fetch_row()) $choices[$key] = $value;
	$result->close();
	return $choices;
	}

// store record(s) on the database and return # stored

public function store($sql) {
	return $this->query($sql);
	}

// update record(s) on the database and return # updated

public function update($sql) {
	return $this->query($sql);
	}

// delete record(s) on the database and return # deleted

public function delete($sql) {
	return $this->query($sql);
	}

}

/**
 * Container class - database table-tied object
 */
abstract class Container {
	public $table;
	public $db;
	public $structure;
	public $properties;

// constructor
//	0,1 or 2 parameters, any of which can be $db ref or key

public function __construct() {
	$args = func_get_args(); $key = null;
	while ($arg = array_shift($args)) {
		if (is_object($arg))	$this->db = $arg;
			else								$key = $arg;
		}
	if (! $this->db) $this->db = Databoss::$lastDataboss;
	$this->structure =& $this->db->structure($this->table);
	$this->properties =& $this->structure['properties'];
	if ($key)	$this->fetch($key);
		else		$this->purge();
	}

// check if this object contains a valid record

public function valid() {
	return $this->{$this->structure['primarykey']} ? true : false;
	}

// purge this object & properties

public function purge() {
	$properties =& $this->structure['properties'];
	$defaults =& $this->structure['defaults'];
	for ($ix=0; $ix<count($properties); $ix++) {
		$property = $properties[$ix];	$default = $defaults[$ix];
		$this->$property = preg_match('/current_timestamp/i',$default) ? null : $default;
		}
	}

// merge properties from another object into this one

public function merge($obj) {
	if (! is_object($obj)) return false;
	foreach ($this->properties as $property)
		$this->$property = $obj->$property;
	return true;
	}

// fetch a database record by key and merge it into this object

public function fetch($key=null) {
	$this->purge();
	if (!$key) return false;
	$query = "SELECT * FROM {$this->table} " .
					 "WHERE `{$this->properties[0]}` = '$key' " .
					 "LIMIT 1";
	if ($results = $this->db->fetch($query)) return $this->merge($results[0]);
	return false;
	}

// store this object into the database as a record

public function store() {
	$keyname = $this->structure['primarykey'];
	$sql = "REPLACE INTO `{$this->table}` VALUES(";
	$values = [];
	for ($ix=0; $ix<count($this->properties); $ix++) {
		$property = $this->properties[$ix];
		$format = $this->structure['formats'][$ix];
		$isNumber = preg_match("/(int|decimal|float|double|bit|bool)/i",$format) ? true : false;
		$value = $this->$property;
		if ($value==null || $value=="" || preg_match("/^\s*null\s*$/i",$value)) {
			$values[] = "NULL";
			continue;
			}
		if ($isNumber) {
			$values[] = $value;
			continue;
			}
		if (preg_match('/timestamp/i',$format) &&
				preg_match('/current/i',$this->structure['defaults'][$ix])) {
			$this->$property = null;
			$values[] = "NULL";
			continue;
			}
		$values[] = '"' . $this->db->escape($value) . '"';
		}
	$sql .= join(',',$values) . ')';
	if (! $this->db->store($sql)) return null;
	if (! $this->$keyname) $this->$keyname = $this->db->lastInsertId();
	return $this->$keyname;
	}
	
// delete the database record for this object (key)

public function delete($key=null) {
	$keyname = $this->structure['primarykey'];
	if (! $key) $key = $this->$keyname;
	$sql = "DELETE FROM $table WHERE `{$keyname}`='{$key}'";
	return $this->db->delete($sql);
	}

}

/**
 * Email - object / methods for dealing with email & content
 */
class Email {

/**
 * ::check for a valid, single e-mail address
 * 
 * @param string $email			: an email address to check (someone@somewhere.com)
 * @return boolean				: true (valid) or false (invalid)
 */
public static function validate($email=null) {
	$regexp = "^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$";
	$valid = false;
	if (eregi($regexp,$email)) {
		list($username,$sld) = split("@",$email);
		if (getmxrr($sld,$mxrecords)) $valid = true;
		}
	return $valid;
	}

/**
 * ::check any request data for spam and trash it
 * 
 * @param string $unit			: (optional) a unit name for logging (Medusa)
 * @return boolean				: false = no spam present
 */
public static function filterSPAM($unit="Medusa") {
	$expr = "/(href=|link=|url=|porno|optimization)/i";
	foreach ($_REQUEST as $key => $value) {
		if (! $value) continue;
		if (! preg_match($expr,$value)) continue;
		$ip = $_SERVER['REMOTE_ADDR'];
		Log::entry(
			"attempted POSTing of SPAM to {$_SERVER['SERVER_NAME']} from {$ip}",
			"added {$unit} offender {$ip} to firewall"
			);
		exec("/sbin/iptables -A xanadu -s {$ip} -j DROP");
		exit;
		}
	return false;
	}

/**
 * ::send an email to a recipient
 * 
 * @param string $subject		: the subject line
 * @param string $message		: the message (plain text or html)
 * @param string $toEmail		: recipient email (someone@somewhere.com)
 * @param string $toName		: (optional) recipient name
 * @param sting $fromEmail		: (optional) originating email address
 * @param string $fromName		: (optional) originator
 * @param mixed $cc				: (optional) string or array w/cc recipient(s)
 * @param mixed $bcc			: (optional) string or array w/bcc recipient(s)
 * @return boolean				: true (accepted) false (failed)
 */
public static function send(
		$subject,$message,$toEmail,$toName=null,$fromEmail=null,$fromName=null,$cc=null,$bcc=null
		) {
	$to = $toEmail;
	if ($toName) $to = "\"{$toName}\" <{$to}>";
	$domain = new Url; $domain = $domain->domain;
	$from = $fromEmail ? $fromEmail : "webmaster@{$domain}";
	if ($fromName) $from = "\"{$fromName}\" <{$from}>";
	$ccHeader = is_array($cc) ? join(",",$cc) : $cc;
	$bccHeader = is_array($bcc) ? join(",",$bcc) : $bcc;
	$headers =
        "MIME-Version: 1.0\r\n" .
        "Content-type: text/plain; charset=iso-8859-1\r\n" .
        "X-Priority: 3\r\n" .
        "X-MSMail-Priority: Normal\r\n" .
        "X-Mailer: {$domain} website\r\n" .
		"From: {$from}\r\n";
	if ($ccHeader) $headers .= ("CC: ".$ccHeader."\r\n");
	if ($bccHeader) $headers .= ("BCC: ".$bccHeader."\r\n");
	return mail($to,$subject,$message,$headers);
	}

}

/**
 * Files - Methods for dealing with files/directories/locations
 */
class Files {

/*
 * Obtain the base directory/path (document root or current directory)
 *	P1 = (optional) filename to append to returned path string
 *	returns a string with the path as: /home/user/somewhere.com[/filename]
 */
public static function getBasePath($filename=null) {
	$path = null;
	if ($_SERVER['DOCUMENT_ROOT'])
		$path = $_SERVER['DOCUMENT_ROOT'];
	else
	if (preg_match('/^(.+\.(biz|com|edu|net|org)).*$/i',$_SERVER['PWD'],$parts))
		$path = $parts[1];
	else
		$path = $_SERVER['PWD'];
	if ($filename) $path .= "/{$filename}";
	return $path;
	}

}

/**
 * Log class - log messages to syslog
 */

class Log {

// entry - log an entry

static function entry($message1,$message2=null) {
	$identity = "Simplicity";
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

/**
 * Math - methods for dealing mathmatically with numbers, currency, etc.
 */
class Math {

/*
 * Convert number or string to pure number format
 *	P1 = string or number to convert
 *	returns a pure number value
 */
static function toNumber($value) {
	$result = '' . $value;
	$minus = preg_match('/\-/',$result);
	$result = preg_replace('/[^0-9.]/','',$result);
	if ($result == '') $result = 0.0;
	$result = 0.0 + $result;
	if ($minus) $result *= -1.0;
	return $result;
	}
/*
 * Format a number or string to currency (99,999.00)
 *	P1 = string or number to format
 *	returns a currency string sans '$'
 */
static function toCurrency($value) {
	$value = round($value,4);
	$sign = ($value < 0) ? '-' : '';
	$string = sprintf('%.2f',round(abs(self::toNumber($value)),2));
	$parts = explode('.',$string);
	$result = "";
	for ($l=strlen($parts[0]); $l > 3; $l=strlen($parts[0])) {
		$result = ',' . substr($parts[0],-3) . $result;
		$parts[0] = substr($parts[0],0,-3);
		}
	$parts[0] = $parts[0] . $result;
	if (strlen($parts[0]) == 0) $parts[0] = '0';
	return $sign.implode('.',$parts);
	}
/*
 * Format a number as cents
 *	P1 = number to present
 *	returns a string as 9.99¢
 */
static function toCents($value) {
	$value = 0.0 + $value;
	if ($value >= 1.00)
		return '&#36;' . self::toCurrency($value);
	else
		return ($value*100.0) . '&cent;';
	}
/*
 * Round a value to the nearest cent
 *	P1 = number value
 *	returns a number rounded to nearest cent
 */
static function roundCents($value=0.0) {
	return (round($value * 100.0) / 100.0);
	}
/*
 * Round a value up to the nearest cent
 *	P1 = number value
 *	returns a number rounded up to nearest cent
 */
static function roundCentsUp($value=0.0) {
	return (ceil($value * 100.0) / 100.0);
	}
/*
 * Round a value down to the nearest cent
 *	P1 = number value
 *	returns a number rounded down to nearest cent
 */
static function roundCentsDown($value=0.0) {
	return (floor($value * 100.0) / 100.0);
	}

}

/**
 * Date class - for dealing with date values & calculations
 */

define("TEXTDATE",2);		// for text (Mmmmmmmmmm dd, yyyy)
define("LONGDATE",1);		// long result (yyyy-mm-dd hh:mm:ss)
define("SHORTDATE",0);		// short result (yyyy-mm-dd)
define("VERYSHORTDATE",-1);	// very short date (mm/dd/yy)
define("ONEDAY",86400);		// one day's seconds
define("ONEHOUR",3600);		// one hour's seconds
define("ONEMINUTE",60);		// one minute's seconds
define("MAXDATE","2037-12-31 23:59:59");	// max unix date value

class Date {

	public static $months = array(
		'January','February','March','April','May','June','July',
		'August','September','October','November','December'
		);
	public static $monthdays = array(31,28,31,30,31,30,31,31,30,31,30,31);

	public $date;			// getdate array
	public $utc = '-0600';	// UTC offset (if known)

/*
 *::Compare two Date objects:
 *	P1 = first Date object
 *	P2 = second Date object
 *	returns -1, 0, or 1 (<,=,>)
 */
public static function compare($first,$second) {
	if ($first->date[0] == $second->date[0]) return 0;
	return ($first->date[0] < $second->date[0]) ? -1 : 1;
	}

/*
 *::Obtain the +/-days difference between two Date objects:
 *	P1 = first Date object
 *	P2 = second Date object
 *	returns the (signed) difference in days
 */
public static function difference($first,$second) {
	$days = ($first->date[0]-$second->date[0])/ONEDAY;
	return $days;
	}

/*
 *::Convert any date to an internal date
 *	P1 = date value as string--i.e. mm/dd/yyyy, etc.
 *	P2 = format: [LONGDATE],SHORTDATE or VERYSHORTDATE
 *	returns an internal date in the requested format
 */
public static function toInternal($date=null,$format=LONGDATE) {
	$that = $date ? new Date($date) : new Date;
	return $that->internal($format);
	}

/**
 * Convert a date to a datestamp date
 * 
 *	@param string $date				: (optional) date to convert
 *	@return string					: a datestamp (yyyymmddhhmmss)
 */
public static function toDatestamp($date=null) {
	$that = $date ? new Date($date) : new Date;
	return $that->datestamp();
	}

/*
 *::Convert any date to an external date
 *	P1 = date value as string--i.e. mm/dd/yyyy, etc.
 *	P2 = format: LONGDATE,[SHORTDATE] or VERYSHORTDATE
 *	returns an external date in the requested format
 */
public static function toExternal($date=null,$format=SHORTDATE) {
	$that = $date ? new Date($date) : new Date;
	return $that->external($format);
	}

/**
 *::Return date/time as a quick date
 * @param string $date		: (optional) date to render as quick
 * @return string			: date as m/dd h:mm(a|p)
 */
public static function toQuick($date=null) {
	$that = $date ? new Date($date) : new Date;
	return $that->quick();
	}

/**
 * return current date & time (as a timestamp)
 *
 * @return string			: current date/time as yyyy-mm-dd hh:mm:ss
 */
public static function timestamp() {
	return self::toInternal(null,LONGDATE);
	}

/**
 * return a date in internal format for the start of today
 *
 * @param int $format		: use SHORTDATE or LONGDATE or null
 * @return string			: a date string as yyyy-mm-dd[ 00:00:00]
 */
public static function today($format=null) {
	$thisdate = self::toInternal(null,SHORTDATE);
	if ($format===SHORTDATE) return $thisdate;
	return $thisdate." 00:00:00";
	}

/**
 * return the maximum date allowed (forever)
 * by then we should all be retired or deceased
 *
 * @return string			: a date string as 2037-12-31 23:59:59
 */
public static function forever() {
	return MAXDATE;
	}

//////////////////// object methods //////////////////

/**
 * return current date & time (timestamp)
 *
 * @return string			: the current date/time as yyyy-mm-dd hh:mm:ss
 */
public static function now() {
	return self::timestamp();
	}

/*
 * Constructor:
 *	P1 = (optional) date value
 */
public function __construct($value=null) {
	if (!func_num_args())
		$this->date = getdate();
	else
		$this->set($value);
	}

/*
 * Destructor:
 */
public function __destruct() {}

/**
 * express this object in 'string' context
 */
public function __toString() {return $this->internal(LONGDATE);}


/*
 * Determine if this object contains a valid date:
 *	returns true (valid) or false (invalid)
 */
public function valid() {
	return ($this->date ? true : false);
	}

/*
 * Set the date value for this object:
 *	P1 = (optional) date value, default = now()
 */
public function set($value=null) {
	if (! $value || $value=='0000-00-00 00:00:00') {
		$this->date = null;
		return;
		}
	$this->date = getdate();
	while ($value) {
		// MySql timestamp:
		if (preg_match('/^\s*(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})\s*$/',$value,$parts)) {
			$this->date['year'] = 0+$parts[1];
			$this->date['mon'] = 0+$parts[2];
			$this->date['mday'] = 0+$parts[3];
			$this->date['hours'] = 0+$parts[4];
			$this->date['minutes'] = 0+$parts[5];
			$this->date['seconds'] = 0+$parts[6];
			break;
			}
		// Unix timestamp:
		if (preg_match('/^\s*([-]?\d{6,})\s*$/',$value,$parts)) {
			$this->date = getdate(0+$parts[1]);
			return;
			}
		// MySql internal date and time format:
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})$/',$value,$parts)) {
			$this->date['year'] = 0+$parts[1];
			$this->date['mon'] = 0+$parts[2];
			$this->date['mday'] = 0+$parts[3];
			$this->date['hours'] = 0+$parts[4];
			$this->date['minutes'] = 0+$parts[5];
			$this->date['seconds'] = 0+$parts[6];
			break;
			}
		// MySql internal date format:
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/',$value,$parts)) {
			$this->date['year'] = 0+$parts[1];
			$this->date['mon'] = 0+$parts[2];
			$this->date['mday'] = 0+$parts[3];
			$this->date['hours'] = $this->date['minutes'] = $this->date['seconds'] = 0;
			break;
			}
		// only hour specified:
		if (preg_match('/^\s*(\d{1,2})\s*$/',$value,$parts)) {
			$this->date['hours'] = 0+$parts[1];
			$this->date['minutes'] = $this->date['seconds'] = 0;
			break;
			}
		// only hour and meridian) specified:
		if (preg_match('/^\s*(\d{1,2})\s*([AaPp]{1}).*$/',$value,$parts)) {
			$this->date['hours'] = 0+$parts[1];
			$this->date['minutes'] = $this->date['seconds'] = 0;
			$meridian = strtolower($parts[2]);
			if ($meridian=='a' && $this->date['hours']==12)	$this->date['hours'] = 0; else
			 if ($meridian=='p' && $this->date['hours']<12)	$this->date['hours'] += 12;
			break;
			}
		// only hour and minutes (and maybe meridian) specified:
		if (preg_match('/^\s*(\d{1,2}):(\d{1,2})\s*([AaPp]?).*$/',$value,$parts)) {
			$this->date['hours'] = 0+$parts[1];
			$this->date['minutes'] = 0+$parts[2];
			$this->date['seconds'] = 0;
			$meridian = strtolower($parts[3]);
			if ($meridian=='a' && $this->date['hours']==12)	$this->date['hours'] = 0; else
			 if ($meridian=='p' && $this->date['hours']<12)	$this->date['hours'] += 12;
			break;
			}
		// only hours, minutes and seconds (and maybe meridian) specified:
		if (preg_match('/^\s*(\d{1,2}):(\d{1,2}):(\d{1,2})\s*([AaPp]?).*$/',$value,$parts)) {
			$this->date['hours'] = 0+$parts[1];
			$this->date['minutes'] = 0+$parts[2];
			$this->date['seconds'] = 0+$parts[3];
			$meridian = strtolower($parts[4]);
			if ($meridian=='a' && $this->date['hours']==12)	$this->date['hours'] = 0; else
			 if ($meridian=='p' && $this->date['hours']<12)	$this->date['hours'] += 12;
			break;
			}
		$this->date['hours'] = $this->date['minutes'] = $this->date['seconds'] = 0;
		// look for m/d/y:
		if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{1,4})/',$value,$parts)) {
			if ($parts[3] < 100)
				if ($parts[3] < 50)	$parts[3] += 2000;
					else		$parts[3] += 1900;
			$this->date['mon'] = 0+$parts[1];
			$this->date['mday'] = 0+$parts[2];
			$this->date['year'] = 0+$parts[3];
			}
		else
		// look for m/d:
		if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})/',$value,$parts)) {
			$this->date['mon'] = 0+$parts[1];
			$this->date['mday'] = 0+$parts[2];
			}
		// look for hh:mm:ss
		if (preg_match('/(\d{1,2}):(\d{1,2}):(\d{1,2})/',$value,$parts)) {
			$this->date['hours'] = 0+$parts[1];
			$this->date['minutes'] = 0+$parts[2];
			$this->date['seconds'] = 0+$parts[3];
			}
		else
		// look for hh:mm
		if (preg_match('/(\d{1,2}):(\d{1,2})/',$value,$parts)) {
			$this->date['hours'] = 0+$parts[1];
			$this->date['minutes'] = 0+$parts[2];
			}
		else
		if (preg_match('/(\d{1,2})[AaPp]/',$value,$parts))
			$this->date['hours'] = 0+$parts[1];
		if (preg_match('/[Aa]/',$value) && ($this->date['hours'] == 12))
			$this->date['hours'] = 00;
		else
		if (preg_match('/[Pp]/',$value) && ($this->date['hours'] < 12))
			$this->date['hours'] += 12;
		break;
		}
	$this->date[0] = mktime(
		$this->date['hours'],
		$this->date['minutes'],
		$this->date['seconds'],
		$this->date['mon'],
		$this->date['mday'],
		$this->date['year']
		);
	$this->date = getdate($this->date[0]);
	}

/**
 * return the month (1-12) for this Date object
 * @param int					: (optional) offset in months to apply +/-
 * @return int					: month, 1-12 or null
 */
public function month($offset=null) {
	if (! $this->date[0]) return null;
	$mm = $this->date['mon'];
	if ($offset) {
		$mm += preg_replace('/[^0-9-]/','',$offset);
		while ($mm > 12) $mm -= 12;
		while ($mm <= 0) $mm += 12;
		}
	return $mm;
	}

/**
 * return the day of month (1-31) for this Date object
 * @param int					: (optional) offset in days to apply +/-
 * @return int					: day, 1-31 or null
 */
public function day($offset=null) {
	if (! $this->date[0]) return null;
	if ($offset) {
		$that = clone $this;
		$that->move(preg_replace('/[^0-9-]/','',$offset)." days");
		return $that->date['mday'];
		}
	else
		return $this->date['mday'];
	}

/**
 * return the year (1900-2037) for this Date object
 * @param int					: (optional) offset in years to apply +/-
 * @return int					: year as yyyy or null
 */
public function year($offset=null) {
	if (! $this->date[0]) return null;
	return $this->date['year']+preg_replace('/[^0-9-]/','',$offset);
	}

/**
 * Return the number of days in this object's month/year
 *	returns an int (28-31) or null if no date present
 */
public function daysInMonth() {
	if (! $this->valid()) return null;
	return cal_days_in_month(CAL_GREGORIAN,$this->date['mon'],$this->date['year']);
	}

/*
 * Return an internal (SQL) date for this object:
 *	P1 = (optional) toggle, long or short result
 *	returns a string as: yyyy-mm-dd( hh:mm:ss)
 */
public function internal($size=LONGDATE) {
	if (! $this->valid()) return null;
	$result = sprintf("%04d-%02d-%02d",
			  $this->date['year'],$this->date['mon'],$this->date['mday']);
	if ($size!=SHORTDATE)
		$result .= sprintf(" %02d:%02d:%02d",$this->date['hours'],
				   $this->date['minutes'],$this->date['seconds']);
	return $result;
	}

/**
 * return object date & time (as a datestamp)
 * 
 *	@return	string				: current date/time as yyyymmddhhmmss
 */
public function datestamp() {
	if (! $this->valid()) return null;
	return sprintf(
		"%04d%02d%02d%02d%02d%02d",
		$this->date['year'],$this->date['mon'],$this->date['mday'],
		$this->date['hours'],$this->date['minutes'],$this->date['seconds']
		);
	}

/*
 * Return an external date for this object:
 *	P1 = (optional) toggle: text, long, short or very short result
 *	returns a string as: mm/dd/(yy)yy( hh:mm:ss a|pm) or Mmmmmmmm dd, yyyy
 */
public function external($size=SHORTDATE) {
	if (! $this->valid()) return null;
	$result = sprintf("%d/%d/%04d",$this->date['mon'],$this->date['mday'],$this->date['year']);
	if ($size==VERYSHORTDATE) {
		$year = $this->date['year'] - (($this->date['year'] < 2000) ? 1900 : 2000);
		return sprintf("%d/%d/%02d",$this->date['mon'],$this->date['mday'],$year);
		}
	if ($size==TEXTDATE) {
		return sprintf(
			"%s %d, %04d",
			self::$months[$this->date['mon']-1],
			$this->date['mday'],
			$this->date['year']
			);
		}
	if ($size==LONGDATE) {
		$hour = $this->date['hours'];
		$mer = "am";
		if ($hour < 12) {
			$mer = "am";
			if ($hour == 0) $hour = 12;
			}
		else	{
			$mer = "pm";
			if ($hour > 12) $hour -= 12;
			}
		$result .= sprintf(" %d:%02d %s",$hour,$this->date['minutes'],$mer);
		}
	return $result;
	}


/**
 * render a 'quick' date for brevity
 *
 * @return string						: date as m/dd h:mm(a|p)
 */
public function quick() {
	$hour = $this->date['hours'];
	$meridian = 'a';
	if ($hour == 0)	$hour = 12;
		else
			if ($hour == 12) $meridian = 'p';
				else
					if ($hour > 12) {$hour -= 12; $meridian = 'p';}
	return sprintf('%d/%02d %d:%02d%s',$this->date['mon'],$this->date['mday'],
				   $hour,$this->date['minutes'],$meridian);
	}

/*
 * Move the object's date by various intervals:
 *	P1 = interval to move the date, i.e.
 *		"first (day)" first day of month
 *		"last (day)" last day of month
 *		"(-)nn second(s)"
 *		"(-)nn minute(s)"
 *		"(-)nn hour(s)"
 *		"(-)nn day(s)"
 *		"(-)nn weekday(s)"
 *		"(-)nn week(s)"
 *		"(-)nn month(s)"
 *		"(-)nn quarter(s)"
 *		"(-)nn year(s)"
 *	returns internal value for new date
 */
public function move($interval=null) {
	if (! $this->valid()) return null;
	if (preg_match('/first|last/i',$interval)) {
		if (preg_match('/first/i',$interval)) {
			$this->date['mday'] = 1;
			}
		else {
			$this->date['mday'] = $this->daysInMonth();
			}
		$this->date['hours'] =
			$this->date['minutes'] =
				$this->date['seconds'] = 0;
		$this->date = getdate(mktime(
			$this->date['hours'],
			$this->date['minutes'],
			$this->date['seconds'],
			$this->date['mon'],
			$this->date['mday'],
			$this->date['year']
			));
		return $this->internal(LONGDATE);
		}
	preg_match('/([0-9-]+)\s*([a-zA-Z]+)*/',$interval,$parts);
	$reps = 0+$parts[1]; $step = strtolower($parts[2]);
	if (! $reps) return $this->internal(LONGDATE);
	if (preg_match('/weekday/',$step)) {
		$count = abs($reps);
		$cursor = ($reps < 0) ? -1 : 1;
		while ($count--)
			while (1) {
				$this->date = getdate(mktime(
					$this->date['hours'],
					$this->date['minutes'],
					$this->date['seconds'],
					$this->date['mon'],
					($this->date['mday']+$cursor),
					$this->date['year']
					));
				if (($this->date['wday']>0) && ($this->date['wday']<6)) break;
				}
		return $this->internal(LONGDATE);
		}
	switch ($step) {
		case "hour": case "hours":
			$this->date['hours'] += $reps;
			break;
		case "minute": case "minutes":
			$this->date['minutes'] += $reps;
			break;
		case "second": case "seconds":
			$this->date['seconds'] += $reps;
			break;
		case "week": case "weeks":
			$this->date['mday'] += ($reps * 7);
			break;
		case "month": case "months":
			$this->date['mon'] += $reps;
			$temp = getdate(mktime(0,0,0,$this->date['mon'],1,$this->date['year'])); // temp marker in time
			$maxdays = self::$monthdays[$temp['mon']-1];
			$this->date['mday'] = ($this->date['mday'] > $maxdays) ? $maxdays : $this->date['mday'];
			$this->date['mon'] = $temp['mon'];
			$this->date['year'] = $temp['year'];
			break;
		case "quarter": case "quarters":
			$this->date['mon'] += ($reps * 3);
			$temp = getdate(mktime(0,0,0,$this->date['mon'],1,$this->date['year'])); // temp marker in time
			$maxdays = self::$monthdays[$temp['mon']-1];
			$this->date['mday'] = ($this->date['mday'] > $maxdays) ? $maxdays : $this->date['mday'];
			$this->date['mon'] = $temp['mon'];
			$this->date['year'] = $temp['year'];
			break;
		case "year": case "years":
			$this->date['year'] += $reps;
			break;
		case "day": case "days":
		default:
			$this->date['mday'] += $reps;
			break;
		}
	$this->date = getdate(mktime(
		$this->date['hours'],
		$this->date['minutes'],
		$this->date['seconds'],
		$this->date['mon'],
		$this->date['mday'],
		$this->date['year']
		));
	return $this->internal(LONGDATE);
	}

/**
 * apply UTC offset to this date (object)
 * 
 * @param string $offset			: offset as [+/-]hh[mm]
 * @return boolean					: true=applied, false=error
 */
public function applyUTCoffset($offset) {
	if (! preg_match('/^[+-]*\d{1,4}$/',$offset)) return false;
	if (preg_match('/(\+|\-)/',$offset,$parts)) {
		$sign = ($parts[1] == '-') ? -1 : 1;
		$offset = preg_replace('/[^0-9]/','',$offset);
		}
	else
		$sign = 1;
	if (strlen($offset) <= 2) {
		$hours = 0 + $offset;
		$minutes = 0;
		}
	else {
		$minutes = 0 + substr($offset,-2);
		$hours = 0 + substr($offset,0,-2);
		}
	$this->utc = sprintf('%s%02d%02d',(($sign < 0) ? '-' : '+'),$hours,$minutes);
	$timestamp = $this->date[0] + ($sign * $minutes * 60) + ($sign * $hours * 3600);
	$this->date = getdate($timestamp);
	return true;
	}

/**
 * calculate elapsed time display (from this object)
 * 
 * @param int $seconds			: (optional) elapsed seconds
 * @return string
 */
public function elapsed($seconds=null) {
	if (! $seconds) $seconds = time() - $this->date[0];
	$result = "";
	$hours = intval($seconds / 3600);
	if ($hours) {
		$result .= "$hours hour".($hours==1 ? '' : 's').", ";
		$seconds -= ($hours * 3600);
		}
	$minutes = intval($seconds / 60);
	if ($minutes) {
		$result .= "$minutes minute".($minutes==1 ? '' : 's').", ";
		$seconds -= ($minutes * 60);
		}
	$result .= "$seconds second".($seconds==1 ? '' : 's');
	return $result;	
	}
	
}

/**
 * Console - stdout to console handler
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

/**
 * Set - methods for dealing with sets of values (lists,sets,etc.)
 */
class Set {

/*
 * Check to see if a value is in a list or set string:
 * Note: both value & list are case-insensitive
 *	P1 = value to look for
 *	P2 = ref. to list/array/set string
 *	returns true or false
 */
static function find($value,&$list) {
	$value = strtolower($value);
	$dataset = is_array($list) ? $list : explode(",",$list);
	foreach ($dataset as $item)
		if ($value == strtolower($item)) return true;
	return false;
	}
/*
 * Remove an entry from a list (if present)
 *	P1 = value to remove
 *	P2 = set (list or set-string)
 *	returns (new) list or set-string
 */
static function remove($value,$list) {
	if (!$value) return $list;
	$isSet = !is_array($list);
	if ($isSet) $list = explode(',',$list);
	for ($ix=0; $ix<count($list); ++$ix)
		if ($value == $list[$ix]) {
			array_splice($list,$ix,1);
			break;
			}
	return ($isSet ? join(',',$list) : $list);
	}
/*
 * Return the length of the longest element in a list or set
 * P1 = ref. to the list/set
 *	returns length of longest element in the list or set
 */
static function longest(&$list) {
	$dataset = is_array($list) ? $list : explode(",",$list);
	$longest = 0;
	foreach ($dataset as $element) {
		$size = strlen($element);
		if ($size > $longest) $longest = $size;
		}
	return $longest;
	}
/*
 * Arrange an array as a line for a CSV (comma-separated values) file
 *	returns a string formatted as: "...","...",...,"..."(nl)
 */
static function toCsvRecord(
		$list,					// array of values to format
		$columns=null			// max columns to show (null=all)
		) {
	if (gettype($list) != "array") return "\"{$list}\"\n";
	if ($columns && (sizeof($list)>$columns)) array_splice($list,$columns);
	return '"' . join('","',$list) . '"' . "\n";
	}

}

/**
 * Typography - dealing with type/fonts & special characters
 */
class Typography {

// special characters:

	public static $copyright		= '©';
	public static $registered		= '®';
	public static $trademark		= '™';
	public static $bullet				= "·";

}
