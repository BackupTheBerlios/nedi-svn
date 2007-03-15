#============================================================================
#
# Program: libcli.pl
# Programmer: Remo Rickli, dcr
#
# -> Net::Telnet/Net::SSH::Perl based Functions <-
#
# Needs quite some perl libs, but works well on OBSD4.  Rather limited at this stage:
#
# Ubuntu install hint
#$ wget http://search.cpan.org/CPAN/authors/id/D/DB/DBROBINS/Net-SSH-Perl-1.30.tar.gz
#$ tar zxvf Net-SSH-Perl-1.30.tar.gz
#$ cd Net-SSH-Perl-1.30
#$ perl Makefile.PL && make
#sudo checkinstall -D --pkgname="Marc-Net-SSH-Perl" --pkgversion="1.30" make install
 
# SSH doesn't support enable at this stage (only 1 command per session)
# Foundry only tested with simple telnet pw/en configs
# HP Procurve is nasty due to lots of escape characters. Only simple telnet pw/en tested as well.
#
# GetCfg is a dummy function, with that there's no need to touch libmisc.pl in case more
# devices are added. This will also help for a complete rewrite (e.g. with DIS)
#
#============================================================================
package cli;
use Net::Telnet::Cisco;

use vars qw($sshnet);

eval 'use Net::SSH::Perl';
if ($@){
	$sshnet = 0;
	print "SSH not available\n" if $main::opt{d};
}else{
	$sshnet = 1;
	print "Net::SSH::Perl loaded\n" if $main::opt{d};
}
# original my $prompt = '/(?m:^[\w.-]+\s?(?:\(config[^\)]*\))?\s?[\$#>]\s?(?:\(enable\))?\s*$)/';
my $prompt = '/.+?[#>]\s?(?:\(enable\)\s*)?$/';

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
# Find login, if device is compatible for mac-address-table or config retrieval
#============================================================================
sub PrepDev{

	my $us  = "";
	my $nok = 2;
	my $na = $_[0];
	my $op = $_[1];
	my @users = @misc::users;

	if($op eq "mac" and $main::dev{$na}{os} !~ /^IOS/){							# Only IOS has support for mac-address stuff
		return 2;
	}	
	if(defined $main::dev{$na}{cp} and $main::dev{$na}{cp} != 0){						# If port not defined, it's new or set to be prepd
		if($main::dev{$na}{us}){									# Do we have a user?
			return 0;										# Lets use that then (clibad=false)
		}else{												# No user but a real port means it failed before
			print " Pu";
			return 2;										#  clibad=2 means very true ;-)
		}
	}else{
		$main::dev{$na}{cp} = &MapTp($main::dev{$na}{ip});						# Go prep device
	}
	if($main::dev{$na}{os} eq "Cat1900"){
		do {
			$us = shift (@users);
			print "P:$us " if $main::opt{d};
			my $session = Net::Telnet::Cisco->new(	Host	=> $main::dev{$na}{ip},
								Port	=> $main::dev{$na}{cp},
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
		} while ($#users ne "-1" and $nok);								# And stop on ok or we ran out of logins
	}elsif( $main::dev{$na}{os} =~ /^(IOS|CatOS|Ironware)/){
		do {
			$us = shift (@users);
			print " P:$us" if $main::opt{d};
			if($sshnet){
				eval {
				my $ssh = Net::SSH::Perl->new($main::dev{$na}{ip}, options => ["BatchMode yes", 
												"RhostsAuthentication no",
												#"UserKnownHostFile /dev/null",
												#"GlobalKnownHostFile /dev/null",
												"protocol => 2" ]);

					$ssh->login($us, $misc::login{$us}{pw});
					my ($stdout, $stderr, $exit) = $ssh->cmd("exit");
					if ($stderr) {
						print "Hl";
					}else{
						$nok = 0;
						$main::dev{$na}{cp} = 22;
					}
				};
			}else{
				$@ = " Hs";
			}
			print $@ if $main::opt{d};
			if ($@){
				my $session = Net::Telnet::Cisco->new(	Host	=> $main::dev{$na}{ip},
									Port	=> $main::dev{$na}{cp},
									Prompt  => $prompt,
									Timeout	=> $misc::timeout,
									Errmode	=> 'return'
									);
				if(defined $session){								# To be sure it doesn't bail out...
					if( $session->login( $us,$misc::login{$us}{pw} ) ){
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
					print "Tc";
					return 2;
				}
			}
		} while ($#users ne "-1" and $nok);								# And stop once a user worked or we ran out of them.
	}elsif( $main::dev{$na}{os} eq "ProCurve"){								# ProCurves throw lots of escape sequences out, which confuse Net::Telnet::Cisco
		do {
			$us = shift (@users);
			print " P:$us" if $main::opt{d};
			if($sshnet){
				eval {
					my $ssh = Net::SSH::Perl->new($main::dev{$na}{ip}, options => ["BatchMode yes", 
													"RhostsAuthentication no",
													#"UserKnownHostFile /dev/null",
													#"GlobalKnownHostFile /dev/null",
													"protocol => 2" ]);

					$ssh->login($us, $misc::login{$us}{pw});
					my ($stdout, $stderr, $exit) = $ssh->cmd("exit");
					if ($exit == 0) {
						$nok = 0;
						$main::dev{$na}{cp} = 22;
					}else{
						print "Hl";
					}
				};
			}else{
				$@ = " Hs";
			}
			print $@ if $main::opt{d};
			if ($@){		
				my $session = Net::Telnet->new(	Host	=> $main::dev{$na}{ip},
								Port	=> $main::dev{$na}{cp},
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
	}else{
		print ":$main::dev{$na}{cp} " if $main::opt{d};
		$main::dev{$na}{us} = $us;
	}
	return $nok;
}

#============================================================================
# Get Ios mac address table.
#============================================================================
sub GetMacTab{

	my $line = "";
	my $nspo = 0;
	my @cam  = ();
	my $cmd = "sh mac-address-table dyn";

	if($main::dev{$_[0]}{os} eq "IOS-wl"){									# Cisco WLan specific...
		$cmd = 'sh bridge | exclude \*\*\*';								# Work around aged (***) forwarding entries
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
			return 2;
		}
	}else{
		my $session = Net::Telnet::Cisco->new(	Host	=> $main::dev{$_[0]}{ip},
							Port	=> $main::dev{$_[0]}{cp},
							Prompt  => $prompt,
							Timeout	=> $misc::timeout,
							Errmode	=> 'return'
							);
		if( defined($session) ){									# To be sure it doesn't bail out...
			if( $session->login( $main::dev{$_[0]}{us}, $misc::login{$main::dev{$_[0]}{us}}{pw} ) ){
				if ( $misc::login{$main::dev{$_[0]}{us}}{en} ){
					if (!$session->enable( $misc::login{$main::dev{$_[0]}{us}}{en} ) ){
						$session->close;
						print "Te";
						return 2;
					}
				}
				$session->cmd("terminal len 0");
				@cam = $session->cmd($cmd);
				$session->close;
			}else{
				$session->close;
				print "Tl";
				return 2;
			}
			$session->close;
		}else{
			print "Tc";
			return 2;
		}
	}
	foreach my $l (@cam){
		if ($l =~ /\s+(dynamic|forward)\s+/i){
			my $mc = "";
			my $po = "";
			my $vl = "";
			my @mactab = split (/\s+/,$l);
			foreach my $col (@mactab){
				if ($col =~ /^(Gi|Fa|Do|Po|Vi)/){
					$po = &misc::Shif($col);
					if($po =~ /\.[0-9]/){							# Does it look like a subinterface?
						my @subpo = split(/\./,$po);
						$vl = $subpo[1];
						if($misc::portprop{$_[0]}{$subpo[0]}{upl}){$misc::portprop{$_[0]}{$po}{upl} = 1}	# inhert uplink metric on subinterface
					}
				}
				elsif ($col =~ /^[0-9|a-f]{4}\./){$mc = $col}			
				elsif ($col =~ /^[0-9]{1,4}$/ and !$vl){$vl = $col}				# Fails if there's an age column :-(
			}
			$mc =~ s/\.//g;
			if ($po =~ /^.EC-|^Po[0-9]|channel/){
				$misc::portprop{$_[0]}{$po}{chn} = 1;
			}
			if ($vl !~ /$misc::ignoredvlans/){
				$misc::portprop{$_[0]}{$po}{pop}++;
				$misc::portnew{$mc}{$_[0]}{po} = $po;
				$misc::portnew{$mc}{$_[0]}{vl} = $vl;
				print "\n FWC:$mc on $po vl$vl" if $main::opt{v};
				$nspo++;
			}
		}
	}
	print " f$nspo";
	return 0;
}

#============================================================================
# Wrapper to get the proper config
#============================================================================
sub GetCfg{

	print " B:$main::dev{$_[0]}{us}:$main::dev{$_[0]}{cp} " if $main::opt{d};

	if($main::dev{$_[0]}{os} =~ /^IOS/){
		&db::BackupCfg( $_[0], &cli::GetIosCfg($_[0]) );
	}elsif($main::dev{$_[0]}{os} eq "CatOS"){
		&db::BackupCfg( $_[0], &cli::GetCatCfg($_[0]) );
	}elsif($main::dev{$_[0]}{os} eq "Cat1900"){
		&db::BackupCfg( $_[0], &cli::GetC19Cfg($_[0]) );
	}elsif($main::dev{$_[0]}{os} eq "Ironware"){
		&db::BackupCfg( $_[0], &cli::GetIronCfg($_[0]) );
	}elsif($main::dev{$_[0]}{os} eq "ProCurve"){
		&db::BackupCfg( $_[0], &cli::GetProCfg($_[0]) );
	}
}

#============================================================================
# Get IOS Config and return it in an array.
#============================================================================
sub GetIosCfg{

	my $cmd = "sh run";
	my $pag = "terminal length 0";
	my $go  = 0;
	my $cl	= 0;
	my @run = ();
	my @cfg = ();

	if($main::dev{$_[0]}{os} eq "IOS-fw"){									# Cisco firewall specific...
		$pag = "pager 0";
	}
			print $pag;
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
							Timeout => ($misc::timeout * 30),			# Increase timeout to build config.
							Errmode	=> 'return'
						  	);
		if( defined($session) ){									# To be sure it doesn't bail out...
			if( $session->login( $main::dev{$_[0]}{us}, $misc::login{$main::dev{$_[0]}{us}}{pw} ) ){
				if ( $misc::login{$main::dev{$_[0]}{us}}{en} ){
					if (!$session->enable( $misc::login{$main::dev{$_[0]}{us}}{en} ) ){
						$session->close;
						print "Te";
						return "Enable failed!\n";
					}
				}
				$session->cmd($pag);
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
		if ($line =~ /^(Current|PIX|FWSM|ASA)\s/){$go = 1}
		if ($go){
			$line =~ s/[\n\r]//g;
			print "\n CFG:$line" if $main::opt{v};
			push @cfg,$line;
			$cl++;
		}
	}
	if( $cfg[$#cfg] eq "" ){pop @cfg}									# Remove empty line at the end.
	print "Bi";
	print "-$cl" if $main::opt{d};
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
							Timeout => ($misc::timeout * 30),			# Increase timeout to build config.
							Errmode	=> 'return'
						  	);
		
		if( defined($session) ){									# To be sure it doesn't bail out...
			if( $session->login( $main::dev{$_[0]}{us}, $misc::login{$main::dev{$_[0]}{us}}{pw} ) ){
				if ( $misc::login{$main::dev{$_[0]}{us}}{en} ){
					if (!$session->enable( $misc::login{$main::dev{$_[0]}{us}}{en} ) ){
						$session->close;
						print "Te";
						return "Enable failed!\n";
					}
				}
				$session->cmd("set length 0");
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
		if ($line =~ /^begin$/){$go = 1}
		if ($go){
			$line =~ s/[\n\r]//g;
			print "\n CFG:$line" if $main::opt{v};
			push @cfg,$line;
			$cl++;
		}
	}
	print "Bc";
	print "-$cl" if $main::opt{d};
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
				print "-$cl" if $main::opt{d};
			} else {
				print "Te";
				return "Enable failed!\n";
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
							Timeout => ($misc::timeout * 30),			# Increase timeout to build config.
							Errmode	=> 'return'
						  	);
		if( defined($session) ){									# To be sure it doesn't bail out...
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
			print "\n CFG:$line" if $main::opt{v};
			push @cfg,$line;
			$cl++;
		}
	}
	if( $cfg[$#cfg] eq "" ){pop @cfg}									# Remove empty line at the end.
	print "Bf";
	print "-$cl" if $main::opt{d};
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
							Timeout => ($misc::timeout * 30),			# Increase timeout to build config.
							Errmode	=> 'return'
						  	);
		if( defined($session) ){									# To be sure it doesn't bail out...
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
						return "Enable failed!\n";
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
			print "\n CFG:$line" if $main::opt{v};
			push @cfg,$line;
			$cl++;
		}
	}
	pop @cfg;
	print "Bh";
	print "-$cl" if $main::opt{d};
	return @cfg;
}

1;
