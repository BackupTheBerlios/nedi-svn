#!/usr/bin/perl
#============================================================================
# Program: trap.pl
# Programmer: Remo Rickli
#
# DATE     COMMENT
# -------- ------------------------------------------------------------------
# 10/06/05 v1.0.s initial version
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

use vars qw(%dev); 

require './inc/libmisc.pl';														# Use the miscellaneous nedi library

&misc::ReadConf();

if ($misc::backend eq "MSQ"){
	require "./inc/libmsq.pl";
}elsif ($misc::backend eq "CSV"){
	require "./inc/libcsv.pl";
}else{
	die "Backend not configured!\n";
}
my $now = time;

# process the trap:
my $name = <STDIN>;
chomp($name);
my $ip = <STDIN>;
chomp($ip);
my $info = <STDIN>;
$info = <STDIN>;
$info = <STDIN>;
chomp($info);

my $level = 10;
my $src = $ip;
&db::ReadDev('ip',&misc::Ip2Dec($ip));

if(exists $dev{$src}){
	$src = $dev{$src};
	$level = 50;
}

if($level == 50){
	if($info =~ s/IF-MIB::ifIndex/Ifchange/){
	}elsif($info =~ s/SNMPv2-SMI::enterprises.45.1.6.4.3.5.1.0/Baystack Auth/){
	}elsif($info =~ s/SNMPv2-SMI::enterprises.9.2.9.3.1.1.1.1/Cisco Auth/){
	}elsif($info =~ s/SNMPv2-SMI::enterprises.9.2.1.5.0/Cisco Auth Failure!/){
		$level = 150;
	}elsif($info =~ s/SNMPv2-SMI::enterprises.9.2.9.3.1.1.2.1/Cisco TCPconnect/){
	}elsif($info =~ s/SNMPv2-SMI::enterprises.9.9.43/IOS Config change/){
		$level = 100;
	}elsif($info =~ s/SNMPv2-SMI::enterprises.9.5.1.1.28/CatOS Config change/){
		$level = 100;
	}elsif($info =~ s/SNMPv2-SMI::enterprises.9.9.46/Cisco VTP/){
	}
}
if( ! &db::Insert('messages','level,time,source,info',"\"$level\",\"$now\",\"$src\",\"($ip) $info\"") ){
	die "DB error alert!\n";
}
