##
#  ____         _                _               
# |  _ \  __ _ | |_  ___  _ __  | |  __ _  _ __  
# | |_) |/ _` || __|/ _ \| '_ \ | | / _` || '_ \ 
# |  _ <| (_| || |_|  __/| |_) || || (_| || | | |
# |_| \_\\__,_| \__|\___|| .__/ |_| \__,_||_| |_|
#                        |_|                     
#	Rateplan - data for a rateplan/billing profile
#
#	properties:
#		id			: (int) db record key
#		name		: (varchar(255)) text name
#		chargefullcallback	: (bit) flag for callbacks
#		maxrefund	: (double) refund amount
#		active		: (int) flag, 0/1
#		master_roamingprofile_id	: master profile
#	calculated properties:
#		attributes			: hash of property=>value pairs
#		originationColumns	: list of ratecodes for origination (mobile) table
#		terminationColumns	: list of ratecodes for termination table
#		costRateplan		: id of cost rateplan/billing profile
#
#	April 2014 - by Harley H. Puthuff
#	Copyright 2014, Telecomm North America (telna)
#

use v5.10;
use feature "switch";

use Medusa;
use Location;
use Rate;
use RateplanGroup;
use PrefixGroup;
use RoamingProfile;

package Rateplan; use parent qw/-norequire Container/;

# list of all rates [ratecode,type,name,columns,bgcolor]

my $ratesList = [
    # origination rates
	["PLMN",'O',"Inbound Calls",["Cost min","Price min","Markup","Incr"],"#ffe"],
	["PLMNONNET",'O',"On-net Inbound Calls",["Cost min","Price min","Markup","Incr"],"#fef"],
	["PLMNCB",'O',"Callback Calls",["Cost min","Price min","Markup","Incr"],"#eff"],
	["PLMNCBONNET",'O',"On-net Callback Calls",["Cost min","Price min","Markup","Incr"],"#eef"],
	["CAMELA",'O',"A-leg for Direct Dialing",["Cost min","Price min","Markup","Incr"],"#fee"],
	["MOSMS",'O',"Mobile Originated SMS",["Cost msg","Price msg","Markup"],"#eed"],
	["MOSMSONNET",'O',"Mobile Originated SMS On-net",["Cost msg","Price msg","Markup"],"#ede"],
	["CBSMS",'O',"SMS for triggering callback",["Cost min","Price msg","Markup"],"#dee"],
	["MTSMS",'O',"MT SMS",["Cost msg","Price msg","Markup"],"#dde"],
	["DATA",'O',"Data/Internet",["Cost KB","Price KB","Markup","Incr"],"#edd"],
	# termination rates
	["PSTN",'T',"Voice Termination",["Cost min","Price min","Markup","Incr"],"#ffe"],
	["PSTNONNET",'T',"On-net Voice Termination",["Cost min","Price min","Markup","Incr"],"#fef"],
	["CAMELB",'T',"Direct Dial Termination",["Cost min","Price min","Markup","Incr"],"#eff"],
	["CAMELBONNET",'T',"On-net Direct Dial Termination",["Cost min","Price min","Markup","Incr"],"#eef"],
	["CBOFFA",'T',"Callback offload Leg A",["Cost min","Price min","Markup","Incr"],"#fee"],
	["CBOFFAONNET",'T',"On-net Callback offload Leg A",["Cost min","Price min","Markup","Incr"],"#eff"],
	["CBOFFB",'T',"Callback offload Leg B",["Cost min","Price min","Markup","Incr"],"#ddd"],
	["CBOFFBONNET",'T',"On-net Callback offload Leg B",["Cost min","Price min","Markup","Incr"],"#efe"],
	["CFPSTN",'T',"Call Forward",["Cost min","Price min","Markup","Incr"],"#fee"],
	["CFPSTNONNET",'T',"On-net Call Forward",["Cost min","Price min","Markup","Incr"],"#fef"],
	["VOIPOUT",'T',"VOIP Termination",["Cost min","Price min","Markup","Incr"],"#eef"],
	["VOIPOUTONNET",'T',"On-net VOIP Termination",["Cost min","Price min","Markup","Incr"],"#eed"],
	["TERMSMS",'T',"SMS Termination",["Cost msg","Price msg","Markup"],"#eec"]
	];

##
# class: find a rate/ratecode entry in the above table by name
#
#	@param string $name		: name of the rate
#	@return ref				: ref. to array with the rate or undef
#
sub findRateByName ($) {
	my ($class,$name) = @_;
	$name =~ s/&amp;/&/ig;
	foreach (@{$ratesList}) {
		return $_ if ($_->[2] =~ /$name/i);
		return $_ if (($_->[0] eq "PSTN") && ($name =~ /Callback Termination/i));
		return $_ if (($_->[0] eq "PSTNONNET") && ($name =~ /On-net Callback Termination/i));
		return $_ if (($_->[0] eq "PLMN") && ($name =~ /Inbound & Callback leg-A/i));
		return $_ if (($_->[0] eq "PLMNONNET") && ($name =~ /Inbound & Callback leg-A On-net/i));
		}
	return undef;
	}

##
# constructor - construct new object
#
#	@param int $id			: (optional) id of realtimebilling.profile record
#
sub	new {
	my ($class,$key) = @_;
	my $this = {};
	bless $this,$class;
	$this->{table} = "realtimebilling.profile";
	$key = abs $key;
	$this->SUPER::new($key);
	$this->analyze if ($key); # fill in if specific rateplan
	return $this;
	}

##
# analyze this rateplan and determine attributes,
# appropriate columns for ratecodes, etc.
#
sub analyze {
	my $this = shift;
	my ($type,$rate);
	$this->{master_roamingprofile_id} = RoamingProfile->getMasterForRateplan($this->{id});
	$this->{attributes} = {};
	$this->{attributes}->{usingOnNet} = $this->usingOnNet;
	$this->{attributes}->{usingCallback} = $this->usingCallback;
	$this->{attributes}->{usingSmsCallback}	= $this->usingSmsCallback;
	$this->{attributes}->{usingCamelB} = $this->usingCamelB;
	$this->{attributes}->{usingData} = $this->usingData;
	$this->{attributes}->{usingCommonTerminationRates} = $this->usingCommonTerminationRates;
	$this->{attributes}->{usingCommonOnNetTerminationRates}
		= ($this->{attributes}->{usingOnNet} & $this->usingCommonTerminationRates("onnet"));
	# go through originations and terminations...
	foreach $type (('O','T')) {
		$this->{originationColumns} = [] if ($type eq 'O');
		$this->{terminationColumns} = [] if ($type eq 'T');
		# and set up visible columns
		foreach $rate (@{$ratesList}) {
			next unless ($type eq $rate->[1]);
			next if ($rate->[0]=~/onnet/i && !$this->{attributes}->{usingOnNet});
			next if ($rate->[0]=~/^PLMNCB/i && !$this->{attributes}->{usingCallback});
			next if ($rate->[0]=~/^CBSMS/i && !$this->{attributes}->{usingSmsCallback});
			next if ($rate->[0]=~/^CAMELB/i && !$this->{attributes}->{usingCamelB});
			next if ($rate->[0]=~/^DATA/i && !$this->{attributes}->{usingData});
			next if ($rate->[0]=~/^(CBOFFA|CBOFFB|CFPSTN|VOIPOUT)$/i &&
					 $this->{attributes}->{usingCommonTerminationRates});
			next if ($rate->[0]=~/^(CBOFFAONNET|CBOFFBONNET|CFPSTNONNET|VOIPOUTONNET)$/i &&
					 $this->{attributes}->{usingCommonOnNetTerminationRates});
			if ($type eq 'O') {
				push @{$this->{originationColumns}},$rate;
				}
			else {
				push @{$this->{terminationColumns}},$rate;
				}
			if ($this->{attributes}->{usingCallback}) {
				$rate->[2] = "Callback Termination" if ($rate->[0]=~/^PSTN/i);
				$rate->[2] = "On-net Callback Termination" if ($rate->[0]=~/^PSTNONNET/i);
				}
			else {
				$rate->[2] = "Inbound & Callback leg-A" if ($rate->[0]=~/^PLMN/i);
				$rate->[2] = "Inbound & Callback leg-A On-net" if ($rate->[0]=~/^PLMNONNET/i);
				}
			}
		}
	$this->getCostRateplan;
	}

##
# determine if this rateplan uses OnNet rates
#
#	@return boolean			: true=using On Net, false=not
#
sub usingOnNet {
	my $this = shift;
	my $result = $this->{db}->fetchObject("
		SELECT
			prc1.rateCode AS rc1,prc1.ratePlanGroup_id rpg1,
			prc2.rateCode AS rc2,prc2.ratePlanGroup_id rpg2
		FROM realtimebilling.profileratecode prc1
		LEFT JOIN realtimebilling.profileratecode prc2
		 ON (prc2.profile_id=prc1.profile_id AND prc2.rateCode='PSTNONNET')
		WHERE prc1.profile_id=$this->{id} AND prc1.rateCode='PSTN'
		LIMIT 1
		");
	if ($result->{rpg1} || $result->{rpg2}) {
		return ($result->{rpg1} == $result->{rpg2}) ? 0 : 1;
		}
    return 0;
    }

##
# determine if this rateplan uses Callback rates
#
#	@return boolean			: true=using callback, false=not
#
sub usingCallback {
	my $this = shift;
	my $result = $this->{db}->fetchObject("
		SELECT
		prc1.rateCode AS rc1,prc1.ratePlanGroup_id rpg1,
		prc2.rateCode AS rc2,prc2.ratePlanGroup_id rpg2
		FROM realtimebilling.profileratecode prc1
		LEFT JOIN realtimebilling.profileratecode prc2
		ON (prc2.profile_id=prc1.profile_id AND
		    prc2.rateCode=CONCAT('PLMNCB',RIGHT(prc1.rateCode,2)))
		WHERE prc1.profile_id=$this->{id} AND prc1.rateCode LIKE 'PLMN__'
		LIMIT 1
		");
	if ($result->{rpg1} || $result->{rpg2}) {
		return ($result->{rpg1} == $result->{rpg2}) ? 0 : 1;
		}
    return 0;
	}

##
# determine if this rateplan uses SMS callback rates
#
#	@return boolean			: true=using SMS callback rates, false=not
#
sub usingSmsCallback {
	my $this = shift;
	my $result = $this->{db}->fetchObject("
		SELECT
		prc1.rateCode AS rc1,prc1.ratePlanGroup_id rpg1,
		prc2.rateCode AS rc2,prc2.ratePlanGroup_id rpg2
		FROM realtimebilling.profileratecode prc1
		LEFT JOIN realtimebilling.profileratecode prc2
		ON (prc2.profile_id=prc1.profile_id AND
		    prc2.rateCode=CONCAT('CBSMS',RIGHT(prc1.rateCode,2)))
		WHERE prc1.profile_id=$this->{id} AND prc1.rateCode LIKE 'MOSMS__'
		LIMIT 1
		");
	if ($result->{rpg1} || $result->{rpg2}) {
		return ($result->{rpg1} == $result->{rpg2}) ? 0 : 1;
		}
    return 0;
	}

##
# determine if this rateplan uses CAMEL termination (B-leg) rates
#
#	@return boolean			: true=using CAMEL termination rates, false=not
#
sub usingCamelB {
	my $this = shift;
	my $result = $this->{db}->fetchObject("
		SELECT
			prc1.rateCode AS rc1,prc1.ratePlanGroup_id rpg1,
			prc2.rateCode AS rc2,prc2.ratePlanGroup_id rpg2
		FROM realtimebilling.profileratecode prc1
		LEFT JOIN realtimebilling.profileratecode prc2
		ON (prc2.profile_id=prc1.profile_id AND prc2.rateCode='CAMELB')
		WHERE prc1.profile_id=$this->{id} AND prc1.rateCode='PSTN'
		LIMIT 1
		");
	if ($result->{rpg1} || $result->{rpg2}) {
		return ($result->{rpg1} == $result->{rpg2}) ? 0 : 1;
		}
    return 0;
	}

##
# determine if this rateplan uses DATA (internet rates
#
#	@return boolean			: true=using data rates, false=not
#
sub usingData {
	my $this = shift;
	return $this->{db}->fetchValue("
		SELECT ratePlanGroup_id FROM realtimebilling.profileratecode
		WHERE profile_id=$this->{id} AND rateCode LIKE 'data%'
		") ? 1 : 0;
	}

##
# determine if this rateplan uses common termination rates
#	i.e., check to see if we have the same rates for:
#		  'PSTN','CBOFFA','CBOFFB','CFPSTN' AND 'VOIPOUT'
#
#	@param boolean			: (optional) flag for using onnet versions or not
#	@return boolean			: true=using common term rates, false=not
#
sub usingCommonTerminationRates {
	my ($this,$onnet) = @_;
    my $ratecodes = $onnet ?
        q/'PSTNONNET','CBOFFAONNET','CBOFFBONNET','CFPSTNONNET','VOIPOUTONNET'/ :
        q/'PSTN','CBOFFA','CBOFFB','CFPSTN','VOIPOUT'/;
	return $this->{db}->fetchValue("
		SELECT COUNT(*) records
		FROM realtimebilling.profileratecode
		WHERE profile_id=$this->{id}
		AND rateCode IN ($ratecodes)
		GROUP BY ratePlanGroup_id
		")==5 ? 1 : 0;
	}

##
# determine the cost rateplan for this rateplan
#
#	@return int				: cost rateplan id
#
sub getCostRateplan {
	my $this = shift;
	my ($costplans,$costplan);
	$costplans = Rateplan->getCostRateplans();
	return ($this->{costRateplan} = $this->{id}) if ($this->{id} ~~ @{$costplans});
	$costplan = $this->{db}->fetchValue("
		SELECT
		al2.profile_id costProfile
		FROM realtimebilling.accountlink al1
		LEFT JOIN telnaswitch.account a1 ON a1.accountlink_id=al1.id
		LEFT JOIN telnaswitch.account a2 ON a2.id=a1.parent_id
		LEFT JOIN realtimebilling.accountlink al2 ON al2.id=a2.accountlink_id
		WHERE al1.profile_id=$this->{id}
		LIMIT 1
		");
	return ($this->{costRateplan} = $costplan) if ($costplan);
	$costplan = $this->{db}->fetchValue("
		SELECT al.profile_id costplan
		FROM security.entity se
		JOIN telnaswitch.account a ON a.id=se.account_id
		JOIN realtimebilling.accountlink al ON al.id=a.accountlink_id
		WHERE se.type LIKE 'RatePlan' AND se.entity_id=$this->{id}
		LIMIT 1
		");
	$this->{costRateplan} = $costplan ? $costplan : $this->{id};
	return $this->{costRateplan};
	}

##
# check if this rateplan is a cost rateplan
#
#	@return int				: 1 = cost rateplan, 0 = not
#
sub isCostRateplan {
	my $this = shift;
	return ($this->{id}==$this->{costRateplan}) ? 1 : 0;
	}

##
# return a list of cost rateplans
#	note: the criteria for being a cost type of rateplan are:
#			1. that it be assigned to an account which DOES have children
#			2. that it be assigned to an account which DOES NOT have an ICCID/SIMcard
#			3. that it be assigned to an account which CANNOT edit it
#
#	@return array			: a list of cost rateplans
#
sub getCostRateplans {
	my $class = shift;
	# Note: this query is flawed and thus we are hard-coding the list of
	#		costplans because the flawed database design does not allow
	#		us to determine if a rateplan is a costplan!!
	return [1];
	my $db = new Databoss;
	return $db->fetchValues("
		SELECT DISTINCT al.profile_id
		FROM telnaswitch.account a
		JOIN realtimebilling.accountlink al ON al.id=a.accountlink_id
		JOIN realtimebilling.profile rp ON rp.id=al.profile_id
		JOIN telnaswitch.account ax ON ax.parent_id=a.id
		LEFT JOIN telnaswitch.accountterminal at ON at.account_id=a.id
		WHERE a.active=1 AND ax.id IS NOT NULL AND ax.active=1 AND at.id IS NULL
		ORDER BY al.profile_id ASC
		");
	}

##
# get a list of originations in a country for this plan
#
#	@param int				: location id for country
#	@return ref. to array	: a ref. to an array of objects, each:
#							:	PrefixGroup		-prefix group id
#							:	Country			-name of country
#							:	CountryId		-location id for country
#							:	Terminus		-name of network
#							:	Network			-network id
#							:	ValidThru		-upper datestamp of validity
#
sub getOriginationsForCountry ($) {
	my ($this,$country) = @_;
	return $this->{db}->fetchAllRecordObjects("
		SELECT DISTINCT
			pfx.prefixgroup_id `PrefixGroup`,
			lbl.label `Country`,
			$country `CountryId`,
			net.label `Terminus`,
			rnet.net_id `Network`,
			rate.validTo `ValidThru`
		FROM common.location loc
		JOIN common.labels lbl
			ON lbl.id=loc.label_id AND lbl.classtype='Country' AND language_code='DEF'
		LEFT JOIN common.prefix AS pfx
			ON pfx.location_id=loc.id AND pfx.prefix_type IN ('e212','e214') AND pfx.validTo>now()
		JOIN common.network_info AS net
			ON net.network_id=pfx.network_id AND net.info_type_id=2 AND net.validTo>now()
		JOIN telnamobile.roamingnetwork AS rnet
			ON rnet.net_id=pfx.network_id
			AND rnet.roamingprofile_id=$this->{master_roamingprofile_id} AND rnet.type<>'NO'
		LEFT JOIN realtimebilling.profileratecode AS prc
			ON prc.profile_id=$this->{id} AND RIGHT(prc.rateCode,2) NOT IN
				(SELECT code FROM common.imsiowner WHERE id<>rnet.imsiowner_id)
		JOIN realtimebilling.rate AS rate
			ON rate.rateplangroup_id=prc.ratePlanGroup_id
			AND rate.prefixgroup_id=pfx.prefixgroup_id AND rate.validTo>now()
		WHERE loc.id=$country
		ORDER BY Terminus,ValidThru
		");
	}

##
# get a list of origination rates for a prefixgroup
#
#	@param int $prefixGroup		: prefix group for location
#	@param string $validThru	: (optional) the validThru date for the rate
#	@return array				: an array of objects having,
#		0 - rateplan			: rateplan number
#		1 - prefixgroup			: prefixgroup id
#		2 - imsi				: imsi owner id
#		3 - ratecode			: ratecode for rate
#		4 - parent				: rpg parent_id
#		5 - fromdate			: low date range
#		6 - thrudate			: high date range
#		7 - rate				: actual rate
#		8 - ratetype			: 0 (real) or 1 (markup)
#		9 - firstincr			: first increment
#		10 - nextincr			: next increment
#		11 - rateid				: id of rate record
#		12 - costid				: id of cost rate record
#		13 - costtype			: 0 (real) or 1 (markup)
#		14 - rpg				: rateplan group id
#		15 - cost				: actual cost rate						
#
sub getOriginationRatesForPrefixGroup ($;$) {
	my ($this,$prefixGroup,$validThru) = @_;
	my ($query,$rates);
	$validThru = "2037-12-31 23:59:59" unless $validThru;
    while (1) {
        if ($this->isCostRateplan) {$query = "
			SELECT
				'$this->{id}' AS `rateplan`,
				'$prefixGroup' AS `prefixgroup`,
				(SELECT DISTINCT io.code
				 FROM common.prefix AS p
				 INNER JOIN telnamobile.roamingnetwork AS rn
				 ON rn.net_id=p.network_id AND rn.roamingprofile_id=$this->{master_roamingprofile_id}
				 INNER JOIN common.imsiowner AS io ON io.id=rn.imsiowner_id
				 WHERE p.prefixgroup_id=$prefixGroup AND p.prefix_type IN ('e212','e214')
				 AND p.validFrom<now() AND now()<p.validTo) AS `imsi`,
				LEFT(prc.rateCode,LENGTH(prc.rateCode)-2) AS `ratecode`,
				rpg.parent_id AS `parent`,
				pr.validFrom AS `fromdate`,
				pr.validTo AS `thrudate`,
				pr.rate AS `rate`,
				pr.type AS `ratetype`,
				IF(pr.firstincr IS NOT NULL,pr.firstincr,rpg.firstincr) AS `firstincr`,
				IF(pr.nextincr IS NOT NULL,pr.nextincr,rpg.nextincr) AS `nextincr`,
				pr.id AS `rateid`,
				pr.id AS `costid`,
				pr.type AS `costtype`,
				rpg.id AS `rpg`,
				pr.rate AS `cost`
			FROM realtimebilling.profileratecode AS prc
			LEFT JOIN realtimebilling.rateplangroup AS rpg ON rpg.id=prc.ratePlanGroup_id
			LEFT JOIN realtimebilling.rate AS pr
			 ON (pr.prefixgroup_id=$prefixGroup AND pr.rateplangroup_id=prc.ratePlanGroup_id)
			WHERE prc.profile_id=$this->{costRateplan}
			AND pr.rate IS NOT NULL
			AND pr.validTo='$validThru'
			GROUP BY prc.rateCode
			HAVING prc.rateCode LIKE CONCAT('%',imsi)
			ORDER BY `ratecode`
			";}
        else {$query = "
			SELECT
				'$this->{id}' AS `rateplan`,
				'$prefixGroup' AS `prefixgroup`,
				(SELECT DISTINCT io.code
				 FROM common.prefix AS p
				 INNER JOIN telnamobile.roamingnetwork AS rn
				  ON rn.net_id=p.network_id AND rn.roamingprofile_id=$this->{master_roamingprofile_id}
				 INNER JOIN common.imsiowner AS io ON io.id=rn.imsiowner_id
				 WHERE p.prefixgroup_id=$prefixGroup AND p.prefix_type IN ('e212','e214')
				 AND p.validFrom<now() AND now()<p.validTo) AS `imsi`,
				LEFT(prc.rateCode,LENGTH(prc.rateCode)-2) AS `ratecode`,
				rpg.parent_id AS `parent`,
				pr.validFrom AS `fromdate`,
				pr.validTo AS `thrudate`,
				pr.rate AS `rate`,
				pr.type AS `ratetype`,
				IF(pr.firstincr IS NOT NULL,pr.firstincr,rpg.firstincr) AS `firstincr`,
				IF(pr.nextincr IS NOT NULL,pr.nextincr,rpg.nextincr) AS `nextincr`,
				pr.id AS `rateid`,
				cr.id AS `costid`,
				cr.type AS `costtype`,
				prc2.ratePlanGroup_id AS `rpg`,
				cr.rate AS `cost`
			FROM realtimebilling.profileratecode AS prc
			LEFT JOIN realtimebilling.rate AS cr
			 ON (cr.rateplangroup_id=prc.ratePlanGroup_id
				 AND cr.prefixgroup_id=$prefixGroup
				 AND cr.validFrom<now() /* added 3/14,HHP */
				 AND cr.validTo>now())
			LEFT JOIN realtimebilling.profileratecode AS prc2
			 ON (prc2.profile_id=$this->{id} AND prc2.rateCode=prc.rateCode)
			LEFT JOIN realtimebilling.rateplangroup AS rpg
			 ON rpg.id=prc2.ratePlanGroup_id
			LEFT JOIN realtimebilling.rate AS pr
			 ON (pr.prefixgroup_id=$prefixGroup AND pr.rateplangroup_id=prc2.ratePlanGroup_id)
			WHERE prc.profile_id=$this->{costRateplan}
			AND cr.rate IS NOT NULL
			AND (pr.validTo IS NULL OR pr.validTo='$validThru')
			GROUP BY prc.rateCode
			HAVING prc.rateCode LIKE CONCAT('%',imsi)
			ORDER BY `ratecode`
			";}
        $rates = $this->{db}->fetchAllRecordObjects($query);
		last if ($this->parseRates($rates));
        }
    return $rates;
    }

##
# get a list of terminations in a country for this plan
#
#	@param int				: location id for country
#	@return ref. to array	: a ref. to an array of objects, each:
#							:	PrefixGroup		-prefix group id
#							:	Type			-prefix group type id
#							:	CountryId		-location id for country
#							:	Country			-name of country
#							:	Terminus		-name of network
#							:	ValidThru		-upper datestamp of validity
#
sub getTerminationsForCountry ($) {
	my ($this,$country) = @_;
	my ($rateRateplanGroups,$prefixGroups,$query);
	$prefixGroups = PrefixGroup->getIdListForRateplanInCountry($this->{costRateplan},$country);
	$rateplanGroups = RateplanGroup->getIdListForRateplan($this->{id});
	return undef unless (length $prefixGroups && length $rateplanGroups);
	$query = "
		SELECT DISTINCT
		    pgp.id PrefixGroup,
		    pgp.type_id Type,
		    IFNULL(loc2.id,loc.id) CountryId,
		    lbl.label Country,
		    lbpg.label Terminus,
		    r.validTo ValidThru
		FROM common.prefixgroup pgp
		LEFT JOIN common.prefix pfx ON pfx.prefixgroup_id=pgp.id
		LEFT JOIN common.location loc ON loc.id=pfx.location_id
		LEFT JOIN common.location loc2 ON loc2.id=loc.parent_id
		LEFT JOIN common.labels lbl
			ON lbl.id=IFNULL(loc2.label_id,loc.label_id)
			AND lbl.classtype='Country'
		    AND lbl.language_code='DEF'
		JOIN common.labels lbpg ON lbpg.id=pgp.name_id
		LEFT JOIN realtimebilling.rate r
			ON r.prefixgroup_id=pgp.id
		    AND r.validTo>now()
		    AND r.rateplangroup_id IN ($rateplanGroups)
		WHERE pgp.id IN ($prefixGroups)
		ORDER BY Type DESC,Terminus ASC,ValidThru ASC
		";
	return $this->{db}->fetchAllRecordObjects($query);
	}

##
# get a list of termination rates for a prefixgroup
#
#	@param int $prefixGroup		: prefix group for terminus
#	@param string $validThru	: (optional) the validThru date for the rates
#	@return array				: an array of objects having,
#		0 - rateplan			: rateplan number
#		1 - prefixgroup			: prefixgroup id
#		2 - imsi				: imsi owner id
#		3 - ratecode			: ratecode for rate
#		4 - parent				: rpg parent_id
#		5 - fromdate			: low date range
#		6 - thrudate			: high date range
#		7 - rate				: actual rate
#		8 - ratetype			: 0 (real) or 1 (markup)
#		9 - firstincr			: first increment
#		10 - nextincr			: next increment
#		11 - rateid				: id of rate record
#		12 - costid				: id of cost rate record
#		13 - costtype			: 0 (real) or 1 (markup)
#		14 - rpg				: rateplan group id
#		15 - cost				: actual cost rate						
#
sub getTerminationRatesForPrefixGroup ($;$) {
	my ($this,$prefixGroup,$validThru) = @_;
	my ($flag,$query,$rates);
	$flag = 1;
    while (1) {
		$validThru = $Date::maximumDate unless $flag;
        if ($this->isCostRateplan) {$query = "
            SELECT
            '$this->{id}' `rateplan`,
            '$prefixGroup' `prefixgroup`,
            '--' `imsi`,
            prc.rateCode `ratecode`,
            rpg.parent_id `parent`,
            pr.validFrom `fromdate`,
            pr.validTo `thrudate`,
            pr.rate `rate`,
            pr.type `ratetype`,
            IF(pr.firstincr IS NOT NULL,pr.firstincr,rpg.firstincr) `firstincr`,
            IF(pr.nextincr IS NOT NULL,pr.nextincr,rpg.nextincr) `nextincr`,
            pr.id `rateid`,
            pr.id `costid`,
            pr.type `costtype`,
            rpg.id `rpg`,
            pr.rate `cost`
            FROM realtimebilling.profileratecode prc
            LEFT JOIN realtimebilling.rateplangroup rpg ON rpg.id=prc.ratePlanGroup_id
            LEFT JOIN realtimebilling.rate pr
             ON (pr.prefixgroup_id=$prefixGroup AND
                 pr.rateplangroup_id=prc.ratePlanGroup_id AND
                 pr.validTo>now())
            WHERE prc.profile_id=$this->{costRateplan} AND pr.rate IS NOT NULL
            AND RIGHT(prc.rateCode,2) NOT IN (SELECT DISTINCT code FROM common.imsiowner)
            ORDER BY `ratecode`
			";}
        else {$query = "
            SELECT
            '$this->{id}' `profile`,
            '$prefixGroup' `prefixgroup`,
            '--' `imsi`,
            prc.rateCode `ratecode`,
            rpg.parent_id `parent`,
            pr.validFrom `fromdate`,
            pr.validTo `thrudate`,
            pr.rate `rate`,
            pr.type `ratetype`,
            IF(pr.firstincr IS NOT NULL,pr.firstincr,rpg.firstincr) `firstincr`,
            IF(pr.nextincr IS NOT NULL,pr.nextincr,rpg.nextincr) `nextincr`,
            pr.id `rateid`,
            cr.id `costid`,
            cr.type `costtype`,
            rpg.id `rpg`,
            cr.rate `cost`
            FROM realtimebilling.profileratecode prc
            LEFT JOIN realtimebilling.rate cr
             ON (cr.rateplangroup_id=prc.ratePlanGroup_id
                 AND cr.prefixgroup_id=$prefixGroup
				 AND cr.validFrom<now() /* added,3/14,HHP */
				 AND cr.validTo>now())
			LEFT JOIN realtimebilling.profileratecode prc2
             ON (prc2.profile_id=$this->{id} AND prc2.rateCode=prc.rateCode)
            LEFT JOIN realtimebilling.rateplangroup rpg ON rpg.id=prc2.ratePlanGroup_id
            LEFT JOIN realtimebilling.rate pr
             ON (pr.prefixgroup_id=$prefixGroup AND
                 pr.rateplangroup_id=prc2.ratePlanGroup_id AND
                 (pr.validTo='$validThru' OR pr.validTo IS NULL))
            WHERE prc.profile_id=$this->{costRateplan}
            AND cr.id IS NOT NULL AND cr.rate IS NOT NULL
            AND RIGHT(prc.rateCode,2) NOT IN (SELECT DISTINCT code FROM common.imsiowner)
            ORDER BY `ratecode`
			";}
        $rates = $this->{db}->fetchAllRecordObjects($query);
        $flag = $this->parseRates($rates);
        last if ($flag);
        }
    return $rates;
    }

##
# parse a list of rate specifications for a terminus/network and
# recalculate those with markups for rates/costs with type=1,
# apply markup and fetch parent rate. note: for missing rates,
# create new rate records & force re-fetch
#
#	@param arrayref $rates		: ref. to list of rates rate as:
#		0 - rateplan			: rateplan number
#		1 - prefixgroup			: prefixgroup id
#		2 - imsi				: imsi owner id
#		3 - ratecode			: ratecode for rate
#		4 - parent				: rpg parent_id
#		5 - fromdate			: low date range
#		6 - thrudate			: high date range
#		7 - rate				: actual rate
#		8 - ratetype			: 0 (real) or 1 (markup)
#		9 - firstincr			: first increment
#		10 - nextincr			: next increment
#		11 - rateid				: id of rate record
#		12 - costid				: id of cost rate record
#		13 - costtype			: 0 (real) or 1 (markup)
#		14 - rpg				: rateplan group id
#		15 - cost				: actual cost rate
#
#	@return boolean				: flag, 0=rates final, 1=re-fetch rates
#
sub parseRates ($) {
	my ($this,$rates) = @_;
	my ($that,$rate,$lastRateType);
	$that = new Rate;
	foreach $rate (@{$rates}) {
		# check cost & evaluate if it is a markup:
		if ($rate->{costtype}==1) {
			$that = Rate->actual($rate->{costid});
			$rate->{cost} = $that->{rate};
			}
		# next, try to create rates for those that don't exist
		if (!$rate->{rate} && !$rate->{rateid} && $rate->{rpg}) {
			$that->purge;
            $that->{rate} = undef;
            $that->{validFrom} = Date->toInternal;
            $that->{validTo} = $Date::maximumDate;
            $that->{enabled} = 1;
            $that->{rateplangroup_id} = $rate->{rpg};
            $that->{prefixgroup_id} = $rate->{prefixgroup};
            $that->{firstincr} = $rate->{firstincr};
            $that->{nextincr} = $rate->{nextincr};
			$that->{type} = defined($lastRateType) ? $lastRateType : $rate->{costtype};
			die unless $that->store;
            return 0;	# force requery results
			}
		# finally, look at the rate itself
		$lastRateType = $rate->{ratetype}; #save the type
#        if ($rate->{ratetype}==1) {
#			$that = Rate->actual($rate->{rateid});
#			$rate->{rate} = $that->{rate};
#			$rate->{ratetype} = 0;
#           }
        }
    return 1;
    }

-1;