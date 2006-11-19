package db;

use DBI;

#===================================================================
# initialize DB.
# Original script by Paul Venezia
# for berlios
#===================================================================
sub InitDB{

	print "MySQL admin user: ";
	my $adminuser = <STDIN>;
	print "MySQL admin pass: "; 
	my $adminpass = <STDIN>;
	chomp($adminuser,$adminpass);
	
#---Connect as nedi db user and create tables.
	$dbh = DBI->connect("DBI:mysql:$misc::dbname:$misc::dbhost", "$misc::dbuser", "$misc::dbpass", { RaiseError => 1, AutoCommit => 0});

	print "Creating Tables:";

	print "devices, ";
	$dbh->do("CREATE TABLE devices	(	name VARCHAR(64) UNIQUE,ip INT unsigned,serial VARCHAR(32),type VARCHAR(32),
						firstseen INT unsigned,lastseen INT unsigned,services TINYINT unsigned,
						description VARCHAR(255),os VARCHAR(8),bootimage VARCHAR(64),
						location VARCHAR(255),contact VARCHAR(255),
						vtpdomain VARCHAR(32),vtpmode TINYINT unsigned,snmpversion TINYINT unsigned,
						community VARCHAR(32),cliport SMALLINT unsigned,login VARCHAR(32),
						icon VARCHAR(16),index (name(8)) )");
 	$dbh->commit;
						
	print "devdel, ";
	$dbh->do("CREATE TABLE devdel	(	device VARCHAR(64) UNIQUE,user VARCHAR(32),time INT unsigned,index (device(8)) )");
 	$dbh->commit;

	print "modules, ";
	$dbh->do("CREATE TABLE modules	(	device VARCHAR(64), slot VARCHAR(32), model VARCHAR(32), description VARCHAR(64), 
						serial VARCHAR(32), hw VARCHAR(16), fw VARCHAR(16), sw VARCHAR(16),
						status TINYINT unsigned, index (device(8)) ) ");
 	$dbh->commit;

	print "interfaces, ";
	$dbh->do("CREATE TABLE interfaces(	device VARCHAR(64), name VARCHAR(32), ifidx SMALLINT unsigned,
						fwdidx SMALLINT unsigned, type INT unsigned, mac CHAR(12),
						description VARCHAR(64), alias VARCHAR(64), status TINYINT unsigned,
						speed BIGINT unsigned, duplex CHAR(2), vlid SMALLINT unsigned, inoct BIGINT unsigned,
						inerr INT unsigned, outoct BIGINT unsigned, outerr INT unsigned,
						comment VARCHAR(255), index (device(8)),index (name(8)),index (ifidx) )");
 	$dbh->commit;

	print "networks, ";
	$dbh->do("CREATE TABLE networks (	device VARCHAR(64),ifname VARCHAR(32),ip INT unsigned,
						mask INT unsigned,index (device(8)),index (ifname),index (ip) )");
 	$dbh->commit;

	print "links, ";
	$dbh->do("CREATE TABLE links	(	id INT unsigned NOT NULL AUTO_INCREMENT, device VARCHAR(64), ifname VARCHAR(32),
						neighbour VARCHAR(32), nbrifname VARCHAR(32), bandwidth BIGINT unsigned, type CHAR(1),
						power INT unsigned, nbrduplex CHAR(2), nbrvlanid SMALLINT unsigned,  index (id), index (device(8)) )");
 	$dbh->commit;

	print "configs, ";
	$dbh->do("CREATE TABLE configs	(	device VARCHAR(64) UNIQUE,config TEXT,changes TEXT,time INT unsigned,index (device(8)) )");
 	$dbh->commit;

	print "nodes, ";
	$dbh->do("CREATE TABLE nodes 	(	name VARCHAR(64),ip INT unsigned,mac CHAR(12) UNIQUE,oui VARCHAR(32),
						firstseen INT unsigned,lastseen INT unsigned, 
						device VARCHAR(64),ifname VARCHAR(32),vlanid SMALLINT unsigned,
						ifmetric TINYINT unsigned,ifupdate INT unsigned,ifchanges INT unsigned,
						ipupdate INT unsigned,ipchanges INT unsigned,iplost INT unsigned,
						index (name(8)),index(ip),index(mac),index(vlanid) )");
 	$dbh->commit;

	print "stock, ";
	$dbh->do("CREATE TABLE stock	(	serial VARCHAR(32) UNIQUE, type VARCHAR(32),
						user VARCHAR(32),time INT unsigned,index(serial) )");
 	$dbh->commit;
	
	print "stolen, ";
	$dbh->do("CREATE TABLE stolen 	(	name VARCHAR(64), ip INT unsigned, mac CHAR(12) UNIQUE,
						device VARCHAR(64),ifname VARCHAR(32),
						who VARCHAR(32),time INT unsigned,index(mac) )");
 	$dbh->commit;

	print "vlans, ";
	$dbh->do("CREATE TABLE vlans	(	device VARCHAR(64),vlanid SMALLINT unsigned,
						vlanname VARCHAR(32),index(vlanid) )");
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
	$sth->execute ( 'admin','21232f297a57a5a743894a0e4a801fc3','1','1','1','1','1','1',$misc::now,'default admin','eng' );
 	$dbh->commit;

	print "monitoring, ";
	$dbh->do("CREATE TABLE monitoring(	device VARCHAR(64) UNIQUE,status INT unsigned,depend VARCHAR(64),
						sms INT unsigned,mail INT unsigned,lastchk INT unsigned,
						uptime INT unsigned,lost INT unsigned,ok INT unsigned, index (device(8)) )");
 	$dbh->commit;

	print "messages, ";
	$dbh->do("CREATE TABLE messages(	id INT unsigned NOT NULL AUTO_INCREMENT, level TINYINT unsigned, time INT unsigned,
						source VARCHAR(64),info VARCHAR(255), index (id) )");
 	$dbh->commit;

	print "incidents, ";
	$dbh->do("CREATE TABLE incidents(	id INT unsigned NOT NULL AUTO_INCREMENT, level TINYINT unsigned, device VARCHAR(64),
						deps INT unsigned, firstseen INT unsigned, lastseen INT unsigned, who VARCHAR(32), 
						time INT unsigned, category TINYINT unsigned, comment VARCHAR(255), index (id) )");
 	$dbh->commit;

	print "wlan";
	$dbh->do("CREATE TABLE wlan (mac VARCHAR(12),time INT unsigned, index(mac) )");
	my @wlan = ();
	if (-e "./inc/wlan.txt"){
		open  ("WLAN", "./inc/wlan.txt" );
		@wlan = <WLAN>;
		close("WLAN");
		chomp(@wlan);
	}
	$sth = $dbh->prepare("INSERT INTO wlan (mac,time) VALUES ( ?,? )");
	for my $mc (sort @wlan ){ $sth->execute ( $mc,$misc::now ) }
 	$dbh->commit;

	print "...done.\n";
	$sth->finish if $sth;
	$dbh->disconnect();

}
1;
