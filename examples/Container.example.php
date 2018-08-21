<?php
/**
 *  ____      _        _ _  ____          _                            
 * |  _ \ ___| |_ __ _(_) |/ ___|   _ ___| |_ ___  _ __ ___   ___ _ __ 
 * | |_) / _ \ __/ _` | | | |  | | | / __| __/ _ \| '_ ` _ \ / _ \ '__|
 * |  _ <  __/ || (_| | | | |__| |_| \__ \ || (_) | | | | | |  __/ |   
 * |_| \_\___|\__\__,_|_|_|\____\__,_|___/\__\___/|_| |_| |_|\___|_|   
 *	RetailCustomer class - a retail customer
 *	Jan-2015 by Harley Puthuff
 *	Copyright 2010-15, Telecom North America (telna)
 */
class RetailCustomer extends Container {

	// class properties

	public static $annualFee			= 19.00;
	public static $rushShippingFee		= 30.00;
	public static $replacementFee		= 10.00;
	
	// public properties
	
	public	$database= "3usite";
	public	$table	 = "customers";
	public	$connection = null;

	// private / working properties
	
	private $blocked = null;	// customer is blocked
	private $warned = null;		// customer has had warning
	private $modified = null;	// customer has changed

/**
 * flush any modified record data back to the db
 */
public function flush() {
	if ($this->valid() && $this->modified) $this->store();
	$this->modified = null;
	}

/**
 * [overloaded]
 * provide cleanup & orderly destruction of object
 */
public function __destruct() {
	$this->flush();
	}

/**
 * [overloaded]
 * fetch a customer (& telnaswitch account)
 * 
 * @param mixed $k1				: id or phone number
 * @param string $k2			: (optional) PIN code if k1=number
 * @return boolean				: true=valid, false=not found
 */
public function fetch($k1=null,$k2=null) {
	$this->flush();
	$this->purge();
	if (! $k1) return false;
	if ($k2) {
		$query = "
			SELECT c.*
			FROM 3usite.customers_numbers AS cn
			INNER JOIN 3usite.customers AS c ON c.id=cn.id
			WHERE cn.number='{$k1}' AND cn.main='yes' AND c.pin='{$k2}'
			LIMIT 1
			";
		$this->merge($this->db->fetchObject($query));
		}
	else
		parent::fetch($k1);
	if ($this->telnaswitchAccountID)
		$this->account = new Account($this->telnaswitchAccountID);
	return $this->valid();
	}

/**
 * [overloaded]
 * store the object as a record in the database
 * @return int					: key of record saved
 */
public function store() {
	$this->modified = null;
	parent::store();
	}

/**
 * Prepend a note to the note queue
 * 
 * @param string $what			: text of the message
 * @param string $who			: tag for submitter i.e 'watchcat'
 */
public function addNote($what,$who=null) {
    if (!$this->valid()) return;
    if (! $who) $who = 
		$_SERVER['REMOTE_USER'] ? $_SERVER['REMOTE_USER'] : $_SERVER['SCRIPT_NAME'];
	$datestamp = Date::toExternal();
	preg_match('/(\d+)\D+(\d+)\D+(\d+)/',$datestamp,$parts);
	$datestamp = sprintf("%02d-%02d-%04d",$parts[1],$parts[2],$parts[3]);
    $this->note =
        "----------------------------------------\n".
        $datestamp." - {$who}\n".
        "{$what}\n".
        $this->note;
	$this->modified = true;
    }

/**
 * record a charge transaction in the customer payments table
 * 
 * @param double $amount			: actual amount
 * @return int						: records actually written
 */
public function recordCharge($amount) {
	return Payments::charge($this->id,$amount);
	}

/**
 * record a credit (chargeback) in the customer payments table
 * 
 * @param double $amount			: amount to credit
 * @return int						: records actually written
 */
public function recordCredit($amount) {
	return Payments::credit($this->id,$amount);
	}

/**
 * record a payment in the customer payments table
 * 
 * @param double $amount			: amount to credit
 * @return int						: records actually written
 */
public function recordPayment($amount) {
	return Payments::payment($this->id,$amount);
	}

/**
 * delete the latest payment record for the specified amount
 * 
 * @param double $amount			: amount of payment
 * @return int						: records actually deleted
 */
public function deletePayment($amount) {
	return Payments::erase($this->id,$amount);
	}

/**
 * check & preserve flag for customer is blocked
 * 
 * @return boolean					: true=blocked, false=not
 */
public function isBlocked() {
	if ($this->blocked===null) $this->blocked = $this->db->fetchValue("
		select count(id) from 3usite.customers_numbers
		where id='{$this->id}' and cc_transfer='yes'
		") ? true : false;
	return $this->blocked;
	}

/**
 * check & preserve flag for customer has been warned
 * 
 * @return boolean					: true=warned, false=not
 */
public function isWarned() {
	if ($this->warned===null) $this->warned = $this->db->fetchValue("
		select count(id) from 3usite.pastdue_warnings
		where id='{$this->id}'
		") ? true : false;
	return $this->warned;
	}

/**
 * return a full name for the customer
 * 
 * @return string					: the full name (first mi lastname)
 */
public function fullname() {
	return Data::combine($this->firstname,$this->mi,$this->lastname);
	}

/**
 * return an array with the mailing address for the customer
 * 
 * @return array					: the mailing address with these elements:
 *									:	address1	-line one of the address
 *									:	address2	-line two of the address
 *									:	city		-the city
 *									:	state		-the state
 *									:	zip			-zip/postal code
 */
public function mailingAddress() {
	$address = array();
	$address['address1'] = Data::sift($this->ccaddress1,$this->billaddress1,$this->address1);
	$address['address2'] = Data::sift($this->ccaddress2,$this->billaddress2,$this->address2);
	$address['city'] = Data::sift($this->cccity,$this->billcity,$this->city);
	$address['state'] = Data::sift($this->ccstate,$this->billstate,$this->state);
	$address['zip'] = Data::sift($this->cczip,$this->billzip,$this->zip);
	return $address;
	}

/**
 * return the current balance for this customer
 * 
 * @param string $cutoff			: cut-off date
 * @return string/double			: current balance
 */
public function balance($cutoff=null) {
	$cutoffDate = Date::toInternal($cutoff,SHORTDATE);
	$statements = 0 + $this->db->fetchValue("
		select sum(`amount`) from CDRS.statements
		where customerid={$this->id}
		and `statementdate`<='{$cutoffDate}'
		");
	$payments   = 0 + $this->db->fetchValue("
		select sum(`amount`) from CDRS.payments
		where customerid={$this->id}
		and `date`<='{$cutoffDate}'
		");
	return sprintf("%.2f",($statements-$payments));
	}

/**
 * change the status of this customer & save to db
 * 
 * @param string $newstatus			: new status value
 *									: one of:
 *									:	apppending
 *									:	apprejected
 *									:	actpending
 *									:	active
 *									:	suspended
 *									:	inactive
 *									:	collection
 *									:	collletter
 *									:	colllettersched
 * @return string					: prior record status
 */
public function changeStatus($newstatus=null) {
	$oldstatus = $this->status;
	if (! $newstatus) $newstatus = "active";
	$this->status = $newstatus;
	$this->store();
	return $oldstatus;
	}

/**
 * obtain the list of numbers used by this customer's sim cards
 * 
 * @return string					: list of numbers or null
 */
public function getMobileNumbers() {
	$numbers = $this->db->fetchValues("
		select s.outboundcli `number`
		from 3usite.customers c
		join telnamobile.simcards s on s.customerid=c.id
		where c.id={$this->id}
		union
		select ai.systemId `number`
		from 3usite.customers c
		join telnaswitch.account a on a.id=c.telnaswitchAccountId
		left join telnaswitch.account ax on ax.parent_id=a.id
		join telnaswitch.accountterminal at
		 on at.account_id=ax.id and at.terminalplugin='TELNAMOBILE'
		 and at.system_id regexp '^[0-9]{12,22}$'
		join telnaswitch.accountidentity ai
		 on ai.account_id=at.account_id and ai.identityplugin_id=1
		where c.id={$this->id}
		order by `number` asc
		");
	return sizeof($numbers) ? join(",",$numbers) : null;
	}

/**
 * bill the annual fee for a simcard
 * 
 * @param string $number			: the actual number on the card
 * @param double $fee				: [optional] fee amount (19.00)
 */
public function billAnnualFee($number,$fee=null) {
	if (!$fee) $fee = self::$annualFee;
	$this->addNote("Annual Fee charged: {$fee}\nfor: {$number}");
	OLDCDR::createAnnualFeeCharge($this->id,$fee,$number);
	}

/**
 * bill the rush shipping fee for a simcard
 * 
 * @param string $number			: the actual number for the card
 * @param double $fee				: [optional] fee amount (19.00)
 */
public function billRushFee($fee=null) {
	if (!$fee) $fee = self::$rushShippingFee;
	$this->addNote("Rush Shipping Fee charged: {$fee}");
	OLDCDR::createRushFeeCharge($this->id,$fee,$number);
	}

/**
 * bill the card replacement fee for a simcard
 * 
 * @param string $number			: the actual number for the card
 * @param double $fee				: [optional] fee amount (10.00)
 */
public function billReplacementFee($fee=null) {
	if (! $fee) $fee = self::$replacementFee;
	$this->addNote("SIM Replacement Fee charged: {$fee}");
	OLDCDR::createSimCardReplacementCharge($this->id,$fee,$number);
	}

/////////////////////////// CLASS methods ///////////////////////////
	
/**
 * get a count of the number of active retail customers
 * 
 * @return int						: total active retail customers
 */
public static function getTotalActiveCustomers() {
	return Databoss::db()->fetchValue("
		select count(id) from 3usite.customers
		where status in ('active','actpending')
		");
	}

/**
 * get a count of the total mobile retail customers in good standing
 * 
 * @return int						: number of mobile customers
 */
public static function getTotalMobileCustomers() {
	$db = new Databoss;
	$oldSimcards = $db->fetchValue("
		select count(id) from 3usite.customers c
		join telnamobile.simcards sc on sc.customerid=c.id
		where c.status in ('active','actpending')
		");
	$newSimcards = $db->fetchValue("
		select count(at.id) from 3usite.customers c
		join telnaswitch.account a on a.id=c.telnaswitchAccountID
		join telnaswitch.account ax on ax.parent_id=a.id
		join telnaswitch.accountterminal at
		 on at.account_id=ax.id and at.terminalplugin='TELNAMOBILE'
		where c.status in ('active','actpending')
		");
	return ($oldSimcards + $newSimcards);
	}

/**
 * return a list of RetailCustomer objects for those of a particular status
 * 
 * @param string $status			: looking for this status, default=colllettersched
 * @return array					: an array of RetailCustomer objects sorted by lastname
 */
public static function getCustomersByStatus($status="colllettersched") {
	return Databoss::db()->fetchObjects("
		select * from 3usite.customers
		where status like '{$status}'
		order by id asc
		","RetailCustomer");
	}

}
?>