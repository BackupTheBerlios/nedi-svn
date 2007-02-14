<?
/*
#============================================================================
# Program: Realtime-Routes.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 12/04/05	initial version.
# 20/03/06	new SQL query
*/

$bg1	= "88CCBB";
$bg2	= "99DDAA";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");
include_once ('inc/libdev.php');

$_GET = sanitize($_GET);
$rtr = isset($_GET['rtr']) ? $_GET['rtr'] : "";
$dst = isset($_GET['dst']) ? $_GET['dst'] : "";
$src = isset($_GET['src']) ? $_GET['src'] : "";
$trc = isset($_GET['trc']) ? $_GET['trc'] : "";

$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);

$query	= GenQuery('networks','s','*','ip');
$res	= @DbQuery($query,$link);
if($res){
	while( ($r = @DbFetchRow($res)) ){
		$netif[long2ip($r[2])] = $r[0];
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}

$query	= GenQuery('devices','s','*','','',array('services'),array('>'),array('3') );
$res	= @DbQuery($query,$link);
if($res){
	while( ($d = @DbFetchRow($res)) ){
		$devip[$d[0]]  = long2ip($d[1]);
		$devtyp[$d[0]] = $d[3];
		$devsrv[$d[0]] = $d[6];
		$devcom[$d[0]] = $d[15];
		$devimg[$d[0]] = $d[18];
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}

?>
<h1>Routes Tool</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>" name="rout">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=<?=$bg1?> ><th width=80><a href=<?=$_SERVER['PHP_SELF']?> >
<img src=img/32/rout.png border=0 title="Either trace a route or display routing table of L3 device">
</a></th>
<th>
Source <SELECT size=1 name="src">
<OPTION VALUE="">Select
<?
foreach (array_keys($netif) as $n ){
		echo "<OPTION VALUE=$n";
		if($src == $n){echo " selected";}
		echo ">$n";
}
?>
</SELECT>

Destinaton <SELECT size=1 name="dst">
<OPTION VALUE="0.0.0.0">Default
<?
foreach (array_keys($netif) as $n ){
		echo "<OPTION VALUE=$n";
		if($dst == $n){echo " selected";}
		echo ">$n";
}
?>
</SELECT>
<input type="submit" value="Trace" name="trc">
</th>
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
?>
</SELECT>
<input type="submit" value="Show">
</th>
</tr></table></form>
<?
if ($trc) {
$ddest	= ip2long($dst);														// fixes potential signing problem
?>
<h2>Route trace from <?=$src?> to <?=$dst?></h2>
<table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bg2?>><th><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/net.png><br>Local Network</th>
<th><img src=img/32/nglb.png><br>Destination</th>
<th><img src=img/32/neti.png ><br>Next Hop</th>
<th><img src=img/32/casp.png><br>Metric 1</th>
<th><img src=img/32/edit.png><br>Protocol</th>

<?
	$lnet	= $src;
	$currtr	= $netif[$lnet];
	$path	= "";
	$row = 0;
	while($row < 255 and $currtr){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		echo "<tr bgcolor=#$bg>";
		echo "<td bgcolor=$bi width=80 align=center><a href=$_SERVER[PHP_SELF]?rtr=$currtr><img src=img/dev/$devimg[$currtr].png title=\"$devtyp[$currtr]\" border=0></a>\n";
		echo "<br><b>$currtr</b></td>\n";
		echo "<td>$lnet</td>";

		unset($r);
		$r = DevRoutes($devip[$currtr],$devcom[$currtr]);
		$newabsmsk = 0;
		foreach (array_keys($r) as $rte){
			$ddst	= ip2long($rte);
			$dmsk	= ip2long($r[$rte]['msk']);
			$drte	= $ddest & $dmsk;
			$absmsk	= sprintf("%u",$dmsk);

			if($ddst  == $drte and $absmsk >= $newabsmsk){
				$newabsmsk = $absmsk;
				$dst = $rte;
				list($pfix,$msk,$bmsk)	= Masker($r[$rte]['msk']);
				$nho			= $r[$rte]['nho'];
				$me1			= $r[$rte]['me1'];
				$rp			= $r[$rte]['pro'];
				$rpimg			= RteProto($r[$rte]['pro']);
			}
		}
		echo "<td>$dst/$pfix</td>";
		echo "<td>$nho</td>";
		echo "<td align=center>$me1</td><td><img src=img/16/$rpimg.png> $rp</td></tr>\n";
		flush();
		if ( strpos($path, $currtr) ){
			echo "<h4>$currtr $lopmsg<h4>\n";
			break;
		}
		$path .= $currtr;
		if ($nho == $dst or $currtr == $netif[$nho]){									// We either reached the destination or an unkown device
			break;
		}else{
			$path .= "-> ";
		}
		$lnet	= $nho;
		$currtr	= $netif[$nho];
	}
?>
</table>
<?
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row hops ($path)</td></tr></table>\n";

}elseif ($rtr) {
	$sv	= Syssrv($devsrv[$rtr]);
		
?>
<h2>General Info</h2>
<table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bga?>><th bgcolor=#<?=$bia?> width=120>
<a href=Devices-Status.php?dev=<?=$rtr?> ><img src=img/dev/<?=$devimg[$rtr]?>.png title="<?=$devtyp[$rtr]?>" border=0></a>
<br><?=$rtr?></th><td><a href=telnet://<?=$devip[$rtr]?>><?=$devip[$rtr]?></a></td></tr>
<tr bgcolor=#<?=$bgb?>><th bgcolor=#<?=$bg1?>>Services</th><td><?=($sv)?$sv:"&nbsp;"?></td></tr>
</table>
<h2>Device Routing Table</h2>
<table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bg2?>>
<th colspan=2><img src=img/32/nglb.png><br>Destination</th>
<th><img src=img/32/neti.png ><br>Next Hop</th>
<th><img src=img/32/dumy.png><br>Interface</th>
<th><img src=img/32/tap.png ><br>Bandwidth</th>
<th><img src=img/32/casp.png><br>Metric 1</th>
<th><img src=img/32/edit.png><br>Protocol</th>
<th><img src=img/32/clock.png><br>Age [s]</th>
<?
	$query	= GenQuery('interfaces','s','*','','',array('device'),array('='),array($rtr) );
	$res	= @DbQuery($query,$link);
	while( ($i = @DbFetchRow($res)) ){
		$ina[$i[2]] = $i[1];
		$ity[$i[2]] = $i[4];
		$ial[$i[2]] = $i[7];
		$icm[$i[2]] = $i[16];
		$isp[$i[2]] = $i[9];
	}
	@DbFreeResult($res);
	$r = DevRoutes($devip[$rtr],$devcom[$rtr]);
	$row = 0;
	foreach (array_keys($r) as $dst){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$if	= $ina[$r[$dst]['ifx']]." (".$ial[$r[$dst]['ifx']]." ".$icm[$r[$dst]['ifx']].")";
		$spd	= ZFix($isp[$r[$dst]['ifx']]);
		$rpimg	= RteProto($r[$dst]['pro']);

		list($pfix,$msk,$bmsk)	= Masker($r[$dst]['msk']);
		list($ifimg,$iftit)	= Iftype($ity[$r[$dst]['ifx']]);
		list($ntimg,$ntit)	= Nettype($dst);
	
		echo "<tr bgcolor=#$bg>";
		echo "<td bgcolor=$bi width=20 align=center><img src=img/16/$ntimg title=$ntit></td>\n";
		echo "<td>$dst/$pfix</td><td>".$r[$dst]['nho']."</td><td><img src=img/$ifimg title=$iftit hspace=4> $if</td>\n";
		echo "<td align=right>$spd</td><td align=center>".$r[$dst]['me1']."</td>\n";
		echo "<td><img src=img/16/$rpimg.png>".$r[$dst]['pro']."</td><td align=right>".$r[$dst]['age']."</td>\n";
		echo "</tr>\n";
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row results using $query</td></tr></table>\n";
}
include_once ("inc/footer.php");

//===================================================================
// Get routes of a device
function DevRoutes($ip,$c){

	global $toumsg;
	
	#error_reporting(1);
	snmp_set_quick_print(1);

	foreach (snmprealwalk($ip,$c,".1.3.6.1.2.1.4.21.1.2") as $oid => $val){
		$i = explode('.', $oid);
		$route["$i[1].$i[2].$i[3].$i[4]"]['ifx'] = $val;
	}
	if(!$oid ){echo $toumsg;die;}
	foreach (snmprealwalk($ip,$c,".1.3.6.1.2.1.4.21.1.3") as $oid => $val){
		$i = explode('.', $oid);
		$route["$i[1].$i[2].$i[3].$i[4]"]['me1'] = $val;
	}
	foreach (snmprealwalk($ip,$c,".1.3.6.1.2.1.4.21.1.7") as $oid => $val){
		$i = explode('.', $oid);
		$route["$i[1].$i[2].$i[3].$i[4]"]['nho'] = $val;
	}
	foreach (snmprealwalk($ip,$c,".1.3.6.1.2.1.4.21.1.9") as $oid => $val){
		$i = explode('.', $oid);
		$route["$i[1].$i[2].$i[3].$i[4]"]['pro'] = $val;
	}
	foreach (snmprealwalk($ip,$c,".1.3.6.1.2.1.4.21.1.10") as $oid => $val){
		$i = explode('.', $oid);
		$route["$i[1].$i[2].$i[3].$i[4]"]['age'] = $val;
	}
	foreach (snmprealwalk($ip,$c,".1.3.6.1.2.1.4.21.1.11") as $oid => $val){
		$i = explode('.', $oid);
		$route["$i[1].$i[2].$i[3].$i[4]"]['msk'] = $val;
	}
	return $route;
}

?>
