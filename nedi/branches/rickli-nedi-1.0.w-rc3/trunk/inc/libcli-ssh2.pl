#============================================================================
#
# Program: libcli.pl
# Programmer: Remo Rickli, dcr
#
# -> Net::Telnet/SSH (binary)  based Functions <-
#
#============================================================================
package cli;
use Net::Telnet::Cisco;

# original my $prompt = '/(?m:^[\w.-]+\s?(?:\(config[^\)]*\))?\s?[\$#>]\s?(?:\(enable\))?\s*$)/';
my $prompt = '/.+?[#>]\s?(?:\(enable\)\s*)?$/';

sub go{
	use Net::SSH2;

	print "pw:";
	my $pw = <STDIN>;
	chomp($pw);

	my $ssh2 = Net::SSH2->new();

	print "C";
	$ssh2->connect($main::opt{t}) or die;

	print "A";
	if ($ssh2->auth_password('rickli',$pw)) {
		print "I";
		my $cfg = $ssh2->channel();
		#$cfg->exec('more config.txt');
		$cfg->shell('term len 0');
		$cfg->exec('sh run');

		#my $sftp = $ssh2->sftp();
		#my $cfg = $sftp->open('config.txt') or die;

#		die "Can't get config.txt" unless 
#		$ssh2->scp_get('config.txt', $cfg);
#		$cfg->seek(0, 0);

		print $_ while <$cfg>;
	}else{
		print "L";
	}
}

#============================================================================
# Map the port to be used for telnet according to config.
#============================================================================
sub MapTp{


	my $tepo = 23;
	if ($misc::map{$_[0]}{cp}){
		$tepo = $misc::map{$_[0]}{cp};
		print "M$tepo " if $main::opt{d};
	}
	return $tepo;
}

#============================================================================
# Find login that works.
# This will only result in multiple tries on new devices, or if username pw changed.
#============================================================================
sub PrepDev{

	my $nok = 2;
	my $us = "";
	my @users = @misc::users;
	$main::dev{$_[0]}{cp} = &MapTp($main::dev{$_[0]}{ip});
	if ( defined $main::dev{$_[0]}{us} and $main::dev{$_[0]}{us} ne ""){					# Build user list, with priority on db entry of device.
		unshift(@users,$main::dev{$_[0]}{us} );
	}
	if( $main::dev{$_[0]}{os} eq "Cat1900" and $misc::cliaccess =~ /t/ ){
		do {
			$us = shift (@users);
			print " $us" if $main::opt{d};
			my $session = Net::Telnet::Cisco->new(	Host	=> $main::dev{$_[0]}{ip},
								Port	=> $main::dev{$_[0]}{cp},
								Prompt  => $prompt,
								Timeout => $misc::timeout,
								Errmode	=> 'return'
								);
		
			if( defined($session) ){
				if( $session->waitfor('/Enter Selection:.*$/') ){
					$session->print("k");
					if ($session->enable( $misc::login{$us}{pw} ) ){
						$nok = 0;
					}else{
						print "Te";
					}
				}
				$session->close;
			}else{
				print "Tc";
				return 2;
			}
		} while ($#users ne "-1" and $nok);								# And stop once a login worked or we ran out of them.
	}elsif( $main::dev{$_[0]}{os} =~ /IOS|CatOS|Ironware/){
		do {
			$us = shift (@users);
			print " U:$us" if $main::opt{d};
			my $user = $us;
			if($us =~ /^N-/){$user = ""}
			if($misc::cliaccess =~ /s/){
				eval {
				#my $ssh = Net::SSH::Perl->new($main::dev{$_[0]}{ip});
				my $ssh = Net::SSH::Perl->new($main::dev{$_[0]}{ip}, options => ["BatchMode yes", 
													"RhostsAuthentication no",
													#"UserKnownHostFile /dev/null",
													#"GlobalKnownHostFile /dev/null",
													"protocol => 2" ]);

					$ssh->login($user, $misc::login{$us}{pw});
					my ($stdout, $stderr, $exit) = $ssh->cmd("exit");
					if ($stderr) {
						print "Hl";
					}else{
						$nok = 0;
						$main::dev{$_[0]}{cp} = 22;
					}
				};
			}else{
				$@ = " (SSH not set)";
			}
			print $@ if $main::opt{d};
			if ($@ and $misc::cliaccess =~ /t/){		
				$main::dev{$_[0]}{cp} = &MapTp($main::dev{$_[0]}{ip});
				my $session = Net::Telnet::Cisco->new(	Host	=> $main::dev{$_[0]}{ip},
									Port	=> $main::dev{$_[0]}{cp},
									Prompt  => $prompt,
									Timeout	=> $misc::timeout,
									Errmode	=> 'return'
									);
				if(defined $session){								# To be sure it doesn't bail out...
					if( $session->login( $user,$misc::login{$us}{pw} ) ){
						if ( $misc::login{$us}{en} ){
							$session->enable( $misc::login{$us}{en} );
							if ($session->is_enabled){
								$nok = 0;
							}else{
								print "Te";
							}
						}else{$nok = 0}
					}else{
						print "Tl";
					}
					$session->close;
				}else{
					#$main::dev{$_[0]}{us} = "";
					print "Tc";
					return 2;
				}
			}
		} while ($#users ne "-1" and $nok);								# And stop once a user worked or we ran out of them.
	}elsif( $main::dev{$_[0]}{os} =~ /ProCurve/){
		do {
			$us = shift (@users);
			print " U:$us" if $main::opt{d};
			my $user = $us;
			if($user =~ /^N-/){$user = ""}
			if($misc::cliaccess =~ /s/){
				eval {
					my $ssh = Net::SSH::Perl->new($main::dev{$_[0]}{ip}, options => ["BatchMode yes", 
													"RhostsAuthentication no",
													#"UserKnownHostFile /dev/null",
													#"GlobalKnownHostFile /dev/null",
													"protocol => 2" ]);

					$ssh->login($us, $misc::login{$us}{pw});
					my ($stdout, $stderr, $exit) = $ssh->cmd("exit");
					if ($exit == 0) {
						$nok = 0;
						$main::dev{$_[0]}{cp} = 22;
					}else{
						print "Hl";
					}
				};
			}else{
				$@ = " (SSH not set)";
			}
			print $@ if $main::opt{d};
			if ($@ and $misc::cliaccess =~ /t/){		
				my $session = Net::Telnet->new(	Host	=> $main::dev{$_[0]}{ip},
								Port	=> $main::dev{$_[0]}{cp},
								Prompt  => $prompt,
								Timeout	=> $misc::timeout,
								input_record_separator => "\r",
								Errmode	=> 'return'
								);
				if(defined $session){								# To be sure it doesn't bail out...
					$session->waitfor('/Password:/');
					if( $session->print($misc::login{$us}{pw}) ){
						if ( $misc::login{$us}{en} ){
							$session->print("enable");
							$session->waitfor('/Password:/');
							$session->print($misc::login{$us}{en});
							if (!$session->errmsg){
								$nok = 0;
							}else{
								print "Te";
							}
						}else{$nok = 0}
					}else{
						print "Tl";
					}
					$session->close;
				}else{
					#$main::dev{$_[0]}{us} = "";
					print "Tc";
					return 2;
				}
			}
		} while ($#users ne "-1" and $nok);								# And stop once a user worked or we ran out of them.
	}else{
		return 2;
	}
	if($nok){
		print "Tu";
		#$main::dev{$_[0]}{us} = "";
	}else{
		print "(port $main::dev{$_[0]}{cp}) " if $main::opt{d};
		$main::dev{$_[0]}{us} = $us;
	}
	return $nok;
}

#============================================================================
# Get Ios mac address table.
#============================================================================
sub GetIosMacTab{

	my $line = "";
	my $nspo = 0;
	my @cam  = ();
	my $cmd = "sh mac-address-table dyn";

	if($misc::sysobj{$main::dev{$_[0]}{so}}{bf} eq "CAP"){
		$cmd = "sh bridge";
	}
	if( $main::dev{$_[0]}{cp} == 22 ){
		eval {
			my $ssh = Net::SSH::Perl->new($main::dev{$_[0]}{ip});
			$ssh->login($main::dev{$_[0]}{us}, $misc::login{$main::dev{$_[0]}{us}}{pw});
			my ($stdout, $stderr, $exit) = $ssh->cmd($cmd);
			@cam = split("\n", $stdout);
			
		};
		if ($@){
			print "Ho";
			return 1;
		}
	}else{
		my $session = Net::Telnet::Cisco->new(	Host	=> $main::dev{$_[0]}{ip},
							Port	=> $main::dev{$_[0]}{cp},
							Prompt  => $prompt,
							Timeout	=> $misc::timeout,
							Errmode	=> 'return'
							);
		if( defined($session) ){										# To be sure it doesn't bail out...
			if( $session->login( $main::dev{$_[0]}{us}, $misc::login{$main::dev{$_[0]}{us}}{pw} ) ){
				if ( $misc::login{$main::dev{$_[0]}{us}}{en} ){
					if (!$session->enable( $misc::login{$main::dev{$_[0]}{us}}{en} ) ){
						$session->close;
						print "Te";
						return 1;
					}
				}
				$session->cmd("terminal len 0");
				@cam = $session->cmd($cmd);
				$session->close;
			}else{
				$session->close;
				print "Tl";
				return 1;
			}
			$session->close;
		}else{
			print "Tc";
			return 1;
		}
	}
	foreach my $l (@cam){
		if ($l =~ /\s+(dynamic|forward)\s+/i){
			my $mc = "";
			my $po = "";
			my $vl = "";
			my @mactab = split (/\s+/,$l);
			foreach my $col (@mactab){
				if ($col =~ /^(Gi|Fa|Do|Po)/){
					$po = $col;
					if($po =~ /\.[0-9]/){					# Does it look like a subinterface?
						my @subpo = split(/\./,$po);
						$vl = $subpo[1];
						if($misc::portprop{$_[0]}{$subpo[0]}{upl}){$misc::portprop{$_[0]}{$po}{upl} = 1}	# inhert uplink metric on subinterface
					}
				}
				elsif ($col =~ /^[0-9|a-f]{4}\./){$mc = $col}			
				elsif ($col =~ /^[0-9]{1,4}$/ and !$vl){$vl = $col}
			}
			$mc =~ s/\.//g;
			$po =~ s/FastEthernet/Fa/g;
			$po =~ s/GigabitEthernet/Gi/g;
			$po =~ s/Dot11Radio/Do/g;
			if ($po =~ /^.EC-|^Po[0-9]|channel/){
				$misc::portprop{$_[0]}{$po}{chn} = 1;
			}
			if ($vl !~ /$misc::ignoredvlans/){
				$misc::portprop{$_[0]}{$po}{pop}++;
				$misc::portnew{$mc}{$_[0]}{po} = $po;
				$misc::portnew{$mc}{$_[0]}{vl} = $vl;
				print "\n FWT:$mc on $po vl$vl" if $main::opt{v};
				$nspo++;
			}
		}
	}
	print " f$nspo";
	return 0;
}

#============================================================================
# Get CatOS mac address table.
#============================================================================
sub GetCatMacTab{

	my $line = "";
	my $nspo = 0;
	my @cam  = ();
	my $cmd = "sh cam dyn";

	if( $main::dev{$_[0]}{cp} == 22 ){
		eval {
			my $ssh = Net::SSH::Perl->new($main::dev{$_[0]}{ip});
			$ssh->login($main::dev{$_[0]}{us}, $misc::login{$main::dev{$_[0]}{us}}{pw});
			my ($stdout, $stderr, $exit) = $ssh->cmd("$cmd");
			@cam = split("\n", $stdout);	
		};
		if ($@){
			print "Ho";
			return 1;
		}
	}else{
		my $session = Net::Telnet::Cisco->new(	Host	=> $main::dev{$_[0]}{ip},
							Port	=> $main::dev{$_[0]}{cp},
							Prompt  => $prompt,
							Timeout	=> $misc::timeout,
							Errmode	=> 'return'
						  	);
		
		if( defined($session) ){										# To be sure it doesn't bail out...
			if( $session->login( $main::dev{$_[0]}{us}, $misc::login{$main::dev{$_[0]}{us}}{pw} ) ){
				if ( $misc::login{$main::dev{$_[0]}{us}}{en} ){
					if (!$session->enable( $misc::login{$main::dev{$_[0]}{us}}{en} ) ){
						$session->close;
						print "Te";
						return 1;
					}
				}
				$session->cmd("set length 0");
				@cam = $session->cmd("sh cam dyn");
				$session->close;
			}else{
				$session->close;
				print "Tl";
				return 1;
			}
			$session->close;
		}else{
			print "Tc";
			return 1;
		}
	}
	foreach my $l (@cam){
		if ($l =~ /^[0-9]{1,4}\s/){
			my @mactab = split (/\s+/,$l);
			my $mc = 0;
			my $po = 0;
			my $vl = "";
			foreach my $col (@mactab){
				if ($col =~ /^[0-9]{1,4}$/){$vl = $col}
				elsif ($col =~ /^[0-9|a-f]{2}-/){$mc = $col}			
				elsif ($col =~ /[0-9]{1,2}\/[0-9]{1,2}/){$po = $col}			
			}
			$mc =~ s/-//g;
			if ($po =~ /,|-/){
				$misc::portprop{$_[0]}{$po}{chn} = 1;
			}
			if ($vl !~ /$misc::ignoredvlans/){
				$misc::portprop{$_[0]}{$po}{pop}++;
				$misc::portnew{$mc}{$_[0]}{po} = $po;
				$misc::portnew{$mc}{$_[0]}{vl} = $vl;
				print "\n FWT:$mc on $po vl $vl" if $main::opt{v};
				$nspo++;
			}
		}
	}
	print " f$nspo";
	return 0;
}

#============================================================================
# Get IOS Config and return it in an array.
#============================================================================
sub GetIosCfg{

	my $cmd = "sh run";
	my $go  = 0;
	my $cl	= 0;
	my @run = ();
	my @cfg = ();

	if( $main::dev{$_[0]}{cp} == 22 ){
		eval {
			my $ssh = Net::SSH::Perl->new($main::dev{$_[0]}{ip});
			$ssh->login($main::dev{$_[0]}{us}, $misc::login{$main::dev{$_[0]}{us}}{pw});
			my ($stdout, $stderr, $exit) = $ssh->cmd($cmd);
			@run = split("\n", $stdout);
		};
		if ($@){
			print "Ho";
			return "SSH failed!";
		}
	}else{
		my $session = Net::Telnet::Cisco->new(	Host	=> $main::dev{$_[0]}{ip},
							Port	=> $main::dev{$_[0]}{cp},
							Prompt  => $prompt,
							#Input_log  => "input.log",
							#output_log  => "output.log",
							Timeout => ($misc::timeout + 10),				# Add 10 seconds to build config.
							Errmode	=> 'return'
						  	);
		if( defined($session) ){										# To be sure it doesn't bail out...
			if( $session->login( $main::dev{$_[0]}{us}, $misc::login{$main::dev{$_[0]}{us}}{pw} ) ){
				if ( $misc::login{$main::dev{$_[0]}{us}}{en} ){
					if (!$session->enable( $misc::login{$main::dev{$_[0]}{us}}{en} ) ){
						$session->close;
						print "Te";
						return "Enable failed!\n";
					}
				}
				$session->cmd("terminal length 0");
				@run = $session->cmd($cmd);
				$session->close;
			}else{
				$session->close;
				print "Tl";
				return "Login $main::dev{$_[0]}{us} failed!\n";
			}

		}else{
			print "Tc";
			return "Telnet failed!";
		}
	}
	foreach my $line (@run){
		if ($line =~ /^Current /){$go = 1}
		if ($go){
			$line =~ s/[\n\r]//g;
			print " CFG:$line\n" if $main::opt{v};
			push @cfg,$line;
			$cl++;
		}
	}
	if( $cfg[$#cfg] eq "" ){pop @cfg}										# Remove empty line at the end.
	print "Bi";
	print "$cl lines " if $main::opt{d};
	return @cfg;
}

#============================================================================
# Get CatOS Config and return it in an array.
#============================================================================
sub GetCatCfg{

	my $cmd = "sh conf";
	my $go  = 0;
	my $cl	= 0;
	my @run = ();
	my @cfg = ();

	if( $main::dev{$_[0]}{cp} == 22 ){
		eval {
			my $ssh = Net::SSH::Perl->new($main::dev{$_[0]}{ip});
			$ssh->login($main::dev{$_[0]}{us}, $misc::login{$main::dev{$_[0]}{us}}{pw});
			my ($stdout, $stderr, $exit) = $ssh->cmd($cmd);
print $stdout;
			@run = split("\n", $stdout);
		};
		if ($@){
			print "Ho";
			return "SSH failed!";
		}
	}else{
		my $session = Net::Telnet::Cisco->new(	Host	=> $main::dev{$_[0]}{ip},
							Port	=> $main::dev{$_[0]}{cp},
							Prompt  => $prompt,
							Timeout => ($misc::timeout + 30),				# Add 30 seconds to build config.
							Errmode	=> 'return'
						  	);
		
		if( defined($session) ){										# To be sure it doesn't bail out...
			if( $session->login( $main::dev{$_[0]}{us}, $misc::login{$main::dev{$_[0]}{us}}{pw} ) ){
				if ( $misc::login{$main::dev{$_[0]}{us}}{en} ){
					if (!$session->enable( $misc::login{$main::dev{$_[0]}{us}}{en} ) ){
						$session->close;
						print "Te";
						return "Enable failed!\n";
					}
				}
				$session->cmd("set length 0");
				my @run = $session->cmd($cmd);
				$session->close;
			}else{
				$session->close;
				print "Tl";
				return "Login $main::dev{$_[0]}{us} failed!\n";
			}
		}else{
			print "Tc";
			return "Telnet failed!";
		}
	}
	foreach my $line (@run){
		if ($line =~ /^begin$/){$go = 1}
		if ($go){
			$line =~ s/[\n\r]//g;
			print " CFG:$line\n" if $main::opt{v};
			push @cfg,$line;
			$cl++;
		}
	}
	print "Bc";
	print "$cl lines " if $main::opt{v};
	return @cfg;
}

#============================================================================
# Get Catalyst 1900 Config and return it in an array.
#============================================================================
sub GetC19Cfg{

	my @cfg = ();
	my $cl	= 0;

	my $session = Net::Telnet::Cisco->new(	Host	=> $main::dev{$_[0]}{ip},
						Port	=> $main::dev{$_[0]}{cp},
						Prompt  => $prompt,
						Timeout => ($misc::timeout + 10),				# Add 10 seconds to build config.
						Errmode	=> 'return'
					  	);
	
	if( defined($session) ){										# To be sure it doesn't bail out...
		if( $session->waitfor('/Enter Selection:.*$/') ){
			$session->print("k");
			if ($session->enable( $misc::login{$main::dev{$_[0]}{us}}{pw} ) ){
				my @run = $session->cmd("show run");
			
				shift @run;									# Trim & Remove Pagebreaks
				shift @run;
				foreach my $line (@run){
					if ($line !~ /--More--|^$/){
						$line =~ s/\r|\n//g;
						push @cfg,$line;
						$cl++;
					}		
				}
				print "B9";
				print "$cl lines " if $main::opt{v};
			} else {
				print "Te";
				return "Couldn't enable!\n";
			}
		}else{
			print "To";
			return "Menu timeout!\n";
		}
		$session->close;
		return @cfg;
	}else{
		print "Tc";
		return "Telnet failed!";
	}
}

#============================================================================
# Get Foundry Config and return it in an array.
#============================================================================
sub GetIronCfg{

	my $cmd = "sh run";
	my $go  = 0;
	my $cl	= 0;
	my @run = ();
	my @cfg = ();

	if( $main::dev{$_[0]}{cp} == 22 ){
		eval {
			my $ssh = Net::SSH::Perl->new($main::dev{$_[0]}{ip});
			$ssh->login($main::dev{$_[0]}{us}, $misc::login{$main::dev{$_[0]}{us}}{pw});
			my ($stdout, $stderr, $exit) = $ssh->cmd($cmd);
			@run = split("\n", $stdout);
		};
		if ($@){
			print "Ho";
			return "SSH failed!";
		}
	}else{
		my $session = Net::Telnet::Cisco->new(	Host	=> $main::dev{$_[0]}{ip},
							Port	=> $main::dev{$_[0]}{cp},
							Prompt  => $prompt,
							Timeout => ($misc::timeout + 10),				# Add 10 seconds to build config.
							Errmode	=> 'return'
						  	);
		if( defined($session) ){										# To be sure it doesn't bail out...
			if( $session->login( $main::dev{$_[0]}{us}, $misc::login{$main::dev{$_[0]}{us}}{pw} ) ){
				if ( $misc::login{$main::dev{$_[0]}{us}}{en} ){
					if (!$session->enable( $misc::login{$main::dev{$_[0]}{us}}{en} ) ){
						$session->close;
						print "Te";
						return "Enable failed!\n";
					}
				}
				$session->cmd("skip-page-display");
				@run = $session->cmd($cmd);
				$session->close;
			}else{
				$session->close;
				print "Tl";
				return "Login $main::dev{$_[0]}{us} failed!\n";
			}

		}else{
			print "Tc";
			return "Telnet failed!";
		}
	}
	foreach my $line (@run){
		if ($line =~ /^Current /){$go = 1}
		if ($go){
			$line =~ s/[\n\r]//g;
			print " CFG:$line\n" if $main::opt{v};
			push @cfg,$line;
			$cl++;
		}
	}
	if( $cfg[$#cfg] eq "" ){pop @cfg}										# Remove empty line at the end.
	print "Bi";
	print "$cl lines " if $main::opt{d};
	return @cfg;
}

#============================================================================
# Get HP ProCurve Config and return it in an array.
#============================================================================
sub GetProCfg{

	my $cmd = "sh run";
	my $go  = 0;
	my $cl	= 0;
	my @run = ();
	my @cfg = ();

	if( $main::dev{$_[0]}{cp} == 22 ){
		eval {
			my $ssh = Net::SSH::Perl->new($main::dev{$_[0]}{ip});
			$ssh->login($main::dev{$_[0]}{us}, $misc::login{$main::dev{$_[0]}{us}}{pw});
			my ($stdout, $stderr, $exit) = $ssh->cmd($cmd);
			@run = split("\n", $stdout);
		};
		if ($@){
			print "Ho";
			return "SSH failed!";
		}
	}else{
		my $session = Net::Telnet->new(	Host	=> $main::dev{$_[0]}{ip},
							Port	=> $main::dev{$_[0]}{cp},
							Prompt  => $prompt,
							#input_record_separator => "\r",
							Timeout => ($misc::timeout + 10),				# Add 10 seconds to build config.
							Errmode	=> 'return'
						  	);
		if( defined($session) ){										# To be sure it doesn't bail out...
print "A" if $main::opt{d};
			$session->waitfor('/Password:/');
print "B" if $main::opt{d};
			if( $session->print($misc::login{$main::dev{$_[0]}{us}}{pw}) ){
print "C" if $main::opt{d};
				if ( $misc::login{$main::dev{$_[0]}{us}}{en} ){
print "D" if $main::opt{d};
					$session->print("enable");
print "E" if $main::opt{d};
					$session->waitfor('/Password:/');
					$session->print($misc::login{$main::dev{$_[0]}{us}}{en});
					if (!$session->errmsg){
						$nok = 0;
					}else{
						print "Te";
					}
print "F" if $main::opt{d};
				}
				$session->print("no page");
print "G" if $main::opt{d};
				$session->cmd($cmd);
print "H" if $main::opt{d};
				my $stdout = $session->get();
				$stdout =~ s/\033.{1,7}[hHKr]+?//g;
				@run = split("\r", $stdout);
#open(FILEWRITE, "> procurve.log");
#print FILEWRITE $stdout;
#close FILEWRITE;
				$session->close;
			}else{
				$session->close;
				print "Tl";
				return "Login $main::dev{$_[0]}{us} failed!\n";
			}

		}else{
			print "Tc";
			return "Telnet failed!";
		}
	}
	foreach my $line (@run){
		if ($line =~ /^Running /){$go = 1}
		if ($go){
			$line =~ s/[\n\r]//g;
			print " CFG:$line\n" if $main::opt{v};
			push @cfg,$line;
			$cl++;
		}
	}
	pop @cfg;
	print "Bi";
	print "$cl lines " if $main::opt{d};
	return @cfg;
}


1;
