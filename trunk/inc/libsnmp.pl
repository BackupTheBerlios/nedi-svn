#============================================================================
#
# Program: libsnmp.pl
# Programmer: Remo Rickli
#
# -> SNMP based Functions <-
#
#============================================================================
package snmp;
use Net::SNMP;

#===================================================================
# Identify device based on sysobj definition
#===================================================================
sub Identify {

	my $comm	= "";
	my $session	= "";
	my $error	= "";
	my $r		= "";
	my $err		= 0;
	my $snmpver	= 1;
	my $name = "";
		
	my $desO	= '1.3.6.1.2.1.1.1.0';
	my $sysO	= '1.3.6.1.2.1.1.2.0';
	my $conO	= '1.3.6.1.2.1.1.4.0';
	my $namO	= '1.3.6.1.2.1.1.5.0';
	my $locO	= '1.3.6.1.2.1.1.6.0';
	my $srvO	= '1.3.6.1.2.1.1.7.0';

	my $peer  = $_[0];
	my @comms = @misc::comms;
	if ($misc::dcomm{$peer}){									# Build Community list, with priority on db entry.
		unshift(@comms,$misc::dcomm{$peer});
	}
	do {
		$comm = shift (@comms);
		print " C:$comm" if $main::opt{d};
		($session, $error) = Net::SNMP->session(-hostname  => $peer,
							-community => $comm,
							-timeout   => $misc::timeout,
							-version   => $snmpver,
							-translate => [-octetstring => 0x0],
							-port      => '161');
	
		$r = $session->get_request($namO);							# Get name to find the right community.
		$err = $session->error;
		$session->close;
	} while ($#comms ne "-1" and $err);								# And stop once a community worked or we ran out of them.
	if ($err){
		print "Sc\t\t";
	}else{
		print "+ " if $main::opt{d};
		$name = &misc::Strip($r->{$namO});
		if ($name =~ /^\s*$/){
			$name = "($peer)";
		}else{
			$name =~  s/^(.*?)\.(.*)/$1/;							# Domain confuses CDP links!		
		}
		if($main::opt{d} or $main::opt{v}){
			print "$name\t";
		}else{
			printf ("%-12.12s\t",$name);
		}
		($session, $error) = Net::SNMP->session(-hostname  => $peer,
							-community => $comm,
							-timeout   => $misc::timeout,
							-version   => $snmpver,
							-translate => [-octetstring => 0x0],
							-port      => '161');
	
		my $so	= "other";
		$r	= $session->get_request($sysO);
		$err	= $session->error;
		if(!$err and defined $r->{$sysO}){$so = &misc::Strip($r->{$sysO})}

		if(!exists $misc::sysobj{$so}){								# Load .def if not done already
			if (-e "$main::p/sysobj/$so.def"){
				open  ("DEF", "$main::p/sysobj/$so.def");
			}else{
				open  ("DEF","$main::p/sysobj/other.def");
			}
			my @def = <DEF>;
			close("DEF");
			chomp @def;
			$misc::sysobj{$so}{bf} = "";
			$misc::sysobj{$so}{ty} = $so;
			$misc::sysobj{$so}{hc} = 0;
			$misc::sysobj{$so}{mv} = 0;
			foreach my $l (@def){
				if ($l !~ /^#|^;|^$/){
					my @v  = split(/\s+/,$l);
					if (!defined $v[1]){$v[1] = ""}
					if ($v[0] eq "Type")		{$misc::sysobj{$so}{ty} = $v[1]}
					elsif ($v[0] eq "OS")		{$misc::sysobj{$so}{os} = $v[1]}
					elsif ($v[0] eq "Icon")		{$misc::sysobj{$so}{ic} = $v[1]}
					elsif ($v[0] eq "SNMPv")	{
						$misc::sysobj{$so}{sp} = substr($v[1],0,1);
						if(substr($v[1],1,2) eq 'HC'){$misc::sysobj{$so}{hc} = 128}
					}
					elsif ($v[0] eq "Serial")	{$misc::sysobj{$so}{sn} = $v[1]}
					elsif ($v[0] eq "Bimage")	{$misc::sysobj{$so}{bi} = $v[1]}
					elsif ($v[0] eq "Bridge")	{$misc::sysobj{$so}{bf} = $v[1]}
					elsif ($v[0] eq "Dispro")	{$misc::sysobj{$so}{dp} = $v[1]}

					elsif ($v[0] eq "VLnams")	{$misc::sysobj{$so}{vn} = $v[1]}
					elsif ($v[0] eq "VTPdom")	{$misc::sysobj{$so}{vd} = $v[1]}
					elsif ($v[0] eq "VTPmod")	{$misc::sysobj{$so}{vm} = $v[1]}

					elsif ($v[0] eq "IFalia")	{$misc::sysobj{$so}{al} = $v[1]}
					elsif ($v[0] eq "IFalix")	{$misc::sysobj{$so}{ax} = $v[1]}
					elsif ($v[0] eq "IFdupl")	{$misc::sysobj{$so}{du} = $v[1]}
					elsif ($v[0] eq "IFduix")	{$misc::sysobj{$so}{dx} = $v[1]}
					elsif ($v[0] eq "Halfdp")	{$misc::sysobj{$so}{hd} = $v[1]}
					elsif ($v[0] eq "Fulldp")	{$misc::sysobj{$so}{fd} = $v[1]}
					elsif ($v[0] eq "IFvlan")	{$misc::sysobj{$so}{vi} = $v[1]}
					elsif ($v[0] eq "IFvlix")	{$misc::sysobj{$so}{vx} = $v[1]}

					elsif ($v[0] eq "Modesc")	{$misc::sysobj{$so}{md} = $v[1]}
					elsif ($v[0] eq "Moclas")	{$misc::sysobj{$so}{mc} = $v[1]}
					elsif ($v[0] eq "Movalu")	{$misc::sysobj{$so}{mv} = $v[1]}
					elsif ($v[0] eq "Mostep")	{$misc::sysobj{$so}{mp} = $v[1]}
					elsif ($v[0] eq "Moslot")	{$misc::sysobj{$so}{mt} = $v[1]}
					elsif ($v[0] eq "Modhw")	{$misc::sysobj{$so}{mh} = $v[1]}
					elsif ($v[0] eq "Modsw")	{$misc::sysobj{$so}{ms} = $v[1]}
					elsif ($v[0] eq "Modfw")	{$misc::sysobj{$so}{mf} = $v[1]}
					elsif ($v[0] eq "Modser")	{$misc::sysobj{$so}{mn} = $v[1]}
					elsif ($v[0] eq "Momodl")	{$misc::sysobj{$so}{mm} = $v[1]}

					
					elsif ($v[0] eq "CPUutl")	{$misc::sysobj{$so}{rc} = $v[1]}
					elsif ($v[0] eq "MemCPU")	{$misc::sysobj{$so}{rm} = $v[1]}
					elsif ($v[0] eq "MemIO")	{$misc::sysobj{$so}{ri} = $v[1]}
					elsif ($v[0] eq "Temp")		{$misc::sysobj{$so}{rt} = $v[1]}
				}
			}
		}
		$r = $session->get_request($desO);
		$err = $session->error;
		my $de = "";
		if(!$err){$de = &misc::Strip($r->{$desO})}
		if (defined $misc::descfilter and $de =~ /$misc::descfilter/){				# Only define device, if not filtered in nedi.conf
			print "Qd";
			return;
		}else{
			$main::dev{$name}{so} = $so;
			if($misc::sysobj{$so}{ty}){
				$main::dev{$name}{ty} = $misc::sysobj{$so}{ty};
			}else{
				$main::dev{$name}{ty} = $so;
			}
			$main::dev{$name}{ip} = $peer;
			$main::dev{$name}{cm} = $comm;
			$main::dev{$name}{de} = $de;
			$main::dev{$name}{os} = $misc::sysobj{$so}{os};
			$main::dev{$name}{ic} = $misc::sysobj{$so}{ic};
			$main::dev{$name}{sp} = $misc::sysobj{$so}{sp};
			$r = $session->get_request($conO);
			$err = $session->error;
			if(!$err){$main::dev{$name}{co} = &misc::Strip($r->{$conO})}
			$r = $session->get_request($locO);
			$err = $session->error;
			if(!$err){$main::dev{$name}{lo} = &misc::Strip($r->{$locO})}
			$r = $session->get_request($srvO);
			$err = $session->error;
			if($err or $r->{$srvO !~ /^\d+$/}){
				$main::dev{$name}{sv} = 6; 						# Could be a buggy SNMP implementation, so we don't set this to 6 and check the device anyway
			}else{
				$main::dev{$name}{sv} = &misc::Strip($r->{$srvO});
			}
			print "SV=$main::dev{$name}{sv} TY=$main::dev{$name}{ty} " if $main::opt{d};
		}
		$session->close;
	}
	return $name;
}

#===================================================================
# Get enterprise specific information using sysobj.def file
#===================================================================
sub Enterprise {

	my $session	= "";
	my $error	= "";
	my $r		= "";
	my $err		= "";
	my $nv		= 0;
	my $so		= $main::dev{$_[0]}{so};
	
	($session, $error) = Net::SNMP->session(-hostname  => $main::dev{$_[0]}{ip},
						-community => $main::dev{$_[0]}{cm},
						-timeout   => $misc::timeout,
						-version   => $main::dev{$_[0]}{sp},
						-translate => [-octetstring => 0x0],
						-port      => '161');

	if($misc::sysobj{$so}{sn}){
		$r  = $session->get_request($misc::sysobj{$so}{sn});
		$err = $session->error;
		if ($err){
			print "Sn";
			print "$err\n" if $main::opt{d};
			$main::dev{$_[0]}{sn} = "err";
		}else{
			$main::dev{$_[0]}{sn} = &misc::Strip($r->{$misc::sysobj{$so}{sn}});
		}
	}else{
		$main::dev{$_[0]}{sn} = "-";
	}
	if($misc::sysobj{$so}{bi}){
		$r  = $session->get_request($misc::sysobj{$so}{bi});
		$err = $session->error;
		if ($err){
			print "Sb";
			print "$err\n" if $main::opt{d};
			$main::dev{$_[0]}{bi} = "err";
		}else{
			$main::dev{$_[0]}{bi} = &misc::Strip($r->{$misc::sysobj{$so}{bi}});
			$main::dev{$_[0]}{bi} =~ s/^flash:|^bootflash:|^slot[0-9]:|^sup-boot(flash|disk):|^disk0://;
			$main::dev{$_[0]}{bi} =~ s/.*\/(.*)/$1/;
			print "BI=$main::dev{$_[0]}{bi} " if $main::opt{d};
		}
	}else{
		$main::dev{$_[0]}{bi} = "-";
	}
	if($misc::sysobj{$so}{rc}){
		$r  = $session->get_request($misc::sysobj{$so}{rc});
		$err = $session->error;
		if ($err or $r->{$misc::sysobj{$so}{rc}} !~ /^[0-9]+$/){
			print "Su";
			print "$err\n" if $main::opt{d};
			$main::dev{$_[0]}{cpu} = 0;
		}else{
			$main::dev{$_[0]}{cpu} = &misc::Strip($r->{$misc::sysobj{$so}{rc}});
		}
		print "CPU=$main::dev{$_[0]}{cpu} " if $main::opt{d};
	}else{
		$main::dev{$_[0]}{cpu} = 0;
	}
	if($misc::sysobj{$so}{rm}){
		$r  = $session->get_request($misc::sysobj{$so}{rm});
		$err = $session->error;
		if ($err or $r->{$misc::sysobj{$so}{rm}} !~ /^[0-9]+$/){
			print "Sm";
			print "$err\n" if $main::opt{d};
			$main::dev{$_[0]}{mcp} = 0;
		}else{
			$main::dev{$_[0]}{mcp} = &misc::Strip($r->{$misc::sysobj{$so}{rm}});
		}
		print "memCPU=$main::dev{$_[0]}{mcp} " if $main::opt{d};
	}else{
		$main::dev{$_[0]}{mcp} = 0;
	}
	if($misc::sysobj{$so}{ri}){
		$r  = $session->get_request($misc::sysobj{$so}{ri});
		$err = $session->error;
		if ($err or $r->{$misc::sysobj{$so}{ri}} !~ /^[0-9]+$/){
			print "Si";
			print "$err\n" if $main::opt{d};
			$main::dev{$_[0]}{mio} = 0;
		}else{
			$main::dev{$_[0]}{mio} = &misc::Strip($r->{$misc::sysobj{$so}{ri}});
		}
		print "memIO=$main::dev{$_[0]}{mio} " if $main::opt{d};
	}else{
		$main::dev{$_[0]}{mio} = 0;
	}
	if($misc::sysobj{$so}{rt}){
		$r  = $session->get_request($misc::sysobj{$so}{rt});
		$err = $session->error;
		if ($err or $r->{$misc::sysobj{$so}{rt}} !~ /^[0-9]+$/){
			print "St";
			print "$err\n" if $main::opt{d};
			$main::dev{$_[0]}{tmp} = 0;
		}else{
			$main::dev{$_[0]}{tmp} = &misc::Strip($r->{$misc::sysobj{$so}{rt}});
		}
		print "Temp=$main::dev{$_[0]}{tmp} " if $main::opt{d};
	}else{
		$main::dev{$_[0]}{tmp} = 0;
	}
	if($misc::sysobj{$so}{vd}){
		$r  = $session->get_request($misc::sysobj{$so}{vd});
		$err = $session->error;
		if ($err){
			print "Vd";
			print "$err\n" if $main::opt{d};
			$main::dev{$_[0]}{vd} = "?";
		}else{
			$main::dev{$_[0]}{vd} = &misc::Strip($r->{$misc::sysobj{$so}{vd}});
		}
		print " VTP domain $main::dev{$_[0]}{vd} " if $main::opt{v};
	}else{
		$main::dev{$_[0]}{vd} = "-";
	}
	if($misc::sysobj{$so}{vm}){
		$r  = $session->get_request($misc::sysobj{$so}{vm});
		$err = $session->error;
		if ($err){
			print "Vm";
			print "$err\n" if $main::opt{d};
			$main::dev{$_[0]}{vm} = 0;
		}else{
			$main::dev{$_[0]}{vm} = &misc::Strip($r->{$misc::sysobj{$so}{vm}});
		}
		print "  mode $main::dev{$_[0]}{vm}\n" if $main::opt{v};
	}else{
		$main::dev{$_[0]}{vm} = 0;
	}
	if($misc::sysobj{$so}{vn}){
		$r = $session->get_table($misc::sysobj{$so}{vn});					# Get Vlan names
		$err = $session->error;
		if ($err){
			print "Vn";
			print "$err\n" if $main::opt{d};
		}else{
			%vlnam  = %{$r};
			while ( (my $vlO,my $na) =  each(%vlnam) ){
				my $vl = substr($vlO,rindex($vlO,'.') + 1);
				$main::vlan{$_[0]}{$vl} = $na;
				print "\n VL:$vl\t$na" if $main::opt{v};
				$nv++;
			}
			print " v$nv" if !$main::opt{v};
		}
	}
	$session->close;
}

#===================================================================
# Get interface information.
#===================================================================
sub Interfaces {

	my $session	= "";
	my $error	= "";
	my $r		= "";
	my $err		= "";
	my $myioc	= "";
	my $myooc	= "";
	
	my $warn	= 0;
	my $ni		= 0;

	my %ifde	= ();
	my %iftp	= ();
	my %ifsp	= ();
 	my %ifmc	= ();
	my %ifas	= ();

	my %ifio	= ();
	my %ifie	= ();
	my %ifoo	= ();
	my %ifoe	= ();
	my %ifna	= ();
	
	my %ifal	= ();
	my %ifax	= ();
	my %alias	= ();
	my %ifvl	= ();
	my %ifvx	= ();
	my %vlid	= ();

	my %ifdp	= ();
	my %ifdx	= ();
	my %duplex	= ();
	my %usedoid	= ();

	my $ifdesO	= '1.3.6.1.2.1.2.2.1.2';
	my $iftypO	= '1.3.6.1.2.1.2.2.1.3';
	my $ifspdO	= '1.3.6.1.2.1.2.2.1.5';
 	my $ifmacO	= '1.3.6.1.2.1.2.2.1.6';
	my $ifadmO	= '1.3.6.1.2.1.2.2.1.7';
	my $ifinoO	= '1.3.6.1.2.1.2.2.1.10';
	my $ifineO	= '1.3.6.1.2.1.2.2.1.14';
	my $ifotoO	= '1.3.6.1.2.1.2.2.1.16';
	my $ifoteO	= '1.3.6.1.2.1.2.2.1.20';

	my $ifnamO	= '1.3.6.1.2.1.31.1.1.1.1';
	my $ifhioO	= '1.3.6.1.2.1.31.1.1.1.6';
	my $ifhooO	= '1.3.6.1.2.1.31.1.1.1.10';

	my $ifaliO	= '1.3.6.1.2.1.31.1.1.1.18';

	my $dv = $_[0];

	if($misc::sysobj{$main::dev{$dv}{so}}{al}){
		$ifaliO = $misc::sysobj{$main::dev{$dv}{so}}{al};
	}
	my $ifalxO	= $misc::sysobj{$main::dev{$dv}{so}}{ax};
	my $ifvlaO	= $misc::sysobj{$main::dev{$dv}{so}}{vi};
	my $ifvlxO	= $misc::sysobj{$main::dev{$dv}{so}}{vx};
	my $ifdupO	= $misc::sysobj{$main::dev{$dv}{so}}{du};
	my $ifduxO	= $misc::sysobj{$main::dev{$dv}{so}}{dx};

	($session, $error) = Net::SNMP->session(-hostname  => $main::dev{$dv}{ip},
						-community => $main::dev{$dv}{cm},
						-timeout   => $misc::timeout,
						-version   => $main::dev{$dv}{sp},
						-translate => [-octetstring => 0x0],
						-port      => '161');


	$r = $session->get_table($ifdesO);								# Walk interface description.
	$err = $session->error;
	if ($err){print "Id";print "$err\n" if $main::opt{d};$warn++}else{%ifde  = %{$r}}

	$r = $session->get_table($ifnamO);								# Walk interface name.
	$err = $session->error;
	if ($err){print "In";print "$err\n" if $main::opt{d};$warn++}else{ %ifna = %{$r}}

	$r = $session->get_table($iftypO);								# Walk interface type.
	$err = $session->error;
	if ($err){print "It";print "$err\n" if $main::opt{d};$warn++}else{%iftp  = %{$r}}

	$r = $session->get_table($ifspdO);								# Walk interface speed.
	$err = $session->error;
	if ($err){print "Is";print "$err\n" if $main::opt{d};$warn++}else{%ifsp  = %{$r}}

	$r = $session->get_table($ifmacO);								# Walk interface mac address.
	$err = $session->error;
	if ($err){print "Im";print "$err\n" if $main::opt{d};$warn++}else{%ifmc  = %{$r}}

	$r = $session->get_table($ifadmO);								# Walk interface admin status
	$err = $session->error;
	if ($err){print "Ia";print "$err\n" if $main::opt{d};$warn++}else{%ifas  = %{$r}}

	if($misc::sysobj{$main::dev{$_[0]}{so}}{hc}){							# Walk interface HC in .def
		$r = $session->get_table($ifhioO);
		$err = $session->error;
		if ($err){
			print "Ih";
			print "$err\n" if $main::opt{d};
			$warn++;
		}else{
			$myioc = $ifhioO;
			%ifio  = %{$r};
			$r = $session->get_table($ifhooO);						# Walk interface HC out octets
			$err = $session->error;
			if ($err){print "IH";$warn++}else{
				$main::dev{$dv}{hc} = 128;						# Remember HC support for future reference
				$myooc = $ifhooO;
				%ifoo  = %{$r};
			}
		}
	}else{
		$main::dev{$dv}{hc} = 0;
		$r = $session->get_table($ifinoO);							# Walk interface in octets otherwhise
		$err = $session->error;
		if ($err){
			print "Io";
			print "$err\n" if $main::opt{d};
			$warn++;
		}else{
			$myioc = $ifinoO;
			%ifio  = %{$r};
			$r = $session->get_table($ifotoO);						# Walk interface admin status
			$err = $session->error;
			if ($err){
				print "IO";
				print "$err\n" if $main::opt{d};
				$warn++;
			}else{
				$myooc = $ifotoO;
				%ifoo  = %{$r};
			}
		}
	}
	$r = $session->get_table($ifineO);								# Walk interface in errors
	$err = $session->error;
	if ($err){print "Ie";print "$err\n" if $main::opt{d};$warn++}else{ %ifie  = %{$r}}

	$r = $session->get_table($ifoteO);								# Walk interface in errors
	$err = $session->error;
	if ($err){print "IE";print "$err\n" if $main::opt{d};$warn++}else{ %ifoe  = %{$r}}

	$r = $session->get_table($ifaliO);								# Walk interface alias.
	$err = $session->error;
	if ($err){print "Il";print "$err\n" if $main::opt{d};$warn++}else{ %ifal  = %{$r}}
	if($ifalxO){
		$r = $session->get_table($ifalxO);							# Walk index table if specified...
		$err = $session->error;
		if ($err){
			print "Il";
			print "$err\n" if $main::opt{d};
		}else{
			%ifax  = %{$r};
			$usedoid{$ifalxO} = \%ifax;							# (store in case it's the same for vlans or duplex)
			foreach my $x (keys (%ifax)){							# ...and map directly to if indexes
				my $i = $x;
				$i =~ s/$ifalxO//;
				$alias{$ifax{$x}} = $ifal{"$ifaliO$i"};
			}
		}
	}else{												# Else use indexes directly
		foreach my $x (keys (%ifal)){
			my $i = $x;
			$i =~ s/$ifaliO\.//;
			$alias{$i} = $ifal{$x};
		}
	}
	if($ifvlaO){											# Same for IF vlans...
		$r = $session->get_table($ifvlaO);
		$err = $session->error;
		if ($err){print "Iv";print "$err\n" if $main::opt{d};$warn++}else{ %ifvl  = %{$r}}
	}
	if($ifvlxO){											# If vlans use a different index
		if(exists $usedoid{$ifvlxO}){								# and if it's been used before
			%ifvx = %{$usedoid{$ifvlxO}};							# assign the vlan oid to where the used one points to.
		}else{											# Otherwhise walk it
			$r = $session->get_table($ifvlxO);
			$err = $session->error;
			if ($err){
				print "Iv";
				print "$err\n" if $main::opt{d};
			}else{
				%ifvx  = %{$r};
				$usedoid{$ifvlxO} = \%ifvx;
			}
		}
		foreach my $x (keys (%ifvx)){
			my $i = $x;
			$i =~ s/$ifvlxO\.//;
			$vlid{$ifvx{$x}} = $ifvl{"$ifvlaO.$i"};
		}
	}else{
		foreach my $x (keys (%ifvl)){
			my $i = $x;
			$i =~ s/$ifvlaO\.//;
			$vlid{$i} = $ifvl{$x};
		}
	}
	if($ifdupO){											# ...and IF duplex
		if($ifdupO eq "doublespeed"){								# If duplex is shown by speed...
			foreach my $x (keys (%ifsp)){
				my $i = $x;
				$i =~ s/$ifspdO\.//;
				if($ifsp{$x} =~ /^20/){
					$ifsp{$x} /= 2;
					$duplex{$i} = "FD";
				}elsif($ifsp{$x} =~ /^10/){
					$duplex{$i} = "HD";
				}
			}
		}else{
			$r = $session->get_table($ifdupO);
			$err = $session->error;
			if ($err){print "Ix";print "$err\n" if $main::opt{d};$warn++}else{ %ifdp  = %{$r}}
		}
	}
	if($ifduxO){											# If duplex uses a different index
		if(exists $usedoid{$ifduxO}){								# and if it's been used before
			%ifdx = %{$usedoid{$ifduxO}};							# assign the duplex oid to where the used one points to.
		}else{											# Otherwhise walk it
			$r = $session->get_table($ifduxO);
			$err = $session->error;
			if ($err){
				print "Ix";
				print "$err\n" if $main::opt{d};
			}else{
				%ifdx  = %{$r};
				$usedoid{$ifduxO} = \%ifdx;
			}
		}
		foreach my $x (keys (%ifdx)){
			my $i = $x;
			$i =~ s/$ifduxO\.//;
			$duplex{$ifdx{$x}} = $ifdp{"$ifdupO.$i"};
		}
	}else{
		foreach my $x (keys (%ifdp)){
			my $i = $x;
			$i =~ s/$ifdupO\.//;
			$duplex{$i} = $ifdp{$x};
		}
	}
	$session->close;

	foreach my $dek (keys (%ifde)){
		my @if  = split(/\./,$dek);
		my $i   = $if[10];									# ...is the IF index
		my $ina = $i;
		my $idn = &misc::Shif( "$ifde{$dek}" );
		if ($ifna{"$ifnamO.$i"} ){
			if(exists $misc::portprop{$dv}{$ifna{"$ifnamO.$i"}} ){
				$ina = $ifna{"$ifnamO.$i"} . "-$i";
			}else{
				$ina = $ifna{"$ifnamO.$i"};
			}
		}elsif( exists $misc::portprop{$dv}{$idn} ){
			$ina = &misc::Shif( "$ifde{$dek}-$i" );						# Use IF desc and make unique, if necessary
		}else{
			$ina = &misc::Shif( "$ifde{$dek}" );						# Use IF desc if name is empty
		}
		$main::int{$dv}{$i}{fwd} = $i;								# Bogus now, but eventually to bridge forwarding port...
		$main::int{$dv}{$i}{ina} = $ina;
		$main::int{$dv}{$i}{des} = (defined $ifde{$dek}?$ifde{$dek}:"");
		$main::int{$dv}{$i}{typ} = (defined $iftp{"$iftypO.$i"}?$iftp{"$iftypO.$i"}:0);
		$main::int{$dv}{$i}{spd} = (defined $ifsp{"$ifspdO.$i"}?$ifsp{"$ifspdO.$i"}:0);
		$main::int{$dv}{$i}{sta} = (defined $ifas{"$ifadmO.$i"}?$ifas{"$ifadmO.$i"}:0);
		$main::int{$dv}{$i}{ioc} = (defined $ifio{"$myioc.$i"}?$ifio{"$myioc.$i"}:0);
		$main::int{$dv}{$i}{ooc} = (defined $ifoo{"$myooc.$i"}?$ifoo{"$myooc.$i"}:0);
		$main::int{$dv}{$i}{ier} = (defined $ifie{"$ifineO.$i"}?$ifie{"$ifineO.$i"}:0);
		$main::int{$dv}{$i}{oer} = (defined $ifoe{"$ifoteO.$i"}?$ifoe{"$ifoteO.$i"}:0);
		$main::int{$dv}{$i}{ali} = (defined $alias{$i}?$alias{$i}:"");
		$main::int{$dv}{$i}{vln} = (defined $vlid{$i}?$vlid{$i}:0);
		$main::int{$dv}{$i}{dio} = 0;
		$main::int{$dv}{$i}{die} = 0;
		$main::int{$dv}{$i}{doo} = 0;
		$main::int{$dv}{$i}{doe} = 0;
		$main::int{$dv}{$i}{com} = "";
		
		if ( $ifmc{"$ifmacO.$i"} ){
			my $imac = unpack('H12', $ifmc{"$ifmacO.$i"});
			$main::int{$dv}{$i}{mac} = $imac;
			$misc::ifmac{$imac}++;
		}else{
			$main::int{$dv}{$i}{mac} = "";
		}
		if (exists $duplex{$i} ){								# Did we get a duplex value?
			if ($duplex{$i} =~ /^[FH]D$/){							# Use if set properly already...
				$main::int{$dv}{$i}{dpx} = $duplex{$i};
			}else{										# ...or assign defined HD,FD key
				if( $duplex{$i} eq $misc::sysobj{$main::dev{$_[0]}{so}}{fd} ){$main::int{$dv}{$i}{dpx} = "FD"}
				elsif( $duplex{$i} eq $misc::sysobj{$main::dev{$_[0]}{so}}{hd} ){$main::int{$dv}{$i}{dpx} = "HD"}
				else{$main::int{$dv}{$i}{dpx} = "?"}
			}
		}else{
			$main::int{$dv}{$i}{dpx} = "-";
		}
		$misc::portprop{$dv}{$ina}{pop} = 0;
		$misc::portprop{$dv}{$ina}{idx} = $i;
		$misc::portprop{$dv}{$ina}{spd} = $main::int{$dv}{$i}{spd};
		$misc::portprop{$dv}{$ina}{vln} = $main::int{$dv}{$i}{vln};
		print "\n IF:$i\t$ina\tT:$main::int{$dv}{$i}{typ}\tD:$main::int{$dv}{$i}{dpx}\tVL:$main::int{$dv}{$i}{vln}\t$ifde{$dek}\t$main::int{$dv}{$i}{ali}" if $main::opt{v};
		$ni++;
	}
	print " i$ni" if !$main::opt{v};
	return $warn;
}

#===================================================================
# Get IP address tables and tries to find best mgmt IP (based on idea from Duane Walker)
#===================================================================

sub IfAddresses {
	my $session	= "";
	my $error	= "";
	my $r		= "";
	my $err		= "";

	my %aifx	= ();
	my %ainm	= ();

	my $warn	= 0;
	my $nia		= 0;
	
	my $newip	= "";
	my $ippri	= 10;

	my $iaixO	= "1.3.6.1.2.1.4.20.1.2";
	my $ianmO	= "1.3.6.1.2.1.4.20.1.3";

	my $dv = $_[0];

	($session, $error) = Net::SNMP->session(-hostname  => $main::dev{$dv}{ip},
						-community => $main::dev{$dv}{cm},
						-timeout   => $misc::timeout,
						-version   => $main::dev{$dv}{sp},
						-translate => [-octetstring => 0x0],
						-port      => '161');

	$r   = $session->get_table($iaixO);
	$err = $session->error;
	if ($err){
		$session->close;
		print "Ai";
		print "$err\n" if $main::opt{d};
		return 1;
	}else{%aifx = %{$r}}

	$r   = $session->get_table($ianmO);
	$err = $session->error;
	if ($err){print "Am";print "$err\n" if $main::opt{d};$warn++}else{%ainm = %{$r}}
	$session->close;

	foreach my $k (keys %aifx){
		if(exists $main::int{$dv}{$aifx{$k}}){							# Avoid non existant IFs (e.g. idx=0 on  cisco2970 and 3750 with IOS 12.1)
			my @i		= split(/\./,$k);
			if (defined $i[13]){								# Some devs have incomplete IPs here!	
				my $iaddr	= "$i[10].$i[11].$i[12].$i[13]";
				$main::net{$dv}{$iaddr}{ifn} = $main::int{$dv}{$aifx{$k}}{ina};
				$main::net{$dv}{$iaddr}{msk} = $ainm{"$ianmO.$iaddr"};
				print "\n IP:$main::net{$dv}{$iaddr}{ifn}\t$iaddr/$main::net{$dv}{$iaddr}{msk}" if $main::opt{v};
				if($iaddr !~ /^127.0.0|^0/){					
					if ($main::int{$dv}{$aifx{$k}}{typ} == 24){				# 1.priority, use loopback IF
						$ippri = 1;
						$newip = $iaddr;
					}elsif ($main::int{$dv}{$aifx{$k}}{typ} == 53 and $ippri > 3){		# 2.priority, use virtual IF(prefer existing IP)
						if($iaddr eq $main::dev{$dv}{ip}){
							$ippri = 3;
						}else{
							$ippri = 4;
						}
						$newip = $iaddr;
					}elsif ($main::int{$dv}{$aifx{$k}}{typ} =~ /^[67]$/ and $ippri > 5){	# 3.priority, use ethernet IF (prefer existing IP)
						if($iaddr eq $main::dev{$dv}{ip}){
							$ippri = 5;
						}else{
							$ippri = 6;
						}
						$newip = $iaddr;
					}
				}
				$nia++;
			}
		}
	}
	if ($ippri < 10 and !$main::opt{I}){
		$main::dev{$dv}{oi} = $main::dev{$dv}{ip};
		$main::dev{$dv}{ip} = $newip;
		print "\n New IP:$main::dev{$dv}{ip} (Priority $ippri)" if $main::opt{v};
	}
	print " p$nia" if !$main::opt{v};
	return $warn;
}

#===================================================================
# Converts CDP capabilities to sys services alike format
#===================================================================
sub Cap2Sv {

	my $srv = 0;
	my $sv  = hex(unpack("C",substr($_[0],length($_[0])-1,1)));
	if ($sv & 1)		{$srv =   4}
	if ($sv & (8|4|2))	{$srv +=  2}
	if ($sv & 16)		{$srv += 64}
	if ($sv & 64)		{$srv +=  1}
	return $srv;
}

#===================================================================
# Get CDP information.
#===================================================================
sub CDP {

	my $session	= "";
	my $error	= "";
	my $r		= "";
	my $err		= "";
	my $ad		= 0;
	my $dn		= 0;
	my $bd		= 0;

	my %cdp  	= ();

	my $cdpO    	= '1.3.6.1.4.1.9.9.23.1.2.1.1';

	($session, $error) = Net::SNMP->session(-hostname  => $main::dev{$_[0]}{ip},
						-community => $main::dev{$_[0]}{cm},
						-timeout   => $misc::timeout,
						-version   => $main::dev{$_[0]}{sp},
						-translate => [-octetstring => 0x0],
						-maxmsgsize=> 4095,
						-port      => '161');

	$r = $session->get_table("$cdpO");								# Walk CDP cache...
	$err = $session->error;
	if ($err){
		$session->close;
		print "Qc";
		print "$err\n" if $main::opt{d};
		return 1;
	}else{
		while( my($key, $val) = each(%{$r}) ) {
			my @ck = split (/\./,$key);
			$cdp{$ck[14]}{$ck[15]}{$ck[13]} = $val;
		}
	}
	$session->close;

	foreach my $i (keys (%cdp)){
		my $lif	  = $main::int{$_[0]}{$i}{ina};							# Assign interfacename.
		foreach my $n (keys (%{$cdp{$i}})){
			my $rdup  = "-";
			
			my $rip   = "0.0.0.0";
			if(defined $cdp{$i}{$n}{4} and $cdp{$i}{$n}{4} ne ""){
				$rip = &misc::MapIp( unpack("C",substr($cdp{$i}{$n}{4},0,1)).".".unpack("C",substr($cdp{$i}{$n}{4},1,1)).".".unpack("C",substr($cdp{$i}{$n}{4},2,1)).".".unpack("C",substr($cdp{$i}{$n}{4},3,1)) );
			}
			my $rdes  = &misc::Strip( $cdp{$i}{$n}{5} );
			my $rci   = &misc::Strip( $cdp{$i}{$n}{6} );
			my $rif	  = &misc::Shif(  $cdp{$i}{$n}{7} );
			my $rtyp  = &misc::Strip( $cdp{$i}{$n}{8} );
			my $rsrv  = Cap2Sv( $cdp{$i}{$n}{9} );
			my $rvtd = (defined $cdp{$i}{$n}{10}?$cdp{$i}{$n}{10}:"?");
			if(defined $cdp{$i}{$n}{12}){
				if($cdp{$i}{$n}{12} == 2){
					$rdup = "HD";
				}elsif($cdp{$i}{$n}{12} == 3){
					$rdup = "FD";
				}
			}
			my $rvln = (defined $cdp{$i}{$n}{14}?$cdp{$i}{$n}{14}:0);
			my $rpwr = (defined $cdp{$i}{$n}{15}?$cdp{$i}{$n}{15}:0);
			if($rpwr > 4000000000){$rpwr = 0}						# > 4MW is just wrong...used until Cisco to fixes it
			my $renam = $rci;
			my $rser  = $rci;
			if($rdes =~ /^Revision /){							# Procurves reverse name and SN...of course
				$renam    =~ s/(.*?)\((.*?)\)/$1/;					# Extract remote name from CatOS neighbours
				$rser     =~ s/(.*?)\((.*?)\)/$2/;			
			}else{			
				$renam    =~ s/(.*?)\((.*?)\)/$2/;					# Extract remote name from CatOS neighbours
				$rser     =~ s/(.*?)\((.*?)\)/$1/;
			}
			$renam    =~  s/^(.*?)\.(.*)/$1/;						# Domain part confuses CDP links, sorry!
			if ($_[1] eq $rci){								# is it me?
				$main::int{$_[0]}{$i}{com} .= " C:Loop?";
				if($misc::notify =~ /d/){
					if( ! &db::Insert('messages','level,time,source,info',"\"150\",\"$main::now\",\"$_[0]\",\"CDP:Loop seeing $rci?\"") ){
						die "DB error messages!\n";
					}
				}
				print "Qs";
			}else{
				$misc::cdplink{$_[0]}{$lif}{$renam}{$rif}{bw} = $main::int{$_[0]}{$i}{spd};
				$misc::cdplink{$_[0]}{$lif}{$renam}{$rif}{ty} = "C";
				$misc::cdplink{$_[0]}{$lif}{$renam}{$rif}{pr} = $rpwr;
				$misc::cdplink{$_[0]}{$lif}{$renam}{$rif}{du} = $rdup;
				$misc::cdplink{$_[0]}{$lif}{$renam}{$rif}{vl} = $rvln;
				$main::int{$_[0]}{$i}{com} .= " C:$renam-$rif";
				if($rip eq "0.0.0.0"){							# ip not set?
					$main::dev{$renam}{ip} = $rip;
					$main::dev{$renam}{sn} = "($rser)";
					$main::dev{$renam}{de} = $rdes;
					$main::dev{$renam}{sv} = $rsrv;
					$main::dev{$renam}{ty} = $rtyp;
					$main::dev{$renam}{os} = "CDP OS";
					$main::dev{$renam}{lo} = "$main::dev{$_[0]}{lo} (from $_[0])";
					$main::dev{$renam}{co} = "$main::dev{$_[0]}{co} (from $_[0])";
					$main::dev{$renam}{vd} = $rvtd;
					if (!$main::dev{$renam}{fs}){$main::dev{$renam}{fs} = $main::now}
					$main::dev{$renam}{ls} = $main::now;
					if($misc::notify =~ /d/){
						if( ! &db::Insert('messages','level,time,source,info',"\"100\",\"$main::now\",\"$_[0]\",\"CDP device $renam with IP 0.0.0.0 on $rif!\"") ){
							die "DB error messages!\n";
						}
					}
					print "Q0\t";
				}elsif(defined $misc::webdev and $rci =~ /$misc::webdev/ or defined $misc::leafdev and $rci =~ /$misc::leafdev/){
					if(defined $misc::webdev and $rci =~ /$misc::webdev/){		# Try to get some info through the web interface
					}
					$misc::cdplink{$renam}{$rif}{$_[0]}{$lif}{bw} = $main::int{$_[0]}{$i}{spd};
					$misc::cdplink{$renam}{$rif}{$_[0]}{$lif}{ty} = "C";
					$misc::cdplink{$renam}{$rif}{$_[0]}{$lif}{pr} = 0;
					$misc::cdplink{$renam}{$rif}{$_[0]}{$lif}{du} = $main::int{$_[0]}{$i}{dpx};
					$misc::cdplink{$renam}{$rif}{$_[0]}{$lif}{vl} = $main::int{$_[0]}{$i}{vln};
					$main::dev{$renam}{ip} = $rip;
					$main::dev{$renam}{sn} = "($rser)";
					$main::dev{$renam}{bi} = $rdes;
					$main::dev{$renam}{de} = "Passive device (Look for nodes on $_[0]-$lif)";
					$main::dev{$renam}{sv} = $rsrv;
					$main::dev{$renam}{ty} = $rtyp;
					$main::dev{$renam}{os} = "CDP OS";
					$main::dev{$renam}{lo} = "$main::dev{$_[0]}{lo}";
					$main::dev{$renam}{co} = "$main::dev{$_[0]}{co}";
					$main::dev{$renam}{vd} = $rvtd;
					if($rtyp =~ /^Cisco IP Phone/){
						$main::dev{$renam}{ic} = "phbl";
					}elsif($rtyp =~ /Phone/){
						$main::dev{$renam}{ic} = "phdb";				
					}elsif($rtyp =~ /AIR-AP/){
						$main::dev{$renam}{ic} = "adgn";				
					}
					if (!$main::dev{$renam}{fs}){$main::dev{$renam}{fs} = $main::now}
					$main::dev{$renam}{ls} = $main::now;
					$misc::portprop{$_[0]}{$lif}{pho} = 1;
				}else{									# none of the above...let's queue it!
					$misc::portprop{$_[0]}{$lif}{upl} = 1;
					if (grep /^\Q$rci\E$/,(@misc::donecdp,@misc::donenam,@misc::todo) ){	# Don't queue if done or already queued... (The \Q \E is to prevent interpreting the CDPid as a regexp)
						$dn++;
					}elsif (defined $misc::border and $rci =~ /$misc::border/){	# ...or matching the border.
						$bd++;
					}else{
						if ($main::cdp) {					# queue if it's a CDP discovery 
							push (@misc::todo,"$rci");
							$misc::doip{$rci} = $rip;
							$ad++;
						}
					}
				}
			}
			print "\n CDP:$lif -> $rtyp $rip $renam (SV:$rsrv, P:$rpwr mW, SN:$rser) on $rif ($rdup) VL$rvln" if $main::opt{v};
		}
	}
	if (!$main::opt{v}){
		print " c$ad";
		if ($bd){print " b$bd"}
	}
}

#===================================================================
# Get LLDP information.
#===================================================================
sub LLDP {
	print "Don't have such a device yet! Please donate ;-)\n";
}

#===================================================================
# Get ARP tables from Layer 3 device and queue entries for OUI matching
# discovery, if desired.
#===================================================================

sub ArpTable {

	my $session	= "";
	my $error	= "";
	my $r		= "";
	my $err		= "";

	my %at		= ();
	my %ntmtab	= ();

	my $narp	= 0;
	my $noui	= 0;

	my $bd		= 0;
	my $dn		= 0;

	my $warn	= 0;

	my $NmifO	= "1.3.6.1.2.1.4.22.1.2";
	
	($session, $error) = Net::SNMP->session(-hostname  => $main::dev{$_[0]}{ip},
						-community => $main::dev{$_[0]}{cm},
						-timeout   => $misc::timeout,
						-version   => $main::dev{$_[0]}{sp},
						-translate => [-octetstring => 0x0],
						-port      => '161');

	$r   = $session->get_table($NmifO);
	$err = $session->error;
	if ($err){print "Aa";print "$err\n" if $main::opt{d}}else{%at  = %{$r}}
	$session->close;

	foreach my $k (keys %at){
		if ($k !~ /127.0.0/){
			my $vl = "";
			my @i   = split(/\./,$k);
			my $mc   = unpack("H2",substr($at{$k},0,1)).unpack("H2",substr($at{$k},1,1)).unpack("H2",substr($at{$k},2,1)).unpack("H2",substr($at{$k},3,1)).unpack("H2",substr($at{$k},4,1)).unpack("H2",substr($at{$k},5,1));
			$misc::arp{$mc} = "$i[11].$i[12].$i[13].$i[14]";
			$misc::rarp{"$i[11].$i[12].$i[13].$i[14]"} = $mc;				# will be needed to identify OUI uplinks;

			my $po = "";
			if(exists $main::int{$_[0]}{$i[10]} ){
				$po = $main::int{$_[0]}{$i[10]}{ina};
				$misc::portprop{$_[0]}{$po}{rtr} = 1;
				$misc::portprop{$_[0]}{$po}{pop}++;
				$misc::portnew{$mc}{$_[0]}{po} = $po;
				$vl = ($po =~ /^Vl(\d+)$/) ? $1 : "";
				$misc::portnew{$mc}{$_[0]}{vl} = $vl;
				print "\n ARP:$mc=$misc::arp{$mc} on $po" if $main::opt{v};
			}else{										# This can happen on loadbalancers, when IF is in AT table
				print "An";
				$warn++;
				$misc::portprop{$_[0]}{'-'}{rtr} = 1;
				$misc::portprop{$_[0]}{'-'}{chn} = 1;					# No IF, treat as channel to avoid MAC population
				$misc::portprop{$_[0]}{'-'}{pop}++;
				$misc::portnew{$mc}{$_[0]}{po} = '-';
			}
			$narp ++;

			if ($main::opt{o}){
				my $oui = &misc::GetOui($mc);
				if($oui =~ /$misc::ouidev/i){
					if (grep /\Q$mc\E/,(@misc::donemac,@misc::oudo) ){		# Don't queue if done or queued.
						$dn++;
					}elsif (defined $misc::border and $mc =~ /$misc::border/){	# ...or matching the border.
						$bd++;
					}else{
						if ($misc::arp{$mc} eq '0.0.0.0'){			# Add to queue, if IP is set
							if($misc::notify =~ /d/){
								if( ! &db::Insert('messages','level,time,source,info',"\"100\",\"$main::now\",\"$mc\",\"OUI device ($oui) with IP of 0.0.0.0!\"") ){
									die "DB error messages!\n";
								}
							}
						}else{
							push (@misc::oudo,"$mc");
							$misc::doip{$mc} = &misc::MapIp($misc::arp{$mc});
							print "\n OUI:$po\t$oui ($misc::arp{$mc}) " if $main::opt{v};
							$noui++;
						}
					}

				}
			}
		}
	}
	if (!$main::opt{v}){
		print " a$narp";
		if ($main::opt{o}){
			print " o$noui";
			if ($bd){print " b$bd"}
		}else{print "   "}
	}
	return $warn;
}

#===================================================================
# Get MAC address table from a device with optional community indexing.
#===================================================================

sub MacTable {

	my $session	= "";
	my $error	= "";
	my $r		= "";
	my $err		= "";

	my $nspo	= 0;
	my @vlans	= ();
	
	my $fwdxO  = '1.3.6.1.2.1.17.1.4.1.2';
	my $fwdpO  = '1.3.6.1.2.1.17.4.3.1.2';
	my $fwdsO  = '1.3.6.1.2.1.17.5.1.1.1';
	
	if($misc::sysobj{$main::dev{$_[0]}{so}}{bf} eq "VLX"){
		@vlans = keys %{$main::vlan{$_[0]}};
	}else{
		$vlans[0] = "";
	}

	foreach my $vl (@vlans){
		if ($vl !~ /$misc::ignoredvlans/){
			my %fwdix = ();
			my %fwdpo = ();
			my %Fdpst = ();

			($session, $error) = Net::SNMP->session(-hostname  => $main::dev{$_[0]}{ip},
								-community => $main::dev{$_[0]}{cm}.($vl ne ''?"\@$vl":""),
								-timeout   => $misc::timeout,
								-version   => $main::dev{$_[0]}{sp},
								-translate => [-octetstring => 0x0],
								-port      => '161');
		
			$r = $session->get_table($fwdxO);
			$err = $session->error;
			if ($err){print "Fi$vl";print "$err\n" if $main::opt{d}}else{%fwdix = %{$r} }
			$r = $session->get_table($fwdpO);
			$err = $session->error;
			if ($err){print "Fp$vl";print "$err\n" if $main::opt{d}}else{%fwdpo = %{$r} }
			$session->close;

			foreach my $fpo (keys (%fwdpo)){
				my @dmac = split(/\./,$fpo);
				if (defined $dmac[16]){								# Some devs have incomplete MACs here!
					my $mc   = sprintf "%02x%02x%02x%02x%02x%02x",$dmac[11],$dmac[12],$dmac[13],$dmac[14],$dmac[15],$dmac[16];
					my $ifx  = $fwdix{"$fwdxO.$fwdpo{$fpo}"};
					if (defined $ifx){
						my $po = "fwd$ifx";						# Fallback name for weird switches...
						if (defined $main::int{$_[0]}{$ifx}){
							$po   = $main::int{$_[0]}{$ifx}{ina};			# ...otherwhise use real name.

							if ($po =~ /[0-9]-[0-9]|[0-9],[0-9]|^Po[0-9]|channel/){
								$misc::portprop{$_[0]}{$po}{chn} = 1;
							}
						}
						$misc::portprop{$_[0]}{$po}{pop}++;
						$misc::portnew{$mc}{$_[0]}{po} = $po;
						if($vl){
							$misc::portnew{$mc}{$_[0]}{vl} = $vl;
						}else{
							$misc::portnew{$mc}{$_[0]}{vl} = $misc::portprop{$_[0]}{$po}{vln};
						}
						print "\n FWS:$mc on $po Vl$misc::portnew{$mc}{$_[0]}{vl}" if $main::opt{v};
						$nspo++;
					}else{
						print "No Ifix:$mc\n" if $main::opt{v};				# happens for switch's own MAC
					}
				}
			}
		}
	}
	print " f$nspo" if !$main::opt{v};
}

#===================================================================
# Get MAC address table from a device with optional community indexing.
#===================================================================

sub Modules {

	my $warn = 0;
	my $nmod = 0;

	my %mde = ();
	my %mcl = ();
	my %msl = ();
	my %mhw = ();
	my %msw = ();
	my %mfw = ();
	my %msn = ();
	my %mmo = ();

	my $so	= $main::dev{$_[0]}{so};

	($session, $error) = Net::SNMP->session(-hostname  => $main::dev{$_[0]}{ip},
						-community => $main::dev{$_[0]}{cm},
						-timeout   => $misc::timeout,
						-version   => $main::dev{$_[0]}{sp},
						-translate => [-octetstring => 0x0],
						-port      => '161');
						
	if($misc::sysobj{$so}{mt}){
		$r = $session->get_table($misc::sysobj{$so}{mt});					# Walk module slots
		$err = $session->error;
		if ($err){print "Mt";print "$err\n" if $main::opt{d};return 1;}else{%msl  = %{$r}}
	}
	if($misc::sysobj{$so}{md}){
		$r = $session->get_table($misc::sysobj{$so}{md});					# Walk module description
		$err = $session->error;
		if ($err){print "Md";print "$err\n" if $main::opt{d};$warn++}else{%mde  = %{$r}}
	}
	if($misc::sysobj{$so}{mc}){
		$r = $session->get_table($misc::sysobj{$so}{mc});					# Walk module classes
		$err = $session->error;
		if ($err){print "Mc";print "$err\n" if $main::opt{d};$warn++}else{%mcl  = %{$r}}
	}
	if($misc::sysobj{$so}{mh}){
		$r = $session->get_table($misc::sysobj{$so}{mh});					# Walk module hardware version
		$err = $session->error;
		if ($err){print "Mh";print "$err\n" if $main::opt{d};$warn++}else{%mhw  = %{$r}}
	}
	if($misc::sysobj{$so}{ms}){
		$r = $session->get_table($misc::sysobj{$so}{ms});					# Walk module software version
		$err = $session->error;
		if ($err){print "Ms";print "$err\n" if $main::opt{d};$warn++}else{%msw  = %{$r}}
	}
	if($misc::sysobj{$so}{mf}){
		$r = $session->get_table($misc::sysobj{$so}{mf});					# Walk module firmware version
		$err = $session->error;
		if ($err){print "Mf";print "$err\n" if $main::opt{d};$warn++}else{%mfw  = %{$r}}
	}
	if($misc::sysobj{$so}{mn}){
		$r = $session->get_table($misc::sysobj{$so}{mn});					# Walk module serial number
		$err = $session->error;
		if ($err){print "Mn";print "$err\n" if $main::opt{d};$warn++}else{%msn  = %{$r}}
	}
	if($misc::sysobj{$so}{mm}){
		$r = $session->get_table($misc::sysobj{$so}{mm});					# Walk module model
		$err = $session->error;
		if ($err){print "Mm";print "$err\n" if $main::opt{d};$warn++}else{%mmo  = %{$r}}
	}
	$session->close;

	foreach my $i (keys (%msl)){
		my $ismod = 0;
		my $s = $msl{$i};
		$i =~ s/$misc::sysobj{$so}{mt}\.//;
		if (exists $mcl{"$misc::sysobj{$so}{mc}.$i"}){
			if($mcl{"$misc::sysobj{$so}{mc}.$i"} eq $misc::sysobj{$so}{mv}){$ismod =1}	# == caused error, if not numeric...
		}else{
			$ismod = 1;
		}
		if($ismod){
			$main::mod{$_[0]}{$i}{sl} = $s;
			$main::mod{$_[0]}{$i}{mo} = (defined $mmo{"$misc::sysobj{$so}{mm}.$i"}?$mmo{"$misc::sysobj{$so}{mm}.$i"}:"-");
			$main::mod{$_[0]}{$i}{de} = (defined $mde{"$misc::sysobj{$so}{md}.$i"}?$mde{"$misc::sysobj{$so}{md}.$i"}:"-");
			$main::mod{$_[0]}{$i}{hw} = (defined $mhw{"$misc::sysobj{$so}{mh}.$i"}?$mhw{"$misc::sysobj{$so}{mh}.$i"}:"-");
			$main::mod{$_[0]}{$i}{fw} = (defined $mfw{"$misc::sysobj{$so}{mf}.$i"}?$mfw{"$misc::sysobj{$so}{mf}.$i"}:"-");
			$main::mod{$_[0]}{$i}{sw} = (defined $msw{"$misc::sysobj{$so}{ms}.$i"}?$msw{"$misc::sysobj{$so}{ms}.$i"}:"-");
			$main::mod{$_[0]}{$i}{sn} = (defined $msn{"$misc::sysobj{$so}{mn}.$i"}?$msn{"$misc::sysobj{$so}{mn}.$i"}:"-");
			$main::mod{$_[0]}{$i}{st} = 0;
			print "\n MOD:$s\t$main::mod{$_[0]}{$i}{mo}\t$main::mod{$_[0]}{$i}{de}"  if $main::opt{v};
			$nmod++;
		}
	}
	print " m$nmod" if !$main::opt{v};
}

1;
