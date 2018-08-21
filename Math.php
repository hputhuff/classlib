<?php
/*---------------------------------------------------------------------*\
|	      Math class - Mathematical Objects & Methods		|
\*---------------------------------------------------------------------*/

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

?>