<?
/*
#============================================================================
# Program: Topology-Multicast.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 15/04/05	initial version.
# 20/03/06	new SQL query support
*/

$bg1	= "99DDCC";
$bg2	= "AAEEDD";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");
include_once ('inc/libdev.php');

$_GET = sanitize($_GET);
$rtr = isset($_GET['rtr']) ? $_GET['rtr'] : "";

$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('devices','s','*','','',array('services'),array('>'),array('3') );
$res	= @DbQuery($query,$link);
if($res){
	while( ($d = @DbFetchRow($res)) ){
		$devtyp[$d[0]] = $d[3];
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}

?>
<h1>Multicast Tool</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>" name="mrout">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=<?=$bg1?> ><th width=80><a href=<?=$_SERVER['PHP_SELF']?> >
<img src=img/32/cam.png border=0 title="Display multicast routing table of L3 device">
</a></th>
<th>
Router
<SELECT size=1 name="rtr">
<OPTION VALUE="">---
<?
foreach (array_keys($devtyp) as $r ){
	echo "<OPTION VALUE=\"$r\" ";
	if($rtr == $r){echo "selected";}
	echo " >$r\n";
}
echo "</select>";
?>
</th><th width=80>
<input type="submit" value="Show">
</th>
</tr></table></form>
<?
if ($rtr) {
	$query	= GenQuery('devices','s','*','','',array('name'),array('='),array($rtr) );
	$res	= @DbQuery($query,$link);
	$ndev	= @DbNumRows($res);
	if ($ndev != 1) {
		echo "<h4>$rtr $n1rmsg</h4>";
		@DbFreeResult($res);
		die;
	}else{
		$dev	= @DbFetchRow($res);
		$ip	= long2ip($dev[1]);
		$sv	= Syssrv($dev[6]);
		$ud = rawurlencode($dev[0]);
		@DbFreeResult($res);
?>
<h2>General Info</h2>
<table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bga?>><th bgcolor=#<?=$bia?> width=140>
<a href=Devices-Status.php?dev=<?=$ud?> ><img src=img/dev/<?=$dev[18]?>.png title="<?=$dev[3]?>" border=0></a>
<br><?=$dev[0]?></th><td><a href=telnet://<?=$ip?>><?=$ip?></a></td></tr>
<tr bgcolor=#<?=$bgb?>><th bgcolor=#<?=$bg1?>>Services</th><td><?=($sv)?$sv:"&nbsp;"?></td></tr>
<tr bgcolor=#<?=$bga?>><th bgcolor=#<?=$bg1?>>Bootimage</th><td><?=$dev[9]?></td></tr>
<tr bgcolor=#<?=$bga?>><th bgcolor=#<?=$bg1?>>Description</th><td><?=$dev[7]?></td></tr>
<tr bgcolor=#<?=$bgb?>><th bgcolor=#<?=$bg1?>>Location</th><td><?=$dev[10]?></td></tr>
<tr bgcolor=#<?=$bga?>><th bgcolor=#<?=$bg1?>>Contact</th><td><?=$dev[11]?></td></tr>
<tr bgcolor=#<?=$bga?>><th bgcolor=#<?=$bg1?>>SNMP</th><td><?=$dev[15]?> (Version <?=$dev[14]?>)</td></tr>
</table>
<h2>Actual Multicast Routing Table</h2>
<table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bg2?>>
<th width=20%><img src=img/32/cam.png><br>Source</th>
<th width=20%><img src=img/32/nglb.png><br>Destination</th>
<th><img src=img/32/tap.png><br>Bit/s</th>
<th><img src=img/32/clock.png><br>Last Used</th>
<?
		error_reporting(1);
		snmp_set_quick_print(1);

		foreach (snmprealwalk($ip,$dev[15],'.1.3.6.1.4.1.9.10.2.1.1.2.1.12') as $ix => $val){
			$prun[substr(strstr($ix,'9.10.2.1.1.2.1.'),18)] = $val;					// cut string at beginning with strstr first, because it depends on snmpwalk & Co being installed!
		}
		foreach (snmprealwalk($ip,$dev[15],'.1.3.6.1.4.1.9.10.2.1.1.2.1.19') as $ix => $val){
			$bps[substr(strstr($ix,'9.10.2.1.1.2.1.'),18)] = $val;
		}
		foreach (snmprealwalk($ip,$dev[15],'.1.3.6.1.4.1.9.10.2.1.1.2.1.23') as $ix => $val){
			$last[substr(strstr($ix,'9.10.2.1.1.2.1.'),18)] = $val;
		}

		$nmrout = 0;

		ksort($prun);
		$row = 0;
		foreach($prun as $mr => $pr){
			if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
			$row++;
			$i		= explode(".", $mr);
			if($pr == 1){$primg = "bstp";}else{$primg = "brgt";}
			sscanf($last[$mr], "%d:%d:%0d:%0d.%d",$lud,$luh,$lum,$lus,$ticks);
			$bpsbar = Bar( intval($bps[$mr]/1000),0);
			$ip = "$i[4].$i[5].$i[6].$i[7]";
			echo "<tr bgcolor=#$bg>";
			echo "<td><a href=Nodes-List.php?ina=ip&opa==&sta=$ip>$ip</td><td><img src=img/16/$primg.png hspace=20 title=\"prune status\">$i[0].$i[1].$i[2].$i[3]</td>\n";
			echo "<td>$bpsbar".$bps[$mr]."</td>\n";
			printf("<td>%d D %d:%02d:%02d</td>",$lud,$luh,$lum,$lus);
			echo "</tr>\n";
		}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row entries found</td></tr></table>\n";
}
include_once ("inc/footer.php");
?>
