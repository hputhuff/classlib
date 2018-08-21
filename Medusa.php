<?php
/**
 *  __  __          _                 
 * |  \/  | ___  __| |_   _ ___  __ _ 
 * | |\/| |/ _ \/ _` | | | / __|/ _` |
 * | |  | |  __/ (_| | |_| \__ \ (_| |
 * |_|  |_|\___|\__,_|\__,_|___/\__,_|
 *
 * Medusa.php - Medusa header file
 * (must be included first in script!)
 * v7.1.1 February 2016 by Harley H. Puthuff
 * Copyright 2008-2016, Your Showcase on the Internet
 */

if (! defined("MEDUSA")) {define("MEDUSA","20160201");

class Object extends stdClass {}		// generic class

class Medusa {							// core Medusa class

	public static $scriptPath;			// where original script lives
	public static $libraryPath;			// where Medusa lives
	public static $confFileName;		// name of the configuration .xml file
	public static $configuration;		// loaded configuration values

/**
 * autoload missing classes
 * 
 * @param string $className				: name of the missing class
 * @return boolean						: true=loaded,false=not
 */
public static function autoload($className) {
	$path = self::$libraryPath;
	$possibles = array(
		// check the master class library
		"{$path}/{$className}.php",
		"{$path}/{$className}.class.php",
		"{$path}/{$className}.inc",
		// check for local class library
		"classes/{$className}.php",
		"classes/{$className}.class.php",
		"classes/{$className}.inc",
		// check local directory
		"{$className}.php",
		"{$className}.class.php",
		"{$className}.inc"
		);
    foreach ($possibles as $possibility)
        if (file_exists($possibility)) {
            require_once($possibility);
            return true;
			}
    return false;
	}

/**
 * constructor - initialize class variables
 * This method returns an object but its purpose is to
 * initialize the class variables used for subsequent
 * methods.
 */
public function __construct() {
	self::$scriptPath = getcwd();
	self::$libraryPath = __DIR__;		// our home dir
	$testname = self::$scriptPath . '/' . "Medusa.xml";
	if (file_exists($testname)) {
		self::$confFileName = $testname;
		}
	else {
		$hostname = gethostname();
		if (preg_match('/^(\S+)\.\S+\.\S+$/',$hostname,$parts)) $hostname = $parts[1];
		$testname = self::$libraryPath . "/{$hostname}.xml";
		if (file_exists($testname))
			self::$confFileName = $testname;
		else
			self::$confFileName = self::$libraryPath . "/Medusa.xml";
		}
	self::$configuration = simplexml_load_file(self::$confFileName);
	if (self::$configuration === false) {
		echo "!! Medusa framework is UNAVAILABLE !!\n";
		exit;
		}
	spl_autoload_register('Medusa::autoload');	// specify our autoloader
	}

/**
 * return a list of Databoss DBMS server connections
 * 
 * @return array		: a list of (name,host,user,pass) entries
 */
public static function getDatabossConnections() {
	$thelist = array();
	foreach (self::$configuration->databoss->connections->connection as $connection)
		$thelist[] = array(
			(string) $connection['name'],
			(string) $connection['host'],
			(string) $connection['user'],
			(string) $connection['pass']
			);
	return $thelist;
	}

/**
 * return the name of the default Databoss connection
 * 
 * @return string				: default connection name (usually localhost)
 */
public static function getDatabossDefaultConnection() {
	return (string) self::$configuration->databoss->defaultConnection;
	}
	
}

new Medusa;	// initialize Medusa

}
?>
