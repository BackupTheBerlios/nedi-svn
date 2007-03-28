#============================================================================
#
# Program: libmsq.pl
# Programmer: Remo Rickli
#
# -> Functions for the MYSQL Database <-
#
#============================================================================
package db;

use DBI;

#===================================================================
# initialize DB.
# Original script by Paul Venezia
#===================================================================
sub InitDB{

	print "\nMySQL DB will be dropped and re-initialized, bail out if don't want this!\n";
	print "-------------------------------------------------------------------------\n";
	print "MySQL admin user: ";
	my $adminuser = <STDIN>;
	print "MySQL admin pass: "; 
	my $adminpass = <STDIN>;
	my $nedihost = 'localhost';
	if($misc::dbhost ne 'localhost'){
		print "NeDi host (where the discovery runs on: "; 
		$nedihost = <STDIN>;
	}
	chomp($adminuser,$adminpass,$nedihost);
	
#---Connect as admin, drop existing DB and create nedi db and user first.
	$dbh = DBI->connect("DBI:mysql:mysql:$misc::dbhost", "$adminuser", "$adminpass", { RaiseError => 1, AutoCommit => 1});

	my $mysqlVer;
	my $sth = $dbh->prepare("SELECT VERSION()");
	$sth->execute();
	while ((my @f) = $sth->fetchrow) {
		$mysqlVer = $f[0];
	}
	print "MySQL Version   : $mysqlVer\n";
	print "----------------------------------------------------------------------\n";
	$dbh->do("DROP DATABASE IF EXISTS $misc::dbname");
	print "Old MySQL:$misc::dbname dropped!\n";

	print "Creating MySQL:$misc::dbname ";
	$dbh->do("CREATE DATABASE $misc::dbname");
	$dbh->do("GRANT ALL PRIVILEGES ON $misc::dbname.* TO \'$misc::dbuser\'\@\'$nedihost\' IDENTIFIED BY \'$misc::dbpass\'");
	if ($mysqlVer =~ /5\./) {                #fix for mysql 5.0 with old client libs
		$dbh->do("SET PASSWORD FOR \'$misc::dbuser\'\@\'$nedihost\' = OLD_PASSWORD(\'$misc::dbpass\')");
	}
	print "for $misc::dbuser\@$nedihost\n";
	$sth->finish if $sth;
	$dbh->disconnect();

#---Connect as nedi db user and create tables.
	$dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 0});

	print "Creating Tables:";

	print "devices, ";
	$dbh->do("CREATE TABLE devices	(	name VARCHAR(64) NOT NULL UNIQUE, ip INT unsigned, serial VARCHAR(32), type VARCHAR(32),
						firstseen INT unsigned, lastseen INT unsigned, services TINYINT unsigned,
						description VARCHAR(255), os VARCHAR(8), bootimage VARCHAR(64),
						location VARCHAR(255), contact VARCHAR(255),
						vtpdomain VARCHAR(32), vtpmode TINYINT unsigned, snmpversion TINYINT unsigned,
						community VARCHAR(32), cliport SMALLINT unsigned, login VARCHAR(32),
						icon VARCHAR(16), origip INT unsigned, index (name(8)), PRIMARY KEY  (name) )");
 	$dbh->commit;
						
	print "devdel, ";
	$dbh->do("CREATE TABLE devdel	(	device VARCHAR(64) NOT NULL UNIQUE, user VARCHAR(32), time INT unsigned,
						index (device(8)), PRIMARY KEY  (device) )");
 	$dbh->commit;

	print "modules, ";
	$dbh->do("CREATE TABLE modules	(	device VARCHAR(64), slot VARCHAR(32), model VARCHAR(32), description VARCHAR(64), 
						serial VARCHAR(32), hw VARCHAR(16), fw VARCHAR(16), sw VARCHAR(16),
						status TINYINT unsigned, index (device(8)), index (slot(8)) ) ");
 	$dbh->commit;

	print "interfaces, ";
	$dbh->do("CREATE TABLE interfaces(	device VARCHAR(64), ifname VARCHAR(32), ifidx SMALLINT unsigned,
						fwdidx SMALLINT unsigned, type INT unsigned, mac CHAR(12),
						description VARCHAR(64), alias VARCHAR(64), status TINYINT unsigned,
						speed BIGINT unsigned, duplex CHAR(2), vlid SMALLINT unsigned,
						inoct BIGINT unsigned, inerr INT unsigned, outoct BIGINT unsigned, outerr INT unsigned,
						dinoct BIGINT signed, dinerr INT signed, doutoct BIGINT signed, douterr INT signed,
						comment VARCHAR(255), index (device(8)), index (ifname(8)),index (ifidx) )");
 	$dbh->commit;

	print "networks, ";
	$dbh->do("CREATE TABLE networks (	device VARCHAR(64), ifname VARCHAR(32), ip INT unsigned,
						mask INT unsigned, index (device(8)), index (ifname), index (ip) )");
 	$dbh->commit;

	print "links, ";
	$dbh->do("CREATE TABLE links	(	id INT unsigned NOT NULL AUTO_INCREMENT, device VARCHAR(64), ifname VARCHAR(32),
						neighbour VARCHAR(32), nbrifname VARCHAR(32), bandwidth BIGINT unsigned, type CHAR(1),
						power INT unsigned, nbrduplex CHAR(2), nbrvlanid SMALLINT unsigned,
						index (id), index (device(8)), index (ifname(8)), index (neighbour(8)), index (nbrifname(8)),
						PRIMARY KEY  (id) )");
 	$dbh->commit;

	print "configs, ";
	$dbh->do("CREATE TABLE configs	(	device VARCHAR(64) UNIQUE, config MEDIUMTEXT, changes MEDIUMTEXT , time INT unsigned,
						index (device(8)), PRIMARY KEY  (device)  )");
 	$dbh->commit;

	print "nodes, ";
	$dbh->do("CREATE TABLE nodes 	(	name VARCHAR(64), ip INT unsigned, mac CHAR(12) NOT NULL UNIQUE, oui VARCHAR(32),
						firstseen INT unsigned, lastseen INT unsigned, device VARCHAR(64), ifname VARCHAR(32),
						vlanid SMALLINT unsigned, ifmetric TINYINT unsigned, ifupdate INT unsigned,
						ifchanges INT unsigned,	ipupdate INT unsigned, ipchanges INT unsigned,
						iplost INT unsigned, index (name(8)),index(ip),index(mac),index(vlanid), PRIMARY KEY  (mac) )");
 	$dbh->commit;
	print "nodiflog, ";
	$dbh->do("CREATE TABLE nodiflog	(	mac CHAR(12),ifupdate INT unsigned,
						device VARCHAR(64), ifname VARCHAR(32), vlanid SMALLINT unsigned,
						ifmetric TINYINT unsigned, index(mac), index(ifupdate) )");
 	$dbh->commit;
	print "nodiplog, ";
	$dbh->do("CREATE TABLE nodiplog (	mac CHAR(12),ipupdate INT unsigned,
						name VARCHAR(64),ip INT unsigned,index(mac),index(ipupdate) )");
 	$dbh->commit;

	print "stock, ";
	$dbh->do("CREATE TABLE stock	(	serial VARCHAR(32) UNIQUE, type VARCHAR(32),user VARCHAR(32), time INT unsigned,
						location VARCHAR(255), state TINYINT unsigned, index(serial) )");
 	$dbh->commit;
	
	print "stolen, ";
	$dbh->do("CREATE TABLE stolen 	(	name VARCHAR(64), ip INT unsigned, mac CHAR(12) UNIQUE,
						device VARCHAR(64), ifname VARCHAR(32),
						who VARCHAR(32), time INT unsigned, index(mac), PRIMARY KEY  (mac) )");
 	$dbh->commit;

	print "vlans, ";
	$dbh->do("CREATE TABLE vlans	(	device VARCHAR(64), vlanid SMALLINT unsigned,
						vlanname VARCHAR(32), index(vlanid) )");
 	$dbh->commit;

	print "user, ";
	$dbh->do("CREATE TABLE user 	(	name varchar(32) NOT NULL UNIQUE, password varchar(32) NOT NULL default '',
						adm TINYINT unsigned, net TINYINT unsigned,
						dsk TINYINT unsigned, mon TINYINT unsigned,
						mgr TINYINT unsigned, oth TINYINT unsigned,
						email VARCHAR(64),phone VARCHAR(32),
						time INT unsigned,lastseen INT unsigned,
						comment varchar(128) default NULL, language VARCHAR(8), PRIMARY KEY  (name) )");
	$sth = $dbh->prepare("INSERT INTO user (name,password,adm,net,dsk,mon,mgr,oth,time,comment,language) VALUES ( ?,?,?,?,?,?,?,?,?,?,? )");
	$sth->execute ( 'admin','21232f297a57a5a743894a0e4a801fc3','1','1','1','1','1','1',$main::now,'default admin','eng' );
 	$dbh->commit;

	print "monitoring, ";
	$dbh->do("CREATE TABLE monitoring(	device VARCHAR(64) UNIQUE, status INT unsigned, depend VARCHAR(64),
						sms INT unsigned, mail INT unsigned, lastchk INT unsigned,
						uptime INT unsigned, lost INT unsigned, ok INT unsigned, index (device(8)) )");
 	$dbh->commit;

	print "messages, ";
	$dbh->do("CREATE TABLE messages(	id INT unsigned NOT NULL AUTO_INCREMENT, level TINYINT unsigned, time INT unsigned,
						source VARCHAR(64), info VARCHAR(255), index (id), index (source(8)), PRIMARY KEY  (id) )");
 	$dbh->commit;

	print "incidents, ";
	$dbh->do("CREATE TABLE incidents(	id INT unsigned NOT NULL AUTO_INCREMENT, level TINYINT unsigned, device VARCHAR(64),
						deps INT unsigned, firstseen INT unsigned, lastseen INT unsigned, who VARCHAR(32), 
						time INT unsigned, category TINYINT unsigned, comment VARCHAR(255),
						index (id), PRIMARY KEY  (id) )");
 	$dbh->commit;

	print "wlan";
	$dbh->do("CREATE TABLE wlan (mac VARCHAR(12),time INT unsigned, index(mac) )");
	my @wlan = ();
	if (-e "$main::p/inc/wlan.txt"){
		open  ("WLAN", "$main::p/inc/wlan.txt" );
		@wlan = <WLAN>;
		close("WLAN");
		chomp(@wlan);
	}
	$sth = $dbh->prepare("INSERT INTO wlan (mac,time) VALUES ( ?,? )");
	for my $mc (sort @wlan ){ $sth->execute ( $mc,$main::now ) }
 	$dbh->commit;

	print "...done.\n";
	$sth->finish if $sth;
	$dbh->disconnect();

}

#===================================================================
# Read Previous device table.
#===================================================================
sub ReadDev {

	my $npdev = 0;
	my $where = "";

	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 1});
	if($_[0] and $_[1]){$where = "WHERE $_[0] = \"$_[1]\""}
	my $sth = $dbh->prepare("SELECT * FROM devices $where");
	$sth->execute();
	while ((my @f) = $sth->fetchrow_array) {
		$main::dev{$f[0]}{ip} = &misc::Dec2Ip($f[1]);
		$main::dev{$f[0]}{oi} = &misc::Dec2Ip($f[19]);								# Used for community tracking too
		$main::dev{$f[0]}{sn} = $f[2];
		$main::dev{$f[0]}{ty} = $f[3];
		$main::dev{$f[0]}{fs} = $f[4];
		$main::dev{$f[0]}{ls} = $f[5];
		$main::dev{$f[0]}{sv} = $f[6];
		$main::dev{$f[0]}{de} = $f[7];
		$main::dev{$f[0]}{os} = $f[8];
		$main::dev{$f[0]}{bi} = $f[9];
		$main::dev{$f[0]}{lo} = $f[10];
		$main::dev{$f[0]}{co} = $f[11];
		$main::dev{$f[0]}{vd} = $f[12];
		$main::dev{$f[0]}{vm} = $f[13];
		$main::dev{$f[0]}{sp} = $f[14] & 127;
		$main::dev{$f[0]}{hc} = $f[14] & 128;
		$main::dev{$f[0]}{cm} = $f[15];
		$misc::dcomm{$main::dev{$f[0]}{ip}} = $f[15];								# Tie community to IPs,
		$misc::dcomm{$main::dev{$f[0]}{oi}} = $f[15];								# that's all we'll know at first
		$main::dev{$f[0]}{cp} = $f[16];
		$main::dev{$f[0]}{us} = $f[17];
		$main::dev{$f[0]}{ic} = $f[18];
		$npdev++;
	}
	$sth->finish if $sth;
	$dbh->disconnect;
	return "$npdev	devices read from MySQL:$misc::dbname.devices\n";
}

#===================================================================
# Read Previous node table.
#===================================================================
sub ReadNod {

	my $nnod = 0;

	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 1});
	my $sth = $dbh->prepare("SELECT * FROM nodes");
	$sth->execute();
	while ((my @f) = $sth->fetchrow_array) {
		$main::nod{$f[2]}{na} = $f[0];
		$main::nod{$f[2]}{ip} = &misc::Dec2Ip($f[1]);
		$main::nod{$f[2]}{nv} = $f[3];
		$main::nod{$f[2]}{fs} = $f[4];
		$main::nod{$f[2]}{ls} = $f[5];
		$main::nod{$f[2]}{dv} = $f[6];
		$main::nod{$f[2]}{if} = $f[7];
		$main::nod{$f[2]}{vl} = $f[8];
		$main::nod{$f[2]}{im} = $f[9];
		$main::nod{$f[2]}{iu} = $f[10];
		$main::nod{$f[2]}{ic} = $f[11];
		$main::nod{$f[2]}{au} = $f[12];
		$main::nod{$f[2]}{ac} = $f[13];
		$main::nod{$f[2]}{al} = $f[14];
		$nnod++;
	}
	$sth->finish if $sth;
	$dbh->disconnect;
	return "$nnod	nodes read from MySQL:$misc::dbname.nodes\n";
	
}

#===================================================================
# Backup configuration and any changes.
#===================================================================
sub BackupCfg {

	my $pcfg = "";
	my $pchg = "";
	my $chg  = "";
	my $na   = shift (@_);
	my $ncfg = join("\n",@_);

	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 1});
	my $sth = $dbh->prepare("SELECT config,changes FROM configs where device = \"$na\"");
	$sth->execute();

	if($sth->rows == 0 and !$main::opt{t}){										# no previous config found, therefore write new.
		$sth = $dbh->prepare("INSERT INTO configs(device,config,changes,time) VALUES ( ?,?,?,? )");
		$sth->execute ($na,$ncfg,$chg,$main::now);
		print "Bn";
	}else{
		while( my @crow = $sth->fetchrow_array ){								# should only yield 1 entry!
			$pcfg = $crow[0];
			$pchg = $crow[1];
		}
		my @pcfg = split(/\n/,$pcfg);
		my $achg = &misc::GetChanges(\@pcfg, \@_);
		if($achg and !$main::opt{t}){										# Only write new, if changed
			$chg  = $pchg . "#--- " . localtime($main::now) ." ---#\n". $achg;
			$dbh->do("DELETE FROM configs where device = \"$na\"");
			$sth = $dbh->prepare("INSERT INTO configs(device,config,changes,time) VALUES ( ?,?,?,? )");
			$sth->execute ($na,$ncfg,$chg,$main::now);
			print "Bu";
			if($misc::notify =~ /c/){
				my $len = length($achg);
				$sth = $dbh->prepare("INSERT INTO messages(level,time,source,info) VALUES ( ?,?,?,? )");
				$sth->execute ('50',$main::now,$na,"Config changed ($len chars)");
			}
		}
	}
	$sth->finish if $sth;
	$dbh->disconnect;
}

#===================================================================
# Write the devices table
#===================================================================
sub WriteDev {

	my $ndev = 0;

	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 0});
	my $sth = $dbh->prepare("SELECT * FROM devdel");
	$sth->execute();
	my %devdel = ();
	while (my @dd = $sth->fetchrow_array) {
		$devdel{$dd[0]} = "$dd[1]";
	}
	$dbh->do("TRUNCATE devdel");
	$dbh->do("TRUNCATE devices");
	$sth = $dbh->prepare("INSERT INTO devices(	name,ip,serial,type,
							firstseen,lastseen,services,
							description,os,bootimage,
							location,contact,
							vtpdomain,vtpmode,snmpversion,
							community,cliport,login,icon,origip) VALUES ( ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,? )");

	foreach my $na ( sort keys(%main::dev) ){
		if (exists $devdel{$na}){
			if( ! &Delete('configs','device',$na) ){
				die "DB error configs!\n";
			}
			if( ! &Delete('messages','source',$na) ){
				die "DB error messages!\n";
			}
			if( ! &Delete('incidents','device',$na) ){
				die "DB error incidents!\n";
			}
			if (-e "$misc::rrdpath/$na"){
				unlink(glob ("$misc::rrdpath/$na/*"));
				rmdir("$misc::rrdpath/$na");
				print "RRDs and dir $misc::rrdpath/$na deleted!\n"  if $main::opt{d};
			}
			if($misc::notify =~ /d/){
				if( ! &Insert('messages','level,time,source,info',"\"100\",\"$main::now\",\"$na\",\"Device deleted by $devdel{$na}\"") ){
					die "DB error messages!\n";
				}
			}
		}else{
			if(!defined $main::dev{$na}{cm}){$main::dev{$na}{cm}	= ""}
			if(!defined $main::dev{$na}{us}){$main::dev{$na}{us}	= ""}
			if(!defined $main::dev{$na}{cp}){$main::dev{$na}{cp}	= 0}
			if(!defined $main::dev{$na}{sp}){$main::dev{$na}{sp}	= 0}
			if(!defined $main::dev{$na}{hc}){$main::dev{$na}{hc}	= 0}
			if(!defined $main::dev{$na}{oi}){$main::dev{$na}{oi}	= 0}
			if(!$main::dev{$na}{ic}){
				if($main::dev{$na}{sv} > 8){
					$main::dev{$na}{ic} = 'geng';
				}elsif($main::dev{$na}{sv} > 4){
					$main::dev{$na}{ic} = 'gens';
				}elsif($main::dev{$na}{sv} > 1){
					$main::dev{$na}{ic} = 'gens';
				}else{
					$main::dev{$na}{ic} = 'genh';
				}
			}
			my $sphc = $main::dev{$na}{sp} + $main::dev{$na}{hc};
			my $dip = &misc::Ip2Dec($main::dev{$na}{ip});
			my $doi = &misc::Ip2Dec($main::dev{$na}{oi});

			$sth->execute (	$na,
					$dip,
					$main::dev{$na}{sn},
					$main::dev{$na}{ty},
					$main::dev{$na}{fs},
					$main::dev{$na}{ls},
					$main::dev{$na}{sv},
					$main::dev{$na}{de},
					$main::dev{$na}{os},
					$main::dev{$na}{bi},
					$main::dev{$na}{lo},
					$main::dev{$na}{co},
					$main::dev{$na}{vd},
					$main::dev{$na}{vm},
					$sphc,
					$main::dev{$na}{cm},
					$main::dev{$na}{cp},
					$main::dev{$na}{us},
					$main::dev{$na}{ic},
					$doi	);
			$ndev++;
		}
	}
	$dbh->commit;
	$sth->finish if $sth;
	$dbh->disconnect;
	return "$ndev	devices written to MySQL:$misc::dbname.devices\n";
}

#===================================================================
# Write the interfaces table
#===================================================================
sub WriteInt {

	my $nint = 0;
	my $ntrf = 0;

	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 0});
	my $sth = $dbh->prepare("SELECT * FROM interfaces");
	$sth->execute();
	while ((my @f) = $sth->fetchrow_array) {
		if(exists($main::int{$f[0]}{$f[2]}) and defined($f[12]) ){$main::int{$f[0]}{$f[2]}{dio} = $main::int{$f[0]}{$f[2]}{ioc} - $f[12]}
		if(exists($main::int{$f[0]}{$f[2]}) and defined($f[13]) ){$main::int{$f[0]}{$f[2]}{die} = $main::int{$f[0]}{$f[2]}{ier} - $f[13]}
		if(exists($main::int{$f[0]}{$f[2]}) and defined($f[14]) ){$main::int{$f[0]}{$f[2]}{doo} = $main::int{$f[0]}{$f[2]}{ooc} - $f[14]}
		if(exists($main::int{$f[0]}{$f[2]}) and defined($f[15]) ){$main::int{$f[0]}{$f[2]}{doe} = $main::int{$f[0]}{$f[2]}{oer} - $f[15]}

	}
	$sth->finish if $sth;
	$dbh->do("TRUNCATE interfaces") if (!$_[0]);
	$sth = $dbh->prepare("INSERT INTO interfaces(device,ifname,ifidx,fwdidx,type,mac,description,alias,status,speed,duplex,vlid,
							inoct,inerr,outoct,outerr,dinoct,dinerr,doutoct,douterr,comment) 
							VALUES ( ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,? )");

	foreach my $dv ( sort keys(%main::int) ){
		foreach my $i ( sort keys( %{$main::int{$dv}} ) ){
			$sth->execute (	$dv,
					$main::int{$dv}{$i}{ina},
					$i,
					$main::int{$dv}{$i}{fwd},
					$main::int{$dv}{$i}{typ},
					$main::int{$dv}{$i}{mac},
					$main::int{$dv}{$i}{des},
					$main::int{$dv}{$i}{ali},
					$main::int{$dv}{$i}{sta},
					$main::int{$dv}{$i}{spd},
					$main::int{$dv}{$i}{dpx},
					$main::int{$dv}{$i}{vln},
					$main::int{$dv}{$i}{ioc},
					$main::int{$dv}{$i}{ier},
					$main::int{$dv}{$i}{ooc},
					$main::int{$dv}{$i}{oer},
					$main::int{$dv}{$i}{dio},
					$main::int{$dv}{$i}{die},
					$main::int{$dv}{$i}{doo},
					$main::int{$dv}{$i}{doe},
					$main::int{$dv}{$i}{com} );
			$nint++;
			if($misc::notify =~ /t/ and $misc::rrdstep and $main::int{$dv}{$i}{spd}){
				my $rioct = int( $main::int{$dv}{$i}{dio} * 800 / ($misc::rrdstep * $main::int{$dv}{$i}{spd}) );
				my $rooct = int( $main::int{$dv}{$i}{doo} * 800 / ($misc::rrdstep * $main::int{$dv}{$i}{spd}) );
				my $rierr = 0;
				my $roerr = 0;
				if($main::int{$dv}{$i}{dio}){$rierr = int( $main::int{$dv}{$i}{die} * 100 / $main::int{$dv}{$i}{dio})}
				if($main::int{$dv}{$i}{doo}){$roerr = int( $main::int{$dv}{$i}{doe} * 100 / $main::int{$dv}{$i}{doo})}

				if($rioct > 75){
					if( ! &db::Insert('messages','level,time,source,info',"\"200\",\"$main::now\",\"$dv\",\"Average input traffic on $main::int{$dv}{$i}{ina} is $rioct% for $misc::rrdstep seconds!\"") ){
						die "DB error messages!\n";
					}
					$ntrf++;
				}elsif($rioct > 50){
					if( ! &db::Insert('messages','level,time,source,info',"\"100\",\"$main::now\",\"$dv\",\"Average input traffic on $main::int{$dv}{$i}{ina} is $rioct% for $misc::rrdstep seconds!\"") ){
						die "DB error messages!\n";
					}
					$ntrf++;
				}
				if($rooct > 75){
					if( ! &db::Insert('messages','level,time,source,info',"\"200\",\"$main::now\",\"$dv\",\"Average output traffic on $main::int{$dv}{$i}{ina} is $rooct% for $misc::rrdstep seconds!\"") ){
						die "DB error messages!\n";
					}
					$ntrf++;
				}elsif($rooct > 50){
					if( ! &db::Insert('messages','level,time,source,info',"\"100\",\"$main::now\",\"$dv\",\"Average output traffic on $main::int{$dv}{$i}{ina} is $rooct% for $misc::rrdstep seconds!\"") ){
						die "DB error messages!\n";
					}
					$ntrf++;
				}
				if($rierr > 1){
					if( ! &db::Insert('messages','level,time,source,info',"\"200\",\"$main::now\",\"$dv\",\"Average input errors on $main::int{$dv}{$i}{ina} are $rierr% for $misc::rrdstep seconds!\"") ){
						die "DB error messages!\n";
					}
					$ntrf++;
				}
				if($roerr > 1){
					if( ! &db::Insert('messages','level,time,source,info',"\"200\",\"$main::now\",\"$dv\",\"Average output errors on $main::int{$dv}{$i}{ina} are $rierr% for $misc::rrdstep seconds!\"") ){
						die "DB error messages!\n";
					}
					$ntrf++;
				}
			}
		}
	}
	$dbh->commit;
	$sth->finish if $sth;
	$dbh->disconnect;
	return "$nint	interfaces (causing $ntrf messages) written to MySQL:$misc::dbname.interfaces\n";
}

#===================================================================
# Write the modules table
#===================================================================
sub WriteMod {

	my $nmod = 0;

	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 0});
	$dbh->do("TRUNCATE modules") if (!$_[0]);
	my $sth = $dbh->prepare("INSERT INTO modules(device,slot,model,description,serial,hw,fw,sw,status) VALUES ( ?,?,?,?,?,?,?,?,? )");

	foreach my $dv ( sort keys(%main::mod) ){
		foreach my $i ( sort keys( %{$main::mod{$dv}} ) ){
       		$sth->execute (	$dv,
				$main::mod{$dv}{$i}{sl},
				$main::mod{$dv}{$i}{mo},
				$main::mod{$dv}{$i}{de},
				$main::mod{$dv}{$i}{sn},
				$main::mod{$dv}{$i}{hw},
				$main::mod{$dv}{$i}{fw},
				$main::mod{$dv}{$i}{sw},
				$main::mod{$dv}{$i}{st} );
			$nmod++;
		}
	}
	$dbh->commit;
	$sth->finish if $sth;
	$dbh->disconnect;
	return "$nmod	modules written to MySQL:$misc::dbname.modules\n";
}

#===================================================================
# Write the network table
#===================================================================
sub WriteNet {

	my $nnet = 0;

	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 0});
	$dbh->do("TRUNCATE networks") if (!$_[0]);
	my $sth = $dbh->prepare("INSERT INTO networks(	device,ifname,ip,mask) VALUES ( ?,?,?,? )");

	foreach my $dv ( sort keys(%main::net) ){
		foreach my $n ( sort keys( %{$main::net{$dv}} ) ){
			my $dn = &misc::Ip2Dec($n);
			my $dm = &misc::Ip2Dec($main::net{$dv}{$n}{msk});

       		$sth->execute (	$dv,
				$main::net{$dv}{$n}{ifn},
				$dn,
				$dm );
			$nnet++;
		}
	}
	$dbh->commit;
	$sth->finish if $sth;
	$dbh->disconnect;
	return "$nnet	networks written to MySQL:$misc::dbname.networks\n";
}

#===================================================================
# Write the link table
#===================================================================
sub WriteLink {

	my $nlink = 0;

	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 0});
	$dbh->do("DELETE FROM links where type != \"S\"") if (!$_[0]);
	my $sth = $dbh->prepare("INSERT INTO links(device,ifname,neighbour,nbrifname,bandwidth,type,power,nbrduplex,nbrvlanid) VALUES ( ?,?,?,?,?,?,?,?,? )");

	foreach my $dv ( sort keys(%main::link) ){
		foreach my $i ( sort keys( %{$main::link{$dv}} ) ){
			foreach my $ne ( sort keys( %{$main::link{$dv}{$i}} ) ){
				foreach my $ni ( sort keys( %{$main::link{$dv}{$i}{$ne}} ) ){
				
					if(!defined $main::link{$dv}{$i}{$ne}{$ni}{pr}){$main::link{$dv}{$i}{$ne}{$ni}{pr} = 0}

					$sth->execute (	$dv,$i,$ne,$ni,
							$main::link{$dv}{$i}{$ne}{$ni}{bw},
							$main::link{$dv}{$i}{$ne}{$ni}{ty},
							$main::link{$dv}{$i}{$ne}{$ni}{pr},
							$main::link{$dv}{$i}{$ne}{$ni}{du},
							$main::link{$dv}{$i}{$ne}{$ni}{vl} );
					$nlink++;
				}
			}
		}
	}
	$dbh->commit;
	$sth->finish if $sth;
	$dbh->disconnect;
	return "$nlink	links written to MySQL:$misc::dbname.links\n";
}

#===================================================================
# Write the vlan table
#===================================================================
sub WriteVlan {

	my $nvlans = 0;

	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 0});
	$dbh->do("TRUNCATE vlans") if (!$_[0]);
	my $sth = $dbh->prepare("INSERT INTO vlans(device,vlanid,vlanname) VALUES ( ?,?,? )");

	foreach my $dv ( sort keys(%main::vlan) ){
		foreach my $i ( sort keys( %{$main::vlan{$dv}} ) ){
			$sth->execute ( $dv,$i,$main::vlan{$dv}{$i} );
			$nvlans++;
		}
	}
	$dbh->commit;
	$sth->finish if $sth;
	$dbh->disconnect;
	return "$nvlans	vlans written to MySQL:$misc::dbname.vlans\n";
}

#===================================================================
# Remove Devices from Stock, which are discovered on the network.
#===================================================================
sub UnStock {

	my %stock = ();
	
	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 0});
	my $nrm = 0;

	my $sth = $dbh->prepare("SELECT * FROM stock");
	$sth->execute();
	while ((my @s) = $sth->fetchrow_array) {
		$stock{$s[0]}++;
	}

	foreach my $na (keys %main::dev){
		if (exists $stock{$main::dev{$na}{sn}} ){
			$dbh->do("DELETE FROM stock where serial = \"$main::dev{$na}{sn}\"");
			$nrm++;
			if($misc::notify =~ /d/){
				if( ! &Insert('messages','level,time,source,info',"\"50\",\"$main::now\",\"$na\",\"Discovered device $main::dev{$na}{sn} removed from stock.\"") ){
					die "DB error messages!\n";
				}
			}
			print "Discovered device $main::dev{$na}{sn} removed from stock.\n" if $main::opt{v};
		}
	}
	$dbh->commit;
	$sth->finish if $sth;
	$dbh->disconnect;

	return "$nrm	devices removed from MySQL:$misc::dbname.stock\n";
}

#===================================================================
# Write the nodes table
#===================================================================
sub WriteNod {

	my $nnod = 0;

	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 0});
	my $sth = $dbh->prepare("SELECT * FROM stolen");
	$sth->execute();
	my %stomac = ();
	while ((my @smac) = $sth->fetchrow_array) {
		$stomac{$smac[2]} = "$smac[6]";
	}
	$dbh->do("TRUNCATE nodes");
	$sth = $dbh->prepare("INSERT INTO nodes(	name,ip,mac,oui,
							firstseen,lastseen,
							device,ifname,vlanid,
							ifmetric,ifupdate,ifchanges,
							ipupdate,ipchanges,iplost) VALUES ( ?,?,?,?,?,?,?,?,?,?,?,?,?,?,? )");

	foreach my $mc ( sort keys(%main::nod) ){
		if (exists $stomac{$mc} and $main::nod{$mc}{ls} == $main::now and $misc::notify =~ /n/){
			if( ! &db::Insert('messages','level,time,source,info',"\"150\",\"$main::now\",\"$mc\",\"Node has reappeared!\"") ){
				die "DB error messages!\n";
			}
			&mon::SendMail("Stolen Node Alert!","Node $mc has reappeared with $main::nod{$mc}{ip} on $main::nod{$mc}{dv} $main::nod{$mc}{if}!");
		}
		if(!defined $main::nod{$mc}{na}){$main::nod{$mc}{na}	= "-"}
		if(!defined $main::nod{$mc}{ip}){$main::nod{$mc}{ip}	= "0"}
		if(!defined $main::nod{$mc}{vl}){$main::nod{$mc}{vl}	= "0"}

		my $dip = &misc::Ip2Dec($main::nod{$mc}{ip});

		$sth->execute (	$main::nod{$mc}{na},
				$dip,
				$mc,
				$main::nod{$mc}{nv},
				$main::nod{$mc}{fs},
				$main::nod{$mc}{ls},
				$main::nod{$mc}{dv},
				$main::nod{$mc}{if},
				$main::nod{$mc}{vl},
				$main::nod{$mc}{im},
				$main::nod{$mc}{iu},
				$main::nod{$mc}{ic},
				$main::nod{$mc}{au},
				$main::nod{$mc}{ac},
				$main::nod{$mc}{al} );
		$nnod++;
	}
	$dbh->commit;
	$sth->finish if $sth;
	$dbh->disconnect;
	return "$nnod	nodes written to MySQL:$misc::dbname.nodes\n";
}

#===================================================================
# Update WLAN table.
#===================================================================
sub WlanUp {

	use File::Find;

	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 0});
	my $sth = $dbh->prepare("SELECT * FROM wlan");
	$sth->execute();

	while ((my @wrow) = $sth->fetchrow_array) {
			my $mc = $wrow[0];
			$ap{$mc} = $main::now;
	}
	my $wprev = keys %ap;
	print "$wprev	old Wlan entries read.\n";

	find(\&misc::GetAp, $main::opt{w});										# Calls GetAp() in libmisc.pl

	$dbh->do("TRUNCATE wlan");
	$sth = $dbh->prepare("INSERT INTO wlan(mac,time) VALUES ( ?,? )");
	for my $mc (sort(keys %ap) ){ $sth->execute ( $mc,$ap{$mc} ) }
	$dbh->commit;
	$sth->finish if $sth;
	$dbh->disconnect;

	my $wnew = scalar keys %ap;
	return "$wnew	new Wlan entries written.\n";
}

#===================================================================
# Read Monitor table.
#===================================================================
sub ReadMon {

	my $nmon  = 0;
	my $where = "";
	
	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 1});
	if($_[0] and $_[1]){$where = "WHERE $_[0] = \"$_[1]\""}
	my $sth = $dbh->prepare("SELECT * FROM monitoring $where");
	$sth->execute();
	while ((my @f) = $sth->fetchrow_array) {
		my $d = $f[0];
		$main::mon{$d}{st} = $f[1];
		$main::mon{$d}{dp} = $f[2];
		$main::mon{$d}{ss} = $f[3];
		$main::mon{$d}{ml} = $f[4];
		$main::mon{$d}{lc} = $f[5];
		$main::mon{$d}{ut} = $f[6];
		$main::mon{$d}{lt} = $f[7];
		$main::mon{$d}{ok} = $f[8];
		$nmon++;
	}
	$sth->finish if $sth;
	$dbh->disconnect;
	return "$nmon	monitor entries read from MySQL:$misc::dbname.monitoring\n";
}

#===================================================================
# Read User table.
#===================================================================
sub ReadUser {

	my $nusr  = 0;
	my $where = "";
	if($_[0] and $_[1]){$where = "WHERE $_[0] = \"$_[1]\""}

	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 1});
	my $sth = $dbh->prepare("SELECT * FROM user $where");
	$sth->execute();
	while ((my @f) = $sth->fetchrow_array) {
		my $u = $f[0];
		$main::usr{$u}{ml} = $f[8];
		$main::usr{$u}{ss} = $f[9];
		$main::usr{$u}{lg} = $f[13];
		$nusr++;
	}
	$sth->finish if $sth;
	$dbh->disconnect;
	return "$nusr	users read from MySQL:$misc::dbname.user\n";
}

#===================================================================
# Update DB value(s)
# 
#===================================================================
sub Update {

	my @setq = ();
	my @matq = ();
	my $table = shift (@_);
	my %scol = %{shift (@_)};
	my %mcol = %{shift (@_)};

	foreach my $s (keys %scol){
		push(@setq,"$s=\"$scol{$s}\"");
	}
	my $set = join(',',@setq);
	
	foreach my $m (keys %mcol){
		push(@matq,"$m=\"$mcol{$m}\""); 
	}
	my $match = join(' AND ',@matq);
	
	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 1});
	my $r = $dbh->do("UPDATE $table SET $set where $match");
	$dbh->disconnect;
	return $r;
}

#===================================================================
# Insert DB Record
#===================================================================
sub Insert {

	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 1});
	my $r = $dbh->do("insert into $_[0] ($_[1]) values ($_[2])");
	$dbh->disconnect;
	return $r;
}

#===================================================================
# Delete DB Record
#===================================================================
sub Delete {

	my $dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 1});
	my $r = $dbh->do("delete from  $_[0] where $_[1] = \"$_[2]\"");
	$dbh->disconnect;
	return $r;
}

1;
