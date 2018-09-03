##
#  __  __          _                 
# |  \/  | ___  __| |_   _ ___  __ _ 
# | |\/| |/ _ \/ _` | | | / __|/ _` |
# | |  | |  __/ (_| | |_| \__ \ (_| |
# |_|  |_|\___|\__,_|\__,_|___/\__,_|
#
# Medusa.pm - Medusa Class Library for Perl
# August 2018 by Harley H. Puthuff
# Copyright 2015-18, Your Showcase on the Internet
#

use feature "switch";
no if $] >= 5.017011, warnings => 'experimental::smartmatch';

##
# Medusa class - base class for the Medusa library
#--------------------------------------------------
#
package Medusa;
use XML::Simple;

# constants, default values:

use constant MEDUSA_PACKAGE  => "Medusa.pm";
use constant MEDUSA_XML_FILE => "Medusa.xml";

# class (& public) properties

our $packageName;
our $libPath;
our $xmlFile;
our $xml;
our $medusa;

# initialize class properties:

BEGIN {
my $default = <<'XML';
<?xml version="1.0" encoding="UTF-8"?>
<medusa>
 <nomenclature>
	<name>Medusa</name>
	<version>v8.1</version>
	<author>Harley H. Puthuff</author>
	<copyright>Copyright 2008-2018, Your Showcase</copyright>
	<description>A low-level interface class library</description>
 </nomenclature>
 <databoss>
  <connections>
	<connection	name="localhost" host="localhost" user="{username}"	pass="{password}"/>
  </connections>
  <defaultConnection>localhost</defaultConnection>
 </databoss>
</medusa>
XML
	$packageName = MEDUSA_PACKAGE;
	$libPath = $INC{$packageName};
	$xmlFile = MEDUSA_XML_FILE;
	$xml = new XML::Simple;
	$medusa = $xml->XMLin($default);			# default configuration
	if (-e $xmlFile) {
		$medusa = $xml->XMLin($xmlFile);		# local configuration
		}
	elsif ($libPath) {
		$libPath =~ /^(\S+\/)/; $libPath = $1 . MEDUSA_XML_FILE;
		if (-e $libPath) {
			$xmlFile = $libPath;
			$medusa = $xml->XMLin($xmlFile);	# library configuration
			}
		}
}

# retrieve the connector for a dbms connection
#
#	param:	 (optional) )name of the connector
#	returns:	 a ref. to the connector hash or null

sub getConnector {
	my ($class,$name) = @_;
	my $connector;
	$name ||= $medusa->{databoss}{defaultConnection};
	$connector = $medusa->{databoss}{connections}{connection}{$name};
	return undef unless $connector;
	$connector->{name} ||= $name;
	return $connector;
	}

##
# Databoss class - database manager
#-----------------------------------
#

package Databoss;
use DBI;

my	$connector;	# current connector

# constructor - construct new Databoss / connection object
#
#	param:	(optional) a connector nametag or db handle
#	param:	(optional) database name to start with
#	returns: a new,connected Databoss object

sub	new {
    my ($class,$param,$database) = @_;
	my ($this,$dsn,$results,$dbname);
    $this = {};
    bless $this,$class;
	if (! $param) {								# no connector passed
		if ($connector)							# use the last connector
			{$this->{connector} = $connector;}
		else									# or just use the default
			{$this->{connector} = Medusa->getConnector;}
		}
	elsif (ref $param) {						# passed a db handle (external)
		$this->{connector} = Medusa->getConnector("dbh");
		$this->{connector}{handle} = $param;
		}
	else {										# passed a connector name
		$this->{connector} = Medusa->getConnector(lc $param);
		}
	return $this unless $this->{connector};
	$this->{connector}{path} = $database;		# pre-select db
	$connector = $this->{connector};			# save current as last
	if (! $this->{connector}{handle}) {			# need to make connection
		$dsn = "DBI:mysql:$this->{connector}{path}:$this->{connector}{host}:3306";
		$this->{connector}{handle} =
			DBI->connect($dsn,$this->{connector}{user},$this->{connector}{pass},{mysql_auto_reconnect=>1})
			or die "! Cannot connect to DB $this->{connector}{name} server: $DBI::errstr\n";
		}
	$this->loadDatabases unless (defined $this->{connector}{databases});
	$this->selectDatabase($this->{connector}{path}) if ($this->{connector}{path});
	return $this;
	}

# escape a string prior to processing by DBI/mysql
#
#	param:	the string to be escaped
#	return: the escaped string

sub quote {
	my ($this,$string) = @_;
	return $this->{connector}{handle}->quote($string);
	}

# check the db connection & attempt to reconnect if down
# otherwise just die a horrible death now...

sub reconnect {
	my $this = shift;
	if (! $this->{connector}{handle}->ping) {
		$dsn = "DBI:mysql:$this->{connector}{path}:$this->{connector}{host}:3306";
		$this->{connector}{handle} =
			DBI->connect($dsn,$this->{connector}{user},$this->{connector}{pass},{mysql_auto_reconnect=>1})
			or die "! Cannot connect to DB $this->{connector}{name} server: $DBI::errstr\n";
		}
	return 1;
	}

# simple query processor for limited amounts of data
#
#	param:	SQL query string text
#	return: a ref. to an array of array refs. (results)

sub query {
	my ($this,$query) = @_;
	my ($cx,$sth,$results);
	$this->reconnect;
	$cx = $this->{connector};
	return $cx->{handle}->do($query) if ($query !~ /^\s*\b(describe|select|show)\b/i);
	if (!($sth = $cx->{handle}->prepare($query)) or !$sth->execute) {
		warn "Cannot run query ($query): $DBI::errstr\n";
		return [];
		}
	$results = $sth->fetchall_arrayref;
	$sth->finish();
	return $results;
	}

# load the list of databases into our connector

sub loadDatabases {
	my $this = shift;
	my ($cx,$results,$dbname);
	$cx = $this->{connector};		# ref. to our connector object
	$cx->{databases} = {};
	$results = $this->query("SHOW DATABASES");
	foreach (@{$results}) {
		$dbname = $_->[0];
		next if ($dbname =~ /^(information_schema|mysql)$/i);
		$cx->{databases}->{$dbname} = undef;
		}
	if ($cx->{path}) {
		$this->loadTables($cx->{path});
		}
	else {
		$cx->{database} = undef;
		}
	}

# select a specific database for subsequent operations
#
#	param: name of database to 'use'

sub selectDatabase {
	my ($this,$database) = @_;
	my $cx = $this->{connector};
	$this->loadDatabases unless (defined $cx->{databases});
	return unless (exists $cx->{databases}{$database});
	$cx->{database} = $database;
	$this->query("USE $database");
	}

# load the list of tables for a database
#
#	param: (optional) database name

sub loadTables {
	my ($this,$database) = @_;
	my ($cx,$db,$results);
	$cx = $this->{connector};		# ref to our connector object
	$database ||= $cx->{database};	# provided or use default db name
	$this->selectDatabase($database);	# focus this database
	$cx->{databases}{$database} = {};		# init the database tables list
	$db = $cx->{databases}{$database};	# ref to THE database
	$db->{name} = $database;		# save the name
	$results = $this->query("SHOW TABLES FROM $database");
	$db->{$_->[0]} = undef foreach (@{$results});
	}

# select a table from a database for subsequent use
#	note: if the structure is not present, create it
#
#	param:	table as xxxxx or xxxxx.yyyyy
#	returns: a ref. to the table hash

sub selectTable {
	my ($this,$table) = @_;
	my ($cx,$database,$db,$tab,$results,$column);
	$cx = $this->{connector};
	if ($table =~ /(\w+)\.(\w+)/) {
		$database = $1;
		$table = $2;
		}
	else {
		$database ||= $cx->{database};
		}
	$cx->{database} = $database;
	$this->loadTables($database) unless (defined $cx->{databases}{$database});
	$db = $cx->{databases}{$database};
	return undef unless (exists $db->{$table});
	return $db->{$table} if (defined $db->{$table});
	$db->{$table} = {};				# init the table hash
	$tab = $db->{$table};			# ref to the table hash
	$tab->{name} = $table;			# save the name
	$tab->{primarykey} = undef;
	$tab->{primarykeycolumn} = undef;
	$tab->{autoincrement} = undef;
	$tab->{properties} = [];
	$tab->{formats} = [];
	$tab->{defaults} = [];
	$results = $this->query("DESCRIBE $database.$table");
	for ($column=0; $column < scalar @{$results}; ++$column) {
		push @{$tab->{properties}},$results->[$column][0]; # column
		push @{$tab->{formats}},$results->[$column][1]; # format
		push @{$tab->{defaults}},$results->[$column][4]; # default
		if ($results->[$column][3] =~ /pri/i) {
			$tab->{primarykey} = $results->[$column][0];
			$tab->{primarykeycolumn} = $column + 1;
			}
		$tab->{autoincrement} = 1 if ($results->[$column][5] =~ /auto/i);
		}
	return $tab;
	}

# return a ref. to the properties list for a table
#
#	param:	name of the table as xxxxx or xxxx.yyyy
#	return:	ref. to the properties list array

sub properties {
	my ($this,$table,$database) = @_;
	my $tab = $this->selectTable($table,$database);
	return undef unless (defined $tab);
	return $tab->{properties};
	}

# fetch a single record from a table by primary key
#
#	param:	name of the table as xxxxx or xxxx.yyyy
#	param:	key value (int or string)
#	return:	record as object/hash or null

sub fetch {
	my ($this,$table,$key) = @_;
	my ($cx,$tab,$fx,@fields,$property,$format,$column,$query,$sth,$record);
	$this->reconnect;
	$cx = $this->{connector};
	$tab = ($this->selectTable($table) or return(undef));
	for (@fields=(),$fx=0; $fx<scalar @{$tab->{properties}}; ++$fx) {
		$property = $tab->{properties}->[$fx];
		$format = $tab->{formats}->[$fx];
		if ($format =~ /bit/i) {
			$column = "(0+`$property`) AS `$property`";
			}
		else {
			$column = "`$property`";
			}
		push @fields,$column;
		}
	$column = $cx->{handle}->quote($key);
	$query = "SELECT ".join(',',@fields)." FROM $table ".
			 "WHERE $tab->{primarykey}=$column LIMIT 1";
	unless (($sth = $cx->{handle}->prepare($query)) and $sth->execute) {
		return undef;
		}
	$record = $sth->fetchrow_hashref;
	$sth->finish();
	return $record;
	}

# pseudonym/alias for fetch:

sub fetchRecord {
	my ($this,$table,$key) = @_;
	return $this->fetch($table,$key);
	}

# fetch a single column from a single record
#
#	param:	query string to fetch data
#	return:	the single column result value

sub fetchValue {
	my ($this,$query) = @_;
	$query .= " LIMIT 1" unless ($query =~ /limit\s+\d+/i);
	my $result = $this->query($query);
	return $result->[0][0];	# return the single value
	}

# fetch a list of values from the database
#	param:	query string to produce a list
#	return:	returns a ref to an array of selected values

sub fetchValues {
	my ($this,$query) = @_;
	my ($result,$list);
	$result = $this->query($query);
	$list = [];
	push @{$list},$_->[0] foreach(@{$result});
	return $list;
	}

# fetch a hash of name=>value pairs from the database
#	param:	query string to produce a list of [name,value] arrays
#	return:	a ref to a hash of name=>value pairs

sub fetchChoices {
	my ($this,$query) = @_;
	my ($result,$hash);
	$result = $this->query($query);
	$hash = {};
	$hash->{$_->[0]} = $_->[1] foreach(@{$result});
	return $hash;
	}

# fetch a list of records using a query
#
#	param:	query string to fetch the data
#	return:	ref to array of arrays

sub fetchAllRecords {
	my ($this,$query) = @_;
	return $this->query($query);
	}

# fetch a single record as an object
# using a caller-provided query
#
#	param:	query string to fetch one record
#	param:	(optional) destination class for object
#	return:	ref to object/hash or undef

sub fetchObject {
	my ($this,$query,$class) = @_;
	my ($cx,$sth,$hash,$object);
	$this->reconnect;
	$cx = $this->{connector};
	return undef unless (($sth = $cx->{handle}->prepare($query)) and $sth->execute);
	$hash = $sth->fetchrow_hashref;
	$sth->finish();
	if ($class) {
		$object = $class->new;
		$object->merge($hash);
		return $object;
		}
	else {
		return $hash;
		}
	}

# fetch a list of record objects/hashes using a query
#
#	param:	query string to fetch the data
#	param:	(optional) destination class for the objects
#	return:	ref to a list of hash references

sub fetchAllRecordObjects {
	my ($this,$query,$class) = @_;
	my ($cx,$sth,$record,$object,$result);
	$this->reconnect;
	$cx = $this->{connector};
	$result = [];
	return $result unless (($sth = $cx->{handle}->prepare($query)) and $sth->execute);
	while ($record = $sth->fetchrow_hashref) {
		if ($class) {
			push @{$result},$class->new;
			$result->[-1]->merge($record);
			}
		else {
			push @{$result},$record;
			}
		}
	$sth->finish();
	return $result;
	}
sub fetchObjects {
	my ($this,$query,$class) = @_;
	return $this->fetchAllRecordObjects($query,$class);
	}

# store a record into a table & database
#
#	param:	name of the table as xxxx or xxxx.yyyy
#	param:	ref to hash with columns & data
#	return:	key/id of record stored or null

sub store {
	my ($this,$table,$record) = @_;
	my ($cx);
	my $tab = ($this->selectTable($table) or return(undef));
	if ($record->{"$tab->{primarykey}"} && $tab->{autoincrement})
		{return $this->updateRecord($table,$record)}
	else
		{return $this->writeRecord($table,$record)}
	}

# update an existing record on the database
#
#	param:	name of the table as xxxx or xxxx.yyyy
#	param:	ref to hash with columns & data
#	return:	key/id of record updated or null

sub updateRecord {
	my ($this,$table,$record) = @_;
	my ($cx,$key,$fx,@pairs,$property,$format,$value,$query);
	$this->reconnect;
	$cx = $this->{connector};
	my $tab = ($this->selectTable($table) or return(undef));
	$key = $tab->{primarykey};
	for (@pairs=(),$fx=0; $fx<scalar @{$tab->{properties}}; ++$fx) {
		$property = $tab->{properties}->[$fx];
		next if ($property eq $key);
		$format = $tab->{formats}->[$fx];
		$value = $record->{$property};
		if (! defined($value))		{$value = "NULL"}
		elsif ($format =~ /blob/i)	{$value = $cx->{handle}->quote($value)}
		elsif ($format =~ /bit/i)	{$value = $value ? "b'1'" : "b'0'"}
		else						{$value = $cx->{handle}->quote($value)}
		push @pairs,"`$property`=$value";
		}
	$query = "UPDATE $table SET ".join(',',@pairs).
			 " WHERE `$key`=".$cx->{handle}->quote($record->{$key});
	return $this->query($query);
	}

# write a record to the database
#
#	param:	name of the table as xxxx or xxxx.yyyy
#	param:	ref to hash with columns & data
#	return:	key/id of record updated or null

sub writeRecord {
	my ($this,$table,$record) = @_;
	my ($cx,$fx,@fields,$property,$format,$value,$query,$pkey,$key);
	my $tab = ($this->selectTable($table) or return(undef));
	$this->reconnect;
	$cx = $this->{connector};
	for (@fields=(),$fx=0; $fx<scalar @{$tab->{properties}}; ++$fx) {
		$property = $tab->{properties}->[$fx];
		$format = $tab->{formats}->[$fx];
		$value = $record->{$property};
		if (! defined($value))		{$value = "NULL"}
		elsif ($format =~ /blob/i)	{$value = $cx->{handle}->quote($value)}
		elsif ($format =~ /bit/i)	{$value = $value ? "b'1'" : "b'0'"}
		else						{$value = $cx->{handle}->quote($value)}
		push @fields,$value;
		}
	$query = "REPLACE INTO $table VALUES(".join(',',@fields).")";
	return undef unless $this->query($query);
	$pkey = $tab->{primarykey}; $key = $record->{$pkey};
	return $key if ($key and ($key !~ /^null$/i));
	return 1 unless $tab->{autoincrement};
	$key = $this->fetchValue("SELECT LAST_INSERT_ID() AS id");
	$record->{$pkey} = $key;
	return $key;
	}

# delete a record from the database
#
#	param:	name of the table as xxxx or xxxx.yyyy
#	param:	primary key of record to delete
#	return:	count of records deleted

sub deleteRecord {
	my ($this,$table,$key) = @_;
	my $tab = ($this->selectTable($table) or return(undef));
	$this->reconnect;
	return $this->query("DELETE FROM $table WHERE `$tab->{primarykey}`='$key'");
	}

# truncate a table (flush), remove all records & reset autoincrement
#
#	param:	name of the table as xxxx or xxxx.yyyy
#	return:	1=success, null=failure

sub flush {
	my ($this,$table) = @_;
	my $tab = ($this->selectTable($table) or return(undef));
	$this->reconnect;
	return $this->query("TRUNCATE TABLE $table");
	}

# return a count of records on file in a database table
#
#	param:	name of the table as xxxx or xxxx.yyyy
#	param:	(optional) where clause to filter count
#	return:	count of records in the table

sub records {
	my ($this,$table,$where) = @_;
	my $tab = ($this->selectTable($table) or return(undef));
	return $this->fetchValue("SELECT COUNT(*) AS `records` FROM $table $where");
	}

##
# Container class - database table-tied object container
#--------------------------------------------------------
#	base class to be extended by other objects
#

package Container;

# pseudo-constructor - DO NOT INVOKE DIRECTLY !
#	Note:	the first parameter may be either
#				- a Databoss object or
#				- the 1st or only column for a key

sub new {
	my ($this,@keys) = @_;
	if (ref $keys[0]) {
		$this->{db} = shift @keys;
		}
	else {
		$this->{db} = new Databoss;
		}
	$this->{structure} = $this->{db}->selectTable($this->{table});
	$this->{properties} = $this->{structure}{properties};
	scalar(@keys) ? $this->fetch(@keys) : $this->purge();
	}

# turn record caching on/off
#
#	param:	(optional) 'on','off', etc.
#	note:	default is to toggle state on/off

sub caching {
	my ($this,$state) = @_;
	if ($state=~/on|1|y/i) {
		$this->{cache} = {};
		}
	elsif ($state=~/off|0|n/i) {
		undef($this->{cache});
		}
	elsif (defined $this->{cache})
		{undef($this->{cache});}
	else
		{$this->{cache} = {};}
	}

# store this object's properties in the cache (if on)
#
#	return:	1=success, 0=failure

sub encache {
	my $this = shift;
	my ($key);
	return 0 unless (defined($this->{cache}) and $this->valid);
	$key = $this->{$this->{properties}->[0]};
	$this->{cache}->{$key} = {};
	$this->{cache}->{$key}->{$_} = $this->{$_} foreach (@{$this->{properties}});
	return 1;
	}

# retrieve this object's properties from the cache (if on)
#
#	param:	key of record in cache
#	return:	1=success, 0=failure

sub decache {
	my ($this,$key) = @_;
	return 0 unless (defined($this->{cache}) and defined($this->{cache}->{$key}));
	$this->merge($this->{cache}->{$key});
	return 1;
	}

# create a new object from parameters
#
#	param:	list of values for the properties
#	return:	the id/key of new record/object

sub create {
	my ($this,@values) = @_;
	my $lastProperty = scalar(@{$this->{properties}}) - 1;
	my @properties = @{$this->{properties}}[1..$lastProperty];
	my ($px);
	$this->purge;
	for ($px=0; $px<scalar @properties; ++$px) {
		$this->{$properties[$px]} = $values[$px];
		}
	return $this->store;
	}

# exhibit this object as a text breakout
#
#	return:	the text for the object

sub exhibit {
	my $this = shift;
	my $mask = "%-20s: %s\n";
	my $result = "$this->{table} object:\n";
	foreach (@{$this->{properties}}) {
		$result .= sprintf($mask,$_,$this->{$_});
		}
	$result .= "---\n";
	return $result;
	}

# is this object valid (i.e.-contains a record) ?
#
#	return:	1=valid, 0=virgin

sub valid {
	my $this = shift;
	return $this->{$this->{structure}->{primarykey}} ? 1 : 0;
	}

# purge the data properties for this object
#	note: where a default is defined on the database,
#		  that property will be set to the default.

sub purge {
	my $this = shift;
	my ($fx,$property,$format,$default);
	for ($fx=0; $fx<scalar(@{$this->{properties}}); ++$fx) {
		$property = $this->{structure}->{properties}->[$fx];
		$format   = $this->{structure}->{formats}->[$fx];
		$default  = $this->{structure}->{defaults}->[$fx];
		if (!$default or ($default=~/timestamp/i))
			{$this->{$property} = undef;}
		else
			{$this->{$property} = $default;}
		}
	}

# merge another hash/object's properties with this object
#
#	param:	ref. to another hash/object

sub merge {
	my ($this,$object) = @_;
	return unless defined($object);
	$this->{$_} = $object->{$_} foreach (@{$this->{properties}});
	}

# enforce value integrity for the properties in this object

sub checkValueIntegrity {
	my $this = shift;
	my ($fx,$property,$value,$format,$default);
	my $valid = $this->valid;
	for ($fx=0; $fx<scalar @{$this->{properties}}; ++$fx) {
		$property	= $this->{structure}->{properties}->[$fx];
		$format		= $this->{structure}->{formats}->[$fx];
		$default	= $this->{structure}->{defaults}->[$fx];
		$value		= $this->{$property};
		if (! defined($value)) {
			next if (! defined($default));	# no default value
			if ($default =~ /timestamp/i) {	# special for timestamp
				$this->{$property} = Date->toInternal;
				next;
				}
			$this->{$property} = $default;	# else default value
			next;
			}
		elsif ($format =~ /(int|year)/i) {
			$this->{$property} =~ tr/0-9-//cd;
			next;
			}
		elsif ($format =~ /(float|double)/i) {
			$this->{$property} =~ tr/0-9Ee.-//cd;
			next;
			}
		elsif ($format =~ /(decimal|numeric)/i) {
			$this->{$property} =~ tr/0-9.-//cd;
			next;
			}
		elsif ($format =~ /timestamp/i) {
			$this->{$property} = Date->toInternal;
			next;
			}
		elsif ($format =~ /(date|time)/i) {
			$this->{$property} =~ tr/0-9: -//cd;
			next;
			}
		}
	}

# fetch a database.table record into this object
#
#	param:	key(s) of object on database
#	return:	id of record or undef

sub fetch {
	my ($this,@keys) = @_;
	my (@fieldlist,$fx,$fields,$query,$result);
	$this->purge;
	return undef unless $keys[0];
	# check if caching, if so return record from cache (if present)
	return $this->{$this->{properties}->[0]}
		if ((scalar(@keys)==1) and $this->decache($keys[0]));
	# build a field/column list for selection
	for (@fieldlist=(),$fx=0; $fx<scalar @{$this->{properties}}; ++$fx) {
		push @fieldlist,($this->{structure}->{formats}->[$fx] =~ /bit/i ?
			"(0+`$this->{properties}->[$fx]`) AS `$this->{properties}->[$fx]`" :
			$this->{properties}->[$fx]);
		}
	$fields = join(',',@fieldlist);
	$query = "SELECT $fields FROM $this->{table} ";
	if (scalar @keys > 1) {			# multi-column key
		for ($fx=0; $fx<scalar @keys; ++$fx) {
			$query .= ($fx==0 ? "WHERE " : "AND ").
					  "`$this->{properties}->[$fx]`=".
					  $this->{db}->quote($keys[$fx]).
					  " ";
			}
		}
	elsif ($this->{properties}->[0] =~ /int/i and
		   $this->{properties}->[1] !~ /int/i and
		   $keys[0] =~ /[^0-9]/) {
		   $query .= "WHERE `$this->{properties}->[1]` LIKE '%$keys[1]%' ";
		}
	else {
		$query .= "WHERE `$this->{structure}->{primarykey}`='$keys[0]' ";
		}
	$query .= "LIMIT 1";
	$result = $this->{db}->fetchObject($query);
	if ($result) {
		$this->merge($result);
		$this->encache;
		return $this->{$this->{properties}->[0]};
		}
	else {
		return undef;
		}
	}

# store this object into the database.table
#
#	return:	key of object stored or undef

sub store {
	my $this = shift;
	my $result;
	$this->checkValueIntegrity;		# enforce value integrity
	$result = $this->{db}->store($this->{table},$this);
	$this->encache if ($result);
	return $result
	}

# update the record for this object
#
#	return:	key of record updated or null

sub updateRecord {
	my $this = shift;
	my $result = $this->{db}->updateRecord($this->{table},$this);
	$this->encache if ($result);
	return $result;
	}

# write/rewrite this object to database record
#
#	return:	key of record written or null

sub writeRecord {
	my $this = shift;
	my $result = $this->{db}->writeRecord($this->{table},$this);
	$this->encache if ($result);
	return $result;
	}

# delete a record from the table for this object
#
#	param:	(optional) key to delect else prikey
#	return:	count of records deleted or null

sub deleteRecord {
	my ($this,$key) = @_;
	$key = $this->{$this->{properties}->[0]} unless $key;
	my $result = $this->{db}->deleteRecord($this->{table},$key);
	undef($this->{cache}->{$key}) if (defined($this->{cache}) and $result);
	return $result
	}

# flush all records from table for this class

sub flush {
	my $this = shift;
	$this->{cache} = {} if (defined $this->{cache});
	return $this->{db}->flush($this->{table});
	}

##
# Date class - date & time objects
#----------------------------------
#

package Date;
use POSIX;
use Time::Local;

##
# Class constants & properties:
#
@daysInMonth = (31,28,31,30,31,30,31,31,30,31,30,31);	# days per cal. month
@monthNames = ('jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec');
$maximumDate = '2037-12-31 23:59:59';					# highest (unix) date

##
# Class method: toInternal - return an internal date from string
#	@param string $date		: (optional) date string/today/now
#	@return string			: mysql/internal format: yyyy-mm-dd hh:mm:ss
#
sub toInternal {
	my $class = shift;
	my $date = shift;
	$date = $class unless ($date or ($class eq 'Date'));
	my $that = Date->new($date);
	return $that->internal(long);
	}

##
# Class method: toDatestamp - return a datestamp from string
#	@param string $date		: (optional) date string/today/now
#	@return string			: a datestamp (yyyymmddhhmmss)
#
sub toDatestamp {
	my $class = shift;
	my $date = shift;
	$date = $class unless ($date or ($class eq 'Date'));
	my $that = Date->new($date);
	return $that->datestamp();
	}

##
# Class method: toExternal - return an external date from string
#	@param string $date		: (optional) date string/today/now
#	@return string			: external format: [m]m/[d]d/yyyy
#
sub toExternal {
	my $class = shift;
	my $date = shift;
	$date = $class unless ($date or ($class eq 'Date'));
	my $that = Date->new($date);
	return $that->external(short);
	}

##
# Class method: filedate - return the change date for a file
#	@param mixed $file		: a file handle or name
#	@return string			: date as yyyy-mm-dd hh:mm:ss
#
sub filedate {
	my $class = shift;
	my $file = shift;
	$file = $class unless ($file or ($class eq 'Date'));
	my $that = Date->new((stat $file)[9]);
	return $that->internal(long);
	}

##
# Constructor:
#   @param string $date     : (optional) string with date [& time]
#   returns object          : a new Medusa::Date object
#
sub new {
    my ($class,$date) = @_;
    my $this = {};
    bless $this,$class;
	@{$this->{properties}} = qw/epoch sec min hour mday mon year string/;
    $this->set($date);
    return $this;
    }

##
# Purge this object's properties & reset to now()
#
sub purge {
    my $this = shift;
    $this->{epoch} = time;
	(	$this->{sec},
		$this->{min},
		$this->{hour},
		$this->{mday},
		$this->{mon},
		$this->{year}
	) = localtime($this->{epoch});
	$this->{string} = $this->internal;
    }

##
# Clone this object
#	@return object			: a copy of this object
sub clone {
	my $this = shift;
	my $that = new Date;
	$that->{$_} = $this->{$_} foreach (@{$this->{properties}});
	return $that;
	}

##
# Set this object's date from a datestring
#   @param string $string		: a date expressed as ...
#		= nothing (use current date & time)
#		= nn (day of current month)
#		= mm/dd (month and day)
#		= mm/dd/yy[yy] (month, day & year)
#		= yyyy-mm-dd (mysql year,month,day)
#		= hh:mm [am|pm] (hour, minute [meridian]
#		= hh:mm:ss [am|pm] (hour,minute,second [meridian]
#	@return string			: object date as yyyy-mm-dd hh:mm:ss
#
sub set {
	my ($this,$string) = @_;
	my ($ix,$month);
	$this->purge;
	# no date or word now
	return $this->{string} unless ($string && $string!~/now/i);
	# textual date: mmmmmm dd, yyyy
	if ($string =~ /([A-Za-z]+)\s+(\d{1,2})\D+(\d{4})/) {
		my $nMonths = scalar @monthNames;
		$this->{sec} = $this->{min} = $this->{hour} = 0;
		$this->{year} = ($3 - 1900);
		$this->{mday} = $2;
		$month = $1;
		for ($ix=0; $ix<$nMonths; ++$ix) {
			if ($month =~ /$monthNames[$ix]/i) {
				$this->{mon} = $ix;
				last;
				}
			}
		$this->{mon} = ($nMonths-1) if ($ix >= $nMonths);
		}
	# simple timestamp: yyyymmddhhmmss
	elsif ($string =~ /^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/) {
		$this->{year} = ($1 - 1900);
		$this->{mon} = ($2 -1 );
		$this->{mday} = $3;
		$this->{hour} = $4;
		$this->{min} = $5;
		$this->{sec} = $6;
		}
	# unix epoch (seconds since 1/1/1970 GMT);
	elsif ($string =~ /^(\d{3,})$/) {
		(	$this->{sec},
			$this->{min},
			$this->{hour},
			$this->{mday},
			$this->{mon},
			$this->{year}
		) = localtime($string);
		}
	else {
		$this->{sec} = $this->{min} = $this->{hour} = 0;
		# the word today
		if ($string =~ /today/i) {}
		# month & day
		elsif ($string =~ m'^\s*(\d{1,2})[/-](\d{1,2})\b') {
			$this->{mon} = ($1 - 1);
			$this->{mday} = $2;
			}
		# SQL date: yyyy-mm-dd
		elsif ($string =~ /^\s*(\d{4})\-(\d{2})\-(\d{2})\b/) {
			$this->{year} = ($1 - 1900);
			$this->{mon} = ($2 - 1);
			$this->{mday} = $3;
			}
		# external date: [m]m/[d]d/y[yyy]
		elsif ($string =~ m'^\s*(\d{1,2})[/-](\d{1,2})[/-](\d{1,4})\b') {
			$this->{mon} = ($1 - 1);
			$this->{mday} = $2;
			$this->{year} = ($3>1900) ? ($3 - 1900) : ($3 + 100);
			}
		# SQL time: hh:mm:ss
		if ($string =~ /\b(\d+):(\d+):(\d+)/) {
			$this->{hour} = $1;
			$this->{min} = $2;
			$this->{sec} = $3;
			}
		# SQL time: hh:mm
		elsif ($string =~ /\b(\d+):(\d+)/) {
			$this->{hour} = $1;
			$this->{min} = $2;
			}
		# SQL time: hh
		elsif ($string =~ /\s+(\d+)/) {
			$this->{hour} = $1;
			}
		# meridian: am
		if ($string =~ /\d\s*(a|A)/) {
			$this->{hour} = 0 if ($this->{hour} == 12);
			}
		# meridian: pm
		if ($string =~ /\d\s*(p|P)/) {
			$this->{hour} += 12 unless ($this->{hour} == 12);
			}
		}
	$this->{epoch} = timelocal($this->{sec},$this->{min},$this->{hour},
							   $this->{mday},$this->{mon},$this->{year});
	return ($this->{string} = $this->internal);
    }

##
# move the date pointer for this object forward/backward
#	@param string $format		: a vector like this:
#		[-]nnn [minute(s)|hour(s)|DAY(s)|week(s)|month(s)|year(s)]
#	#return string				: new datestring for position
#
sub move {
	my ($this,$vector) = @_;
	my ($quantity,$unit,$months,$years,$days);
	return $this->{string} unless ($vector);
	if ($vector =~ /^\s*([0-9-]+)\s*$/) {					# simple number nnn
		$quantity = $1;
		$unit = "days";
		}
	elsif ($vector =~ /^\s*([0-9-]+)\s*([A-Za-z]+)\s*$/) {	# nnn minutes|hours|etc.
		$quantity = $1;
		$unit = lc $2;
		$unit = "days" unless ($unit =~ /(min|hour|day|week|mon|year)/i);
		}
	$this->{epoch} += ($quantity * 60) if ($unit=~/min/i);
	$this->{epoch} += ($quantity * 3600) if ($unit=~/hour/i);
	$this->{epoch} += ($quantity * 86400) if ($unit=~/day/i);
	$this->{epoch} += ($quantity * 604800) if ($unit=~/week/i);
	if ($unit=~/mon/i) {
		$months = abs $quantity;
		$years = int($months/12);
		$months = $months-($years*12);
		if ($quantity < 0) {$months*=-1; $years*=-1;}
		$this->{year} += $years;
		$this->{mon} += $months;
		if ($this->{mon} < 0) {--$this->{year}; $this->{mon} += 12;}
		elsif ($this->{mon} > 11) {++$this->{year};	$this->{mon} -= 12;}
		$days = $daysInMonth[$this->{mon}];
		++$days if (($this->{mon}==1) && (!($this->{year}%4) || !($this->{year}%400)));
		$this->{mday} = $days if ($this->{mday} > $days);
		$this->{epoch} = timelocal($this->{sec},$this->{min},$this->{hour},
								   $this->{mday},$this->{mon},$this->{year});
		}
	if ($unit=~/year/i) {
		$this->{year} += $quantity;
		$this->{epoch} = timelocal($this->{sec},$this->{min},$this->{hour},
								   $this->{mday},$this->{mon},$this->{year});
		}
	(	$this->{sec},
		$this->{min},
		$this->{hour},
		$this->{mday},
		$this->{mon},
		$this->{year}
	) = localtime($this->{epoch});
	return ($this->{string} = $this->internal);
	}


##
# internal (SQL) datestring from this object
#   @param string $format		: (optional) short,LONG, etc.
#	@return string				: date as yyyy-mm-dd[ hh:mm:ss]
#
sub internal {
	my ($this,$format) = @_;
	my ($result);
	$result = sprintf('%04d-%02d-%02d',($this->{year}+1900),($this->{mon}+1),$this->{mday});
	return $result if ($format =~ /short/i);
	$result .= sprintf(' %02d:%02d:%02d',$this->{hour},$this->{min},$this->{sec});
	return $result;
	}

##
# datestamp string from this object
#	@return string				: datestampt as yyyymmddhhmmss
#
sub datestamp {
	my $this = shift;
	return sprintf(
		'%04d%02d%02d%02d%02d%02d',
		($this->{year}+1900),($this->{mon}+1),$this->{mday},
		$this->{hour},$this->{min},$this->{sec}
		);
	}

##
# return the month (1-12) for this Date object
# @param int					: (optional) offset in months to apply +/-
# @return int					: month, 1-12 or null
#
sub month {
	my ($this,$offset) = @_;
	return undef unless ($this->{year});	# no date within
	$mm = $this->{mon} + 1;					# actual month
	if ($offset) {
		$mm += $offset;
		$mm -= 12 while ($mm > 12);
		$mm += 12 while ($mm <= 0);
		}
	return $mm;
	}

##
# return the day of month (1-31) for this Date object
# @param int					: (optional) offset in days to apply +/-
# @return int					: day, 1-31 or null
#
sub day {
	my ($this,$offset) = @_;
	return undef unless ($this->{year});	# no date within
	if ($offset) {
		$that = Date->new($this->internal);
		$that->move("$offset days");
		return $that->{mday};
		}
	else {
		return $this->{mday};
		}
	}

##
# return the year (1900-2037) for this Date object
# @param int					: (optional) offset in years to apply +/-
# @return int					: year as yyyy or null
#
sub year {
	my ($this,$offset) = @_;
	return undef unless ($this->{year});	# no date within
	return $this->{year} + 1900 + $offset
	}

##
# external datestring from this object
#   @param string $format		: (optional) short,long, etc.
#	@return string				: date as mm/dd/yy[yy][ hh:mm(a|p)m]
#
sub external {
	my ($this,$format) = @_;
	my ($result,$hour,$minute,$meridian);
	$result = sprintf('%d/%d/%d',($this->{mon}+1),$this->{mday},($this->{year}+1900));
	return $result unless ($format =~ /long/i);
	$meridian = "am";
	$hour = $this->{hour};
	$minute = $this->{min};
	if		($hour == 0)	{$hour = 12;}
	 elsif	($hour >= 12)	{$meridian = "pm"; $hour -= 12 unless ($hour == 12);}
	$result .= sprintf(' %d:%02d%s',$hour,$minute,$meridian);
	}

##
# adjust this date/time to reflect GMT/UTC
#	@param string $offset		: offset from GMT to local time (i.e. -800 or 0800,etc.)
#	@return string				: new internal date as yyyy-mm-dd hh:mm:ss
#
sub utc {
	my ($this,$offset) = @_;
	my ($sign,$hours,$mins);
	return $this->{string} unless ($offset =~ /^\s*([+-]*)\s*(\d{1,2}):*(\d{2})$/);
	$sign = $1; $hours = $2; $mins = $3;
	$sign = ($sign=~/-/) ? 1 : -1;
	$hours *= $sign;
	$mins *= $sign;
	$this->{epoch} += (($hours*3600) + ($mins*60));
	(	$this->{sec},
		$this->{min},
		$this->{hour},
		$this->{mday},
		$this->{mon},
		$this->{year}
	) = localtime($this->{epoch});
	return ($this->{string} = $this->internal);
	}

##
# adjust this date/time to reflect our local timezone
#	note: this assumes that the current object is in UTC
#	@return string				: new internal date as yyyy-mm-dd hh:mm:ss
#
sub localTZ {
	my $this = shift;
	my $offset = strftime("%z",localtime());
	my ($sign,$hours,$mins);
	$offset =~ /^\s*([+-]*)\s*(\d{1,2}):*(\d{2})$/;
	$sign = $1; $hours = $2; $mins = $3;
	$sign = ($sign=~/-/) ? -1 : 1;
	$hours *= $sign;
	$mins *= $sign;
	$this->{epoch} += (($hours*3600) + ($mins*60));
	(	$this->{sec},
		$this->{min},
		$this->{hour},
		$this->{mday},
		$this->{mon},
		$this->{year}
	) = localtime($this->{epoch});
	return ($this->{string} = $this->internal);
	}

##
# elapsed - Calculate a meaningful elapsed time value
#	@param int				: elapsed seconds
#	@return string			: result, i.e. "nn hours, nn minutes, ss seconds"
#
sub elapsed ($) {
	my ($class,$seconds) = @_;
	my ($result,$hours,$minutes);
	if ($seconds > 3600) {
		$hours = int($seconds / 3600);
		$seconds -= ($hours * 3600);
		$result .= "$hours hour".($hours==1 ? '' : 's').", ";
		}
	if ($seconds > 60) {
		$minutes = int($seconds / 60);
		$seconds -= ($minutes * 60);
		$result .= "$minutes minute".($minutes==1 ? '' : 's').", ";
		}
	$result .= "$seconds second".($seconds==1 ? '' : 's');
	return $result;
	}

##
# validate an internal long date as yyyy-mm-dd hh:mm:ss
#	@param string			: the date string to validate
#	@return boolean			: 1=valid,undef/0=not valid
#
sub validate ($) {
	my ($class,$datestring) = @_;
	$datestring = $maximumDate if ($datestring =~ /forever/i);
	if ($datestring =~ /(\d{4})\D(\d{1,2})\D(\d{1,2})\s+(\d{1,2})\D(\d{1,2})\D(\d{1,2})/) {
		return undef unless (($1>=1970) && ($1<=2037));	# year
		return undef unless (($2>=1) && ($2<=12));		# month
		return undef unless (($3>=1) && ($3<=31));		# day
		return undef unless (($4>=0) && ($4<=23));		# hour
		return undef unless (($5>=0) && ($5<=59));		# minute
		return undef unless (($6>=0) && ($6<=59));		# second
		return 1;
		}
	else {
		return undef; # invalid date
		}
	}

#
##
###	Data class - utility methods for data
##
#

package Data;

##
# Class method: statistics	- create display statistics string
#	@param hash $stats		: statistics as name=>value pairs
#							: note: names like "StatisticsValue"
#							:		will be shown as "Statistics Value:"
#	@param string $title	: (optional) title for display
#	@return string			: the statistics formatted for display
#
sub statistics {
	my ($class,$stats,$title) = @_;
	my ($width,$size,$buffer,$key,$val);
	foreach (keys %{$stats}) {
		$key = $_;
		$key = join ' ',/([A-Z][a-z0-9-]*)/g if (/[A-Z][a-z0-9-]/);
		$size = length($key)+1;
		$width = $size if ($size>$width);
		}
	$title ||= "Statistics";
	$buffer = "$title:\n\n";
	foreach (sort keys %{$stats}) {
		$key = $_;
		$val = $stats->{$_};
		$key = join ' ',/([A-Z][a-z0-9-]*)/g if (/[A-Z][a-z0-9-]/);
		$size = length $key;
		$key .= ('.'x($width-$size)) if ($size<$width);
		$val = join(',',@{$val}) if (ref($val) eq 'ARRAY');
		$buffer .= " $key: $val\n";
		}
	return $buffer."\n";
	}

##
# Class method: read file contents into variable
#	@param string $filename	: full name of the file
#	@return string			: entire file contents in a string
#
sub readfile {
	my ($class,$filename) = @_;
	my ($buffer);
	open(INPUT,$filename) || return "";
	read(INPUT,$buffer,-s INPUT);
	close(INPUT);
	return $buffer;
	}

##
# class method: convert 'raw' MAC address to displayable string
#	@param string $mac		: mac address as a plain, hex string
#	@return string			: a MAC address as: XX:XX:XX:XX:XX:XX
#
sub displayMACaddress {
	my ($class,$mac) = @_;
	$mac =~ /^(.{2})(.{2})(.{2})(.{2})(.{2})(.{2})$/i;
	return uc($1).':'.uc($2).':'.uc($3).':'.uc($4).':'.uc($5).':'.uc($6);
	}

##
# class method: convert decimal integer to hex notation
#	@param int $decimal		: the decimal value to convert
#	@return string			: a hex string as 0xXXX..XX
#
sub decToHex {
	my ($class,$decimal) = @_;
	return sprintf("0x%X",$decimal);
	}

##
# class method: convert hex string to decimal integer
#	@param string $hex		: string in hex notation
#	@return int				: a decimal value;
#
sub hexToDec {
	my ($class,$hex) = @_;
	return 0+hex($hex);
	}

##
# class method: convert ascii string to hex notation
#	@param string $ascii	: a string in ordinary ASCII
#	@return string			: string expressed in hex notation
#
sub asciiToHex {
	my ($class,$ascii) = @_;
	my $hex = $ascii;
	$hex =~ s/(.)/sprintf '%02X',ord $1/seg;
	return $hex;
	}

##
# class method: convert hex notation to ASCII string
#	@param string $hex		: string in hex notation
#	@return string			: plain ascii string
#
sub hexToAscii {
	my ($class,$hex) = @_;
	my $ascii = $hex;
	$ascii =~ s/([a-fA-F0-9][a-fA-F0-9])/chr(hex($1))/eg;
	return $ascii;
	}

##
# class method: return a mnemonic for an ethernet type
#	@param int $type		: type code for ETHERNET
#	@return string			: returns mnemonic & hex
#
sub ethernetType {
	my ($class,$type) = @_;
	my $hex = Data->decToHex($type);
	my $mnemonic;
	given ($type) {
		when (0x0800)		{ $mnemonic = 'IP' }
		when (0x0806)		{ $mnemonic = 'ARP' }
		when (0x809b)		{ $mnemonic = 'APPLETALK' }
		when (0x8035)		{ $mnemonic = 'RARP' }
		when (0x814c)		{ $mnemonic = 'SNMP' }
		when (0x86dd)		{ $mnemonic = 'IPv6' }
		when (0x880b)		{ $mnemonic = 'PPP' }
		when (0x8100)		{ $mnemonic = '802 1Q' }
		when (0x8137)		{ $mnemonic = 'IPX' }
		when (0x8863)		{ $mnemonic = 'PPPOED' }
		when (0x8864)		{ $mnemonic = 'PPPOES' }
		default				{ $menonic = 'unknown' }
		}
	return "$mnemonic ($hex)";
	}

##
# class method: return a mnemonic for an Internet protocol
#	@param int $proto		: protocol for IP
#	@return string			: returns mnemonic & value
#
sub ipProtocol {
	my ($class,$proto) = @_;
	my $hex = Data->decToHex($proto);
	my $mnemonic;
	given ($proto) {
		when (0)			{ $mnemonic = 'TCP' }
		when (1)			{ $mnemonic = 'ICMP' }
		when (2)			{ $mnemonic = 'IGMP' }
		when (4)			{ $mnemonic = 'IPIP' }
		when (6)			{ $mnemonic = 'TCP' }
		when (17)			{ $mnemonic = 'UDP' }
		when (121)			{ $mnemonic = 'SMP' }
		when (132)			{ $mnemonic = 'SCTP' }
		default				{ $mnemonic = 'unknown' }
		}
	return "$mnemonic ($proto)";
	}

##
# class method: round a number to at least two decimal places
# with a maximum of six places of precision
#	@param mixed $value		: a number/string/whatever to round to .nn
#	@return string			: the number expressed as nn.nn[nn..n]
#
sub roundDecimal ($) {
	my ($class,$value) = @_;
	if ($value =~ /^[0-9.-]+e[0-9.-]+$/) {		# scientific notation
		$value = sprintf("%.10f",$value);
		$value =~ s/0+$//;
		return $value;
		}
	else {										# simple float/double
		$value = 0.0 + sprintf("%.6f",$value);
		$value .= "0" if ($value =~ /\.\d?$/);
		return $value;
		}
	}

##
# trim function to remove white space from the start and end of the string
#	@param string $string	: a character string
#	@param char $filler		: (optional) character to remove (default=whitespace)
#	@return string			: the string with leading/trailing white space removed
#
sub trim ($;$) {
    my ($class,$string,$filler) = @_;
    return $class->rtrim($class->ltrim($string,$filler),$filler);
	}

##
# left trim function to remove white space from the start of the string
#	@param string $string	: a character string
#	@param char $filler		: (optional) character to remove (default=whitespace)
#	@return string			: the string with leading white space removed
#
sub ltrim ($;$) {
    my ($class,$string,$filler) = @_;
	$filler = '\s' unless length $filler;
    $string =~ s/^$filler+//;
    return $string;
	}

##
# right trim function to remove white space from the end of the string
#	@param string $string	: a character string
#	@param char $filler		: (optional) character to remove (default=whitespace)
#	@return string			: the string with trailing white space removed
#
sub rtrim ($;$) {
    my ($class,$string,$filler) = @_;
	$filler = '\s' unless length $filler;
    $string =~ s/$filler+$//;
    return $string;
	}

1;
