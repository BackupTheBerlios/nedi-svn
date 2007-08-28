#!/usr/bin/perl
#============================================================================
#
# Program: Devsend.pl
# Programmer: Remo Rickli
#
# -> Send commands to devices via telnet <-
#
# DATE	COMMENT
# --------------------------------------------------------------------------
# 14/04/05	initial version.
# 24/07/07	 better OS handling & cmd output.
#============================================================================
#use strict;
#use vars qw($timeout @users %login);

use Net::Telnet::Cisco;
# original my $prompt = '/(?m:^[\w.-]+\s?(?:\(config[^\)]*\))?\s?[\$#>]\s?(?:\(enable\))?\s*$)/';
my $prompt = '/.*?[#>]\s?(?:\(enable\)\s*)?$/';

select(STDOUT); $| = 1;

die "6 arguments needed not " . @ARGV . "!\n" if @ARGV != 6;

my $ip = $ARGV[0];
my $po = $ARGV[1];
my $us = $ARGV[2];
my $pw = $ARGV[3];
my $os = $ARGV[4];
my $cf = $ARGV[5];

ReadConf();

open  (CFG, "$cf" );
my @cfg = <CFG>;
close(CFG);
chomp @cfg;

if(defined $guiauth and $guiauth =~ /i/){
	$login{$us}{pw} = $pw;
}

if ($os eq 'Cat1900'){
	SendC19();
}elsif ($os eq 'Ironware'){
	SendDev('skip-page-display',0);
}elsif ($os eq 'ProCurve'){
	SendDev('no page',1);
}elsif ($os eq 'CatOS'){
	SendDev('set length 0',0);
}elsif ($os eq 'IOS-fw'){
	SendDev('pager 0',0);
}else{
	SendDev('terminal len 0',0);
}

#===================================================================
# Read and parse Configuration file.
#===================================================================
sub ReadConf {

	if (-e "/var/nedi/nedi.conf"	){
		open  (CONF, "/var/nedi/nedi.conf" );
	}elsif (-e "/etc/nedi.conf"){
		open  (CONF, "/etc/nedi.conf" );
	}else{
		die "Dude, where's nedi.conf?\n";
	}
	my @conf = <CONF>;
	close(CONF);
	chomp @conf;

	foreach my $l (@conf){
		if ($l !~ /^[#;]|^$/){
			my @v  = split(/\t+/,$l);
			if ($v[0] eq "usr"){
				$login{$v[1]}{pw} = $v[2];
				$login{$v[1]}{en} = $v[3];
			}elsif ($v[0] eq "timeout"){
				$timeout = $v[1];
			}elsif ($v[0] eq "guiauth"){
				$guiauth = $v[1];
			}
		}
	}
}

#============================================================================
# Send to device
#============================================================================
sub SendDev{

	my $line = "";
	my @out;

	my $session = Net::Telnet::Cisco->new(	Host	=> $ip,
						Port	=> $po,
						Prompt  => $prompt,
						Timeout => $timeout,
						#Input_log  => "log/input.log",
						#output_log  => "log/output.log",
						Errmode => "return",
						);
	open  (LOG, ">$cf-$ip.log" ) or print "$os: can't write to $cf";
	print LOG $session->send_wakeup( 'connect' ) if $_[1];

	if( $session->login( $us, $login{$us}{pw} ) ){
		if ( $login{$us}{en} ){
			if (!$session->enable( $login{$us}{en} ) ){
				$session->close;
				print "$os-enable: " . $session->errmsg;
				return 1;
			}
		}
		print LOG $session->cmd($_[0]);

		foreach my $c (@cfg){
			print LOG "$c\n";
			@out = $session->cmd($c);
			print LOG join("",@out);
			print ".";
			if( $session->errmsg ){
				$session->close;
				close (LOG);
				print "$os-command: " . $session->errmsg;
				return 1;
			}
		}
	}else{
		$session->close;
		close (LOG);
		print "$os-connect: " . $session->errmsg;
		return 1;
	}
	$session->close;
	print " ok";
}

#============================================================================
# Send to Catalyst 1900 device
#============================================================================
sub SendC19{
	
	my $line = "";
	my @out;

	my $session = Net::Telnet::Cisco->new(	Host	=> $ip,
						Port	=> $po,
						Prompt  => $prompt,
						Timeout   => $timeout,
						Errmode => "return",
						);

	if( $session->waitfor('/Enter Selection:.*$/') ){
		open  (LOG, ">$cf-$ip.log" ) or print "SendC19: can't write to $cf";
		print LOG $session->print("k");
		if (!$session->enable( $login{$us}{pw} ) ){
			$session->close;
			print "$os-enable: " . $session->errmsg;
			return 1;
		}
		foreach my $c (@cfg){
			@out = $session->cmd($c);
			print LOG join("",@out);
			print ".";
			if( $session->errmsg ){
				$session->close;
				close (LOG);
				print "$os-command: " . $session->errmsg;
				return 1;
			}
		}
	}else{
		$session->close;
		close (LOG);
		print "$os-menu: " . $session->errmsg;
		return 1;
	}
	$session->close;
	print " ok";
}