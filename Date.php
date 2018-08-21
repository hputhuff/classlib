<?php
/**
 * Date class - for dealing with date values & calculations
 */

define("TEXTDATE",2);		// for text (Mmmmmmmmmm dd, yyyy)
define("LONGDATE",1);		// long result (yyyy-mm-dd hh:mm:ss)
define("SHORTDATE",0);		// short result (yyyy-mm-dd)
define("VERYSHORTDATE",-1);	// very short date (mm/dd/yy)
define("ONEDAY",86400);		// one day's seconds
define("ONEHOUR",3600);		// one hour's seconds
define("ONEMINUTE",60);		// one minute's seconds
define("MAXDATE","2037-12-31 23:59:59");	// max unix date value

class Date {

	public static $months = array(
		'January','February','March','April','May','June','July',
		'August','September','October','November','December'
		);
	public static $monthdays = array(31,28,31,30,31,30,31,31,30,31,30,31);

	public $date;			// getdate array
	public $utc = '-0600';	// UTC offset (if known)

/*
 *::Compare two Date objects:
 *	P1 = first Date object
 *	P2 = second Date object
 *	returns -1, 0, or 1 (<,=,>)
 */
static function compare($first,$second) {
	if ($first->date[0] == $second->date[0]) return 0;
	return ($first->date[0] < $second->date[0]) ? -1 : 1;
	}

/*
 *::Obtain the +/-days difference between two Date objects:
 *	P1 = first Date object
 *	P2 = second Date object
 *	returns the (signed) difference in days
 */
static function difference($first,$second) {
	$days = ($first->date[0]-$second->date[0])/ONEDAY;
	return $days;
	}

/*
 *::Convert any date to an internal date
 *	P1 = date value as string--i.e. mm/dd/yyyy, etc.
 *	P2 = format: [LONGDATE],SHORTDATE or VERYSHORTDATE
 *	returns an internal date in the requested format
 */
static function toInternal($date=null,$format=LONGDATE) {
	$that = $date ? new Date($date) : new Date;
	return $that->internal($format);
	}

/**
 * Convert a date to a datestamp date
 * 
 *	@param string $date				: (optional) date to convert
 *	@return string					: a datestamp (yyyymmddhhmmss)
 */
public static function toDatestamp($date=null) {
	$that = $date ? new Date($date) : new Date;
	return $that->datestamp();
	}

/*
 *::Convert any date to an external date
 *	P1 = date value as string--i.e. mm/dd/yyyy, etc.
 *	P2 = format: LONGDATE,[SHORTDATE] or VERYSHORTDATE
 *	returns an external date in the requested format
 */
public static function toExternal($date=null,$format=SHORTDATE) {
	$that = $date ? new Date($date) : new Date;
	return $that->external($format);
	}

/**
 *::Return date/time as a quick date
 * @param string $date		: (optional) date to render as quick
 * @return string			: date as m/dd h:mm(a|p)
 */
public static function toQuick($date=null) {
	$that = $date ? new Date($date) : new Date;
	return $that->quick();
	}

/**
 * return current date & time (as a timestamp)
 *
 * @return string			: current date/time as yyyy-mm-dd hh:mm:ss
 */
public static function timestamp() {
	return self::toInternal(null,LONGDATE);
	}

/**
 * return a date in internal format for the start of today
 *
 * @param int $format		: use SHORTDATE or LONGDATE or null
 * @return string			: a date string as yyyy-mm-dd[ 00:00:00]
 */
public static function today($format=null) {
	$thisdate = self::toInternal(null,SHORTDATE);
	if ($format===SHORTDATE) return $thisdate;
	return $thisdate." 00:00:00";
	}

/**
 * return the maximum date allowed (forever)
 * by then we should all be retired or deceased
 *
 * @return string			: a date string as 2037-12-31 23:59:59
 */
public static function forever() {
	return MAXDATE;
	}

//////////////////// object methods //////////////////

/**
 * return current date & time (timestamp)
 *
 * @return string			: the current date/time as yyyy-mm-dd hh:mm:ss
 */
public static function now() {
	return self::timestamp();
	}

/*
 * Constructor:
 *	P1 = (optional) date value
 */
public function __construct($value=null) {
	if (!func_num_args())
		$this->date = getdate();
	else
		$this->set($value);
	}

/*
 * Destructor:
 */
public function __destruct() {}

/**
 * express this object in 'string' context
 */
public function __toString() {return $this->internal(LONGDATE);}


/*
 * Determine if this object contains a valid date:
 *	returns true (valid) or false (invalid)
 */
public function valid() {
	return ($this->date ? true : false);
	}

/*
 * Set the date value for this object:
 *	P1 = (optional) date value, default = now()
 */
public function set($value=null) {
	if (! $value || $value=='0000-00-00 00:00:00') {
		$this->date = null;
		return;
		}
	$this->date = getdate();
	while ($value) {
		// MySql timestamp:
		if (preg_match('/^\s*(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})\s*$/',$value,$parts)) {
			$this->date['year'] = 0+$parts[1];
			$this->date['mon'] = 0+$parts[2];
			$this->date['mday'] = 0+$parts[3];
			$this->date['hours'] = 0+$parts[4];
			$this->date['minutes'] = 0+$parts[5];
			$this->date['seconds'] = 0+$parts[6];
			break;
			}
		// Unix timestamp:
		if (preg_match('/^\s*([-]?\d{6,})\s*$/',$value,$parts)) {
			$this->date = getdate(0+$parts[1]);
			return;
			}
		// MySql internal date and time format:
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})$/',$value,$parts)) {
			$this->date['year'] = 0+$parts[1];
			$this->date['mon'] = 0+$parts[2];
			$this->date['mday'] = 0+$parts[3];
			$this->date['hours'] = 0+$parts[4];
			$this->date['minutes'] = 0+$parts[5];
			$this->date['seconds'] = 0+$parts[6];
			break;
			}
		// MySql internal date format:
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/',$value,$parts)) {
			$this->date['year'] = 0+$parts[1];
			$this->date['mon'] = 0+$parts[2];
			$this->date['mday'] = 0+$parts[3];
			$this->date['hours'] = $this->date['minutes'] = $this->date['seconds'] = 0;
			break;
			}
		// only hour specified:
		if (preg_match('/^\s*(\d{1,2})\s*$/',$value,$parts)) {
			$this->date['hours'] = 0+$parts[1];
			$this->date['minutes'] = $this->date['seconds'] = 0;
			break;
			}
		// only hour and meridian) specified:
		if (preg_match('/^\s*(\d{1,2})\s*([AaPp]{1}).*$/',$value,$parts)) {
			$this->date['hours'] = 0+$parts[1];
			$this->date['minutes'] = $this->date['seconds'] = 0;
			$meridian = strtolower($parts[2]);
			if ($meridian=='a' && $this->date['hours']==12)	$this->date['hours'] = 0; else
			 if ($meridian=='p' && $this->date['hours']<12)	$this->date['hours'] += 12;
			break;
			}
		// only hour and minutes (and maybe meridian) specified:
		if (preg_match('/^\s*(\d{1,2}):(\d{1,2})\s*([AaPp]?).*$/',$value,$parts)) {
			$this->date['hours'] = 0+$parts[1];
			$this->date['minutes'] = 0+$parts[2];
			$this->date['seconds'] = 0;
			$meridian = strtolower($parts[3]);
			if ($meridian=='a' && $this->date['hours']==12)	$this->date['hours'] = 0; else
			 if ($meridian=='p' && $this->date['hours']<12)	$this->date['hours'] += 12;
			break;
			}
		// only hours, minutes and seconds (and maybe meridian) specified:
		if (preg_match('/^\s*(\d{1,2}):(\d{1,2}):(\d{1,2})\s*([AaPp]?).*$/',$value,$parts)) {
			$this->date['hours'] = 0+$parts[1];
			$this->date['minutes'] = 0+$parts[2];
			$this->date['seconds'] = 0+$parts[3];
			$meridian = strtolower($parts[4]);
			if ($meridian=='a' && $this->date['hours']==12)	$this->date['hours'] = 0; else
			 if ($meridian=='p' && $this->date['hours']<12)	$this->date['hours'] += 12;
			break;
			}
		$this->date['hours'] = $this->date['minutes'] = $this->date['seconds'] = 0;
		// look for m/d/y:
		if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{1,4})/',$value,$parts)) {
			if ($parts[3] < 100)
				if ($parts[3] < 50)	$parts[3] += 2000;
					else		$parts[3] += 1900;
			$this->date['mon'] = 0+$parts[1];
			$this->date['mday'] = 0+$parts[2];
			$this->date['year'] = 0+$parts[3];
			}
		else
		// look for m/d:
		if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})/',$value,$parts)) {
			$this->date['mon'] = 0+$parts[1];
			$this->date['mday'] = 0+$parts[2];
			}
		// look for hh:mm:ss
		if (preg_match('/(\d{1,2}):(\d{1,2}):(\d{1,2})/',$value,$parts)) {
			$this->date['hours'] = 0+$parts[1];
			$this->date['minutes'] = 0+$parts[2];
			$this->date['seconds'] = 0+$parts[3];
			}
		else
		// look for hh:mm
		if (preg_match('/(\d{1,2}):(\d{1,2})/',$value,$parts)) {
			$this->date['hours'] = 0+$parts[1];
			$this->date['minutes'] = 0+$parts[2];
			}
		else
		if (preg_match('/(\d{1,2})[AaPp]/',$value,$parts))
			$this->date['hours'] = 0+$parts[1];
		if (preg_match('/[Aa]/',$value) && ($this->date['hours'] == 12))
			$this->date['hours'] = 00;
		else
		if (preg_match('/[Pp]/',$value) && ($this->date['hours'] < 12))
			$this->date['hours'] += 12;
		break;
		}
	$this->date[0] = mktime(
		$this->date['hours'],
		$this->date['minutes'],
		$this->date['seconds'],
		$this->date['mon'],
		$this->date['mday'],
		$this->date['year']
		);
	$this->date = getdate($this->date[0]);
	}

/**
 * return the month (1-12) for this Date object
 * @param int					: (optional) offset in months to apply +/-
 * @return int					: month, 1-12 or null
 */
public function month($offset=null) {
	if (! $this->date[0]) return null;
	$mm = $this->date['mon'];
	if ($offset) {
		$mm += preg_replace('/[^0-9-]/','',$offset);
		while ($mm > 12) $mm -= 12;
		while ($mm <= 0) $mm += 12;
		}
	return $mm;
	}

/**
 * return the day of month (1-31) for this Date object
 * @param int					: (optional) offset in days to apply +/-
 * @return int					: day, 1-31 or null
 */
public function day($offset=null) {
	if (! $this->date[0]) return null;
	if ($offset) {
		$that = clone $this;
		$that->move(preg_replace('/[^0-9-]/','',$offset)." days");
		return $that->date['mday'];
		}
	else
		return $this->date['mday'];
	}

/**
 * return the year (1900-2037) for this Date object
 * @param int					: (optional) offset in years to apply +/-
 * @return int					: year as yyyy or null
 */
public function year($offset=null) {
	if (! $this->date[0]) return null;
	return $this->date['year']+preg_replace('/[^0-9-]/','',$offset);
	}

/**
 * Return the number of days in this object's month/year
 *	returns an int (28-31) or null if no date present
 */
public function daysInMonth() {
	if (! $this->valid()) return null;
	return cal_days_in_month(CAL_GREGORIAN,$this->date['mon'],$this->date['year']);
	}

/*
 * Return an internal (SQL) date for this object:
 *	P1 = (optional) toggle, long or short result
 *	returns a string as: yyyy-mm-dd( hh:mm:ss)
 */
public function internal($size=LONGDATE) {
	if (! $this->valid()) return null;
	$result = sprintf("%04d-%02d-%02d",
			  $this->date['year'],$this->date['mon'],$this->date['mday']);
	if ($size!=SHORTDATE)
		$result .= sprintf(" %02d:%02d:%02d",$this->date['hours'],
				   $this->date['minutes'],$this->date['seconds']);
	return $result;
	}

/**
 * return object date & time (as a datestamp)
 * 
 *	@return	string				: current date/time as yyyymmddhhmmss
 */
public function datestamp() {
	if (! $this->valid()) return null;
	return sprintf(
		"%04d%02d%02d%02d%02d%02d",
		$this->date['year'],$this->date['mon'],$this->date['mday'],
		$this->date['hours'],$this->date['minutes'],$this->date['seconds']
		);
	}

/*
 * Return an external date for this object:
 *	P1 = (optional) toggle: text, long, short or very short result
 *	returns a string as: mm/dd/(yy)yy( hh:mm:ss a|pm) or Mmmmmmmm dd, yyyy
 */
public function external($size=SHORTDATE) {
	if (! $this->valid()) return null;
	$result = sprintf("%d/%d/%04d",$this->date['mon'],$this->date['mday'],$this->date['year']);
	if ($size==VERYSHORTDATE) {
		$year = $this->date['year'] - (($this->date['year'] < 2000) ? 1900 : 2000);
		return sprintf("%d/%d/%02d",$this->date['mon'],$this->date['mday'],$year);
		}
	if ($size==TEXTDATE) {
		return sprintf(
			"%s %d, %04d",
			self::$months[$this->date['mon']-1],
			$this->date['mday'],
			$this->date['year']
			);
		}
	if ($size==LONGDATE) {
		$hour = $this->date['hours'];
		$mer = "am";
		if ($hour < 12) {
			$mer = "am";
			if ($hour == 0) $hour = 12;
			}
		else	{
			$mer = "pm";
			if ($hour > 12) $hour -= 12;
			}
		$result .= sprintf(" %d:%02d %s",$hour,$this->date['minutes'],$mer);
		}
	return $result;
	}


/**
 * render a 'quick' date for brevity
 *
 * @return string						: date as m/dd h:mm(a|p)
 */
public function quick() {
	$hour = $this->date['hours'];
	$meridian = 'a';
	if ($hour == 0)	$hour = 12;
		else
			if ($hour == 12) $meridian = 'p';
				else
					if ($hour > 12) {$hour -= 12; $meridian = 'p';}
	return sprintf('%d/%02d %d:%02d%s',$this->date['mon'],$this->date['mday'],
				   $hour,$this->date['minutes'],$meridian);
	}

/*
 * Move the object's date by various intervals:
 *	P1 = interval to move the date, i.e.
 *		"first (day)" first day of month
 *		"last (day)" last day of month
 *		"(-)nn second(s)"
 *		"(-)nn minute(s)"
 *		"(-)nn hour(s)"
 *		"(-)nn day(s)"
 *		"(-)nn weekday(s)"
 *		"(-)nn week(s)"
 *		"(-)nn month(s)"
 *		"(-)nn quarter(s)"
 *		"(-)nn year(s)"
 *	returns internal value for new date
 */
public function move($interval=null) {
	if (! $this->valid()) return null;
	if (preg_match('/first|last/i',$interval)) {
		if (preg_match('/first/i',$interval)) {
			$this->date['mday'] = 1;
			}
		else {
			$this->date['mday'] = $this->daysInMonth();
			}
		$this->date['hours'] =
			$this->date['minutes'] =
				$this->date['seconds'] = 0;
		$this->date = getdate(mktime(
			$this->date['hours'],
			$this->date['minutes'],
			$this->date['seconds'],
			$this->date['mon'],
			$this->date['mday'],
			$this->date['year']
			));
		return $this->internal(LONGDATE);
		}
	preg_match('/([0-9-]+)\s*([a-zA-Z]+)*/',$interval,$parts);
	$reps = 0+$parts[1]; $step = strtolower($parts[2]);
	if (! $reps) return $this->internal(LONGDATE);
	if (preg_match('/weekday/',$step)) {
		$count = abs($reps);
		$cursor = ($reps < 0) ? -1 : 1;
		while ($count--)
			while (1) {
				$this->date = getdate(mktime(
					$this->date['hours'],
					$this->date['minutes'],
					$this->date['seconds'],
					$this->date['mon'],
					($this->date['mday']+$cursor),
					$this->date['year']
					));
				if (($this->date['wday']>0) && ($this->date['wday']<6)) break;
				}
		return $this->internal(LONGDATE);
		}
	switch ($step) {
		case "hour": case "hours":
			$this->date['hours'] += $reps;
			break;
		case "minute": case "minutes":
			$this->date['minutes'] += $reps;
			break;
		case "second": case "seconds":
			$this->date['seconds'] += $reps;
			break;
		case "week": case "weeks":
			$this->date['mday'] += ($reps * 7);
			break;
		case "month": case "months":
			$this->date['mon'] += $reps;
			$temp = getdate(mktime(0,0,0,$this->date['mon'],1,$this->date['year'])); // temp marker in time
			$maxdays = self::$monthdays[$temp['mon']-1];
			$this->date['mday'] = ($this->date['mday'] > $maxdays) ? $maxdays : $this->date['mday'];
			$this->date['mon'] = $temp['mon'];
			$this->date['year'] = $temp['year'];
			break;
		case "quarter": case "quarters":
			$this->date['mon'] += ($reps * 3);
			$temp = getdate(mktime(0,0,0,$this->date['mon'],1,$this->date['year'])); // temp marker in time
			$maxdays = self::$monthdays[$temp['mon']-1];
			$this->date['mday'] = ($this->date['mday'] > $maxdays) ? $maxdays : $this->date['mday'];
			$this->date['mon'] = $temp['mon'];
			$this->date['year'] = $temp['year'];
			break;
		case "year": case "years":
			$this->date['year'] += $reps;
			break;
		case "day": case "days":
		default:
			$this->date['mday'] += $reps;
			break;
		}
	$this->date = getdate(mktime(
		$this->date['hours'],
		$this->date['minutes'],
		$this->date['seconds'],
		$this->date['mon'],
		$this->date['mday'],
		$this->date['year']
		));
	return $this->internal(LONGDATE);
	}

/**
 * apply UTC offset to this date (object)
 * 
 * @param string $offset			: offset as [+/-]hh[mm]
 * @return boolean					: true=applied, false=error
 */
public function applyUTCoffset($offset) {
	if (! preg_match('/^[+-]*\d{1,4}$/',$offset)) return false;
	if (preg_match('/(\+|\-)/',$offset,$parts)) {
		$sign = ($parts[1] == '-') ? -1 : 1;
		$offset = preg_replace('/[^0-9]/','',$offset);
		}
	else
		$sign = 1;
	if (strlen($offset) <= 2) {
		$hours = 0 + $offset;
		$minutes = 0;
		}
	else {
		$minutes = 0 + substr($offset,-2);
		$hours = 0 + substr($offset,0,-2);
		}
	$this->utc = sprintf('%s%02d%02d',(($sign < 0) ? '-' : '+'),$hours,$minutes);
	$timestamp = $this->date[0] + ($sign * $minutes * 60) + ($sign * $hours * 3600);
	$this->date = getdate($timestamp);
	return true;
	}

/**
 * calculate elapsed time display (from this object)
 * 
 * @param int $seconds			: (optional) elapsed seconds
 * @return string
 */
public function elapsed($seconds=null) {
	if (! $seconds) $seconds = time() - $this->date[0];
	$result = "";
	$hours = intval($seconds / 3600);
	if ($hours) {
		$result .= "$hours hour".($hours==1 ? '' : 's').", ";
		$seconds -= ($hours * 3600);
		}
	$minutes = intval($seconds / 60);
	if ($minutes) {
		$result .= "$minutes minute".($minutes==1 ? '' : 's').", ";
		$seconds -= ($minutes * 60);
		}
	$result .= "$seconds second".($seconds==1 ? '' : 's');
	return $result;	
	}
	
}

?>