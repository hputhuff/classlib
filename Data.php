<?php
/**
 *  ____        _        
 * |  _ \  __ _| |_ __ _ 
 * | | | |/ _` | __/ _` |
 * | |_| | (_| | || (_| |
 * |____/ \__,_|\__\__,_|
 *
 * Data class - class methods for working with data
 * Copyright 2016, Your Showcase on the Internet
 */
class Data {

/**
 * sift terms to produce a single term
 * 
 * @param array $arguments			: a variable number of terms
 * @return string					: chosen term or null
 */
public static function sift() {
	foreach (func_get_args() as $arg) if ($arg) return $arg;
	return null;
	}
	
/**
 * combine terms to produce a single result
 * 
 * @param array $arguments			: a variable number of terms
 * @return string					: combined terms or null
 */
public static function combine() {
	$provided = func_get_args(); // get all arguments
	$valued = array();
	foreach ($provided as $arg) if ($arg) $valued[] = $arg;
	$values = count($valued);
	if (! $values) return null;
	if ($values == 1) return $valued[0];
	return join(' ',$valued);
	}

/**
 * render a displayable name from a db column name or tag or ??
 * 
 * @param string $name			: original tag/name
 * @return string				: rendered for display
 */
public static function toDisplayName($name) {
    $interim = preg_replace('/([a-z0-9])([A-Z])/','$1_$2',$name);
	$parts = preg_split('/[^0-9a-zA-Z]+/',$interim);
	foreach ($parts as &$part) $part = ucfirst($part);
	return join(' ',$parts);
	}

/**
 * render a phone number to a displayable string
 * 
 * @param string $phone			: phone number w/wo formatting
 * @return string				: standardized phone# display
 */
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

}

?>