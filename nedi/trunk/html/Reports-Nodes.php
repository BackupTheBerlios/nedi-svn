<?
/*
#============================================================================
# Program: Reports-Nodes.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 21/04/05	initial version.
# 27/04/05	added update stats.
# 04/05/05	added ambiguous IPs
# 20/05/05	added unused vlans
# 06/03/06	added nomads and reorganized everything
# 20/03/06	new SQL query support
*/

error_reporting(E_ALL ^ E_NOTICE);

$bg1	= "D0EED6";
$bg2	= "E0FFE6";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");
include_once ('inc/libnod.php');

$_GET = sanitize($_GET);
$rep = isset($_GET['rep']) ? $_GET['rep'] : array();
$lim = isset($_GET['lim']) ? $_GET['lim'] : 10;
$ord = isset($_GET['ord']) ? "checked" : "";
?>
<h1>Node Reports</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/dcub.png border=0 title="Node based statistics">
</a></th>
<th>Select Report(s)</th>
<th>
<SELECT MULTIPLE name="rep[]" size=4>
<OPTION VALUE="sum" <? if(in_array("sum",$rep)){echo "selected";} ?> >Summary
<OPTION VALUE="ips" <? if(in_array("ips",$rep)){echo "selected";} ?> >IP Addresses
<OPTION VALUE="ifs" <? if(in_array("ifs",$rep)){echo "selected";} ?> >Interfaces
<OPTION VALUE="vln" <? if(in_array("vln",$rep)){echo "selected";} ?> >Vlans
<OPTION VALUE="nom" <? if(in_array("nom",$rep)){echo "selected";} ?> >Nomads
<OPTION VALUE="ust" <? if(in_array("ust",$rep)){echo "selected";} ?> >Update Stats 
</select>

</th>
<th>Limit
<SELECT size=1 name="lim">
<? selectbox("limit",$lim);?>
</SELECT>
</th>
<th>
<INPUT type="checkbox" name="ord" <?=$ord?> > reverse order
</th>
</SELECT></th>
<th width=80><input type="submit" name="gen" value="Show"></th>
</tr></table></form><p>
<?
if($rep){
$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('nodes');
$res	= @DbQuery($query,$link);
	if($res){
		$tnod   = 0;
		$lip    = 0;
		$nswift = 0;
		$nodif	= array();
		$nonf	= array();
		$nodip['0'] = 0;
		while( ($n = @DbFetchRow($res)) ){
			if(!$n[0]){$nodns++;}
			if($n[14]){$lip++;}
			if($n[4] == $n[5]){$nswift++;}
			if(!$n[9]){$nodif["$n[6];;$n[7]"]++;}
			$nodip[$n[1]]++;
			$oui[$n[3]]++;
			$nodup[$n[4]]['fs']++;
			$nodup[$n[5]]['ls']++;
			$ifvl["$n[6];;$n[7]"] = $n[8];
			$uvlid[$n[6]][$n[8]]++;
			$nodup[$n[10]]['iu']++;
			$ival[$n[9]]++;
			$ifchg[$n[11]]++;
			$ipchg[$n[13]]++;
			$iplost[$n[14]]++;
			if($n[13] and $n[11]){
				$nonf[$n[2]] = $n[11] * $n[13];
				$nona[$n[2]] = $n[0];
				$noou[$n[2]] = $n[3];
				$nodv[$n[2]] = $n[6];
				$noif[$n[2]] = $n[7];
				$nofs[$n[2]] = $n[4];
				$nols[$n[2]] = $n[5];
			}
			$tnod++;
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
		die;
	}
}
if ( in_array("sum",$rep) ){
	$nodb = Bar($nodns,0);
	$noib = Bar($nodip['0'],0);
	$lipb = Bar($lip,0);
	$swib = Bar($nswift,0);
	$totb = Bar($tnod,0);

?>
<table cellspacing=10 width=100%>
<tr><td width=50% valign=top align=center>

<h2>Node Summary</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th width=25%><img src=img/32/db.png title="Lost IP nodes used to have an address. Swift nodes were discovered only once."><br>Item</th>
<th><img src=img/32/form.png><br>Value</th>
<tr bgcolor=#<?=$bga?> ><th bgcolor=#<?=$bia?>>Non DNS Nodes</th><td><?=$nodb?><a href=Nodes-List.php?ina=name&opa=regexp&sta=%5E%24> <?=$nodns?></a></td></tr>
<tr bgcolor=#<?=$bgb?> ><th bgcolor=#<?=$bib?>>Non IP Nodes</th><td><?=$noib?><a href=Nodes-List.php?ina=ip&opa==&sta=0> <?=$nodip['0']?></a></td></tr>
<tr bgcolor=#<?=$bga?> ><th bgcolor=#<?=$bia?>>Lost IPs</th><td><?=$lipb?><a href=Nodes-List.php?ina=iplost&opa=%3E&sta=0> <?=$lip?></a></td></tr>
<tr bgcolor=#<?=$bgb?> ><th bgcolor=#<?=$bib?>>Swift Nodes</th><td><?=$swib?><a href=Nodes-List.php?ina=firstseen&cop==&inb=lastseen> <?=$nswift?></a></td></tr>
<tr bgcolor=#<?=$bga?> ><th bgcolor=#<?=$bia?>>Total Nodes</th><td><?=$totb?> <?=$tnod?></td></tr>
</table>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>><td>5 summary items</td></tr></table>

</td><td width=50% valign=top align=center>

<h2>OUI Chart</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=3 width=25%><img src=img/32/cnic.png><br>NIC Vendor</th>
<th><img src=img/32/cubs.png><br>Nodes</th>
<?
	if($ord){
		asort($oui);
	}else{
		arsort($oui);
	}
	$row = 0;
	foreach ($oui as $o => $nn){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$obar = Bar($nn,0);
		$img  = Nimg($o);
		$uo = str_replace(" ","%20",$o);

		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi>$row</th><th bgcolor=#$bi><img src=img/oui/$img></th>\n";
		echo "<td><a href=Nodes-List.php?ina=oui&opa==&sta=$uo>$o</a></td><td>$obar $nn</td></tr>\n";
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row OUI vendors found in total</td></tr></table>\n";
?>

</td></tr></table>

<?
}
if ( in_array("ips",$rep) ){
?>
<table cellspacing=10 width=100%>
<tr><td width=50% valign=top align=center>

<h2>IP Changes</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th width=25%><img src=img/32/brld.png title="# of times a node was seen with a different IP where > 1000000 means IP was lost."><br>IP Changes</th>
<th><img src=img/32/cubs.png><br>Nodes</th>
<?
	if($ord){
		krsort($ipchg);
	}else{
		ksort($ipchg);
	}
	$row = 0;
	foreach ($ipchg as $c => $nc){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$cbar = Bar($nc,0);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi>$c times</th><td>$cbar <a href=Nodes-List.php?ina=ipchanges&opa==&sta=$c>$nc</a></td></tr>\n";
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row values in total for changed IPs</td></tr></table>\n";
?>
</td><td valign=top align=center>

<h2>Lost IPs</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th width=25%><img src=img/32/bdwn.png title="# of times a node lost its IP address."><br>IP Lost</th>
<th><img src=img/32/cubs.png><br>Nodes</th>
<?
	if($ord){
		krsort($iplost);
	}else{
		ksort($iplost);
	}
	$row = 0;
	foreach ($iplost as $c => $nc){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$cbar = Bar($nc,0);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi>$c times</th><td>$cbar <a href=Nodes-List.php?ina=iplost&opa==&sta=$c>$nc</a></td></tr>\n";
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row values in total for lost IPs</td></tr></table>\n";
?>
</td></tr>
<tr><td valign=top align=center>

<h2>Ambiguous IP Addresses</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2 width=20%><img src=img/32/brgt.png><br>IP Address</th>
<th><img src=img/32/cubs.png><br>Nodes</th>
<?

	if($ord){
		asort($nodip);
	}else{
		arsort($nodip);
	}
	$row = 0;
	foreach ($nodip as $ai => $nm){
		if ($ai and $nm > 1){
			if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
			$row++;
			$ip = long2ip($ai);
			$mbar = Bar($nm,5);

			echo "<tr bgcolor=#$bg>\n";
			echo "<th bgcolor=#$bi>$row</th>\n";
			echo "<td><a href=Nodes-List.php?ina=ip&&opa==&sta=$ip&ord=lastseen>$ip</a></td>\n";
			echo "<td>$mbar $nm</td></tr>\n";
			if($row == $lim){break;}
		}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row unique IP addresses in total</td></tr></table>\n";
?>
</td></tr></table>
<?
}
if ( in_array("ifs",$rep) ){
?>
<table cellspacing=10 width=100%>
<tr><td width=50% valign=top align=center>

<h2>Multiple MAC Addresses</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2 width=20%><img src=img/32/dev.png><br>Device</th>
<th width=20%><img src=img/32/dumy.png><br>Interface</th>
<th><img src=img/32/stat.png><br>Vlan</th>
<th width=50%><img src=img/32/cubs.png><br>Nodes</th>
<?

	if($ord){
		asort($nodif);
	}else{
		arsort($nodif);
	}
	$row = 0;
	foreach ($nodif as $di => $nm){
		if ($nm > 1){
			if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
			$row++;
			$d = explode(';;', $di);
			$mbar = Bar($nm,8);
			echo "<tr bgcolor=#$bg>\n";
			echo "<th bgcolor=#$bi>$row</th><td><a href=Devices-Status.php?dev=$d[0]>$d[0]</a></td>\n";
			echo "<td><a href=Nodes-List.php?ina=device&&opa==&sta=$d[0]&cop=AND&inb=ifname&opb==&stb=$d[1]>$d[1]</a></td>\n";
			echo "<td align=center>$ifvl[$di]</td><td>$mbar $nm</td></tr>\n";
			if($row == $lim){break;}
		}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row interfaces with multiple macs found in total</td></tr></table>\n";
?>
</td><td valign=top align=center>

<h2>Interface Changes</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th width=25%><img src=img/32/dumy.png title="# of times a node was discovered on a different IF."><br>IF Changes</th>
<th><img src=img/32/cubs.png><br>Nodes</th>
<?
	if($ord){
		krsort($ifchg);
	}else{
		ksort($ifchg);
	}
	$row = 0;
	foreach ($ifchg as $c => $nc){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$cbar = Bar($nc,0);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi>$c times</th><td>$cbar <a href=Nodes-List.php?ina=ifchanges&opa==&sta=$c>$nc</a></td></tr>\n";
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row values in total for changed interfaces</td></tr></table>\n";
?>
</td></tr>
<tr><td valign=top align=center>

<h2>Metric Distribution</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th width=25%><img src=img/32/casp.png title="0 Access, 10 IP-Phone, 30 Router, 50 Uplink, 100 Channel (lower = more accurate)"><br>Metric</th>
<th><img src=img/32/cubs.png><br>Nodes</th>
<?
	ksort($ival);
	$row = 0;
	foreach ($ival as $v => $nn){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$vbar = Bar($nn,0);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi>$v</th><td>$vbar <a href=Nodes-List.php?ina=ifmetric&opa==&sta=$v>$nn</a></td></tr>\n";
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row metrics found on $tnod nodes</td></tr></table>\n";

?>
</td></tr></table>
<?
}
if ( in_array("vln",$rep) ){
?>
<h2>Unpopulated Vlans</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th width=80><img src=img/32/stat.png><br>Vlan Id</th>
<th><img src=img/32/dev.png><br>Devices</th>
<?
	$query	= GenQuery('vlans');
	$res	= @DbQuery($query,$link);
	
	if($res){
		$nvl = 0;
		$nunvl = 0;
		$uvlandev = array();
		while( ($vl = @DbFetchRow($res)) ){
			if(! $uvlid[$vl[0]][$vl[1]] and ! preg_match("/$ignoredvlans/",$vl[1]) ){
				$uvlandev[$vl[1]] .= "<a href=Devices-Status.php?dev=$vl[0]>$vl[0]</a> ($vl[2]) ";
				$nunvl++;
			}
			$nvl++;
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
		die;
	}
	if($ord){
		krsort($uvlandev);
	}else{
		ksort($uvlandev);
	}
	$row = 0;
	foreach ($uvlandev as $vl => $dvs){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$ubar = Bar($up,50);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi>$vl</th>\n";
		echo "<td>$dvs</td></tr>\n";
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$nunvl unpopulated of $nvl vlans in total</td></tr></table>\n";
}
if ( in_array("nom",$rep) ){
?>

<h2>Nomads</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=3><img src=img/32/ngrn.png title="Nodes according to Nomad-factor (ipchanges * ifchanges)"><br>Node</th>
<th colspan=2><img src=img/32/dev.png><br>Device - IF</th>
<th><img src=img/32/clock.png><br>First Seen</th>
<th><img src=img/32/clock.png><br>Last Seen</th>
<th><img src=img/32/form.png><br>Nomad Factor</th>
<?
	if($ord){
		asort($nonf);
	}else{
		arsort($nonf);
	}
	$row = 0;
	foreach ($nonf as $m => $nf){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$nbar = Bar($nf,100);
		$img  = Nimg($noou[$m]);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi>$row</th><th bgcolor=#$bi><a href=Nodes-Status.php?mac=$m><img src=img/oui/$img border=0></a></th><td>$nona[$m]</td>\n";
		echo "<td> $nodv[$m]</td><td>$noif[$m]\n";
		list($fc,$lc)	= Agecol($nofs[$m],$nols[$m],$row%2);
		$fs = date("j.M G:i:s",$nofs[$m]);
		echo "<td bgcolor=#$fc>$fs</td>";
		$ls = date("j.M G:i:s",$nols[$m]);
		echo "<td bgcolor=#$lc>$ls</td>";
		echo "<td>$nbar $nf</td></tr>\n";
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row nomads out of $tnod nodes</td></tr></table>\n";
}
if ( in_array("ust",$rep) ){
?>
<h2>Nodes Update Statistic</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th width=33%><img src=img/32/clock.png><br>Timestamp</th>
<th><img src=img/32/eyes.png><br>Events</th>
<?

	if($ord){
		ksort ($nodup);
	}else{
		krsort ($nodup);
	}
	$row = 0;
	foreach ( array_keys($nodup) as $d ){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$nodup[$d]['fs'] = isset($nodup[$d]['fs']) ? $nodup[$d]['fs'] : 0;
		$nodup[$d]['ls'] = isset($nodup[$d]['ls']) ? $nodup[$d]['ls'] : 0;
		$nodup[$d]['iu'] = isset($nodup[$d]['iu']) ? $nodup[$d]['iu'] : 0;
		$fbar = Bar($nodup[$d]['fs'],100000);
		$lbar = Bar($nodup[$d]['ls'],1);
		$ibar = Bar($nodup[$d]['iu'],0);
		$fd   = rawurlencode(date("m/d/Y H:i:s",$d));
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bg1>".date("r",$d)."\n";
		echo "<td>$fbar <a href=Nodes-List.php?ina=firstseen&opa==&sta=".$fd.">".$nodup[$d]['fs']."</a> first seen<br>\n";
		echo "$lbar <a href=Nodes-List.php?ina=lastseen&opa==&sta=".$fd.">".$nodup[$d]['ls']."</a> last seen <br>\n";
		echo "$ibar <a href=Nodes-List.php?ina=ifupdate&opa==&sta=".$fd.">".$nodup[$d]['iu']."</a> IF Updates</td></tr>\n";
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row updates from $tnod nodes in total</td></tr></table>\n";
}

include_once ("inc/footer.php");
?>
