<?php
/*---------------------------------------------------------------------*\
|	 Html class - methods for HTML constructs, forms, etc.		|
\*---------------------------------------------------------------------*/
class Html {

static $countries = array(	// ISO 2-char codes
"United States"=>'US',
"Afghanistan"=>'AF',"Albania"=>'AL',"Algeria"=>'DZ',"American Samoa"=>'AS',"Andorra"=>'AD',
"Angola"=>'AO',"Anguilla"=>'AI',"Antarctica"=>'AQ',"Antigua & Barbuda"=>'AG',"Argentina"=>'AR',
"Armenia"=>'AM',"Aruba"=>'AW',"Australia"=>'AU',"Austria"=>'AT',"Azerbaijan"=>'AZ',"Bahamas"=>'BS',
"Bahrain"=>'BH',"Bangladesh"=>'BD',"Barbados"=>'BB',"Belarus"=>'BY',"Belgium"=>'BE',"Belize"=>'BZ',
"Benin"=>'BJ',"Bermuda"=>'BM',"Bhutan"=>'BT',"Bolivia"=>'BO',"Bosnia"=>'BA',"Botswana"=>'BW',
"Bouvet Island"=>'BV',"Brazil"=>'BR',"Brunei"=>'BN',"Bulgaria"=>'BG',"Burkina Faso"=>'BF',
"Burundi"=>'BI',"Cambodia"=>'KH',"Cameroon"=>'CM',"Canada"=>'CA',"Cape Verde"=>'CV',
"Cayman Islands"=>'KY',"Cent. African Rep."=>'CF',"Chad"=>'TD',"Chile"=>'CL',"China"=>'CN',
"Christmas Is."=>'CX',"Cocos Islands"=>'CC',"Columbia"=>'CO',"Comoros"=>'KM',"Congo"=>'CG',
"Cook Islands"=>'CK',"Costa Rica"=>'CR',"Cote D'Ivorie"=>'CI',"Croatia"=>'HR',"Cuba"=>'CU',
"Cyprus"=>'CY',"Czech Rep."=>'CZ',"Dem. Rep. Of Congo"=>'CD',"Denmark"=>'DK',"Djibouti"=>'DJ',
"Dominica"=>'DM',"Dominican Republic"=>'DO',"East Timor"=>'TP',"Ecuador"=>'EC',"Egypt"=>'EG',
"El Salvador"=>'SV',"Equatorial Guinea"=>'GQ',"Eritrea"=>'ER',"Estonia"=>'EE',"Ethiopia"=>'ET',
"Falkland Islands"=>'FK',"Faroe Islands"=>'FO',"Fiji"=>'FJ',"Finland"=>'FI',"France"=>'FR',
"France, Met."=>'FX',"Fr. Guinea"=>'GF',"Fr. Polynesia"=>'PF',"Fr. S. Terr."=>'TF',"Gabon"=>'GA',
"Gambia"=>'GM',"Georgia"=>'GE',"Germany"=>'DE',"Ghana"=>'GH',"Gibraltar"=>'GI',"Greece"=>'GR',
"Greenland"=>'GL',"Grenada"=>'GD',"Guadeloupe"=>'GP',"Guam"=>'GU',"Guatemala"=>'GT',"Guinea"=>'GN',
"Guinea-Bissau"=>'GW',"Guyana"=>'GY',"Haiti"=>'HT',"Honduras"=>'HN',"Hong Kong"=>'HK',
"Hungary"=>'HU',"Iceland"=>'IS',"India"=>'IN',"Indonesia"=>'ID',"Iran"=>'IR',"Iraq"=>'IQ',
"Ireland"=>'IE',"Israel"=>'IL',"Italy"=>'IT',"Jamaica"=>'JM',"Japan"=>'JP',"Jordan"=>'JO',
"Kazakhstan"=>'KZ',"Kenya"=>'KE',"Kiribati"=>'KI',"Kuwait"=>'KW',"Kyrgyzstan"=>'KG',"Laos"=>'LA',
"Latvia"=>'LV',"Lebanon"=>'LB',"Lesotho"=>'LS',"Liberia"=>'LR',"Libya"=>'LY',"Liechtenstein"=>'LI',
"Lithuania"=>'LT',"Luxembourg"=>'LU',"Macau"=>'MO',"Macedonia"=>'MK',"Madagascar"=>'MG',
"Malawi"=>'MW',"Malaysia"=>'MY',"Maldives"=>'MV',"Mali"=>'ML',"Malta"=>'MT',
"Marshall Islands"=>'MH',"Martinique"=>'MQ',"Mauritania"=>'MR',"Mauritius"=>'MU',"Mayotte"=>'YT',
"Mexico"=>'MX',"Micronesia"=>'FM',"Moldova"=>'MD',"Monaco"=>'MC',"Mongolia"=>'MN',
"Montserrat"=>'MS',"Morocco"=>'MA',"Mozambique"=>'MZ',"Myanmar (Burma)"=>'MM',"Namibia"=>'NA',
"Nauru"=>'NR',"Nepal"=>'NP',"Netherlands"=>'NL',"Netherlands Antilles"=>'AN',"New Caledonia"=>'NC',
"New Zealand"=>'NZ',"Nicaragua"=>'NI',"Niger"=>'NE',"Nigeria"=>'NG',"Niue"=>'NU',
"Norfolk Island"=>'NF',"North Korea"=>'KP',"N. Mariana Is."=>'MP',"Norway"=>'NO',"Oman"=>'OM',
"Pakistan"=>'PK',"Palau"=>'PW',"Panama"=>'PA',"Papua New Guinea"=>'PG',"Paraguay"=>'PY',
"Peru"=>'PE',"Philippines"=>'PH',"Pitcairn"=>'PN',"Poland"=>'PL',"Portugal"=>'PT',
"Puerto Rico"=>'PR',"Qatar"=>'QA',"Reunion"=>'RE',"Romania"=>'RO',"Russia"=>'RU',"Rwanda"=>'RW',
"St. Helena"=>'SH',"St. Kitts"=>'KN',"St. Lucia"=>'LC',"St. Pierre"=>'PM',"St. Vincent"=>'VC',
"San Marino"=>'SM',"Saudi Arabia"=>'SA',"Senegal"=>'SN',"Seychelles"=>'SC',"Sierra Leone"=>'SL',
"Singapore"=>'SG',"Slovak Republic"=>'SK',"Slovenia"=>'SI',"Solomon Islands"=>'SB',"Somalia"=>'SO',
"South Africa"=>'ZA',"South Korea"=>'KR',"Spain"=>'ES',"Sri Lanka"=>'LK',"Sudan"=>'SD',
"Suriname"=>'SR',"Swaziland"=>'SZ',"Sweden"=>'SE',"Switzerland"=>'CH',"Syria"=>'SY',"Taiwan"=>'TW',
"Tajikistan"=>'TJ',"Tanzania"=>'TZ',"Thailand"=>'TH',"Togo"=>'TG',"Tokelau"=>'TK',"Tonga"=>'TO',
"Trinidad & Tobago"=>'TT',"Tunisia"=>'TN',"Turkey"=>'TR',"Turkmenistan"=>'TM',
"Turks & Caicos"=>'TC',"Tuvalu"=>'TV',"Uganda"=>'UG',"Ukraine"=>'UA',"United Arab Emirates"=>'AE',
"United Kingdom"=>'UK',"United States (Is.)"=>'UM',"Uruguay"=>'UY',"Uzbekistan"=>'UZ',
"Vanuatu"=>'VU',"Vatican City"=>'VA',"Venezuela"=>'VE',"Vietnam"=>'VN',"Virgin Islands (Br)"=>'VG',
"Virgin Islands (US)"=>'VI',"Western Sahara"=>'EH',"Western Samoa"=>'WS',"Yemen"=>'YE',
"Yugoslavia"=>'YU',"Zambia"=>'ZM',"Zimbabwe"=>'ZW'
);

static $provinces = array(			// Canadian provinces
	"Alberta"=>'AB',"British Columbia"=>'BC',"Manitoba"=>'MB',"New Brunswick"=>'NB',
	"Newfoundland/Lab."=>'NL',"NW Territories"=>'NT',"Nova Scotia"=>'NS',"Nunavut"=>'NU',
	"Ontario"=>'ON',"Prince Edward Is."=>'PE',"Quebec"=>'QC',"Saskatchewan"=>'SK',
	"Yukon"=>'YT'
	);
static $states = array(				// USA states
	"Alabama"=>'AL',"Alaska"=>'AK',"Arizona"=>'AZ',"Arkansas"=>'AR',"California"=>'CA',
	"Colorado"=>'CO',"Connecticut"=>'CT',"Delaware"=>'DE',"D.C."=>'DC',"Florida"=>'FL',
	"Georgia"=>'GA',"Hawaii"=>'HI',"Iowa"=>'IA',"Idaho"=>'ID',"Illinois"=>'IL',
	"Indiana"=>'IN',"Kansas"=>'KS',"Kentucky"=>'KY',"Louisiana"=>'LA',"Maine"=>'ME',
	"Massachusetts"=>'MA',"Maryland"=>'MD',"Michigan"=>'MI',"Minnesota"=>'MN',
	"Mississippi"=>'MS',"Missouri"=>'MO',"Montana"=>'MT',"Nebraska"=>'NE',"Nevada"=>'NV',
	"New Hampshire"=>'NH',"New Jersey"=>'NJ',"New Mexico"=>'NM',"New York"=>'NY',
	"North Carolina"=>'NC',"North Dakota"=>'ND',"Ohio"=>'OH',"Oklahoma"=>'OK',
	"Oregon"=>'OR',"Pennsylvania"=>'PA',"Rhode Island"=>'RI',"South Carolina"=>'SC',
	"South Dakota"=>'SD',"Tennessee"=>'TN',"Texas"=>'TX',"Utah"=>'UT',"Vermont"=>'VT',
	"Virginia"=>'VA',"Washington"=>'WA',"West Virginia"=>'WV',"Wisconsin"=>'WI',
	"Wyoming"=>'WY'
	);

/*
 * return a style for the alternating color bar background
 *	returns 'even' or 'odd'
 */
static $colorBar = 'odd';
static function colorbar() {
	self::$colorBar = (self::$colorBar=='odd') ? 'even' : 'odd';
	return self::$colorBar;
	}
/*
 * Display the http headers for a non-cached page
 */
static function headers() {
	header("Content-type: text/html; charset=ISO-8859-1");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	}
/*
 * Return the HTML meta tags for a dynamic content page
 */
static function dynamicMetaTags() {
return <<<ETX
<META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
<META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">
<META HTTP-EQUIV="EXPIRES" CONTENT="Sat, 01 Jan 2000 00:00:00 GMT">
ETX;
}

/**
 * Produce an html summary table of tag: value elements
 * @param array $hash
 * @param string $class (optional)
 * @return string html
 */
public static function summaryTable($hash,$class=null) {
	$summary = "<table" . ($class ? " class=\"{$class}\"" : "") . ">\n";
	foreach ($hash as $key=>$value) {
		if (preg_match('/^\w+@\w+/i',$value))
			$value = "<a href=\"mailto:{$value}\">{$value}</a>";
		else
		if (preg_match('/^http/i',$value))
			$value = "<a href=\"{$value}\" target=\"_blank\">{$value}</a>";
		$summary .= "<tr><td>{$key}:&nbsp;</td><th>{$value}</th></tr>\n";
		}
	$summary .= "</table>\n";
	return $summary;
	}

/*
 * Create HTML for a <select ...> field:
 *	P1 = name of the field
 *	P2 = ref. to an array of choices
 *	P3 = (optional) default value
 *	P4 = (optional) flag for inserting blank choice at front
 *	P5 = (optional) css class name
 *	returns HTML for the field
 */
static function select($name,&$choices,$default=null,$blank=false,$class=null) {
	$ident = $name ? " name=\"{$name}\"" : "";
	$id = (!$name || substr_count($name,'[')) ? " " : " id=\"{$name}_id\" ";
	$html = "<select{$ident}{$id}size=\"1\"" .
	 ($class ? " class=\"{$class}\"" : "") . ">";
	if ($blank) $html .= "<option value=\"\"></option>";
	list($key) = array_keys($choices);
	$selector = " selected";
	if (is_numeric($key) && ($key===0))
		foreach ($choices as $choice) {
			if ($choice == $default) {
				$selected = $selector;
				$selector = "";
				}
			else
				$selected = "";
			$html .= "<option value=\"{$choice}\"$selected>{$choice}</option>";
			}
	else
		foreach ($choices as $key=>$value) {
			if ($value == $default) {
				$selected = $selector;
				$selector = "";
				}
			else
				$selected = "";
			$html .= "<option value=\"{$value}\"$selected>{$key}</option>";
			}
	$html .= "</select>";
	return $html;
	}
/*
 * Create HTML to select a country value:
 *	P1 = name of the field
 *	P2 = default choice
 *	P3 = css class name
 *	returns HTML for the field
 */
static function selectCountry($name,$default=null,$class=null) {
	return self::select($name,self::$countries,$default,true,$class);
	}
/*
 * Create HTML to select a 2-char country ISO code (XX) value:
 *	P1 = name of the field
 *	P2 = default choice
 *	P3 = css class name
 *	returns HTML for the field
 */
static function selectCountryCode($name,$default=null,$class=null) {
	$codes = array_values(self::$countries);
	sort($codes);
	return self::select($name,$codes,$default,true,$class);
	}
/**
 * Create a combined hash with states & provinces
 * 
 * @return hash						: a combined hash array,sorted
 */
static function combineStatesProvinces() {
	$combined = array_merge(self::$states,self::$provinces);
	ksort($combined);
	return $combined;
	}
/**
 * lookup a state code by state name
 * 
 * @param string $name				: the full state name
 * @return string					: the two-char code
 */
static function lookupStateCode($name) {
	$complete = self::combineStatesProvinces();
	return $complete[$name];
	}
/**
 * lookup a state name by state code
 * 
 * @param string $code				: 2-char code for state
 * @return string					: full state name
 */
static function lookupStateName($code) {
	$complete = self::combineStatesProvinces();
	return array_search($code,$complete);
	}
/*
 * Create HTML to select a state value:
 *	P1 = name of the field
 *	P2 = default choice
 *	P3 = css class name
 *	returns HTML for the field
 */
static function selectState($name,$default=null,$class=null) {
	return self::select($name,self::combineStatesProvinces(),$default,true,$class);
	}
/*
 * Create HTML to select a state postal code (XX) value:
 *	P1 = name of the field
 *	P2 = default choice
 *	P3 = css class name
 *	returns HTML for the field
 */
static function selectStateCode($name,$default=null,$class=null) {
	$codes = array_values(self::combineStatesProvinces());
	sort($codes);
	return self::select($name,$codes,$default,true,$class);
	}
/*
 * Create HTML for a credit card expiration control:
 *	P1 = (optional) name for the form field (cardexpires)
 *	P2 = (optional) default value as m[m][/]yy[yy]
 *	returns HTML for the field
 */
static private $expireCode = 0;
static function selectCardExpiration($name='cardexpires',$value=null) {
	$now = getdate();
	$id = $name."_id";
	$defaultMM = $defaultYY = $defaultValue = null;
	if (preg_match('/^(\d\d)(\d\d)$/',$value,$parts) ||
		preg_match('/^(\d+)\D+(\d+)$/',$value,$parts)) {
		$defaultMM = $parts[1];
		$defaultYY = $parts[2];
		if ($defaultYY < 100) $defaultYY += 2000;
		$defaultValue = sprintf('%02d/%04d',$defaultMM,$defaultYY);
		}
	$html = "";
	if (! self::$expireCode++) $html .= <<<ETX
<script type="text/javascript">
// update a credit card expiration:
function updateCardExpiration(field) {
var value;
var whole = document.getElementById(field+"_id");
var mmpart = document.getElementById(field+"_mm_id");
var yypart = document.getElementById(field+"_yy_id");
var mmvalue = mmpart.options[mmpart.selectedIndex].value;
var yyvalue = yypart.options[yypart.selectedIndex].value;
if (mmvalue=='' || yyvalue=='') {whole.value = ''; return;}
value = (mmvalue<10 ? '0' : '') + mmvalue.toString() + '/' + yyvalue.toString();
whole.value = value;
}
</script>
ETX;
	$html .= "<input type='hidden' name='{$name}_original'" .
			 " id='{$name}_original_id'" .
			 " value=\"{$defaultValue}\">" .
			 "<input type='hidden' name='{$name}' id='{$id}'" .
			 " value=\"{$defaultValue}\">" .
			 "<select name='{$name}_mm' id='{$name}_mm_id' size='1'" .
			 " onchange=\"updateCardExpiration('{$name}')\">";
	$selected = $defaultMM ? "" : " selected";
	$html .= "<option value=''{$selected}></option>";
	for ($mx=1; $mx<=12; ++$mx) {
		$selected = ($mx == $defaultMM) ? " selected" : "";
		$html .= " <option value='{$mx}'{$selected}>{$mx}</option>";
		}
	$html .= "</select>" .
			 "<select name='{$name}_yy' id='{$name}_yy_id' size='1'" .
			 " onchange=\"updateCardExpiration('{$name}')\">";
	$selected = $defaultYY ? "" : " selected";
	$html .= "<option value=''{$selected}></option>";
	for ($yx=$now['year']; $yx<($now['year']+10); ++$yx) {
		$selected = ($yx == $defaultYY) ? " selected" : "";
		$html .= " <option value='{$yx}'{$selected}>{$yx}</option>";
		}
	$html .= "</select>\n";
	return $html;
	}
/*
 * Create HTML for a row of <input type=radio...> buttons
 *	P1 = name of the field
 *	P2 = array of choices
 *	P3 = (optional) default value
 *	P4 = (optional) css class name
 *	returns HTML for the field
 */
static function radio($name,&$choices,$default=null,$class=null) {
	if (!$default) $default = $choices[0];
	$html = "";
	foreach ($choices as $choice) {
		if (! $choice) continue;
		$checked = ($choice == $default) ? " checked" : "";
		$text = $class ? "<span class=\"{$class}\">{$choice}</span>" : $choice;
		$html .= "<input type=\"radio\" name=\"{$name}\"" .
			 " value=\"{$choice}\"{$checked}>{$text}&nbsp;";
		}
	return $html;
	}
/*
 * Create HTML for a series of checkbox items w/labels:
 *	P1 = name of the field
 *	P2 = options as a set(string) or array
 *	P3 = default set of choices
 *	P4 = (optional) max. choices per row
 *	P5 = (optional) css class name for control
 *	returns HTML for the field
 */
static function checkboxes($name,$set,$default=null,$max=null,$class=null) {
	$html = "";
	$rowcount = 0;
	if (is_array($set))
		$choices =& $set;
	else
		$choices = explode(",",$set);
	foreach ($choices as $choice) {
		$checked = Set::find($choice,$default) ? " checked" : "";
		$label = $class ? "<span class=\"{$class}\">{$choice}</span>" : $choice;
		$html .= "<input type=\"checkbox\" name=\"{$name}[]\" value=\"{$choice}\"{$checked}>" .
			 "{$label}&nbsp;&nbsp;&nbsp;";
		++$rowcount;
		if ($max && ($rowcount >= $max)) {
			$html .= "<br>";
			$rowcount = 0;
			}
		}
	$html .= "<input type=\"hidden\" name=\"{$name}[]\">";	// for when no other box is checked
	return $html;
	}
/*
 * Produce HTML for a psuedo-dialog box
 *	P1 = title of the dialog box
 *	     note: if the title contains a ! then the message is a warning
 *		   otherwise it is informational.
 *	P2 = message for the dialog box
 *	P3 = (optional) document background: style attribute
 *	P4 = (optional) url to next page or array of label=>url pairs
 *	     note: if the url has '://' in it, it will open in the top window
 *	     note: if the url is an integer, it links to that entry in the history object
 *	returns HTML string
 */
static function dialogBox($title,$message,$bg=null,$url=null) {
	$background = $bg ? $bg : "whitesmoke";
	if (strstr($title,'!')!==false) {
		$banner = "rgb(228,30,38)";
		$icon = "/images/Warning.png";
		}
	else	{
		$banner = "rgb(0,149,218)";
		$icon = "/images/Ok.png";
		}
	if ($url) {
		if (! is_array($url)) $url = array('Continue'=>$url);
		$buttons = array();
		foreach ($url as $tag=>$link) {
			$button = "<input type=\"button\" value=\"{$tag}\" ";
			if (is_numeric($link))
				$button .= "onClick=\"history.go({$link})\">";
			else
			if (strstr($link,'://'))
				$button .= "onClick=\"window.top.location='{$link}'\">";
			else
			if (stristr($link,"javascript:") !== false)
				$button .= "onClick=\"{$link}\">";
			else
				$button .= "onClick=\"window.location='{$link}'\">";
			$buttons[] = $button;
			}
		$buttons = "<div id='buttons'>".join('&nbsp;&nbsp;',$buttons)."</div>\n";
		}
	return <<<ETX
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/1999/REC-html401-19991224/loose.dtd">
<html><head><title>{$title}</title><style type="text/css">
body {background: {$background}; margin: auto; text-align: center;}
div#panel {margin: 5em auto; color: black; width: 76%; padding: 20px; text-align: center;
 background-color: gainsboro; border: none;}
div#panel #banner {font: small-caps bold 12pt verdana,sans; color: white; background: {$banner};
 border: thin inset; padding: .25em; width: 100%; text-align: left;}
div#panel table {font: 12pt verdana,sans; color: black;	border: none; width: 100%;}
div#panel input {font: small-caps 12pt verdana,sans; color: navy; cursor: hand;}
div#buttons {text-align: center; height: 1.5em;}
</style><script type="text/JavaScript" src="/javascript/jquery.js"></script>
<script type="text/javascript" src="/javascript/jquery.corners.js"></script>
<script type="text/javascript">\$(document).ready(function(){\$("#panel").corner("20px");});</script>
</head><body><form><table width="100%" height="96%"><tr><th><div id="panel"><div id="banner">{$title}</div>
<table><tr><th><img src="{$icon}" width="100" border="0"></th><td>{$message}</td></tr></table>
{$buttons}</div><br><br></form></th></tr></table></body></html>
ETX;
}

}

?>