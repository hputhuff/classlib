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
	$this->database = $name;
	$this->hostname = $host ? $host : Simplicity::$dbHost;
	$this->username = $user ? $user : Simplicity::$dbUsername;
	$this->password = $pass ? $pass : Simplicity::$dbPassword;
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

// limit query results to one record

public function limitOne($query) {
	if (! preg_match('/limit\s+\d+/i',$query)) $query .= " LIMIT 1";
	return $query;
	}

// return the last inserted id for autoincrement tables

public function lastInsertId() {
	return $this->db->insert_id;
	}

// perform a low-level query against the database & return result

public function query($query=null) {
	if (! $query) return null;
	$result = $this->db->query($query);
	if ($result===false) {
		$this->logErrors($query);
		return false;
		}
	if ($result===true) {
		return $this->db->affected_rows;
		}
	if (is_object($result)) {
		$resultSet = array();
		while ($row = $result->fetch_row()) $resultSet[] = $row;
			$result->close();
			return $resultSet;
			}
	return null;
	}

}