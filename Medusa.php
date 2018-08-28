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
 * v8.1 August 2018 by Harley H. Puthuff
 * Copyright 2008-2018, Your Showcase on the Internet
 */

if (! defined("MEDUSA")) {define("MEDUSA","20180801");

class Object extends stdClass {}		// generic class

class Medusa {							// core Medusa class

	public static $scriptPath;			// where original script lives
	public static $libraryPath;			// where Medusa lives
	public static $confFileName;			// name of the configuration .xml file
	public static $configuration;		// loaded configuration values

// In the absence of a Medusa.xml file in the current working directory
// or the Medusa library directory, these default values are used. Modify
// them to suit your needs.
	
private static $defaultXML = <<<XML
<?xml version='1.0'?>
<!--
  __  __          _ 
 |  \/  | ___  __| |_   _ ___  __ _ 
 | |\/| |/ _ \/ _` | | | / __|/ _` |
 | |  | |  __/ (_| | |_| \__ \ (_| |
 |_|  |_|\___|\__,_|\__,_|___/\__,_|

 Medusa.xml - Medusa specifications

 localhost testing ! 8/2018, HHP
 v8.1, Aug 2018 by Harley H. Puthuff
 Copyright 2008-2018, Your Showcase on the Internet
-->
<medusa>
 <nomenclature>
  <name>Medusa</name>
  <version>v8.1</version>
  <author>Harley H. Puthuff</author>
  <copyright>Copyright 2008-2018, Your Showcase</copyright>
  <description>A low-level interface class library</description>
 </nomenclature>
 <databoss>
  <connections>
   <connection name="dbh" /> <!--special connector for prev. opened database-->
   <connection name="localhost" host="localhost" user="{username}" pass="{password}" />
  </connections>
  <defaultConnection>localhost</defaultConnection>
 </databoss>
</medusa>
XML;

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
	self::$libraryPath = __DIR__;
	self::$configuration = simplexml_load_string(self::$defaultXML);
	foreach(array(self::$scriptPath,self::$libraryPath) as $path) {
		$xmlfile = $path.'/'."Medusa.xml";
		if (file_exists($xmlfile)) {
			self::$confFileName = $xmlfile;
			self::$configuration = simplexml_load_file($xmlfile);
			if (self::$configuration === false) {
				echo "!! Corrupt Medusa.xml file: $xmlfile !!\n";
				exit;
				}
			break;
			}
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
