<?
/*
#============================================================================
# Program: Topology-Routes.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 12/04/05	initial version.
# 20/03/06	new SQL query
# 25/05/07	Minor improvements
*/

$bg1	= "88CC99";
$bg2	= "99DDAA";

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

$query	= GenQuery('devices','s','name,ip,type,services,community,icon','','',array('services'),array('>'),array('3') );
$res	= @DbQuery($query,$link);
if($res){
	while( ($d = @DbFetchRow($res)) ){
		$devip[$d[0]]  = long2ip($d[1]);
		$devtyp[$d[0]] = $d[2];
		$devsrv[$d[0]] = $d[3];
		$devcom[$d[0]] = $d[4];
		$devimg[$d[0]] = $d[5];
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
		$ur	= rawurlencode($currtr);
		echo "<tr bgcolor=#$bg>";
		echo "<td bgcolor=$bi width=80 align=center><a href=$_SERVER[PHP_SELF]?rtr=$ur><img src=img/dev/$devimg[$currtr].png title=\"$devtyp[$currtr]\" border=0></a>\n";
		echo "<br><b>$currtr</b></td>\n";
		echo "<td>$lnet</td>";

		unset($r);
		$r = DevRoutes($devip[$currtr],$devcom[$currtr]);
		$newabsmsk = 0;
		$pfix      = 0;
		$nho       = "not found!";
		$rpimg     = "impt";
		foreach (array_keys($r) as $rte){
			$ddst	= ip2long($rte);
			$dmsk	= ip2long($r[$rte]['msk']);
			$drte	= ip2long($dst) & $dmsk;
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
<h2><?=$rtr?> Routing Table</h2>
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
		$icm[$i[2]] = $i[20];
		$isp[$i[2]] = $i[9];
	}
	@DbFreeResult($res);
	$r = DevRoutes($devip[$rtr],$devcom[$rtr]);
	$row = 0;
	foreach (array_keys($r) as $rd){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$if	= $ina[$r[$rd]['ifx']]." ".$ial[$r[$rd]['ifx']]." ".$icm[$r[$rd]['ifx']];
		$spd	= ZFix($isp[$r[$rd]['ifx']]);
		$rpimg	= RteProto($r[$rd]['pro']);
		$unh    = rawurlencode($netif[$r[$rd]['nho']]);

		list($pfix,$msk,$bmsk)	= Masker($r[$rd]['msk']);
		list($ifimg,$iftit)	= Iftype($ity[$r[$rd]['ifx']]);
		list($ntimg,$ntit)	= Nettype($rd);
	
		echo "<tr bgcolor=#$bg>";
		echo "<td bgcolor=$bi width=20 align=center><img src=img/16/$ntimg title=$ntit></td>\n";
		echo "<td><a href=Topology-Map.php?ina=network&flt=$rd%2F$pfix&draw=1>$rd/$pfix</a></td>\n";
		echo "<td>".$r[$rd]['nho']." <a href=?rtr=$unh>".$netif[$r[$rd]['nho']]."</a></td><td><img src=img/$ifimg title=$iftit hspace=4> $if</td>\n";
		echo "<td align=right>$spd</td><td align=center>".$r[$rd]['me1']."</td>\n";
		echo "<td><img src=img/16/$rpimg.png> ".$r[$rd]['pro']."</td><td align=right>".$r[$rd]['age']."</td>\n";
		echo "</tr>\n";
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row entries in route table</td></tr></table>\n";
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

//===================================================================
// Return Routing Protocol
function RteProto($p) {

	if	($p == "local")	{return "fogr";}
	elseif	($p == "netmgmt"){return "fobl";}
	elseif	($p == "icmp")	{return "fobl";}
	elseif	($p == "egp")	{return "fobl";}
	elseif	($p == "ggp")	{return "fobl";}
	elseif	($p == "hello")	{return "fobl";}
	elseif	($p == "rip")	{return "fovi";}
	elseif	($p == "is-is")	{return "fobl";}
	elseif	($p =="es-is")	{return "fobl";}
	elseif	($p =="ciscoIgrp"){return "fogy";}
	elseif	($p =="bbnSpfIgp"){return "fogy";}
	elseif	($p =="ospf")	{return "foor";}
	elseif	($p =="bgp")	{return "ford";}
	else{return "impt";}
}

?>
