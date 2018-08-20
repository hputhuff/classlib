##
#	Elapsed - handle elapsed time in seconds
#
#	By: Harley H. Puthuff, July 2015
#	Copyright 2015-18, Your Showcase
#	

package Elapsed;

use constant DAY	=> 86400;		# one day
use constant HOUR	=> 3600;		# one hour
use constant MINUTE	=> 60;			# one minute

##
#	Constructor
#
#	returns:		Elapsed object w/start timestamp set
#
sub new {
	my $class = shift;
	my $this = {};
	bless $this,$class;
	$this->{start} = time;
	$this->lap;
	return $this;
	}

##
#	lap - record a lap or interval
#
#	returns:		elapsed time in seconds
#
sub lap {
	my $this = shift;
	$this->{finish} = time;
	return ($this->{elapsed} = ($this->{finish} - $this->{start}));
	}

##
#	show - return a string display for elapsed time
#
#	returns:		string as [nn day[s] ][nn hour[s] ][nn minute[s] ]nn second[s]
#
sub show {
	my $this = shift;
	my ($seconds,$slurp,$days,$hours,$mins,$result);
	$seconds = $this->lap;	$result = qq||;
	if ($seconds >= DAY) {
		$slurp = $seconds % DAY;
		$days = sprintf("%.0f",(($seconds-$slurp)/DAY));
		$seconds = $slurp;
		$result .= sprintf("%d day%s ",$days,($days>1 ? "s" : ""));
		}
	if ($seconds >= HOUR) {
		$slurp = $seconds % HOUR;
		$hours = sprintf("%.0f",(($seconds-$slurp)/HOUR));
		$seconds = $slurp;
		$result .= sprintf("%d hour%s ",$hours,($hours>1 ? "s" : ""));
		}
	if ($seconds >= MINUTE) {
		$slurp = $seconds % MINUTE;
		$mins = sprintf("%.0f",(($seconds-$slurp)/MINUTE));
		$seconds = $slurp;
		$result .= sprintf("%d minute%s ",$mins,($mins>1 ? "s" : ""));
		}
	$result .= sprintf("%d second%s",$seconds,($seconds!=1 ? "s" : ""));
	return $result;
	}

1;