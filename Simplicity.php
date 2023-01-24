<?php
/**
 * Simplicity - A simple PHP framework
 * v1.1, January 2023 by Harley H. Puthuff
 * Copyright 2023, Your Showcase on the Internet
 */

/**
 * Global default variables
 */
class Simplicity {
	// for database connections
	public static $dbName					= null;
	public static $dbHost					= "localhost";
	public static $dbUsername			= "databoss";
	public static $dbPassword			= "dbpasswd";
}

/**
 * Databoss SQL database services
 */
class Databoss {

	private static $reportingEnabled = false;
	
	public $database;
	public $hostname;
	public $username;
	public $password;
	public $db;				// connection to mysqli
	public $tables;		// hash of table definitions

// constructor
public function __construct($name,$host=null,$user=null,$pass=null) {
	$database = $name;
	$hostname = $host ? $host : Simplicity::$dbHost;
	$username = $user ? $user : Simplicity::$dbUsername;
	$password = $pass ? $pass : Simplicity::$dbPassword;
	if (! self::$reportingEnabled) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		self::$reportingEnabled = true;
		}
	if (!$database ||
			!($this->db = new mysqli($hostname,$username,$password,$database))) {
		die("Databoss: cannot obtain database connection for: $database !");
		}
	$this->tables = array();
	$results = $this->db->query("SHOW TABLES");
	while (list($table) = $results->fetch_row()) $this->tables[$table] = null;
	$results->close();
	}

// destructor
public function __destruct() {
	if ($this->db) $this->db->close();
	}

}
