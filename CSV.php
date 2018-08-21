<?php
/**
 *   ____ ______     __
 *  / ___/ ___\ \   / /
 * | |   \___ \\ \ / / 
 * | |___ ___) |\ V /  
 *  \____|____/  \_/   
 *
 * CSV.php - class for .csv output data
 * October 2015 by Harley H. Puthuff
 * Copyright 2015, Your Showcase on the Internet
 */
class CSV {

	public	$names;				// column names
	public	$headings;			// column headings
	public	$maxcolumns;		// max # columns

/**
 * constructor
 *
 * @param array $columns		: (optional) column names
 */
public function __construct($columns=null) {
	if ($columns) $this->columns($columns);
	}

/**
 * columns - establish column names, headings, etc.
 *
 * @param mixed $columns		: object, hash, array or string
 * @return boolean				: true=success,false=not
 */
public function columns($columns) {
	$this->names = $this->headings = null; $this->maxcolumns = 0;
	if (is_object($columns)) {
		$properties = array();
		foreach ($columns as $property=>$value) {
			if (preg_match('/^(database|table|db)$/i',$property)) continue;
			$properties[] = $property;
			}
		$columns = $properties;
		}
	else
	if (is_array($columns)) {
		$keys = array_keys($columns);
		if ($keys[0] != "0") $columns = $keys;
		}
	else
	if (is_string($columns)) {
		$columns = array($columns);
		}
	else
		return false;
	$this->names = $columns;
	$this->headings = array();
	foreach ($this->names as $column) $this->headings[] = ucfirst($column);
	$this->maxcolumns = count($this->names);
	return true;
	}

/**
 * heading - produce a heading line for the file
 *
 * @return string				: double-quoted,comma-separated heading
 */
public function heading() {
	return '"'.join('","',$this->headings).'"'."\n";
	}

/**
 * data - produce a line of data for the file
 *
 * @param mixed $source			: object, hash, array or string
 * @return string				: line of data or false
 */
public function data($source=null) {
	$values = array();
	if (is_object($source)) {			// is an object
		foreach ($this->names as $property)
			$values[] = $source->$property;
		}
	else
	if (is_array($source)) {
		$keys = array_keys($source);
		if (!ctype_digit($keys[0])) {	// is a hash
			foreach ($this->names as $key)
				$values[] = $source[$key];
			}
		else {							// is a simple array
			for ($ix=0; $ix<$this->maxcolumns; ++$ix)
				$values = $source[$ix];
			}
		}
	else
	if (is_string($source))
		$values[] = $source;
	else
		return false;
	return '"'.join('","',$values).'"'."\n";
	}

}

?>