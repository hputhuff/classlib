##
#	Cgi - Perl Common Gateway Interface (CGI)
#
#	By: Harley H. Puthuff, July 2005
#	Copyright 2005-18, Your Showcase
#

package Cgi;

##
# Internal: send line to STDOUT (w/newline appended)
#	P1 = text of line
#
sub put ($) {print STDOUT shift(),"\n"}

#-------Internal: compute a cookie date/expiration in GMT:
#	P1 = days to keep cookie (0-n)
#	returns a string with GMT expiration date
sub expireDate ($) {
    my $days = (shift() * 86400);
    my @t = gmtime($days + time);
    return sprintf("%3s, %02d %3s %04d %02d:%02d:%02d GMT",
	(Sun,Mon,Tue,Wed,Thu,Fri,Sat)[$t[6]],$t[3],
	(Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec)[$t[4]],
	$t[5]+1900,$t[2],$t[1],$t[0]);
    }

#-------Internal: Decode HTTP_COOKIE variable
sub decodeCookies {
    my $this = shift;
    my ($name,$value);
    foreach (split /; /,$ENV{HTTP_COOKIE}) {
		($name,$value) = split /=/;
		$value =~ tr/\+/ /;
		$value =~ s/%(..)/chr(hex($1))/ge;
		$this->{cookies}->{$name} = $value;
		}
    }

#-------Internal: Decode URL-Encoded data
#	P1 = encoded string
sub decodeUrlEncoded {
    my $this = shift;
    my $string = (shift || return);
    my ($key,$data);
    foreach (split /\\*&/,$string) {
		tr/\+/ /;
		($key,$data) = split /=/,$_,2;
		foreach ($key,$data) {s/%(..)/chr(hex($1))/ge}
		if ($this->{$key} ne '')
			{$this->{$key} .= ",$data" unless ($data eq '')}
		else
			{$this->{$key} = $data}
		}
    }

#-------Internal: Decode Multipart-Form data from STDIN

sub decodeMultipart {
    my $this = shift;
    my ($buffer,$line,$name,$filename,$type,$pfile);
    my ($phandle,$lastname,$thisname,$boundary);
    if ($ENV{CONTENT_TYPE} =~ /boundary=(.*)/i)
        {$boundary = $1} else {return}
    while ($line = <STDIN>) {
		if ($line =~ /$boundary/) {
			chomp $buffer;
			chop $buffer if (substr($buffer,-1,1) eq "\r");
			if ($pfile ne '') {
				print $phandle $buffer if ($buffer ne '');
				close $phandle;
				}
			elsif (($name ne '') && ($this->{$name} eq '')) {
				$this->{$name} = $buffer;
				}
			elsif (($name ne '') && ($this->{$name} ne '')) {
				$this->{$name} .= ",$buffer" unless ($buffer eq '');
				}
			$name = ''; $filename = ''; $type = '';
			$pfile = ''; $phandle = ''; $buffer = '';
			next;
			}
		if ($line =~ /Content-Type:/i) {
			$type = $1 if ($line =~ /Content-Type: (.*)\s*/i);
			$line = <STDIN>;
			next if ($line =~ /^\r?\n$/);
			redo;
			}
		if ($line =~ /Content-Disposition:/) {
			$name = $1 if ($line =~ /name=\"(.*?)\"/i);
			$filename = $2 if ($line =~ m'filename=\"(.*[\\\/])*(.+)\"'i);
			if ($filename ne '') {
                $pfile = $filename;
                $this->{$name} = $pfile;
                open($phandle,">/tmp/$pfile");
				}
			$line = <STDIN>;
			next if ($line =~ /^\r?\n$/);
			redo;
			}
		if ($filename ne '') {
			print $phandle $buffer if ($buffer ne '');
			$buffer = $line;
			next;
			}
		$buffer .= $line;
		}
    }

#-------constructor:
#	returns a ref. to a new object

sub new {
    my $class = shift;
    my $this = {};
    bless $this, $class;
    $this->{cookies} = {};
    $this->decodeCookies() if ($ENV{HTTP_COOKIE} ne '');
    $this->decodeUrlEncoded($ENV{QUERY_STRING}) if ($ENV{QUERY_STRING} ne '');
    $this->decodeUrlEncoded($ENV{QUERY_STRING_UNESCAPED})
		if ($ENV{QUERY_STRING_UNESCAPED} ne '');
    if ($ENV{REQUEST_METHOD} =~ /post/i) {
		if ($ENV{CONTENT_TYPE} =~ /multipart\/form-data/i)
			{$this->decodeMultipart()}
		else {
			my $buffer;
			read(STDIN,$buffer,$ENV{CONTENT_LENGTH});
			$this->decodeUrlEncoded($buffer);
			}
		}
    return $this;
    }

#-------destructor:

sub DESTROY {
    my $this = shift;
    return;
    }

#-------fetch a cookie:
#	P1 = cookie name
#	returns a string w/cookie value

sub getCookie {
    my $this = shift;
    my $name = (shift || return);
    return $this->{cookies}->{$name};
    }

#-------store a set-cookie header:
#	P1 = cookie name
#	P2 = (optional) cookie value (omitted=delete)
#	P3 = (optional) days to keep
#	P4 = (optional) path for cookie
#	P5 = (optional) domain for cookie

sub setCookie {
    my $this = shift;
    my $name = (shift || return);
    my ($value,$days,$path,$domain) = @_;
    my $cookie = "Set-Cookie: $name=";
    $this->{cookies}->{$name} = $value;
	if ($value ne '') {
		$value =~ s/([^a-zA-Z0-9 ])/sprintf("%%%s",uc(unpack('H*',$1)))/ge;
		$value =~ tr/ /\+/;
		$cookie .= ($value . '; ');
		$cookie .= ('expires=' . expireDate($days) . '; ') unless ($days == 0);
		}
    else {
		$cookie .= ('; expires=' . expireDate(-1) . '; ');
		}
    $path = '/' if ($path eq '');
    $cookie .= "path=$path; ";
    $cookie .= "domain=$domain; " unless ($domain eq '');
    put $cookie;
    }

#-------produce HTTP headers:

sub headers {
    my $this = shift;
    put qq|Expires: Sat, 01 Jan 2000 00:00:00 GMT|;
    put qq|Cache-Control: NO-CACHE|;
    put qq|Pragma: NO-CACHE|;
    put qq|Content-type: text/html\n|;
    }

#-------produce HTTP redirect headers:
#	P1 = redirect to URL

sub redirect {
    my $this = shift;
    my $url = (shift or return);
    put qq|Location: $url\n|;
    exit 0;
    }

#-------wrap CGI output with HTML from a template file:
#       P1 = pointer to function for included content
#       P2 = (optional) name of template file (.html) to use
#            (if omitted, 'template.html' is used)
#       returns = nothing

sub wrapper {
    my $this = shift;
    my $content = (shift || return);
    my $template = (shift || 'template.html');
    $this->headers;
    if (open(TEMPLATE,"<$template")) {
        while (<TEMPLATE>) {
            if (/\<\!\-\-\s*insert\s+content\s*\-\-\>/i)
                {&{$content}}
            elsif (/\[s*insert\s+content\s*\]/i)
                {&{$content}}
            else
                {print STDOUT}
            }
        close TEMPLATE;
        }
    else {
        put qq|<html><body style="font: bold 10pt verdana,sans; color: #0000B0;| .
            qq| background: #FFFFF0; text-align: center; margin: 3px">|;
        &{$content};
        put qq|</body></html>|;
        }
    }

1;
