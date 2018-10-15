<?php
/**
 *   ____            _        _                 
 *  / ___|___  _ __ | |_ __ _(_)_ __   ___ _ __ 
 * | |   / _ \| '_ \| __/ _` | | '_ \ / _ \ '__|
 * | |__| (_) | | | | || (_| | | | | |  __/ |   
 *  \____\___/|_| |_|\__\__,_|_|_| |_|\___|_|   
 *
 * Container.php - class to extend for DB table objects
 * (this class cannot be instantiated without extension)
 * v7.1, November 2015 by Harley H. Puthuff
 * Copyright 2008-2015, Your Showcase on the Internet
 */
abstract class Container {

	public	$database;			// name of the database
	public	$table;				// database table name
	public	$connection;		// name of connector
	public	$db;				// handle to Databoss object
	public	$structure;			// structure of table on db
	public	$properties;		// list of fields/properties

/**
 * constructor:
 * 
 * @param object $db			: (optional) Databoss object
 * @param array $args			: (optional) arguments for constructor
 * @return object				: returns new Container object
 */
public function __construct() {
	$args = func_get_args();
	if ($args && $args[0] && is_object($args[0])) {
		$this->db = $args[0];
		array_shift($args);
		}
	else {
		$this->db = new Databoss;
		}
	$this->connection = $this->db->connector->name;
	$this->db->focus($this->database);
	$this->structure =& $this->db->structure($this->table,$this->database);
	$this->properties =& $this->structure['properties'];
	if (count($args))	// at least one argument
		call_user_func_array(array($this,"fetch"),$args);
	else
		$this->purge();
	}

/**
 * destructor:
 * 
 * Note: overload with your own clean-up code
 */
public function __destruct() {
	}

/**
 * determine if object has a valid record
 * 
 * @return boolean				: true (valid) false (not)
 */
public function valid() {
	return $this->{$this->structure['primarykey']} ? true : false;
	}

/**
 * exhibit this object as "property.....: value" pairs/lines
 * 
 * @return string				: string with object breakout
 */
public function exhibit() {
	$propertyNames = $this->properties;
	for ($max=0,$i=0; $i < sizeof($propertyNames); ++$i) {
		$len = strlen($propertyNames[$i]);
		if ($len > $max) $max = $len;
		}
	$mask = "%-{$max}s : %s\n";
	$text = get_class($this) . ":\n\n";
	for ($i=0; $i < sizeof($this->properties); ++$i)
		$text .= sprintf($mask,$propertyNames[$i],$this->{$this->properties[$i]});
	return $text;
	}

/**
 * purge all data properties in this object to default values
 */
public function purge() {
	$properties =& $this->structure['properties'];
	$defaults =& $this->structure['defaults'];
	for ($ix=0; $ix<count($properties); ++$ix) {
		$property = $properties[$ix];	$default = $defaults[$ix];
		$this->$property = ($default == "CURRENT_TIMESTAMP") ? null : $default;
		}
	}

/**
 * merge properties into this object from object or hash
 * 
 * @param mixed $ref			: ref. to hash or object
 */
public function merge(&$ref) {
	if (is_object($ref)) {
		foreach ($this->properties as $property) $this->$property = $ref->$property;
		return;
		}
	else
	if (is_array($ref))
	foreach ($this->properties as $property)
       	if (array_key_exists($property,$ref))
			if (is_array($ref[$property])) {
				$validlist = array();
				foreach ($ref[$property] as $item)
					if ($item && $item != '') $validlist[] = $item;
				$this->$property = join(',',$validlist);
				if (preg_match('/(.*)\,$/',$this->property,$parts))
					$this->property = $parts[1];
				}
			else
				$this->$property = $ref[$property];
	}

/**
 * fetch a database table record into this object
 * 
 * @param mixed $k1				: either int primary key or char 2nd field value
 * @param mixed $k2				: (optional) 2nd field of multi-field primary key
 * @param mixed $k3				: (optional) 3rd field of multi-field primary key
 * @return boolean				: true (success) or false (not)
 */
public function fetch($k1=null,$k2=null,$k3=null) {
	$this->purge();
	if (!$k1) return false;
	for ($fieldList=array(),$ix=0; $ix<sizeof($this->structure['properties']); ++$ix)
		if (preg_match('/^bit/i',$this->structure['formats'][$ix]))
			$fieldList[] =
				"(0+`{$this->structure['properties'][$ix]}`) AS `{$this->structure['properties'][$ix]}`";
		else
			$fieldList[] = "`{$this->structure['properties'][$ix]}`";
	$fieldList = join(',',$fieldList);
	if ($k2!=null)
		$query = "SELECT {$fieldList} FROM `{$this->database}`.{$this->table} " .
				 "WHERE `{$this->properties[0]}`='{$k1}' " .
				 "AND `{$this->properties[1]}`='{$k2}' " .
				 ($k3 ? "AND `{$this->properties[2]}`='{$k3}' " : "") .
				 "LIMIT 1";
	else
	if ( preg_match('/int/i',$this->structure['formats'][0]) &&
		!preg_match('/int/i',$this->structure['formats'][1]) &&
		!is_numeric($k1))
		$query = "SELECT {$fieldList} FROM `{$this->database}`.{$this->table} " .
				 "WHERE `{$this->properties[1]}` LIKE '%{$k1}%' " .
				 "LIMIT 1";
	else
		$query = "SELECT {$fieldList} FROM `{$this->database}`.{$this->table} " .
				 "WHERE `{$this->properties[0]}`='{$k1}' " .
				 "LIMIT 1";
	$sqlObject = $this->db->fetchObject($query);
	if ($sqlObject) {
		$this->merge($sqlObject);
		return true;
		}
	else
		return false;
	}

/**
 * store this object as a new/changed record into the database table
 * 
 * @return mixed				: key of stored record or false
 */
public function store() {
	// enforce data integrity for fields
	for ($i=0; $i<count($this->properties); ++$i) {
		$property = $this->properties[$i];
		$format = $this->structure['formats'][$i];
		$type = preg_replace('/[^a-z]/','',strtolower($format));
		$size = 0 + preg_replace('/[^0-9]/','',$format);
		switch ($type) {
			case 'float':
			case 'double':
				if ($this->$property!==null && $this->$property!=='')
					$this->$property = preg_replace('/[^0-9Ee.-]/','',$this->$property);
				break;				
			case 'int':
			case 'integer':
			case 'tinyint':
			case 'smallint':
			case 'mediumint':
			case 'bigint':
			case 'decimal':
			case 'numeric':
			case 'year':
				if ($this->$property!==null && $this->$property!=='')
					$this->$property = preg_replace('/[^0-9.-]/','',$this->$property);
				break;
			default:
				break;
			}
		}
	$this->db->focus($this->database);
	return $this->db->store("{$this->database}.{$this->table}",$this);
	}

/**
 * Update an existing record on the database
 * 
 * @return mixed				: key of record updated or false				
 */
public function update() {
	return $this->db->update("`{$this->database}`.{$this->table}",$this);
	}

/**
 * write this object directly into the database as a new record
 * 
 * @return mixed				: key of record written or false
 */
public function write() {
	return $this->db->write("`{$this->database}`.{$this->table}",$this);
	}
	
/**
 * delete a record from the table for this object
 * 
 * @param mixed $key			: (optional) key of record or null
 * @return int					: count of records deleted or false
 */
public function delete($key=null) {
	return $this->db->delete("`{$this->database}`.{$this->table}",
		($key ? $key : $this->{$this->structure['primarykey']}));
	}

/**
 * truncate / purge all records in the table for this object
 * 
 * @return int					: count of records or false
 */
public function truncate() {
	$database = $this->database ? "`{$this->database}`." : "";
 	$query = "TRUNCATE TABLE {$database}{$this->table}";
	return $this->db->query($query);
	}

/**
 * export the table in text, MS-Excel/.CSV or XML format:
 * 
 * @param string $type			: (optional) type of output (.csv)
 *		"csv" or "excel" - MS-Excel .csv file
 *		"txt" or "text"  - simple text file
 *		"xml" - XML file (default)
 * @param string $where			: (optional) SQL where clause
 * note:  this function exits the program
 */
public function export($type=null,$where=null) {
	switch (strtolower($type)) {
		case 'xml':
			$options = "xml";
			$mimetype = "text/xml";
			$filename = "{$this->table}.xml";
			break;
		case 'txt':
		case 'text':
			$options = "text";
			$mimetype = "text/plain";
			$filename = "{$this->table}.txt";
			break;
		case 'csv':
		case 'excel':
		default:
			$options = "heading,double,comma,csv";
			$mimetype = "application/vnd.ms-excel";
			$filename = "{$this->table}.csv";
			break;
		}
	$fullname = $this->db->export("`{$this->database}`.{$this->table}",$options,$where);
	$filesize = filesize($fullname);
	header("Content-Type: {$mimetype}");
	header("Content-Length: {$filesize}");
	header("Content-Disposition: attachment; filename=\"{$filename}\"");
	readfile($fullname);
	exit;
	}

}

?>