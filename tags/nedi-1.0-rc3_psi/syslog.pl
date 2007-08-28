#!/usr/bin/perl
#============================================================================
# Program: syslog.pl
# Programmer: Remo Rickli
#
# DATE     COMMENT
# -------- ------------------------------------------------------------------
# 21/07/05 v0.1.s initial version
# 2/03/06 v0.1.w sanitized and performance optimized message handling
# 17/01/07 v1.0.w updated path handling
#============================================================================
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
#============================================================================
# Visit http://nedi.sourceforge.net for more information.
#============================================================================

use strict;
use IO::Socket;
use Getopt::Std;

use vars qw($nediconf);
use vars qw($p %opt %dev %dip %usr); 

getopts('DvV',\%opt) or &Help();

$p = $0;
$p =~ s/(.*)\/(.*)/$1/;
if($0 eq $p){$p = "."};
require "$p/inc/libmisc.pl";										# Use the miscellaneous nedi library
&misc::ReadConf();
require "$p/inc/libmon.pl";										# Use the SNMP function library
require "$p/inc/lib" . lc($misc::backend) . ".pl" || die "Backend error ($misc::backend)!";

#Disable buffering.
select(STDOUT); $| = 1;

if ($opt{D}) {
	print "syslog.pl Daemonizing\n";
	&misc::Daemonize;
}
my $maxlen	= 512;
my $port	= 514;
my $devup	= 0;

my $sock = IO::Socket::INET->new(LocalPort => $port, Proto => 'udp') or die "socket: $@";
print "Awaiting syslog messages on port $port\n" if $opt{v};
while ($sock->recv(my $msg, $maxlen)) {
		my $now = time;
		if($now - $misc::pause * 10 > $devup){							# refresh devices if older than 10 pauses...
			$devup = $now;
			undef (%dev);
			undef (%dip);
			my $rd = &db::ReadDev();
			print $rd  if $opt{v};
			foreach my $d (keys %dev){
				$dip{$dev{$d}{'ip'}} = $d;
			}
			print "Devs updated!\n" if $opt{v};
		}
	my($client_port, $client_ip) = sockaddr_in($sock->peername);
	my $ip = inet_ntoa($client_ip);
	&Process($ip,$msg);
}
die "recv: $!";

#===================================================================
# Display some help
#===================================================================
sub Help {
	print "\n";
	print "usage: syslog.pl <Option(s)>\n\n";
	print "---------------------------------------------------------------------------\n";
	print "Options:\n";
	print "-D		daemonize moni.pl\n";
	print "-v		verbose output\n\n";
	print "-V		alternative verbose output\n\n";
	die "Syslog 0.1 23.July 2005\n";
}

#===================================================================
# Process Message
#===================================================================
sub Process {

	my $src = $_[0];
	my $now = time;

	my $level = 10;
	if( exists $dip{$_[0]}){
		$src = $dip{$_[0]};
		$level = 50;
	}
	my $pri = $_[1];
	print "$src $pri\n"  if $opt{V};
	$pri =~ s/<(\d+)>.*/$1/;
	my $sev = ($pri & 7);
	if($level == 50){
		   if ($sev == 4){$level = 100}
		elsif ($sev == 3){$level = 150}
		elsif ($sev =~ /[012]/){$level = 200}
	}
	my $msg = $_[1];
	$msg =~ s/<(\d+)>(.*)/$2/;
	$msg =~ s/[^\w\t\/\Q(){}[]!@#$%^&*-+=',.:<>? \E]//g;
	print "$src ($_[0])\tS:$sev\tL:$level\t$msg\n"  if $opt{v};
	if( $level == 200){&mon::SendMail("$src Syslog Alert!","$msg!")}
	if( ! &db::Insert('messages','level,time,source,info',"\"$level\",\"$now\",\"$src\",\"$msg\"") ){
		die "DB error messages!\n";
	}

}