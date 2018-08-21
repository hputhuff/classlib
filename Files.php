<?php
/*---------------------------------------------------------------------*\
|	 Files class - methods for dealing with files, paths, etc.			|
\*---------------------------------------------------------------------*/
class Files {

/*
 * Obtain the base directory/path (document root or current directory)
 *	P1 = (optional) filename to append to returned path string
 *	returns a string with the path as: /home/user/somewhere.com[/filename]
 */
public static function getBasePath($filename=null) {
	$path = null;
	if ($_SERVER['DOCUMENT_ROOT'])
		$path = $_SERVER['DOCUMENT_ROOT'];
	else
	if (preg_match('/^(.+\.(biz|com|edu|net|org)).*$/i',$_SERVER['PWD'],$parts))
		$path = $parts[1];
	else
		$path = $_SERVER['PWD'];
	if ($filename) $path .= "/{$filename}";
	return $path;
	}

}

?>