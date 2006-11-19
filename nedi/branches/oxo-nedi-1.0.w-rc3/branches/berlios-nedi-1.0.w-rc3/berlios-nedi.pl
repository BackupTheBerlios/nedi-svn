#!/usr/bin/perl

use strict;
use Getopt::Std;
 
use vars qw($nediconf $cdp $lldp $oui);
use vars qw(%nod %dev %int %mod %link %vlan %opt %net %usr); 

$misc::now = time;
require './inc/libmisc.pl';											# Use the miscellaneous nedi library
&misc::ReadConf();

# Disable buffering so we can see what's going on right away.
select(STDOUT); $| = 1;
require "./inc/berlios-libmsq.pl";

	&db::InitDB();
