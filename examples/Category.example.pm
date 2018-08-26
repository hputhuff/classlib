##
# Category Class/object for SourceRy database
#
#	v1.1		August, 2018
#	by		Harley H. Puthuff
#	Copyright 2018, Your Showcase
#

use Medusa;

package Category; use parent qw/-norequire Container/;

# class properties:

our $table;	# fully qualified database.table name

BEGIN {
	$table = "sourcery.categories";
	}

# class methods:

# ::return a list of name=>category choices
#
#	return:	hash of name->category pairs

sub choices {
    my $class = shift;
	my $db = new Databoss;
    return $db->fetchChoices(
        qq|SELECT name,category FROM $table | .
        qq|WHERE category > 0|
		);
	}

# constructor
#
#	param:	(optional) key(s) for record on database
#	return:	new Category object

sub new {
	my ($class,@keys) = @_;
	my $this = {};
	bless $this,$class;
	$this->{table} = $table;
	$this->SUPER::new(@keys);
	return $this;
	}

1;
