##
#  ___ __  __ ___ ___
# |_ _|  \/  / __|_ _|
#  | || |\/| \__ \| |
# |___|_|  |_|___/___|
#	IMSI - data for an IMSI class
#
#	properties:
#		id			: (int) db record key
#		active		: (int) 1=yes,null=no
#		imsitelna	: (char(15)) internal imsi
#		imsireal	: (char(15)) external imsi
#		iccid		: (char(22)) simcard #
#		imsiowner_id: (int) id in imsiowner table
#		sk_imsi_id	: (int) ???
#		type		: (varchar(45)) ???
#
#	June, 2013 - by Harley H. Puthuff
#	Copyright 2013, Telecomm North America (telna)
#

use Medusa;

package IMSI; use parent qw/-norequire Container/;

##
# constructor - construct new object
#
#	@param int $id			: (optional) id of IMSI record
#
sub	new {
	my ($class,@keys) = @_;
	my $this = {};
	bless $this,$class;
	$this->{table} = "telnamobile.imsi";
	$this->SUPER::new(@keys);
	return $this;
	}

##
# fetch an IMSI by the IMSI#
#
#	@param string $imsi			: IMSI string
#	@param string $timestamp	: (optional) local timestamp (yyyy-mm-dd hh:mm:ss)
#	@param string $offset		: (optional UTC offset for local timestamp (+nnnn)
#	@return int					: id of record or null
#
sub getByIMSI {
	my ($this,$imsi,$timestamp,$offset) = @_;
	my ($when,$result);
	return undef unless ($imsi);
	# if caching, attempt to find in cache
	if (defined $this->{cache}) {
		foreach (keys %{$this->{cache}}) {
			next unless (
				($this->{cache}->{$_}->{imsireal} eq $imsi) or
				($this->{cache}->{$_}->{imsitelna} eq $imsi)
				);
			$this->decache($_);
			return $this->{id};
			}
		}
	$this->purge;
	$when = new Date;
	$when = Date->new($timestamp) if ($timestamp);
	if (defined $offset) {
		$when->utc($offset) if ($offset);	# first move to UTC TZ
		$when->localTZ();					# then move to our TZ
		}
	$when = $when->internal;
	$query = "
		SELECT * FROM $this->{table}
		WHERE (imsireal='$imsi' OR imsitelna='$imsi')
		AND (`begin` IS NULL OR '$when'>`begin`)
		AND (`end` IS NULL OR '$when'<`end`)
		LIMIT 1
		";
	if ($result = $this->{db}->fetchObject($query)) {
		$this->merge($result);
		$this->encache;
		return $this->{id};
		}
	return undef;
	}

-1;