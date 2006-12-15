#============================================================================
#
# Program: libmisc.pl
# Programmer: Remo Rickli
#
# -> Miscellaneous Functions <-
#
#============================================================================
package misc;

my $rrdpath	= "$main::p/rrd";
my $rrdcmd	= "rrdtool";

use vars qw($now $seedlist $netfilter $webdev $leafdev $border $ouidev $descfilter);
use vars qw($backend $dbpath $dbname $dbuser $dbpass $dbhost $rrdpath);
use vars qw($arpwatch $ignoredvlans $retire $timeout $rrdstep $redbuild);
use vars qw($notify $thres $pause $smtpserver $mailfrom);
use vars qw(%login %map %doip %dcomm %ouineb %cdplink %sysobj %ifmac); 
use vars qw(%oui %arp %rarp %arpn %portprop %portnew);
use vars qw(@todo @oudo @doneoth @donecdp @donenam @donemac @doneip @comms @seeds @users @devdel); 

#===================================================================
# Read and parse Configuration file.
#===================================================================
sub ReadConf {

	if (-e "$main::p/nedi.conf"){
		open  ("CONF", "$main::p/nedi.conf");
	}elsif (-e "/etc/nedi.conf"){
		open  ("CONF", "/etc/nedi.conf");
	}else{
		die "Dude, where's nedi.conf?\n";
	}
	my @conf = <CONF>;
	close("CONF");
	chomp @conf;

	foreach my $l (@conf){
		if ($l !~ /^[#;]|^$/){
			my @v  = split(/\s+/,$l);
			if ($v[0] eq "comm"){push (@comms,$v[1])}
			if ($v[0] eq "usr"){
				push (@users,$v[1]);
				$login{$v[1]}{pw} = $v[2];
				$login{$v[1]}{en} = $v[3];
			}
			if ($v[0] eq "mapip"){$map{$v[1]}{ip} = $v[2]}
			if ($v[0] eq "maptp"){$map{$v[1]}{cp} = $v[2]}

			elsif ($v[0] eq "leafdev"){$leafdev = $v[1]}
			elsif ($v[0] eq "webdev"){$webdev = $v[1]}
			elsif ($v[0] eq "netfilter"){$netfilter = $v[1]}
			elsif ($v[0] eq "border"){$border = $v[1]}
			elsif ($v[0] eq "ouidev"){$ouidev = $v[1]}
			elsif ($v[0] eq "descfilter"){$descfilter = $v[1]}

			elsif ($v[0] eq "backend"){$backend = $v[1]}
			elsif ($v[0] eq "dbpath"){$dbpath = $v[1]}
			elsif ($v[0] eq "dbname"){$dbname = $v[1]}
			elsif ($v[0] eq "dbuser"){$dbuser = $v[1]}
			elsif ($v[0] eq "dbpass"){$dbpass = $v[1]}
			elsif ($v[0] eq "dbhost"){$dbhost = $v[1]}

			elsif ($v[0] eq "ignoredvlans"){$ignoredvlans = $v[1]}
			elsif ($v[0] eq "retire"){$retire = $misc::now - $v[1] * 86400;}
			elsif ($v[0] eq "timeout"){$timeout = $v[1]}
			elsif ($v[0] eq "arpwatch"){$arpwatch = $v[1]}
			elsif ($v[0] eq "rrdstep"){$rrdstep = $v[1]}

			elsif ($v[0] eq "notify"){$notify = $v[1]}
			elsif ($v[0] eq "threshold"){$thres = $v[1]}
			elsif ($v[0] eq "pause"){$pause = $v[1]}
			elsif ($v[0] eq "smtpserver"){$smtpserver = $v[1]}
			elsif ($v[0] eq "mailfrom"){$mailfrom = $v[1]}

			elsif ($v[0] eq "authuser"){$authuser = $v[1]}
			elsif ($v[0] eq "redbuild"){$redbuild = $v[1]}
		}
	}
}

#===================================================================
# Load NIC vendor database (extracts vendor information from the oui.txt and iab.txt files)
# download to ./inc from http://standards.ieee.org/regauth/oui/index.shtml
#===================================================================
sub ReadOUIs {

	open  ("OUI", "$main::p/inc/oui.txt" ) or die "oui.txt not in $main::p/inc, please dl from ieee.org first!";		# read OUI's first
	my @oui = <OUI>;
	close("OUI");
	chomp @oui;

	my @nics = grep /(base 16)/,@oui;
	foreach my $l (@nics){
		my @m = split(/\s\s+/,$l);
		if(defined $m[2]){
			$oui{lc($m[0])} = substr($m[2],0,32);
		}
	}
	open  ("IAB", "$main::p/inc/iab.txt" ) or die "iab.txt not in $main::p/inc, please dl from ieee.org first!";		# now add IAB's (00-50-C2)	
	my @iab = <IAB>;
	close("IAB");
	chomp @iab;
	
	@nics = grep /(base 16)/,@iab;
	foreach my $l (@nics){
		my @m = split(/\t+/,$l);
		if(defined $m[2]){
			$m[0] = "0050C2".substr($m[0],0,3);
			$oui{lc($m[0])} = substr($m[2],0,32);
		}
	}
	my $nnic = keys(%oui);
	return "$nnic	NIC vendor entries read.\n";
}

#===================================================================
# Load NIC vendor database (extracts vendor information from the oui.txt file),
# which can be downloaded at http://standards.ieee.org/regauth/oui/index.shtml
#===================================================================
sub GetOui {

	my $oui =  "?";
	
	if ($_[0] =~ /^0050C2/i) {
		$oui = $oui{substr($_[0],0,9)};
	} else {
		$oui = $oui{substr($_[0],0,6)};
	}
	if (!$oui){$oui =  "?"}
	return $oui;
}

#===================================================================
# Strip unwanted characters from a string.
#===================================================================

sub Strip {

	if(! defined $_[0]){return ''}
	my $ch = $_[0];

	$ch =~ s/\n|\r|\s+/ /g;											# Remove strange characters.
	$ch =~ s/["']//g;
	$ch =~ s/\c@//g;       											# Remove Null String
	$ch =~ s/\c[\[D//g;											# Remove Escape Sequence
	$ch =~ s/\c[OD//g;											# Remove Escape Sequence
	$ch =~ s/\c[M1//g;											# Remove Escape Sequence
	$ch =~ s/\c[//g;											# Remove Escape Char
	
	return $ch;
}

#===================================================================
# Shorten interface names;
#===================================================================
sub Shif {

	my $n = $_[0];

	if ($n){
		$n =~ s/gigabit[\s]{0,1}ethernet/Gi/i;
		$n =~ s/fast[\s]{0,1}ethernet/Fa/i;
		$n =~ s/^Ethernet/Et/;
		$n =~ s/^Serial/Se/;
		$n =~ s/^[F|G]EC-//;										# Doesn't match telnet CAM table!
		$n =~ s/^BayStack (.*?)- //;									# Nortel specific
		$n =~ s/(Port\d): .*/$1/g;									# Ruby specific
		$n =~ s/pci|motorola|power|switch|network|interface|management//ig;				# Strip other garbage
		$n =~ s/\s+//g;											# Strip spaces
		return $n;
	}else{
		return "-";
	}
}

#===================================================================
# Map IP address, if specified in config.
#===================================================================
sub MapIp {


	my $ip = $_[0];
	if ($misc::map{$_[0]}{ip}){
		$ip = $misc::map{$_[0]}{ip};
		print "M$ip " if $main::opt{d};
	}
	return $ip;
}

#===================================================================
# Converts IP addresses to dec for efficiency in DB
#===================================================================
sub Ip2Dec {
	if(!$_[0]){$_[0] = 0}
    return unpack N => pack CCCC => split /\./ => shift;
}

#===================================================================
# Of course we need to convert them back...
#===================================================================
sub Dec2Ip {
	return join '.' => map { ($_[0] >> 8*(3-$_)) % 256 } 0 .. 3;
}

#===================================================================
# Get APs from Kismet CSV dumps. This is called from the DB module
#===================================================================
sub GetAp {

	
	my $file = $File::Find::name;

	return unless -f $file;
	return unless $file =~ /csv$/;

	open  ("KCSV", "$file" ) or print "couldn't open $file\n" && return '';
	my @kcsv = <KCSV>;
	close("KCSV");
	chomp(@kcsv);

	my @aps = grep /(infrastructure)/,@kcsv;
	foreach my $l (@aps){
			my @f = split(/;/,$l);
			$f[3] =~ s/^(..):(..):(..):(..):(..):(..)/\L$1$2$3$4\E/;
			$db::ap{lc($f[3])} = $now;
   	}
}

#===================================================================
# Find changes in device configurations.
#===================================================================
sub GetChanges {

	use Algorithm::Diff qw(diff);

	my $chg = '';
	my $diffs = diff($_[0], $_[1]);
	return '' unless @$diffs;

	foreach my $chunk (@$diffs) {
		foreach $line (@$chunk) {
			my ($sign, $lineno, $l) = @$line;
			if ( $l !~ /\#time:|ntp clock-period/){
				$chg .=	sprintf "%4d$sign %s\n", $lineno+1, $l;
			}
		}
	}

	return $chg;
}


#===================================================================
# Get the default gateway of your system.
#===================================================================
sub GetGw {

	my @routes = `netstat -rn`;
	my @l = grep /^\s*(0\.0\.0\.0|default)/,@routes;
	my @gw = split(/\s+/,$l[0]);

	if ($gw[1] eq "0.0.0.0"){
		return $gw[3] ;
	}else{
		return $gw[1] ;
	}
}

#===================================================================
# Queue devices to discover based on the seedlist.
#===================================================================
sub InitSeeds {

	my $s = 0;

	if($main::opt{t}){
		push (@todo,"testing");
		$doip{"testing"} = $main::opt{t};
		print "$main::opt{t} added for testing\n" if $main::opt{t};
		$s++;
	}elsif (-e "$main::p/$seedlist"){
		open  (LIST, "$main::p/$seedlist");
		my @list = <LIST>;
		close(LIST);
		chomp @list;
		foreach my $l (@list){
			if ($l !~ /^#|^$/){
				my @f  = split(/\s+|,|;/,$l);
				my $ip = $f[0];
				if ($f[0] !~ /[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/){			# Resolve name if it's not an IP.
					(my $a, my $b, my $c, my $d) = unpack( 'C4',gethostbyname($f[0]) );
					$ip = join('.',$a,$b,$c,$d);
					print "($f[0]) " if $main::opt{v};
				}
				if ($f[1]){$dcomm{$ip} = $f[1]}
				push (@todo,"seed$s");
				$doip{"seed$s"} = $ip;
				print "$ip seed$s added\n" if $main::opt{v};
				$s++;
			}
		}
	}
	if (!$s) {												# Use default GW if no seeds are available.
		my $gw		=  &GetGw();
		$todo[0] 	= 'seed1';
		$doip{'seed1'}	= $gw;
		$s = 1;
	}
	return $s;
}

#===================================================================
# Discover a single device
#===================================================================
sub Discover {

	my $peer  = $doip{$_[0]};
	my @cfg   = ();
	my $name  = "";
	my $cnok = 1;
	print "$peer\t";
	if($peer =~ /$netfilter/){
		$name  = &snmp::Identify($peer);
	}else{
		if($misc::notify =~ /d/){
			if( ! &db::Insert('messages','level,time,source,info',"\"100\",\"$misc::now\",\"$peer\",\"netfilter $netfilter prevents discovery.\"") ){
				die "DB error messages!\n";
			}
		}
		print "\tnetfilter $netfilter prevents discovery.\t";
		return;
	}
	if ($name){
		if ( grep /^\Q$name\E$/, @donenam ){
			print "Done already\t\t\t";
			return '';
		}else{
			&snmp::Enterprise($name);
			my $w = 0;
			if(&snmp::Interfaces($name)){print " "}else{print "\t"}					# Get interfaces and use less spacing, if we had warnings
			if(&snmp::IfAddresses($name)){print " "}else{print "\t"}				# Get IP addresses and use less spacing, if we had warnings
			if(defined $rrdstep){&ManageRRD($name)}
			if($misc::sysobj{$main::dev{$name}{so}}{dp} eq "CDP"){
				&snmp::CDP($name,$_[0]);
			}elsif($misc::sysobj{$main::dev{$name}{so}}{dp} eq "LLDP"){
				&snmp::LLDP($name,$_[0]);
			}else{
				print "   ";
			}
			if($misc::sysobj{$main::dev{$name}{so}}{mt}){&snmp::Modules($name)}
			if ($main::dev{$name}{sv} > 3){
				&snmp::ArpTable($name);
			}else{
				print "\t";									# Spacer instead of L3 info.
			}
			if($main::dev{$name}{us}){								# Don't prep if name exists
				$cnok = 0;
			}elsif(defined $main::dev{$name}{cp}){							# No port no name no service
				$cnok = 2;
			}
			if($misc::sysobj{$main::dev{$name}{so}}{bf}){						# Get mac address tables, if  specified in .def
				if(defined $main::opt{s}){							# Force SNMP if opt_s or specified in .def
					&snmp::MacTable($name);
				}else{
					if($cnok != 2){$cnok = &cli::PrepDev($name)}				# PrepDev returns 2, if failed
					if(!$cnok and $main::dev{$name}{os} eq "IOS"){
						if( &cli::GetIosMacTab($name) ){				# Fall back to SNMP if telnet fails.
							&snmp::MacTable($name);
						}
					}elsif(!$cnok and $main::dev{$name}{os} eq "CatOS"){
						if( &cli::GetCatMacTab($name) ){				# Fall back to SNMP if telnet fails.
							&snmp::MacTable($name);
						}
					}else{
						&snmp::MacTable($name);						# Fall back to SNMP for unsupported switches
					}
				}
			}else{
				print "  ";									# Spacer instead of L2 info.
			}
			if($cnok != 2 and $main::opt{b}){
				if($cnok){$cnok = &cli::PrepDev($name)}						# prep if cnok is 1
				if(!$cnok and $main::dev{$name}{os} eq "IOS"){
					&db::BackupCfg( $name, &cli::GetIosCfg($name) );
				}elsif(!$cnok and $main::dev{$name}{os} eq "CatOS"){
					&db::BackupCfg( $name, &cli::GetCatCfg($name) );
				}elsif(!$cnok and $main::dev{$name}{os} eq "Cat1900"){
					&db::BackupCfg( $name, &cli::GetC19Cfg($name) );
				}elsif(!$cnok and $main::dev{$name}{os} eq "Ironware"){
					&db::BackupCfg( $name, &cli::GetIronCfg($name) );
				}elsif(!$cnok and $main::dev{$name}{os} eq "ProCurve"){
					&db::BackupCfg( $name, &cli::GetProCfg($name) );
				}
			}
			if (!exists $main::dev{$name}{fs}){$main::dev{$name}{fs} = $now}
			$main::dev{$name}{ls} = $now;
			print "\t";
			return $name;
		}
	}else{
		if($misc::notify =~ /d/){
			if( ! &db::Insert('messages','level,time,source,info',"\"100\",\"$misc::now\",\"$peer\",\"$_[0] is not discoverable!\"") ){
				die "DB error messages!\n";
			}
		}
		print " is not discoverable!\t";
		return '';
	}
}

#===================================================================
# Find most accurate port entry for a MAC address based on statistics.
#===================================================================
sub LinkIf {
	
	my $newdv = "";
	my $newif = "";
	my $pop   = 65535;
	my $metric = 250;											# This should never be seen in DB!
	my $mc    = $_[0];

	print "$mc [" if $main::opt{v};

	foreach my $dv (keys(%{$portnew{$mc}}) ){								# Cycle thru ports...
		my $if = $portnew{$mc}{$dv}{po};
		if(!defined $portprop{$dv}{$if}{rtr}){$portprop{$dv}{$if}{rtr} = 0}
		if(!defined $portprop{$dv}{$if}{upl}){$portprop{$dv}{$if}{upl} = 0}
		if(!defined $portprop{$dv}{$if}{chn}){$portprop{$dv}{$if}{chn} = 0}
		#if(!defined $portprop{$dv}{$if}{pho}){$portprop{$dv}{$if}{pho} = 0}

		my $newmet =	$portprop{$dv}{$if}{rtr} * 50 + 
				$portprop{$dv}{$if}{upl} * 30 + 
				$portprop{$dv}{$if}{chn} * 100;

		if( $portprop{$dv}{$if}{pop} <= $pop and $newmet <= $metric ){
			$newdv = $dv;										# ...and use the one with least# of other MACs for links, if interface value is equal or better than the existing entry.
			$newif = $if;
			$metric = $newmet;
			$pop = $portprop{$dv}{$if}{pop};
			print "$pop/$metric($dv-$if) " if $main::opt{v};
		}
	}
	print "] $newdv $newif\n" if $main::opt{v};

	return ($newdv, $newif, $metric);
}

#===================================================================
# Figure out all possible uplinks and then connections.
# Still rather experimental...next thing to be cleaned up in 2006!
#===================================================================
sub Links {

	my %devmac = ();
	foreach my $dv (@donenam){										# Build array with device MACs
		my $mc =$rarp{$main::dev{$dv}{ip}};
		if(defined $mc){
			$devmac{$mc} = $dv;
		}
	}
	foreach my $dmc ( keys %devmac ){									# Use any device MACs to identify uplinks
		if(exists $portnew{$dmc}){
			print "Dev MAC $dmc:" if $main::opt{v};
			foreach my $dv (keys(%{$portnew{$dmc}}) ){
				my $if = $portnew{$dmc}{$dv}{po};
				if(!$portprop{$dv}{$if}{upl}){
					$portprop{$dv}{$if}{upl} = 1;
					print " $dv-$if" if $main::opt{v};
				}
			}
			print "\n" if $main::opt{v};
		}
	}
	foreach my $dv (@donenam){
		foreach my $if (keys (%{$portprop{$dv}})){
			if (!$portprop{$dv}{$if}{rtr} and $portprop{$dv}{$if}{pop} > 24){			# A switchport with more than 24 macs is an uplink, because I say so...
				if(!$portprop{$dv}{$if}{upl}){
					$portprop{$dv}{$if}{upl} = 1;
					print "UPL:$dv-$if ($portprop{$dv}{$if}{pop} MACs)\n" if $main::opt{v};
				}
			}
		}
	}
	if ($main::opt{c}) {											# Simpler approach, if we have CDP devices.
		%main::link = %cdplink;
		foreach my $na (@doneoth){
			my $foundupl = 0;
			my $mc =$rarp{$main::dev{$na}{ip}};							# MAC of device
			(my $ndv, my $nif, my $imet) = &LinkIf($mc);
			if ($ndv and $nif){
				$portprop{$ndv}{$nif}{upl} = 1;
				my $nmc = $rarp{$main::dev{$ndv}{ip}};						# MAC of CDP device
	
				if(defined $nmc and defined $portnew{$nmc}{$na}){				# Neighbour found on own IF?
					my $upl = $portnew{$nmc}{$na}{po};
					$foundupl = 1;
					$portprop{$na}{$upl}{upl} = 1;
					$main::link{$ndv}{$nif}{$na}{$upl}{bw} = $portprop{$ndv}{$nif}{spd};
					$main::link{$ndv}{$nif}{$na}{$upl}{ty} = "M";
					$main::link{$ndv}{$nif}{$na}{$upl}{du} = $main::int{$na}{$portprop{$na}{$upl}{idx}}{dpx};
					$main::link{$ndv}{$nif}{$na}{$upl}{vl} = $main::int{$na}{$portprop{$na}{$upl}{idx}}{vln};
					$main::link{$na}{$upl}{$ndv}{$nif}{bw} = $portprop{$na}{$upl}{spd};
					$main::link{$na}{$upl}{$ndv}{$nif}{ty} = "M";
					$main::link{$na}{$upl}{$ndv}{$nif}{du} = $main::int{$ndv}{$portprop{$ndv}{$nif}{idx}}{dpx};
					$main::link{$na}{$upl}{$ndv}{$nif}{vl} = $main::int{$ndv}{$portprop{$ndv}{$nif}{idx}}{vln};
					$main::int{$na}{$portprop{$na}{$upl}{idx}}{com} .= "to $ndv-$nif ";
					$main::int{$ndv}{$portprop{$ndv}{$nif}{idx}}{com} .= "to $na-$upl ";
					print "$na:$upl <-> $ndv:$nif\n" if $main::opt{v};
				}else{
					my @dif = ();
	
					foreach my $dv (@donenam){						# Any OUI MAc on own IF?
						my $devmc = $rarp{"$main::dev{$dv}{ip}"};
						if(defined $devmc){
							if(defined $portnew{$devmc}{$na} and !grep /$portnew{$devmc}{$na}{po}/, @dif){
								my $upl = $portnew{$devmc}{$na}{po};
								$foundupl = 1;
								push (@dif,$upl);
								$portprop{$na}{$upl}{upl} = 1;
								$main::link{$ndv}{$nif}{$na}{$upl}{bw} = $portprop{$ndv}{$nif}{spd};
								$main::link{$ndv}{$nif}{$na}{$upl}{ty} = "O";
								$main::link{$ndv}{$nif}{$na}{$upl}{du} = $main::int{$na}{$portprop{$na}{$upl}{idx}}{dpx};
								$main::link{$ndv}{$nif}{$na}{$upl}{vl} = $main::int{$na}{$portprop{$na}{$upl}{idx}}{vln};
								$main::link{$na}{$upl}{$ndv}{$nif}{bw} = $portprop{$na}{$upl}{spd};
								$main::link{$na}{$upl}{$ndv}{$nif}{ty} = "O";
								$main::link{$na}{$upl}{$ndv}{$nif}{du} = $main::int{$ndv}{$portprop{$ndv}{$nif}{idx}}{dpx};
								$main::link{$na}{$upl}{$ndv}{$nif}{vl} = $main::int{$ndv}{$portprop{$ndv}{$nif}{idx}}{vln};
								$main::int{$na}{$portprop{$na}{$upl}{idx}}{com} .= "to $ndv-$nif? ";# Problem with port channels on CatOS?
								$main::int{$ndv}{$portprop{$ndv}{$nif}{idx}}{com} .= "to $na-$upl? ";
								print "$na:$upl <-> $ndv:$nif?\n" if $main::opt{v};
							}
						}
					}
				}
				if(0 and !$foundupl){								# Use port with highest population as last resort.
			
					my $upl = "";
					my $pop = 0;
	
					foreach my $if (keys (%{$portprop{$na}})){				# Use port with highest population as last resort.
						if ($portprop{$na}{$if}{pop} > $pop){
							$pop = $portprop{$na}{$if}{pop};
							$upl = $if;
						}
					}
					$portprop{$na}{$upl}{upl} = 1;
					$main::link{$ndv}{$nif}{$na}{$upl}{bw} = $portprop{$ndv}{$nif}{spd};
					$main::link{$ndv}{$nif}{$na}{$upl}{ty} = "P";
					$main::link{$ndv}{$nif}{$na}{$upl}{du} = $main::int{$na}{$portprop{$na}{$upl}{idx}}{dpx};
					$main::link{$ndv}{$nif}{$na}{$upl}{vl} = $main::int{$na}{$portprop{$na}{$upl}{idx}}{vln};
					$main::link{$na}{$upl}{$ndv}{$nif}{bw} = $portprop{$na}{$upl}{spd};
					$main::link{$na}{$upl}{$ndv}{$nif}{ty} = "P";
					$main::link{$na}{$upl}{$ndv}{$nif}{du} = $main::int{$ndv}{$portprop{$ndv}{$nif}{idx}}{dpx};
					$main::link{$na}{$upl}{$ndv}{$nif}{vl} = $main::int{$ndv}{$portprop{$ndv}{$nif}{idx}}{vln};
					$main::int{$ndv}{$portprop{$ndv}{$nif}{idx}}{com} .= "to $na-$upl?? ";
					$main::int{$na}{$portprop{$na}{$upl}{idx}}{com} .= "to $ndv-$nif?? ";
					print "$na:$upl <-> $ndv:$nif??\n" if $main::opt{v};
				}
			}else{
				print "$mc no current IF\n" if $main::opt{v};
			}

		}
	}else{
	}
}

#===================================================================
# Find most appropriate interface for a MAC address based on its metric.
#===================================================================
sub NodeIf {
	
	my $newdv = "";
	my $newif = "";
	my $vlan = "";
	my $metric = 250;											# This should never be seen in DB!
	my $mc    = $_[0];

	if($_[1]){												#  Check whether node exists already.
		if($main::nod{$mc}{iu} < $retire){
			$metric = 200;										# forces update if interface hasn't been updated in the retirement period.
		}else{
			$metric = $main::nod{$mc}{im};								# Use old if value if available.
		}
	}
	print " $metric-> " if $main::opt{v};
	foreach my $dv (keys(%{$portnew{$mc}}) ){								# Cycle thru ports...
		my $if = $portnew{$mc}{$dv}{po};

		if(!defined $portprop{$dv}{$if}{rtr}){$portprop{$dv}{$if}{rtr} = 0}
		if(!defined $portprop{$dv}{$if}{upl}){$portprop{$dv}{$if}{upl} = 0}
		if(!defined $portprop{$dv}{$if}{chn}){$portprop{$dv}{$if}{chn} = 0}
		if(!defined $portprop{$dv}{$if}{pho}){$portprop{$dv}{$if}{pho} = 0}

		my $newmet =	$portprop{$dv}{$if}{pho} * 10 + 
				$portprop{$dv}{$if}{rtr} * 30 + 
				$portprop{$dv}{$if}{upl} * 50 + 
				$portprop{$dv}{$if}{chn} * 100;
		if ($newmet <= $metric ){
			$newdv  = $dv;										# ...and use the new one, if interface value is equal or better than the existing entry or update is forced due to age.
			$newif  = $if;
			$metric = $newmet;
			$vlan   = $portnew{$mc}{$newdv}{vl};
			print "$dv-$if:$metric Vl$vlan " if $main::opt{v};
		}
	}
	return ($newdv, $newif, $metric, $vlan);
}


#===================================================================
# IP update of a node
#===================================================================
sub UpIpNod {

	use Socket;
	my $mc = $_[0];
	
	$main::nod{$mc}{ip} = $arp{$mc};
	$main::nod{$mc}{na} = gethostbyaddr(inet_aton($arp{$mc}), AF_INET) or $main::nod{$mc}{na} = "";
	$main::nod{$mc}{au} = $now;
	
	print "IP:$arp{$mc} $main::nod{$mc}{na} "  if $main::opt{v};
}

#===================================================================
# Build the nodes from the arp and cam (for non-IP) tables.
#===================================================================
sub BuildNod {

	my $nnip = 0;
	my $nip  = 0;

	if(defined $arpwatch){
		my $nad = 0;
		open  ("ARPDAT", $arpwatch ) or die "ARP:$arpwatch not found!";					# read arp.dat
		
		my @adat = <ARPDAT>;
		close("ARPDAT");
		chomp @adat;
		foreach my $l (@adat){
			my @ad = split(/\s/,$l);
			if($ad[2] > $retire){									# Only add current entries
				my $m = sprintf "%02s%02s%02s%02s%02s%02s",split(/:/,$ad[0]);
				$arp{$m}  = $ad[1];
				if($ad[3]){$arpn{$m} = $ad[3]}
				$nad++;
			}
		}
		print "$nad arpwatch entries used.\n"  if $main::opt{d};
	}
	print "Building Nodes (i:IP n:non-IP x:ignored p:no port):\n"  if $main::opt{d};
	print "Building IP nodes from Arp cache:\n"  if $main::opt{v};
	foreach my $mc (keys(%arp)){
		if (!grep /^$arp{$mc}$/,@doneip){								# Don't use devices as nodes.
			print "NOD:$mc [" if $main::opt{v};
			if ( exists $portnew{$mc} ){
				my $nodex = 0;
				if(exists $main::nod{$mc}){
					$nodex = 1;
					if(exists $arpn{$mc}){							# Simple update, if ARPwatch got a name, ...
						$main::nod{$mc}{ip} = $arp{$mc};
						$main::nod{$mc}{na} = $arpn{$mc};
						$main::nod{$mc}{au} = $now;
					}elsif($main::nod{$mc}{ip} ne $arp{$mc}){				# ...IP changed...
						&UpIpNod($mc);
						$main::nod{$mc}{ac}++;
					}elsif($main::nod{$mc}{au} < $retire){					# ...or it's been a while.
						&UpIpNod($mc);
					}
				}else{
					&UpIpNod($mc);
					$main::nod{$mc}{fs} = $now;
					$main::nod{$mc}{ic} = 0;
					$main::nod{$mc}{ac} = 0;
					$main::nod{$mc}{al} = 0;
				}
				$main::nod{$mc}{nv} = &GetOui($mc);
				$main::nod{$mc}{ls} = $now;
				(my $dv, my $if, my $imet, my $vl) = &NodeIf($mc,$nodex);
				if($dv){
					$main::nod{$mc}{ic}++ if ($main::nod{$mc}{dv} and ($main::nod{$mc}{dv} ne $dv or $main::nod{$mc}{if} ne $if) );
					$main::nod{$mc}{im} = $imet;
					$main::nod{$mc}{dv} = $dv;
					$main::nod{$mc}{if} = $if;
					$main::nod{$mc}{vl} = $vl;
					$main::nod{$mc}{iu} = $now;
					print "] $dv-$if\n" if $main::opt{v};
				}else{
					print "old IF kept $main::nod{$mc}{dv}-$main::nod{$mc}{if}:$main::nod{$mc}{im}]\n" if $main::opt{v};
				}
				print "i"  if $main::opt{d};
			}else{
				print " no new IF??? ]\n" if $main::opt{v};
				print "p"  if $main::opt{d};
			}
			$nip++;
		}else{
			print "x" if $main::opt{d};
			print "Device $arp{$mc} not added to nodes!\n" if $main::opt{v};
		}
	}
	print "Building non IP nodes from MAC tables:\n"  if $main::opt{v};

	foreach my $mc (keys(%portnew)){
		if (!(grep /^$mc$/,@donemac or exists $arp{$mc})){
			print "NOD:$mc " if $main::opt{v};
			if(exists $ifmac{$mc}){
				print "x"  if $main::opt{d};
				print " device MAC!\n" if $main::opt{v};
			}else{
				my $nodex = 0;
				if(exists $main::nod{$mc}){
					$nodex = 1;
					if($main::nod{$mc}{ip} eq '0.0.0.0'){
						$main::nod{$mc}{au} = $now;
					}else{
						$main::nod{$mc}{al}++;
					}
				}else{
					$main::nod{$mc}{fs} = $now;
					$main::nod{$mc}{au} = $now;
					$main::nod{$mc}{ic} = 0;
					$main::nod{$mc}{ac} = 0;
					$main::nod{$mc}{al} = 0;
				}
				$main::nod{$mc}{nv} = &GetOui($mc);
				$main::nod{$mc}{ls} = $now;
				(my $dv, my $if, my $imet, my $vl) = &NodeIf($mc,$nodex);
				if($dv){									# was a (better) IF found?
					$main::nod{$mc}{nv} = &GetOui($mc);
					$main::nod{$mc}{ic}++ if ($main::nod{$mc}{dv} and ($main::nod{$mc}{dv} ne $dv or $main::nod{$mc}{if} ne $if) );
					$main::nod{$mc}{im} = $imet;
					$main::nod{$mc}{dv} = $dv;
					$main::nod{$mc}{if} = $if;
					$main::nod{$mc}{vl} = $vl;
					$main::nod{$mc}{iu} = $now;
					print "] $dv-$if\n" if $main::opt{v};
				}else{
					print "old IF kept $main::nod{$mc}{dv}-$main::nod{$mc}{if}:$main::nod{$mc}{im}]\n" if $main::opt{v};
				}
				print "n"  if $main::opt{d};
				$nnip++;
			}
		}
	}
	print "\n"  if $main::opt{d};
	print "$nip/$nnip	IP/non-IP nodes processed.\n";
}

#===================================================================
# Retire nodes which have been inactive longer than $misc::retire days
#===================================================================
sub RetireNod {

	my $nret = 0;

	foreach my $mc (keys %main::nod){
		if ($main::nod{$mc}{ls} < $retire){
			print "$mc $main::nod{$mc}{na} $main::nod{$mc}{ip} $main::nod{$mc}{dv}-$main::nod{$mc}{if}\n"  if $main::opt{v};
			delete $main::nod{$mc};
			$nret++;
		}
	}
	print "$nret	nodes have been retired.\n";
}

#===================================================================
# Update or create RRDs if necessary
#===================================================================
sub ManageRRD {

	my $dv		= $_[0];
	my $ok		= 0;
	
	$dv =~ s/([^a-zA-Z0-9_-])/"%" . uc(sprintf("%2.2x",ord($1)))/eg;
	if (-e "$rrdpath/$dv"){
		$ok = 1;
	}else{
		$ok = mkdir ("$rrdpath/$dv", 0755);
	}
	if($ok){
		if (-e "$rrdpath/$dv/system.rrd"){
			$ok = 1;
		}else{
			my $ds = 2 * $rrdstep;
			$ok = 1 + system ($rrdcmd,
					"create","$rrdpath/$dv/system.rrd",
					"-s","$rrdstep",
					"DS:cpu:GAUGE:$ds:0:100",
					"DS:memcpu:GAUGE:$ds:0:U",
					"DS:memio:GAUGE:$ds:0:U",
					"DS:temp:GAUGE:$ds:-100:100",
					"RRA:AVERAGE:0.5:1:720",
					"RRA:AVERAGE:0.5:24:720");
		}
		if($ok){
			if ($main::opt{t}){
				if ($main::opt{d}){
					print "\n\nRRDs in $rrdpath/$dv would be filled with:\n";
					print "CPU=$main::dev{$_[0]}{cpu} Mem=$main::dev{$_[0]}{mcp}/$main::dev{$_[0]}{mio}  TMP=$main::dev{$_[0]}{tmp}\n";
					printf ("\n%12s %12s %12s %8s %8s\n", "Interface","Inoctet","Outoctet","Inerror","Outerror"  );
				}
			}else{
				$ok = 1 + system ($rrdcmd,
						"update",
						"$rrdpath/$dv/system.rrd","N:$main::dev{$_[0]}{cpu}:$main::dev{$_[0]}{mcp}:$main::dev{$_[0]}{mio}:$main::dev{$_[0]}{tmp}");
				print "Ru" if !$ok;
			}
		}else{print "Rs"}
		$ok = 0;
		foreach my $i ( keys(%{$main::int{$_[0]}}) ){
			$irf =  $main::int{$_[0]}{$i}{ina};
			$irf =~ s/([^a-zA-Z0-9_-])/"%" . uc(sprintf("%2.2x",ord($1)))/eg;
			if (-e "$rrdpath/$dv/$irf.rrd"){
				$ok = 1;
			}else{
				my $ds = 2 * $rrdstep;
				$ok = 1 + system ($rrdcmd,
						"create","$rrdpath/$dv/$irf.rrd",
						"-s","$rrdstep",
						"DS:inoct:COUNTER:$ds:0:10000000000",
						"DS:outoct:COUNTER:$ds:0:10000000000",
						"DS:inerr:COUNTER:$ds:0:10000000000",
						"DS:outerr:COUNTER:$ds:0:10000000000",
						"RRA:AVERAGE:0.5:1:720",
						"RRA:AVERAGE:0.5:24:720");
			}
			if($ok){
				if ($main::opt{t}){
					printf ("%12s %12d %12d %8d %8d\n", $irf,$main::int{$_[0]}{$i}{ioc},$main::int{$_[0]}{$i}{ooc},$main::int{$_[0]}{$i}{ier},$main::int{$_[0]}{$i}{oer}  ) if $main::opt{d};
				}else{
					$ok = 1 + system ($rrdcmd,
							"update",
							"$rrdpath/$dv/$irf.rrd","N:$main::int{$_[0]}{$i}{ioc}:$main::int{$_[0]}{$i}{ooc}:$main::int{$_[0]}{$i}{ier}:$main::int{$_[0]}{$i}{oer}");
							print "Ru($irf)" if !$ok;
				}
			}else{print "Ri($irf)"}
		}
	}else{
		print "Rd";
	}
}

#===================================================================
# Daemonize
#===================================================================
sub Daemonize {

	use POSIX 'setsid';

	#    open STDOUT, ">>$config::nedilog" or die "Can't write to $config::nedilog: $!";

	defined(my $pid = fork)   or die "Can't fork: $!";
	exit if $pid;
	SETSID                    or die "Can't start a new session: $!";
	umask 0;
}

#===================================================================
# Retrieve Vars for debugging.
#===================================================================
sub RetrVar{

	use Storable;
	
	my $sysobj = retrieve("$main::p/sysobj.db");
	%sysobj = %$sysobj;
	my $portnew = retrieve("$main::p/portnew.db");
	%portnew = %{$portnew};
	my $portprop = retrieve("$main::p/portprop.db");
	%portprop = %$portprop;
	my $doip = retrieve("$main::p/doip.db");
	%doip = %$doip;
	my $arp = retrieve("$main::p/arp.db");
	%arp = %$arp;
	my $rarp = retrieve("$main::p/rarp.db");
	%rarp = %$rarp;
	my $ifmac = retrieve("$main::p/ifmac.db");
	%ifmac = %$ifmac;

	my $doneoth = retrieve("$main::p/doneoth.db");
	@doneoth = @{$doneoth};
	my $donecdp = retrieve("$main::p/donecdp.db");
	@donecdp = @$donecdp;
	my $donenam = retrieve("$main::p/donenam.db");
	@donenam = @$donenam;
	my $donemac = retrieve("$main::p/donemac.db");
	@donemac = @$donemac;
	my $doneip = retrieve("$main::p/doneip.db");
	@doneip = @$doneip;


	my $dev = retrieve("$main::p/dev.db");
	%main::dev = %$dev;
	my $net = retrieve("$main::p/net.db");
	%main::net = %$net;
	my $int = retrieve("$main::p/int.db");
	%main::int = %$int;
	my $cdplink = retrieve("$main::p/cdplink.db");
	%misc::cdplink = %$cdplink;
	my $vlan = retrieve("$main::p/vlan.db");
	%main::vlan = %$vlan;
}

#===================================================================
# Store Vars for debugging.
#===================================================================
sub StorVar{

	use Storable;
	
	store \%sysobj, "$main::p/sysobj.db";
	store \%portnew, "$main::p/portnew.db";
	store \%portprop, "$main::p/portprop.db";
	store \%doip, "$main::p/doip.db";
	store \%arp, "$main::p/arp.db";
	store \%rarp, "$main::p/rarp.db";
	store \%ifmac, "$main::p/ifmac.db";
	
	store \@doneoth, "$main::p/doneoth.db";
	store \@donecdp, "$main::p/donecdp.db";
	store \@donenam, "$main::p/donenam.db";
	store \@donemac, "$main::p/donemac.db";
	store \@doneip, "$main::p/doneip.db";

	store \%main::dev, "$main::p/dev.db";
	store \%main::int, "$main::p/int.db";
	store \%main::net, "$main::p/net.db";
	store \%misc::cdplink, "$main::p/cdplink.db";
	store \%main::vlan, "$main::p/vlan.db";
}


1;
