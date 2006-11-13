#!/usr/bin/perl
#============================================================================
# Program: nedi.pl
# Programmer: Remo Rickli
#
# DATE		COMMENT
#----------------------------------------------------------------------------
# 07/09/04	v1.0.a initial merged (0.8 and 0.9) version 
# 21/12/04	v1.0.e first alpha^17 version.
# 22/02/05	v1.0.p alpha^5 version.
# 27/04/05	v1.0.s alpha^2 version (1 timestamp per discovery for coherence).
# 30/03/06	v1.0.w rrd integration, .def philosopy, monitoring (RC1)
# 30/06/06	v1.0.w system rrd, modules, monitoring, discovery (RC2)
# 3/11/06		v1.0.w  1st SSH implementation, link mgmt, defgen(RC3)
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
use Getopt::Std;
 
use vars qw($nediconf $cdp $lldp $oui);
use vars qw(%nod %dev %int %mod %link %vlan %opt %net %usr); 

getopts('AbcdDilLnost:u:vw:',\%opt) or &Help();
#if (! keys %opt ){&Help()}

$misc::now = time;
require './inc/libmisc.pl';											# Use the miscellaneous nedi library
&misc::ReadConf();
require './inc/libsnmp.pl';											# Use the SNMP function library
require './inc/libcli-sshnet.pl';												# Use the Net::SSH::Perl CLI library
#require './inc/libcli-ssh2.pl';											# Use the Net::SSH2 based CLI library
#require './inc/libcli-sshpty.pl';												# Use the ssh-binary based CLI library
require './inc/libmon.pl';											# Use the Monitoring lib for notifications.
require "./inc/lib" . lc($misc::backend) . ".pl" || die "Backend error ($misc::backend)!";

if($opt{u}){
	$misc::seedlist = "$opt{u}";
}else{
	$misc::seedlist = "seedlist";
}
# Disable buffering so we can see what's going on right away.
select(STDOUT); $| = 1;

# -------------------------------------------------------------------
# This is the debug mode, using previousely saved vars instead of discovering...
# -------------------------------------------------------------------
if ($opt{D}){
#	&cli::go();
#	die;
	#&misc::ReadOUIs();
	#&db::ReadDev();
#	&misc::RetrVar();
# Functions to be debugged go here
	#&db::UnStock();
#	&db::WriteDev();
#	&db::WriteVlan();
#	&db::WriteInt();
#	&db::WriteNet();
#	&misc::Links();
#	&db::WriteLink();

#	&db::ReadNod();
#	&misc::BuildNod();
#	&misc::RetireNod();
#	&db::WriteNod();

	die "\n=== Debugging ended! ===\n";
}
# -------------------------------------------------------------------

if ($opt{w}) {
	&db::WlanUp();
}elsif($opt{i}) {
	&db::InitDB();
}else{
	&misc::ReadOUIs();
	&db::ReadDev();

	my $nseed = &misc::InitSeeds();

	$cdp = 0;
	$oui = 0;
	if ( $opt{c} or $opt{l} ) {												# Use seed(s) for CDP or LLDP discovery.
		if($opt{c}){$cdp = 1}
		if($opt{l}){$lldp = 1}
		print "Dynamic discovery with $nseed seed(s) on ". localtime($misc::now)."\n";
		print "==================================================================================\n";
		print "Device				Status					Todo/Done\n";
		print "----------------------------------------------------------------------------------\n";
		while ($#misc::todo ne "-1"){
			my $cdpid = shift(@misc::todo);
			my $name = &misc::Discover($cdpid);
			push (@misc::donenam, $name) if ($name);
			push (@misc::donecdp,$cdpid);
			push (@misc::doneip,$misc::doip{$cdpid});
			printf ("%4d/%d \n",scalar(@misc::todo),scalar(@misc::donenam) );
		}
	}else{
		print "Static discovery with $nseed devices on ". localtime($misc::now)."\n";
		print "==================================================================================\n";
		print "Device				Status					Todo/Done\n";
		print "----------------------------------------------------------------------------------\n";
		while ($#misc::todo ne "-1"){
			my $ip = shift(@misc::todo);
			my $name = &misc::Discover($ip);
			push (@misc::donenam,$name) if ($name);
			push (@misc::doneoth,$name) if ($name);
			push (@misc::doneip,$ip) if ($name);
			printf ("%4d/%d\n",scalar(@misc::todo),scalar(@misc::donenam) );
		}
	}
	if ($opt{o}) {																# Use seed(s) for OUI discovery.
		$cdp = 0;
		$oui = 1;
		my $noudo = scalar(@misc::oudo);
		if($noudo){
			print "- - OUI Discovery with $noudo canditates - - - - - - - - - - - - - - - - - - - - -\n";
			while ($#misc::oudo ne "-1"){
				my $mac = shift(@misc::oudo);
				my $name = &misc::Discover($mac);
				push (@misc::donemac,$mac);
				push (@misc::donenam,$name) if ($name);
				push (@misc::doneoth,$name) if ($name);
				push (@misc::doneip,$misc::doip{$mac}) if ($name);
				printf ("%4d/%d\n",scalar(@misc::oudo),scalar(@misc::donenam) );
			}
		}
	}
	print "----------------------------------------------------------------------------------\n";
	if (scalar @misc::donenam){
		&misc::StorVar() if ($opt{d});
		&misc::Links();
	
		&db::ReadNod();
		&misc::BuildNod();
		&misc::RetireNod();
	
		die "Only testing, nothing written!" if $opt{t};
		
		&db::UnStock();
		&db::WriteDev();
		
		&db::WriteInt($opt{A});
		&db::WriteVlan($opt{A});
		&db::WriteMod($opt{A});

		&db::WriteLink($opt{A}) if (!$opt{L});
		&db::WriteNet();

		&db::WriteNod();
	}else{
		print "Nothing discovered, nothing written...\n";
	}
}

#===================================================================
# Display some help
#===================================================================
sub Help {
	print "\n";
	print "usage: nedi.pl [-i|-t|-l|-c|-w|(-D)] <more option(s)>\n";
	print "Discovery Options (can be combined, default is static) --------------------\n";
	print "-u<file>	use specified seedlist\n";
	print "-c	CDP discovery\n";
	print "-l 	LLDP discovery\n";
	print "-o	OUI discovery (based on ARP chache entries of the above\n";
	print "-b	backup running configs\n";
	print "-A 	Append to networks, links, vlans, interfaces and modules tables.\n";
	print "-L 	Don't touch links, so you can maintain them manually.\n";
	print "Other Options -------------------------------------------------------------\n";
	print "-i	initialize database and start all over\n";
	print "-w<path>	add Kismet csv files in path to WLAN database.\n";
	print "-t<ip>	test IP only, but don't write anything\n";
	print "-d/D	store (and verbose discovery)/retrieve vars in debug mode\n\n";
	print "-v	verbose output\n";
	print "\nOutput Legend -----------------------------------------------------------\n";
	print "Statistics (lower case letters):\n";
	print "i#	Interfaces\n";
	print "p#	IF IP addresses\n";
	print "a#	ARP entries\n";
	print "f#	Forwarding entries\n";
	print "m#	Modules\n";
	print "#/#	Queueing (added/done already)\n";
	print "b#	border hits\n";
	print "\nNotifications (upper case letters):\n";
	print "Ax	Addresses (i=IF IP, m=IF mask, a=arptable, n=no IF)\n";
	print "Bx	Backup configs (i=IOS, c=Cat, 9=C1900, n=new, u=updated)\n";
	print "Fx(#)	Forwarding table (i=IF, p=Port, #=vlan)\n";
	print "Ix	Interface (d=desc, n=name, t=type, s=speed, m=mac, a=admin status,\n";
	print "		h(in)/H(out)=HC octet,o/O=octet,e/E=error, l=alias, x=duplex, v=vlan)\n";
	print "M#..	Mapping IP or telnet port according to config\n";
	print "Mx	Modules (t=slot, d=desc, c=class, h=hw, f=fw, s=sw, n=SN, m=model)\n";
	print "Qx	Queueing (c=CPD, l=LLDP, 0=IP is 0.0.0.0, s=seeing itself, d=desc filter, v=voip)\n";
	print "Rx	RRD (c=create, d=mkdir, u=update)\n";
	print "Sx	SNMP (c=connect, n=SN, B=Bootimage,u=CPU util, m=CPUmem,i=IOmem,t=Temp)\n";
	print "Tx	Telnet (c=connect,e=enable, l=login, u=no user, o=other\n";
	print "Hx	SSH (s=no ssh libs, c=connect, l=login, u=no user, o=other\n";
	print "Vx	VTP or Vlan (d=VTP domain, m=VTP mode, n=Vl name)\n";
	print "---------------------------------------------------------------------------\n";
	die "NeDi 1.0.w-rc3 3.Nov 2006\n";
}
