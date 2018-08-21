<?php
/**
 *  _____                 _ _ 
 * | ____|_ __ ___   __ _(_) |
 * |  _| | '_ ` _ \ / _` | | |
 * | |___| | | | | | (_| | | |
 * |_____|_| |_| |_|\__,_|_|_|
 *
 * Email class - handle email operations
 * September 2015 by Harley H. Puthuff
 * Copyright 2015, Your Showcase on the Internet
 */

class Email {

/**
 * ::check for a valid, single e-mail address
 * 
 * @param string $email			: an email address to check (someone@somewhere.com)
 * @return boolean				: true (valid) or false (invalid)
 */
public static function validate($email=null) {
	$regexp = "^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$";
	$valid = false;
	if (eregi($regexp,$email)) {
		list($username,$sld) = split("@",$email);
		if (getmxrr($sld,$mxrecords)) $valid = true;
		}
	return $valid;
	}

/**
 * ::check any request data for spam and trash it
 * 
 * @param string $unit			: (optional) a unit name for logging (Medusa)
 * @return boolean				: false = no spam present
 */
public static function filterSPAM($unit="Medusa") {
	$expr = "/(href=|link=|url=|porno|optimization)/i";
	foreach ($_REQUEST as $key => $value) {
		if (! $value) continue;
		if (! preg_match($expr,$value)) continue;
		$ip = $_SERVER['REMOTE_ADDR'];
		Log::entry(
			"attempted POSTing of SPAM to {$_SERVER['SERVER_NAME']} from {$ip}",
			"added {$unit} offender {$ip} to firewall"
			);
		exec("/sbin/iptables -A xanadu -s {$ip} -j DROP");
		exit;
		}
	return false;
	}

/**
 * ::send an email to a recipient
 * 
 * @param string $subject		: the subject line
 * @param string $message		: the message (plain text or html)
 * @param string $toEmail		: recipient email (someone@somewhere.com)
 * @param string $toName		: (optional) recipient name
 * @param sting $fromEmail		: (optional) originating email address
 * @param string $fromName		: (optional) originator
 * @param mixed $cc				: (optional) string or array w/cc recipient(s)
 * @param mixed $bcc			: (optional) string or array w/bcc recipient(s)
 * @return boolean				: true (accepted) false (failed)
 */
public static function send(
		$subject,$message,$toEmail,$toName=null,$fromEmail=null,$fromName=null,$cc=null,$bcc=null
		) {
	$to = $toEmail;
	if ($toName) $to = "\"{$toName}\" <{$to}>";
	$domain = new Url; $domain = $domain->domain;
	$from = $fromEmail ? $fromEmail : "webmaster@{$domain}";
	if ($fromName) $from = "\"{$fromName}\" <{$from}>";
	$ccHeader = is_array($cc) ? join(",",$cc) : $cc;
	$bccHeader = is_array($bcc) ? join(",",$bcc) : $bcc;
	$headers =
        "MIME-Version: 1.0\r\n" .
        "Content-type: text/plain; charset=iso-8859-1\r\n" .
        "X-Priority: 3\r\n" .
        "X-MSMail-Priority: Normal\r\n" .
        "X-Mailer: {$domain} website\r\n" .
		"From: {$from}\r\n";
	if ($ccHeader) $headers .= ("CC: ".$ccHeader."\r\n");
	if ($bccHeader) $headers .= ("BCC: ".$bccHeader."\r\n");
	return mail($to,$subject,$message,$headers);
	}

}

?>