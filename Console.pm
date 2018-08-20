##
#   Console - Console (CLI) input / output
#
#	By: Harley H. Puthuff, March 2012
#	Copyright 2012-18, Your Showcase
#

package Console;

use constant DEFAULT_PREFIX => '>';	# default line prefix
use constant LABEL_SIZE     => 20;	# max length of value label

##
# Constructor:
#
#	param:		(optional) prefix string for output
#	returns:		Console object
#
sub new {
    my $class = shift;
    my $this = {};
    bless $this,$class;
    $this->{prefix} = shift;			# get any prefix
    $this->{prefix} ||= DEFAULT_PREFIX;	# use default if none
    $this->{prefix} .= " ";				# append a space
    $0 =~ /(.*\/)*([^.]+)(\..*)*/;		# extract
    $this->{script} = $2;				#  our name
    $this->{script} = "<unknown>" if ($this->{script} =~ /[0-9]+/);
    $this->{bold} = `tput bold`; chomp($this->{bold});
    $this->{normal} = `tput sgr0`; chomp($this->{normal});
    return $this;
    }

##
# write string(s)/line(s) to STDOUT
#
#	param:		string[,string][,string]...
#
sub write {
	my $this = shift;
	my $msg;
	print(STDOUT $this->{prefix},$msg,"\n") while ($msg = shift);
	}

##
# read string from STDIN
#
#	param:		(optional) text for prompt
#	param:		(optional) default value
#	returns:		input string or undef
#
sub read {
	my ($this,$prompt,$default) = @_;
	print STDOUT $this->{prefix};
	print STDOUT $prompt if $prompt;
	print STDOUT " [$default]" if $default;
	print STDOUT ": " if $prompt;
	my $buffer = readline(STDIN);
	chomp $buffer;
	$buffer = $default if ($default and !$buffer);
	return $buffer;
	}

##
# confirm a decision or action
#
#	param:		prompt text /question
#	returns:		0=false/no, 1=true/yes
#
sub confirm {
	my ($this,$prompt) = @_;
	print STDOUT $this->{prefix},$prompt," [Y,n]? ";
	my $buffer = readline(STDIN); chomp $buffer;
	return (!$buffer or $buffer=~/y/i) ? 1 : 0;
	}

##
# display a header line followed by underscores
#
#	param:		(optional) text for header line
#
sub header {
	my ($this,$title) = @_;
	unless ($title) {
		my $ltime = localtime;
		$title = sprintf("%s start: %s",$this->{script},$ltime);
		}
	print STDOUT "\n";
	$this->write($title,('-' x length($title)));
	}

##
# display a footer line preceeded by underscores
#
#	param:		(optional) text for footer line
#
sub footer {
	my ($this,$title) = @_;
	unless ($title) {
		my $ltime = localtime;
		$title = sprintf("%s ended: %s",$this->{script},$ltime);
		}
	$this->write(('-' x length($title)),$title);
	print STDOUT "\n";
	}

##
# exhibit a label (& value)
#
#	param:		label of the value
#	param:		(optional) value to show
#	note:		any $label value ending in ':' treated as a subheading
#
sub exhibit {
	my ($this,$label,$value) = @_;
	my $trailer = (length($label) >= LABEL_SIZE) ? "" : (' 'x(LABEL_SIZE-length($label)));
	if (substr($label,-1) eq ':') { #subheading
		$this->write($this->{bold}.$label.$this->{normal});
		}
	else { #label & value
		$value =~ tr/\x20-\x7f//cd;	# only printable
		$value =~ s/\s{2,}/ /g;		# strip multiple spaces
		$value =~ s/\s+$//;			# strip trailing white space
		$this->write(" ".$label.$trailer." ".$this->{bold}.$value.$this->{normal});
		}
	}

1;