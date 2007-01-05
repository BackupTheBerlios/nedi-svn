<?
/*
#============================================================================
# Program: Realtime-Spanningtree.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 18/04/05	initial version.
# 20/03/06	new SQL query  & graph support
*/

$bg1	= "66AA99";
$bg2	= "77BBAA";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");
include_once ('inc/libdev.php');

$_GET = sanitize($_GET);
$dev = isset($_GET['dev']) ? $_GET['dev'] : "";
$shg = isset($_GET['shg']) ? "checked" : "";
$vln = isset($_GET['vln']) ? $_GET['vln'] : "";

?>
<h1>Spanningtree Tool</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>" name="stree">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=<?=$bg1?> ><th width=80><a href=<?=$_SERVER['PHP_SELF']?> >
<img src=img/32/tabt.png border=0 title="Select VLAN for VLAN indexing devices only">
</a></th>
<th>
Switch
<select size=1 name="dev" onchange="document.stree.vln.value=''">
<option value="">---
<?
$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('devices','s','*','','',array('services & 2'),array('='),array('2') );
$res	= @DbQuery($query,$link);
if($res){
	while( ($d = @DbFetchRow($res)) ){
		echo "<option value=\"$d[0]\" ";
		if($dev == $d[0]){
			echo "selected";
			$img	= $d[18];
			$ip	= long2ip($d[1]);
			$sv	= Syssrv($d[6]);
			$comm	= $d[15];
		}
		echo " >$d[0]\n";
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}
echo "</select>";
if ($dev) {
	$query	= GenQuery('vlans','s','*','','',array('device'),array('='),array($dev) );
	$res	= @DbQuery($query,$link);
	$nvln	= @DbNumRows($res);

	if($res and $nvln){
?>
 Vlan
<SELECT size=1 name="vln">
<OPTION VALUE="">---
<?

		while( ($v = @DbFetchRow($res)) ){
			echo "<OPTION VALUE=\"$v[1]\" ";
			if($vln == $v[1]){echo "selected";}
			echo " >$v[1] $v[2]\n";
		}
		@DbFreeResult($res);
		echo "</select>";
	}
}
echo '</th>';
if($rrdstep){
	echo "<th><input type=checkbox name=\"shg\" $shg> graphs</th>";
}
?>
<th width=80>
<input type="submit" value="Show">
</th>
</tr></table></form>
<?
if ($dev) {
	$query	= GenQuery('interfaces','s','*','','',array('device'),array('='),array($dev) );
	$res	= @DbQuery($query,$link);
	$nif = 0;
	while( ($i = @DbFetchRow($res)) ){
		$ifn[$i[2]] = $i[1];
		$ift[$i[2]] = $i[4];
		$ifi[$i[2]] = "$i[6] $i[7] $i[16]";
		$nif++;
	}
	@DbFreeResult($res);
if('0.0.0.0' == $ip){
	echo "<h4>no IP!</h4>";
	die;
}

?>
<table cellspacing=10 width=100%>
<tr><td width=50% valign=top>
<h2>General Info</h2>
<table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bga?>><th bgcolor=#<?=$bia?> width=140>
<a href=Devices-Status.php?dev=<?=$dev?> ><img src=img/dev/<?=$img?>.png title="<?=$dev[3]?>" border=0></a>
<br><?=$dev?></th><td><a href=telnet://<?=$ip?>><?=$ip?></a></td></tr>
<tr bgcolor=#<?=$bgb?>><th bgcolor=#<?=$bg1?>>Services</th><td><?=($sv)?$sv:"&nbsp;"?></td></tr>
<tr bgcolor=#<?=$bgb?>><th bgcolor=#<?=$bg1?>>SNMP</th><td><?=$comm?></td></tr>
</table>
</td><td width=50% valign=top align=center>
<h2>Spanningtree Info <?=($vln)?"for vlan $vln":""?></h2>
<table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bga?>><th bgcolor=#<?=$bg2?>>Bridge Address</th>
<?
	error_reporting(1);
	snmp_set_quick_print(1);

	$braddr	= str_replace('"','',snmpget("$ip","$comm",".1.3.6.1.2.1.17.1.1.0",($timeout * 1000000) ) );
	if ($braddr){
		echo "<td>$braddr</td></tr>\n";
	}else{
		echo $toumsg;
		echo "</td></tr></table>\n";
		include_once ("inc/footer.php");
		die;
	}
	if($vln){$comm = "$comm@$vln";}
	$stppri	= str_replace('"','',snmpget("$ip","$comm",".1.3.6.1.2.1.17.2.2.0") );
	if(!$stppri){
		echo $toumsg;
		include_once ("inc/footer.php");
		die;
	}
	$laschg	= str_replace('"','',snmpget("$ip","$comm",".1.3.6.1.2.1.17.2.3.0") );
	sscanf($laschg, "%d:%d:%0d:%0d.%d",$tcd,$tch,$tcm,$tcs,$ticks);
	$tcstr  = sprintf("%d D %d:%02d:%02d",$tcd,$tch,$tcm,$tcs);
	$numchg	= str_replace('"','',snmpget("$ip","$comm",".1.3.6.1.2.1.17.2.4.0") );

	$droot	= str_replace('"','',snmpget("$ip","$comm",".1.3.6.1.2.1.17.2.5.0") );
	$rport	= str_replace('"','',snmpget("$ip","$comm",".1.3.6.1.2.1.17.2.7.0") );
?>
<tr bgcolor=#<?=$bgb?>><th bgcolor=#<?=$bg2?> width=140>STP Priority</th><td><?=$stppri?></td></tr>
<tr bgcolor=#<?=$bga?>><th bgcolor=#<?=$bg2?>>Topology Changes</th><td><?=$numchg?></td></tr>
<tr bgcolor=#<?=$bgb?>><th bgcolor=#<?=$bg2?>>Topology Changed</th><td><?=$tcstr?></td></tr>
<tr bgcolor=#<?=$bga?>><th bgcolor=#<?=$bg2?>>Designated Root</th><td><?=$droot?></td></tr>
</table>
</td></tr>
</table>

<h2>Interfaces</h2>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2 ><img src=img/32/dumy.png><br>Name</th>
<th><img src=img/32/say.png><br>Info</th>
<? if($rrdstep and $shg){echo '<th valign=bottom><img src=img/32/3d.png><br>Traffic/Errors</th>';} ?>
<th colspan=2><img src=img/32/tap.png ><br>State</th>
<th><img src=img/32/star.png ><br>Cost</th>
<?
	if(!$nif){
		echo "</table>\n";
		echo $resmsg;
		echo "<div align=center>$query</dev>";
		include_once ("inc/footer.php");
		die;
	}
	foreach (snmprealwalk("$ip","$comm",".1.3.6.1.2.1.17.1.4.1.2") as $ix => $val){
		$pidx[substr(strrchr($ix, "."), 1 )] = $val;
	}
	foreach (snmprealwalk("$ip","$comm",".1.3.6.1.2.1.17.2.15.1.3") as $ix => $val){
		$pstate[substr(strrchr($ix, "."), 1 )] = $val;
	}
	foreach (snmprealwalk("$ip","$comm",".1.3.6.1.2.1.17.2.15.1.4") as $ix => $val){
		$stpen[substr(strrchr($ix, "."), 1 )] = $val;
	}
	foreach (snmprealwalk("$ip","$comm",".1.3.6.1.2.1.17.2.15.1.5") as $ix => $val){
		$pcost[substr(strrchr($ix, "."), 1 )] = $val;
	}
	asort($pidx);

	$row = 0;
	foreach($pidx as $po => $ix){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$rpimg = "";
		if($rport == $po){$rpimg = "<img src=img/16/idea.png hspace=8 title=Rootport>";}

		if($pstate[$po] == 1){$pst = "<img src=img/16/bcnl.png hspace=8>disabled";}
		elseif($pstate[$po] == 2){$pst = "<img src=img/16/bstp.png hspace=8>blocking";}
		elseif($pstate[$po] == 3){$pst = "<img src=img/16/bup.png hspace=8>listening";}
		elseif($pstate[$po] == 4){$pst = "<img src=img/16/brld.png hspace=8>learning";}
		elseif($pstate[$po] == 5){$pst = "<img src=img/16/brgt.png hspace=8>forwarding";}
		else{$pst = "<img src=img/16/bcls.png hspace=8>broken";}

		if($stpen[$po] == 1){$sten = "<img src=img/16/bchk.png hspace=8>STP enabled";}
		else{$sten = "<img src=img/16/bcnl.png hspace=8>STP disabled";}

		list($ifimg,$iftit) = Iftype($ift[$ix]);

		echo "<tr bgcolor=#$bg>";
		echo "<th bgcolor=#$bi><img src=img/$ifimg title=$iftit vspace=8></th><th>$ifn[$ix]</th>\n";
		echo "<td>$ifi[$ix] $rpimg</td>\n";
		if($rrdstep and $shg){
			if($d = urlencode($dev) and $if = urlencode($ifn[$ix]) ){
				echo "<td nowrap align=center>\n";
				echo "<a href=Devices-Graph.php?dv=$d&if%5B%5D=$if><img src=inc/drawrrd.php?dv=$d&if%5B%5D=$if&s=s&t=trf border=0>\n";
				echo "<img src=inc/drawrrd.php?dv=$d&if%5B%5D=$if&s=s&t=err border=0></a>\n";
			}else{
				echo "<td></td>";
			}
		}
		echo "<td>$pst</td><td>$sten</td></td><td align=center>$pcost[$po]</td>\n";
		echo "</tr>\n";
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$nif Interfaces</td></tr></table>\n";
}
include_once ("inc/footer.php");
?>
