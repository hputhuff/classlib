<?php
/*---------------------------------------------------------------------*\
|    TOD class - properties & methods for dealing with Time-of-Day	|
\*---------------------------------------------------------------------*/

define("LONGTIME",1);		// long result (hh:mm:ss or hh:mm:ss a|pm)
define("SHORTTIME",0);		// short result (hh:mm or hh:mm a|pm)

class TOD {

	static $javascript = 0;	// toggle for javascript time handler
	public $tod;		// unix time value for this object

/*
 *::Convert any time to an internal time
 *	P1 = time value as string--i.e. hh:mm (a|pm), etc.
 *	returns an internal time as hh:mm:ss (military/SQL time)
 */
static function toInternal($tod=-1) {
	$obj = $tod!=-1 ? new TOD($tod) : new TOD;
	return $obj->internal();
	}
/*
 *::Convert any time to an external time
 *	P1 = time value as string--i.e. hh:mm(:ss) (a|pm), etc.
 *	P2 = format: LONGTIME,SHORTTIME
 *	returns an external time in the requested format
 */
static function toExternal($tod=-1,$format=SHORTTIME) {
	$obj = $tod!=-1 ? new TOD($tod) : new TOD;
	return $obj->external($format);
	}
/*
 * Constructor:
 *	P1 = (optional) time-of-day value
 */
public function __construct($value=null) {
	if (!func_num_args())
		$this->tod = time();
	else
		$this->set($value);
	}
/*
 * Destructor:
 */
public function __destruct() {}
/*
 * Determine if this object contains a valid time:
 *	returns true (valid) or false (invalid)
 */
public function valid() {
	return ($this->tod !== null ? true : false);
	}
/*
 * Set the time value for this object:
 *	P1 = (optional) time-of-day value, default = now()
 */
public function set($value=null) {
	if (! $value) {
		$this->tod = null;
		return;
		}
	$date = getdate();
	while ($value) {
		$date['hours'] = $date['minutes'] = $date['seconds'] = 0;
		if (preg_match('/(\d{1,2})\D+(\d{1,2})\D+(\d{1,2})/',$value,$parts)) {
			$date['hours'] = $parts[1];
			$date['minutes'] = $parts[2];
			$date['seconds'] = $parts[3];
			}
		else
		if (preg_match('/(\d{1,2})\D+(\d{1,2})/',$value,$parts)) {
			$date['hours'] = $parts[1];
			$date['minutes'] = $parts[2];
			}
		else
		if (preg_match('/(\d{1,2})\s*[AaPp]*/',$value,$parts))
			$date['hours'] = $parts[1];
		if (preg_match('/[Aa]/',$value) && ($date['hours'] == 12))
			$date['hours'] = 00;
		else
		if (preg_match('/[Pp]/',$value) && ($date['hours'] < 12))
			$date['hours'] += 12;
		break;
		}
	$this->tod = mktime($date['hours'],$date['minutes'],$date['seconds']);
	}
/*
 * Return an internal (SQL) time for this object:
 *	returns a string as: hh:mm:ss
 */
public function internal() {
	if (! $this->valid()) return null;
	$date = getdate($this->tod);
	$result = sprintf("%02d:%02d:%02d",$date['hours'],$date['minutes'],$date['seconds']);
	return $result;
	}
/*
 * Return an external time for this object:
 *	P1 = (optional) toggle, long or short result
 *	returns a string as: hh:mm(:ss) a|pm
 */
public function external($size=SHORTTIME) {
	if (! $this->valid()) return null;
	$date = getdate($this->tod);
	$hour = $date['hours'];
	$mer = "am";
	if ($hour < 12) {
		$mer = "am";
		if ($hour == 0) $hour = 12;
		}
	else	{
		$mer = "pm";
		if ($hour > 12) $hour -= 12;
		}
	$result = sprintf("%d:%02d",$hour,$date['minutes']);
	if ($size) $result .= sprintf(":%02d",$date['seconds']);
	$result .= " {$mer}";
	return $result;
	}
/*
 * Return an hour value for this object as an integer
 *	returns an integer (0-23)
 */
public function hour() {
	if (! $this->valid()) return null;
	$date = getdate($this->tod);
	return 0+$date['hours'];
	}
/*
 * Return a minute value for this object as an integer
 *	returns an integer (0-59)
 */
public function minute() {
	if (! $this->valid()) return null;
	$date = getdate($this->tod);
	return 0+$date['minutes'];
	}
/*
 * Return a second value for this object as an integer
 *	returns an integer (0-59)
 */
public function second() {
	if (! $this->valid()) return null;
	$date = getdate($this->tod);
	return 0+$date['seconds'];
	}
/*
 * Move the object's time by various intervals
 *	P1 = interval to move the time, i.e.
 *		"[-]nn second[s]"
 *		"[-]nn minutes[s]"
 *		"[-]nn hour[s]"
 *	returns internal value for new time
 */
public function move($interval=null) {
	if (! $this->valid()) return null;
	preg_match('/([0-9-]+)\s*([a-zA-Z]+)*/',$interval,$parts);
	$reps = 0+$parts[1]; $step = strtolower($parts[2]);
	if (! $reps) return $this->internal();
	switch ($step[0]) {
		case 'h':	$this->tod += (3600 * $reps); break;
		case 'm':	$this->tod += (60 * $reps); break;
		case 's':
		default:	$this->tod += $reps; break;
		}
	return $this->internal();
	}
/*
 * Return the html for selecting the time:
 *	P1 = name of the html object/form result
 *	P2 = (optional) css class for the fields
 *	returns html string for the form fields
 */
public function select($name,$class=null) {
	$html = "";
	if (! self::$javascript++) $html = <<<ETX
<script language="JavaScript" type="text/javascript">
// Build time field from parts
// call w/ID of date field
function buildTime(id) {
var e=document.getElementById(id),n=e.name,f=e.form;
if (eval('f.'+n+'_hh.selectedIndex==0')) {
	e.value='';
	return;
	}
if (eval('f.'+n+'_mm.selectedIndex')==0) eval('f.'+n+'_mm.selectedIndex=1');
var hh=Number(eval('f.'+n+'_hh.options[f.'+n+'_hh.selectedIndex].value'));
var mm=Number(eval('f.'+n+'_mm.options[f.'+n+'_mm.selectedIndex].value'));
var ss=0;
var mer=eval('f.'+n+'_mer.options[f.'+n+'_mer.selectedIndex].value');
if (hh==12) hh=0; if (mer=='pm') hh+=12;
hh=hh.toString(); if (hh.length<2) hh='0'+hh;
mm=mm.toString(); if (mm.length<2) mm='0'+mm;
ss=ss.toString(); if (ss.length<2) ss='0'+ss;
e.value=hh+':'+mm+':'+ss;
}
</script>
ETX;

	$two = "%02d";
	$css = $class ? " class=\"{$class}\"" : null;
	$tod = getdate($this->tod);
	$hh = $tod['hours']; $mm = $tod['minutes'];
	$mer = "am";
	if ($this->valid()) {
		if ($hh >= 12) {
			$mer = "pm";
			if ($hh > 12) $hh -= 12;
			}
		if (! $hh) $hh = 12;
		}
	$default = $this->internal();
	if ($default) $default = substr($default,0,-2) . "00";
	$html .= "<input id=\"{$name}\" type=\"hidden\" name=\"{$name}\" value=\"{$default}\">\n";
	$html .= "<select name=\"{$name}_hh\" size=\"1\" onChange=\"buildTime('{$name}')\"{$css}>";
	$html .= $this->tod ? "<option></option>" : "<option selected></option>";
	for ($ix=1; $ix<=12; ++$ix) {
		$selected = ($this->tod && ($hh == $ix)) ? " selected" : null;
		$text = sprintf($two,$ix);
		$html .= "<option value=\"{$ix}\"{$selected}>{$text}</option>";
		}
	$html .= "</select>";
	$html .= "<select name=\"{$name}_mm\" size=\"1\" onChange=\"buildTime('{$name}')\"{$css}>";
	$html .= $this->tod ? "<option></option>" : "<option selected></option>";
	for ($ix=0; $ix<=59; ++$ix) {
		$selected = ($this->tod && ($mm == $ix)) ? " selected" : null;
		$text = sprintf($two,$ix);
		$html .= "<option value=\"{$ix}\"{$selected}>{$text}</option>";
		}
	$html .= "</select>";
	$html .= "<select name=\"{$name}_mer\" size=\"1\" onChange=\"buildTime('{$name}')\"{$css}>";
	foreach (array('am','pm') as $ix) {
		$selected = ($mer == $ix) ? " selected" : null;
		$text = $ix;
		$html .= "<option value=\"{$ix}\"{$selected}>{$text}</option>";
		}
	$html .= "</select>\n";
	return $html;
	}

}

?>