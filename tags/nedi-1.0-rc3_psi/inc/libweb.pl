#============================================================================
#
# Program: libweb.pl
# Programmer: Remo Rickli, dcr
#
# -> LWP based Functions <-
#
# Fetches info from supported web only devices 
#
#============================================================================
package web;

use vars qw($lwpok);

eval 'use LWP::UserAgent;';
if ($@){
	$lwpok = 0;
	print "LWP not available\n" if $main::opt{d};
}else{
	$lwpok = 1;
	print "LWP loaded\n" if $main::opt{d};
}

#============================================================================
# Fetch info through webinterface of Cisco phones
#============================================================================
sub CDPphone{

	my $ua = LWP::UserAgent->new;
	$ua->timeout($misc::timeout);

	my $response = $ua->get("http://$main::dev{$_[0]}{ip}");

	if ($response->is_success) {
		my $ext = $response->content;
		$ext    =~ s/.*<TD><B>([0-9]{2,8})<\/B><\/TD>.*/$1/s;
		$main::dev{$_[0]}{co} = $ext;

		my $sn = $response->content;
		$sn    =~ s/.*<TD><B>(INM\w+).*<\/B><\/TD>.*/$1/s;
		$main::dev{$_[0]}{sn} = $sn;
		print "\n LWP:$_[0] is $ext ($sn)" if $main::opt{v};
	}else {
		print " LWP:" . $response->status_line if $main::opt{d};
		return 1;
	}
}

1;