#!/usr/bin/perl
#============================================================================
#
# Program: Devsend.pl
# Programmer: Remo Rickli
#
# -> Send commands to devices via telnet <-
#
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

if ($os eq "IOS"){
	SendIOS();
}elsif ($os eq "CatOS"){
	SendCat();
}elsif ($os eq "Cat1900"){
	SendC19();
}

#===================================================================
# Read and parse Configuration file.
#===================================================================
sub ReadConf {

	if (-e "../nedi.conf"	){
		open  (CONF, "../nedi.conf" );
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
			my @v  = split(/\s+/,$l);
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
# Send to IOS device
#============================================================================
sub SendIOS{

	my $line = "";
	my @out;

	my $session = Net::Telnet::Cisco->new(	Host	=> $ip,
						Port	=> $po,
						Prompt  => $prompt,
						Timeout => $timeout,
						Errmode => "return",
						);
	if( $session->login( $us, $login{$us}{pw} ) ){
		if ( $login{$us}{en} ){
			if (!$session->enable( $login{$us}{en} ) ){
				$session->close;
				print "SendIOS-en: " . $session->errmsg;
				return 1;
			}
		}
		$session->cmd("terminal len 0");

		open  (LOG, ">$cf-$ip.log" ) or print "SendIOS: can't write to $cf";

		foreach my $c (@cfg){
			@out = $session->cmd($c);
			print LOG join("",@out);
			print ".";
			if( $session->errmsg ){
				$session->close;
				close (LOG);
				print "SendIOS-cmd: " . $session->errmsg;
				return 1;
			}
		}
	}else{
		$session->close;
		close (LOG);
		print "SendIOS: " . $session->errmsg;
		return 1;
	}
	$session->close;
	print " ok";
}

#============================================================================
# Send to CatOS device
#============================================================================
sub SendCat{

	my $line = "";
	my @out;

	my $session = Net::Telnet::Cisco->new(	Host	=> $ip,
						Port	=> $po,
						Prompt  => $prompt,
						Timeout   => $timeout,
						Errmode => "return",
						);
	
	if( $session->login( $us, $login{$us}{pw} ) ){
		if ( $login{$us}{en} ){
			if (!$session->enable( $login{$us}{en} ) ){
				$session->close;
				print "SendCat: " . $session->errmsg;
				return 1;
			}
		}
		$session->cmd("set length 0");

		open  (LOG, ">$cf-$ip.log" ) or print "SendCat: can't write to $cf";

		foreach my $c (@cfg){
			@out = $session->cmd($c);
			print LOG join("",@out);
			print ".";
			if( $session->errmsg ){
				$session->close;
				close (LOG);
				print "SendCat: " . $session->errmsg;
				return 1;
			}
		}
	}else{
		$session->close;
		close (LOG);
		print "SendCat: " . $session->errmsg;
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
		$session->print("k");
		if (!$session->enable( $login{$us}{pw} ) ){
			$session->close;
			print "SendC19: " . $session->errmsg;
			return 1;
		}

		open  (LOG, ">$cf-$ip.log" ) or print "SendC19: can't write to $cf";

		foreach my $c (@cfg){
			@out = $session->cmd($c);
			print LOG join("",@out);
			print ".";
			if( $session->errmsg ){
				$session->close;
				close (LOG);
				print "SendC19: " . $session->errmsg;
				return 1;
			}
		}
	}else{
		$session->close;
		close (LOG);
		print "SendC19: " . $session->errmsg;
		return 1;
	}
	$session->close;
	print " ok";
}