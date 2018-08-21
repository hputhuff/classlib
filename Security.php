<?php
/*---------------------------------------------------------------------*\
|	Security class - methods for providing security access, etc.	|
\*---------------------------------------------------------------------*/

class Security {

/**
 * Return a formatted 'salt' value for encryption/decryption
 *
 * @param int $type		: length of salt
 * @return string		: either a 2-char(DES) or 12-char(MD5) salt
 */
static function getSaltValue($type=CRYPT_SALT_LENGTH) {
	switch($type) {
		case 8:	 $saltlen=9; $saltprefix='$1$'; $saltsuffix='$'; break;
		case 2:
		default: $saltlen=2; $saltprefix=''; $saltsuffix=''; break;
		}
	$salt = '';
	while(strlen($salt) < $saltlen) $salt .= chr(rand(64,126));
	return $saltprefix.$salt.$saltsuffix;
	}
/*
 * Construct a unix-style password
 *	P1 = clear text password
 *	returns an enCRYPTed password
 */
static function makePassword($cleartext) {
	return crypt($cleartext,self::getSaltValue());
	}
/*
 * Build an Apache user/password gate in a directory
 *	P1 = textual name of the password gate
 *	P2 = absolute path where gate is to be placed
 *	P3 = ref. to array of username:password entries
 *	returns a count of users defined
 */
static function gateMaker($name,$directory,&$entries) {
	$users = 0;
	$fh = fopen("$directory/.htaccess","wt");
	fwrite($fh,"AuthName \"$name\"\n");
	fwrite($fh,"AuthType Basic\n");
	fwrite($fh,"AuthUserFile $directory/.htpasswd\n");
	fwrite($fh,"require valid-user\n");
	fclose($fh);
	$fh = fopen("$directory/.htpasswd","wt");
	foreach ($entries as $entry) {
		list($username,$password) = explode(':',$entry);
		if (!$username || !$password) continue;
        	fwrite($fh,$username.':'.self::makePassword($password)."\n");
		++$users;
		}
	fclose($fh);
	return $users;
	}

/*
 * Generate a random password of nn characters in length
 *	P1 = (optional) # of characters desired (8)
 *	P2 = (optional) use capital letters (true)
 *	P3 = (optional) use numeric digits (true)
 *	P4 = (optional) use special characters (false)
 *	returns a clear-text, random password
 */
static function generatePassword($pw_length=8,$use_caps=true,$use_numeric=true,
							     $use_specials=false) {
	$caps = array();
	$numbers = array();
	$num_specials = 0;
	$reg_length = $pw_length;
	$pws = array();
	for ($ch = 97; $ch <= 122; $ch++) $chars[] = $ch; // create a-z
	if ($use_caps) for ($ca = 65; $ca <= 90; $ca++) $caps[] = $ca; // create A-Z
	if ($use_numeric) for ($nu = 48; $nu <= 57; $nu++) $numbers[] = $nu; // create 0-9
	$all = array_merge($chars, $caps, $numbers);
	if ($use_specials) {
		$reg_length =  ceil($pw_length*0.75);
		$num_specials = $pw_length - $reg_length;
		if ($num_specials > 5) $num_specials = 5;
		for ($si = 33; $si <= 47; $si++) $signs[] = $si;
		$rs_keys = array_rand($signs, $num_specials);
		foreach ($rs_keys as $rs) {
			$pws[] = chr($signs[$rs]);
			}
		}
	$rand_keys = array_rand($all, $reg_length);
	foreach ($rand_keys as $rand) $pw[] = chr($all[$rand]);
	$compl = array_merge($pw, $pws);
	shuffle($compl);
	return implode('', $compl);
	}

/**
 * Check GET and POSTED data for 'injection' attacks
 * Note: both sources of web input data are checked
 *		 and if any compromise is found, an HTTP
 *		 403 status is automatically sent and
 *		 all script activity is terminated.
 *
 * @return boolean 'true'	: all input is valid & safe
 */
public static function validateWebData() {
	$redFlag = 0;
	$lessThanGreaterThan	= '/(<|&lt;|&#60;|%3C|>|&gt;|&#62;|%3E)/i';
	$sqlReservedWords		= '/(\bselect\b|\binsert\b|'.
							    '\bupdate\b|\bdrop\b|\balter\b|\bdelete\b|'.
								'\bdescribe\b|\bascii\b|\bsubstr\b)/i';
	$functionCoding			= '/\(\s*[\"\'].*[\"\']\s*\)/';
	// examine the URI/URL:
	if (preg_match($lessThanGreaterThan,$_SERVER['REQUEST_URI'])) ++$redFlag;
	if (preg_match($sqlReservedWords,$_SERVER['REQUEST_URI'])) ++$redFlag;
	if (preg_match($functionCoding,$_SERVER['REQUEST_URI'])) ++$redFlag;
	// examine the query string:
	if (preg_match($lessThanGreaterThan,$_SERVER['QUERY_STRING'])) ++$redFlag;
	if (preg_match($sqlReservedWords,$_SERVER['QUERY_STRING'])) ++$redFlag;
	if (preg_match($functionCoding,$_SERVER['QUERY_STRING'])) ++$redFlag;
	// examine POSTed data:
	foreach ($_POST as $key=>$data) {
		if (preg_match($lessThanGreaterThan,$data)) ++$redFlag;
		if (preg_match($sqlReservedWords,$data)) ++$redFlag;
		if (preg_match($functionCoding,$data)) ++$redFlag;
		}
	if ($redFlag>0) {
		header('HTTP/1.1 403 Forbidden');
		exit;
		}
	return true;
	}

}

?>