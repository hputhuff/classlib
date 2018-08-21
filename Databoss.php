<?php
/**
 *  _____        _        _                   
 * |  __ \      | |      | |                  
 * | |  | | __ _| |_ __ _| |__   ___  ___ ___ 
 * | |  | |/ _` | __/ _` | '_ \ / _ \/ __/ __|
 * | |__| | (_| | || (_| | |_) | (_) \__ \__ \
 * |_____/ \__,_|\__\__,_|_.__/ \___/|___/___/
 *
 * Databoss.php - Medusa Databoss class
 * Low-level DBMS operations
 * v7.1, November 2015 by Harley H. Puthuff
 * Copyright 2008-2015, Your Showcase on the Internet
 */

class Databoss {

// private class properties:

	private static $connections;		// list of dbms server connections
	private static $defaultConnection;	// name of the default connection
	private static $connection;			// ref. to the last connection used

// private class methods:

/**
 * Load the information from Medusa configuration
 * 
 * @return int					: number of connections available
 */
private static function loadConnections() {
	self::$connections = array();
	$available = Medusa::getDatabossConnections();
	foreach ($available as $connection) {
		self::$connections[] = new Object;
		$ref =& self::$connections[sizeof(self::$connections)-1];
		$ref->name = $connection[0];
		$ref->host = $connection[1];
		$ref->user = $connection[2];
		$ref->pass = $connection[3];
		$ref->db = null;
		$ref->databases = null;
		$ref->database = null;
		}
	self::$defaultConnection = Medusa::getDatabossDefaultConnection();
	return count(self::$connections);
	}

// public class methods:

/**
 * return a ref. to a connection object by name
 * 
 * @param string $name			: name of the connection
 * @return object				: ref. to connection object or null if not found
 */
public static function &getConnection($name=null) {
	$name = strtolower($name);
	if (! $name) $name = self::$connection ? self::$connection->name : self::$defaultConnection;
	foreach (self::$connections as &$connector) if ($name == $connector->name) break;
	if ($name != $connector->name) return null;
	self::$connection = $connector;
	self::$defaultConnection = $connector->name;
	if ($connector->db) return $connector; // already connected
	$connector->db = new mysqli($connector->host,$connector->user,$connector->pass);
	if (mysqli_connect_error())
		die('Databoss: connect error ('.mysqli_connect_errno().') '.mysqli_connect_error());
	$connector->databases = array();
	$results = $connector->db->query("SHOW DATABASES");
	while (list($database) = $results->fetch_row()) {
		if (preg_match('/schema|mysql/i',$database)) continue;
		$connector->databases["$database"] = null;
		}
	$results->close();
	return $connector;
	}

/**
 * Create an ad-hoc Databoss object and return it
 * 
 * @param string $connection	: (optional) connection name
 * @param string $database		: (optional) database name
 * @return object				: a new Databoss object
 */
public static function db($connection=null,$database=null) {
	return new self($connection,$database);
	}

// object properties:

	public $connector;			// ref. to dbms connection

/**
 * constructor:
 * 
 * @param string $connection	: (optional) connection name
 * @param string $database		: (optional) database name
 */
public function __construct($connection=null,$database=null) {
	if (! self::$connections) self::loadConnections();
	$this->connector =& self::getConnection($connection);
	if (! $this->connector) {
		echo "Databoss: failed to find connection: $connection !!";
		return;
		}
	if ($database) $this->focus($database);
	}

/**
 * focus/use/select a database on the connection
 * 
 * @param string $database		: name of the database
 * @return boolean				: true (success) or false (failed)
 */
public function focus($database) {
	if (!$database && $this->connector->database) return true;
	if (! array_key_exists($database,$this->connector->databases)) return false;
	$this->connector->db->select_db($database);
	$this->connector->database = $database;
	$ref =& $this->connector->databases[$database];
    if (is_array($ref) && count($ref)) return true;
	$ref = array();
	$results = $this->connector->db->query("SHOW TABLES FROM $database");
	while (list($table) = $results->fetch_row()) {
		$ref[$table] = null;
		}
	$results->close();
	return true;
	}

/**
 * verify that a database & table exist for the connection
 * 
 * @param string $table			: name of the table
 * @param string $dbname		: (optional) name of the database
 * @return mixed				: ref. to table definition or false
 */
public function &exists($table=null,$dbname=null) {
	if (! $table) return false;
	if (preg_match('/(\w+)\.(\w+)/',$table,$parts)) {
		$dbname = $parts[1];
		$table = $parts[2];
		}
	else
		$dbname = $this->connector->database;
	foreach (array_keys($this->connector->databases) as $database) {
		if ($dbname && ($dbname != $database)) continue;
		if (array_key_exists($table,$this->connector->databases[$database])) {
			$this->focus($database);
			return $this->connector->databases[$database][$table];
			}
		}
	return false;
	}

/**
 * Return a ref. to the structure info for a table:
 * 
 * @param string $table			: name of the table
 * @param string $database		: (optional) name of the database
 * @return mixed				: ref. to table structure or false
 */
public function &structure($table=null,$database=null) {
	if (! $table) return false;
	if (preg_match('/(\w+)\.(\w+)/',$table,$parts)) {
		$database = $parts[1];
		$table = $parts[2];
		}
	$focus =& $this->exists($table,$database);
	if ($focus === false) return false;
	if (is_array($focus)) return $focus;
	$focus = array();
	$focus['table'] = $table;
	$focus['primarykey'] = null;
	$focus['primarykeycolumn'] = null;
	$focus['autoincrement'] = null;
	$focus['properties'] = array();
	$focus['formats'] = array();
	$focus['defaults'] = array();
	$query = $database ?
		"SHOW COLUMNS FROM {$database}.{$table}" :
		"SHOW COLUMNS FROM $table";
	$results = $this->connector->db->query($query);
	if (!$results || $this->connector->db->errno) {
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

/**
 * Return the properties list for a table--i.e. columns
 * 
 * @param string $table			: the name of the table (xxx or xxx.yyy)
 * @return array				: property list or false
 */
public function properties($table=null) {
	$structure =& $this->structure($table);
	return $structure['properties'];
	}

/**
 * Return the last database error that occurred
 * 
 * @return string				: mysql error string or null
 */
public function error() {
	$errno = $this->connector->db->errno;
	$error = $this->connector->db->error;
	return $errno ? "$error ($errno)" : null;
	}

/**
 * Log an unexpected DB error to the system log:
 * 
 *	@param string $query		: the text of the query
 */
public function logErrors($query) {
	if (! ($message = $this->error())) return;
	Log::entry($message,$query);
	}

/**
 * Return a string properly escaped for use in a query
 * 
 * @param string $string		: unescaped string
 * @return string				: escaped string
 */
public function escape($string) {
	return $this->connector->db->escape_string($string);
	}

/**
 * Limit query results to one record
 * 
 * @param string $query			: a query string
 * @return string				: query string with limit clause
 */
public function limitOne($query) {
	if (! preg_match('/limit\s+\d+/i',$query)) $query .= " LIMIT 1";
	return $query;
	}

/**
 * Return the last inserted id for autoincrement tables
 * 
 * @return int					: last inserted id/key
 */
public function lastInsertId() {
	return $this->connector->db->insert_id;
	}

/**
 * perform a low-level query against the database & return result
 * 
 * @param string $query			: an SQL query statement
 * @return mixed				: false, int or array
 */
public function query($query=null) {
    if (! $query) return null;
    $result = $this->connector->db->query($query);
    if ($result===false) {
        $this->logErrors($query);
        return false;
        }
    if ($result===true) {
        return $this->connector->db->affected_rows;
        }
    if (is_object($result)) {
        $resultSet = array();
        while ($row = $result->fetch_row()) $resultSet[] = $row;
        $result->close();
        return $resultSet;
        }
    return null;
    }

/**
 * Fetch a single column/value from a record and return it
 * 
 * @param string $query			: an SQL query statement
 * @return mixed				: int, string or null
 */
public function fetchValue($query) {
	if ($result = $this->connector->db->query($this->limitOne($query))) {
		list($value) = $result->fetch_row();
		$result->close();
		return $value;
		}
	$this->logErrors($query);
	return null;
	}

/**
 * Fetch single column/value from records and return list
 * 
 * @param string $query			: an SQL query statement
 * @return array				: list of values or empty array
 */
public function fetchValues($query) {
	$values = array();
	if (! ($result = $this->connector->db->query($query))) {
		$this->logErrors($query);
		return $values;
		}
	while (list($value) = $result->fetch_row()) $values[] = $value;
	$result->close();
	return $values;
	}

/**
 * Fetch a single record and return it as an array
 * 
 * @param string $query			: an SQL query statement
 * @return array				: array or null
 */
public function fetchArray($query) {
	if ($result = $this->connector->db->query($this->limitOne($query))) {
		$row = $result->fetch_row();
		$result->close();
		return $row;
		}
	$this->logErrors($query);
	return null;
	}

/**
 * Fetch records and return list of arrays
 * 
 * @param string $query			: an SQL query statement
 * @return array				: list of arrays or empty array
 */
public function fetchArrays($query) {
	$rows = array();
	if (! ($result = $this->connector->db->query($query))) {
		$this->logErrors($query);
		return $rows;
		}
	while ($row = $result->fetch_row()) $rows[] = $row;
	$result->close();
	return $rows;
	}

/**
 * Fetch a single record and return it as a hash
 * 
 * @param string $query			: an SQL query statement
 * @return array				: hash or null
 */
public function fetchHash($query) {
	if ($result = $this->connector->db->query($this->limitOne($query))) {
		$hash = $result->fetch_assoc();
		$result->close();
		return $hash;
		}
	$this->logErrors($query);
	return null;
	}

/**
 * Fetch records and return list of hashes
 * 
 * @param string $query			: an SQL query statement
 * @return array				: list of hashes or empty array
 */
public function fetchHashes($query) {
	$hashes = array();
	if (! ($result = $this->connector->db->query($query))) {
		$this->logErrors($query);
		return $hashes;
		}
	while ($hash = $result->fetch_assoc()) $hashes[] = $hash;
	$result->close();
	return $hashes;
	}

/**
 * Fetch a single record and return it as an object
 * 
 * @param string $query			: an SQL query statement
 * @param string $class			: (optional) a class name
 * @return object				: object or null
 */
public function fetchObject($query,$class=null) {
	if ($result = $this->connector->db->query($this->limitOne($query))) {
		$std = $result->fetch_object();
		$result->close();
		if (! $std) return null;
		if ($class) {
			$object = new $class;
			$object->merge($std);
			return $object;
			}
		else
			return $std;
		}
	$this->logErrors($query);
	return null;
	}

/**
 * Fetch records and return list of objects
 * 
 * @param string $query			: an SQL query statement
 * @param string $class			: (optional) a class name
 * @return array				: list of objects
 */
public function fetchObjects($query,$class=null) {
	$objects = array();
	if (! ($result = $this->connector->db->query($query))) {
		$this->logErrors($query);
		return $objects;
		}
	while ($std = $result->fetch_object())
		if ($class) {
			$objects[] = new $class;
			$objects[count($objects)-1]->merge($std);
			}
		else
			$objects[] = $std;
	$result->close();
	return $objects;
	}

/**
 * fetch a hash of objects indexed by a column within each
 *
 * @param string $query			: SQL query string to fetch list
 * @param string $index			: column name for hash keys
 * @param string $class			: (optional) class name for objects
 * @return hash					: a hash of objects
 */
public function fetchIndexedObjects($query,$index,$class=null) {
	$hash = array();
	if (! ($result = $this->connector->db->query($query))) {
		$this->logErrors($query);
		return $hash;
		}
	while ($object = $result->fetch_object())
		if ($class) {
			$hash[$object->$index] = new $class;
			$hash[$object->$index]->merge($object);
			}
		else
			$hash[$object->$index] = $object;
	$result->close();
	return $hash;	
	}

/**
 * fetch a hash of choices using a query
 * 
 * @param string $query			: SQL query statement to fetch list
 * @return hash					: a hash of name=>value pairs
 */
public function fetchChoices($query) {
	$matrix = $this->fetchArrays($query);
	$choices = array();
	foreach ($matrix as &$row) $choices[$row[0]] = $row[1];
	return $choices;
	}

/**
 * Fetch a specific row from a database table by primary key
 * 
 * @param string $table			: table as table or database.table
 * @param mixed $key			: key value, int or string
 * @return hash					: a hash array w/record or false
 */
public function fetch($table=null,$key=null) {
	if (!($focus =& $this->structure($table)) || ! $key) return false;
	$key = $this->escape($key);
	for ($fieldList=array(),$ix=0; $ix<sizeof($focus['properties']); ++$ix)
		if (preg_match('/^bit/i',$focus['formats'][$ix]))
			$fieldList[] = "(0+`{$focus['properties'][$ix]}`) AS `{$focus['properties'][$ix]}`";
		else
			$fieldList[] = "`{$this->structure['properties'][$ix]}`";
	$fieldList = join(',',$fieldList);
	$query = "SELECT {$fieldList} FROM {$table} WHERE {$focus['primarykey']}='{$key}'";
	if (!($result = $this->connector->db->query($query))) $this->logErrors($query);
	if ($this->connector->db->affected_rows) {
		$hash = $result->fetch_assoc();
		$result->close();
		return $hash;
		}
	return false;
	}

/**
 * store a record from an object or hash
 * 
 * @param string $table			: name of the table as table or database.table
 * @param ref $source			: ref. to a hash or object with data
 * @return int					: key of record stored or false
 */
public function store($table,&$source) {
	if (!($structure =& $this->structure($table))) return false;
	if (is_array($source))
		$record = $source;
	else
	if (is_object($source))
		$record = get_object_vars($source);
	else
		return false;
	return ($record["{$structure['primarykey']}"] && !strstr($structure['primarykey'],',')) ?
		$this->update($table,$source) :
		$this->write($table,$source);
	}

/**
 * update a record from an object or hash
 * 
 * @param string $table			: name of the table as table or database.table
 * @param ref $source			: ref. to a hash or object with data
 * @return mixed				: key of record updated or false
 */
public function update($table,&$source) {
	if (!($structure =& $this->structure($table))) return false;
	if (is_array($source))
		$record = $source;
	else
	if (is_object($source))
		$record = get_object_vars($source);
	else
		return false;
	$fields = array();
	for ($ix=0; $ix < count($structure['properties']); ++$ix) {
		$value = $record["{$structure['properties'][$ix]}"];
		if ((gettype($value)=="NULL") || (strcasecmp($value,"null")==0))
			$value = 'NULL';
		else
		if (preg_match('/blob/i',$structure['formats'][$ix]))
			$value = "'" . mysql_real_escape_string($value) . "'";
		else
		if (preg_match('/^bit/i',$structure['formats'][$ix]))
			$value = $value ? "b'1'" : "b'0'";
		else
			$value = "'" . $this->escape($value) . "'";
		array_push($fields,$value);
		}
	$tags = $structure['properties'];
	$keyname = array_shift($tags);
	$keyvalue = array_shift($fields);
	$pairs = array();
	$query = "UPDATE {$table} SET ";
	for ($ix=0; $ix<count($tags); ++$ix) $pairs[] = "`{$tags[$ix]}`={$fields[$ix]}";
	$query .= join(',',$pairs) . " WHERE `{$keyname}`={$keyvalue}";
	return $this->query($query) ? $keyvalue : false;
	}

/**
 * write a record from an object or hash
 * 
 * @param string $table			: table name as table or database.table
 * @param ref $source			: ref. to hash or object w/data
 * @return false				: key of record written or false
 */
public function write($table,&$source) {
	if (!($structure =& $this->structure($table))) return false;
	if (is_array($source))
		$record = $source;
	else
	if (is_object($source))
		$record = get_object_vars($source);
	else
		return false;
	$fields = array();
	for ($ix=0; $ix < count($structure['properties']); ++$ix) {
		$value = $record["{$structure['properties'][$ix]}"];
		if ((gettype($value)=="NULL") || (strcasecmp($value,"null")==0))
			$value = 'NULL';
		else
		if (preg_match('/blob/i',$structure['formats'][$ix]))
			$value = "'" . mysql_real_escape_string($value) . "'";
		else
		if (preg_match('/^bit/i',$structure['formats'][$ix]))
			$value = $value ? "b'1'" : "b'0'";
		else
			$value = "'" . $this->escape($value) . "'";
		array_push($fields,$value);
		}
	$query = "REPLACE INTO $table VALUES(".join(',',$fields).")";
	if (! $this->query($query)) return false;
	$keyfield = $structure['primarykey'];
	$key = $record["$keyfield"];
	if ($key && ($key != 'NULL')) return $key;
	if (! $structure['autoincrement']) return $key;
	$key = $this->connector->db->insert_id;
	if (is_object($source))
		$source->$keyfield = $key;
	else
		$source["$keyfield"] = $key;
	return $key;
	}

/**
 * Delete a database record by key
 * 
 * @param string $table			: name of table as table or database.table
 * @param mixed $key			: the primary key value to delete
 * @return boolean				: true (success) or false (failed)
 */
public function delete($table,$key) {
	if (!($structure =& $this->structure($table))) return false;
	$query = "DELETE FROM $table WHERE {$structure['primarykey']}='{$key}'";
	return $this->query($query);
	}

/**
 * Truncate / purge all records in a table
 * 
 * @param string $table			: name of the table as table or database.table
 * @return boolean				: true (success) or false (failed)
 */
public function truncate($table) {
	if (!($structure =& $this->structure($table))) return false;
	$query = "TRUNCATE TABLE {$table}";
	return $this->query($query);
	}

/**
 * return a count of records on a table
 * 
 * @param string $table			: name of the table as table or database.table
 * @param string $where			: (optional) SQL WHERE clause for selection
 * @return int					: count of records or false
 */
public function records($table,$where=null) {
	if (!($structure =& $this->structure($table))) return false;
	$query = "SELECT COUNT(*) AS records FROM {$table} {$where}";
	return $this->fetchValue($query);
	}

/**
 * return a hash of table=>records statistics for a database
 * 
 * @param string $database		: (optional) name of the database
 * @return hash					: hash of table=>count entries or null
 */
public function statistics($database=null) {
	if (! $this->focus($database)) return null;
	$stats = array();
	foreach (array_keys($this->connector->databases[$this->connector->database]) as $table)
		$stats[$table] = $this->records($table);
	return $stats;
	}

/**
 * export a table to a file in the /tmp directory
 * 
 * @param string $table			: name of the table as table or database.table
 * @param string $options		: (optional) list of space-separated options:
 *		head(ing)		= write a heading with column names
 *		single			= enclose fields in single-quotes
 *		double			= enclose fields in double-quotes
 *		space			= separate fields with a space
 *		comma			= separate fields with a comma
 *		tab				= sepearate fields with a tab
 *		text | txt		= create a simple text file
 *		csv | excel		= create an MS-Excel compatible CSV file
 *		xml				= create a simple XML file
 * @param string $where			: (optional) where clause for query
 * @return string				: full path/filename or false
 */
public function export($table,$options=null,$where=null) {
	if (!($structure =& $this->structure($table))) return false;
	$extension = "txt"; $heading = false; $delimiter = ''; $separator = ' ';
	if (stristr($options,'head')) $heading = true;
	if (stristr($options,'single')) $delimiter = "\'";
	if (stristr($options,'double')) $delimiter = "\"";
	if (stristr($options,'space')) $separator = " ";
	if (stristr($options,'comma')) $separator = ",";
	if (stristr($options,'tab')) $separator = "\t";
	if (stristr($options,'txt') || stristr($options,'text')) $extension = 'txt';
	if (stristr($options,'csv') || stristr($options,'excel')) $extension = 'csv';
	if (stristr($options,'xml')) $extension = 'xml';
	$separator = $delimiter . $separator . $delimiter;
	$filename = "/tmp/{$table}.{$extension}";
	$properties = $this->properties($table);
	$fh = fopen($filename,"wt");
	if ($heading && ($extension != 'xml')) {
		$record = $delimiter . join($separator,$properties) . $delimiter . "\n";
		fwrite($fh,$record);
		}
	if ($extension == 'xml') {
		fwrite($fh,"<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n");
		fwrite($fh,"<!-- Produced by: Medusa, v".MEDUSA." -->\n");
		fwrite($fh,"<{$table}>\n");
		}
	$query = "SELECT * FROM $table $where";
	$result = $this->fetchArrays($query);
	foreach ($result as &$fields) {
		for ($i=0; $i < count($fields); $i++)
			$fields[$i] = preg_replace("/<br>|\r|\n|\t/"," ",$fields[$i]);
		if ($extension == 'xml') {
			for ($i=0; $i < count($fields); $i++)
				$fields[$i] = preg_replace("/&/","&amp;",$fields[$i]);
			fwrite($fh,"\t<record>\n");
			for ($i=0; $i < count($properties); $i++)
				if (! $fields[$i])
					fwrite($fh,"\t\t<{$properties[$i]} />\n");
				else
					fwrite($fh,"\t\t<{$properties[$i]}>{$fields[$i]}</{$properties[$i]}>\n");
			fwrite($fh,"\t</record>\n");
			}
		else	{
			$record = $delimiter . join($separator,$fields) . $delimiter . "\n";
			fwrite($fh,$record);
			}
		}
	if ($extension == 'xml') fwrite($fh,"</{$table}>\n");
	fclose($fh);
	return $filename;
	}

}

?>