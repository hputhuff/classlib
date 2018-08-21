<?php
/*---------------------------------------------------------------------*\
|   Set class - methods for dealing with sets of values	(lists,sets)	|
\*---------------------------------------------------------------------*/

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

?>