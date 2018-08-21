<?php
/*---------------------------------------------------------------------*\
|		Url class - Container for Internet URL			|
\*---------------------------------------------------------------------*/

class Url {

	static $tlds = array('biz','com','edu','info','name','net','org','pro',
		'aero','asia','cat','coop','gov','int','jobs','mil',
		'mobi','museum','tel','travel','arpa','example',
		'invalid','localhost','test');

	public $url;			// original url
	public $protocol;		// Internet protocol (http,etc.)
	public $username;		// embedded username
	public $password;		// embedded password
	public $host;			// host/server name (www, etc.)
	public $sld;			// 2nd level domain (yourshowcase)
	public $tld;			// top level domain (com, net, etc.)
	public $domain;			// full domain name (yourshowcase.com)
	public $port;			// server port (80, 8080, etc.)
	public $uri;			// request URI
	public $path;			// path to the document (/)
	public $document;		// document name (index.html)
	public $anchor;			// anchor (#top,#bottom,etc.)
	public $query;			// query string
	public $parameters;		// array of parameter=>values

/*
 * Constructor
 *	P1 = (optional) full url to initialize object
 */
public function __construct($url=null) {
	$this->set($url);
        }
/*
 * Set the object properties from a full or partial url string:
 *	P1 = (optional) full url to analyze (default is ServerName)
 */
public function set($url=null) {
	$this->url = $this->protocol = $this->username = $this->password =
	 $this->host = $this->sld = $this->tld = $this->domain =
	  $this->port = $this->uri = $this->path = $this->document =
	   $this->anchor = $this->query = $this->parameters = null;
	if (! $url) $url = $_SERVER['SERVER_NAME'];
	$this->url = $url;
	if (preg_match('/^([a-zA-Z0-9]+)\:\/\/(.+)/',$url,$parts)) {
		$this->protocol = strtolower($parts[1]);
		$url = $parts[2];
		}
	if (preg_match('/^(.+)@(.*)/',$url,$parts)) {
		list($this->username,$this->password) = explode(':',$parts[1]);
		$url = $parts[2];
		}
	if (preg_match('/^(\S+?)(\/\S*)/',$url,$parts)) {
		$this->uri = $parts[2];
		$url = $parts[1];
		}
	if (preg_match('/^(\S+)\:(\d*)/',$url,$parts)) {
		$this->port = $parts[2];
		$url = $parts[1];
		}
	$parts = explode('.',$url);
	$pc = count($parts);
	for ($ix=0; $ix<$pc; $ix++) $parts[$ix] = strtolower($parts[$ix]);
	if ($pc <= 1)
		$this->sld = $parts[0];
	else	{
		for ($sldi=($pc-1); $sldi>=0; $sldi--) {
			$part = $parts[$sldi];
			if ((strlen($part) <= 2) || in_array($part,self::$tlds)) continue;
			break;
			}
		$this->sld = $parts[$sldi];
		if ($sldi) $this->host = join('.',array_slice($parts,0,$sldi));
		if ($sldi < ($pc-1)) $this->tld = join('.',array_slice($parts,($sldi+1)));
		$this->domain = $this->sld . '.' . $this->tld;
		}
	if (! $this->uri || ($this->uri == '/')) return;
	$uri = $this->uri;
	if (preg_match('/^(\S*)\?(\S+)/',$uri,$parts)) {
		$this->query = $parts[2];
		$uri = $parts[1];
		}
	if (preg_match('/^(\S*)\#(\S*)/',$uri,$parts)) {
		$this->anchor = $parts[2];
		$uri = $parts[1];
		}
	if (preg_match('/^(\S*\/)(\S*)/',$uri,$parts)) {
		$this->path = $parts[1];
		$uri = $parts[2];
		}
	$this->document = $uri;
	if (strpos($this->query,'=') != false) {
		$this->parameters = array();
		foreach (split('&',$this->query) as $pair) {
			list($name,$value) = explode('=',$pair);
			$name = urldecode(str_replace('+',' ',$name));
			$value = urldecode(str_replace('+',' ',$value));
			$this->parameters[$name] = $value;
			}
		}
	}
/*
 * Build a full Url from properties of this object:
 *	returns a full Url as string
 */
public function fullUrl() {
	$url = ($this->protocol ? $this->protocol : "http") . "://";
	if ($this->username) {
		$url .= $this->username . ":";
		if ($this->password) $url .= $this->password;
		$url .= "@";
		}
	$url .= ($this->host ? $this->host : "www") . ".";
	$url .= ($this->sld ? $this->sld : "!!missing!!");
	$url .= "." . ($this->tld ? $this->tld : "com");
	if ($this->port) $url .= ":{$this->port}";
	if ($this->uri) $url .= $this->uri;
	return $url;
	}

}

?>