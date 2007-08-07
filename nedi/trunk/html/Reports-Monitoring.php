<?
/*
#============================================================================
# Program: Reports-Monitoring.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 15/06/05	initial version.
# 20/03/06	new SQL query support
# 05/07/06	uptime report
# 25/07/07	implemented new location scheme
*/

error_reporting(E_ALL ^ E_NOTICE);

$bg1	= "DFD0D0";
$bg2	= "EFE0E0";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");
include_once ('inc/libdev.php');
include_once ('inc/libmon.php');

$_GET = sanitize($_GET);
$rep = isset($_GET['rep']) ? $_GET['rep'] : array();
$lim = isset($_GET['lim']) ? $_GET['lim'] : 10;
$ord = isset($_GET['ord']) ? "checked" : "";
?>
<h1>Monitoring Reports</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/dpie.png border=0 title="Statistics based on device monitoring">
</a></th>
<th>Select Report(s)</th>
<th>
<select multiple name="rep[]" size=4>
<OPTION value="dav" <? if(in_array("dav",$rep)){echo "selected";} ?> >Device Availability
<OPTION value="lav" <? if(in_array("lav",$rep)){echo "selected";} ?> >Location Availability
<OPTION value="mss" <? if(in_array("mss",$rep)){echo "selected";} ?> >Message Sources
<OPTION value="tup" <? if(in_array("tup",$rep)){echo "selected";} ?> >Uptimes
</SELECT></th>
</th>
<th>Limit
<SELECT size=1 name="lim">
<? selectbox("limit",$lim);?>
</SELECT>
</th>
<th>
<INPUT type="checkbox" name="ord" <?=($ord)?"checked":""?> title="Reverse or order by levels in Message Sources"> alternative order
</th>
</SELECT></th>

<th width=80><input type="submit" name="do" value="Show"></th>
</tr></table></form><p>
<?
if($rep){

$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('monitoring');
$res	= @DbQuery($query,$link);
if($res){
	$nmon = 0;
	$mavl	= array();
	$topup	= array();
	while( ($m = @DbFetchRow($res)) ){
		if($m[8]){
			$mavl[$m[0]] = (1 - $m[7] / $m[8]) * 100;
		}else{
			$mavl[$m[0]] = 0;
		}
		$topup[$m[0]] = $m[6];
		$nmon++;
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
	die;
}
$query	= GenQuery('devices','s','name,ip,type,location,contact,icon');
$res	= @DbQuery($query,$link);
if($res){
	$ndev = 0;
	while( ($d = @DbFetchRow($res)) ){
		$dip[$d[0]]  = long2ip($d[1]);
		$dtyp[$d[0]] = $d[2];
		$dcon[$d[0]] = $d[4];
		$dico[$d[0]] = $d[5];
		$l = explode($locsep, $d[3]);
		$dcity["$l[0]$locsep$l[1]"]['nd']++;
		$dbuild["$l[0]$locsep$l[1]"][$l[2]]['nd']++;
		if($mavl[$d[0]]){
			$dcity["$l[0]$locsep$l[1]"]['su'] += $mavl[$d[0]];
;			$dcity["$l[0]$locsep$l[1]"]['md']++;
			$dbuild["$l[0]$locsep$l[1]"][$l[2]]['su'] += $mavl[$d[0]];
;			$dbuild["$l[0]$locsep$l[1]"][$l[2]]['md']++;
		}
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
	die;
}

if ( in_array("dav",$rep) ){
?>
<h2>Device Availability</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2  width=20%><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/umgr.png><br>Contact</th>
<th><img src=img/32/bup.png><br>Availability</th>
</tr>
<?
	if($ord){
		arsort($mavl);
	}else{
		asort($mavl);
	}
	$row = 0;
	foreach ($mavl as $dv => $av){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$img  = $dico[$dv];
		$ud   = rawurlencode($dv);
		$dbar = Bar($av,-99);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi><a href=Devices-Status.php?dev=$ud><img src=img/dev/$img.png border=0 title=\"$dtyp[$dv]\"><p></a>$dv</th>\n";
		echo "<td><a href=telnet://$dip[$dv]>$dip[$dv]</td><td>$dcon[$dv]</td><td>$dbar".sprintf("%01.2f",$av)." %</td></tr>\n";
		if($row == $lim){break;}

	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row devices of $nmon monitored devices in total</td></tr></table>\n";
}

if ( in_array("lav",$rep) ){
?>
<h2>Location Availability</h2><p>
<table width=100%>
<tr><td width=50% valign=top align=center>

<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th width=20%><img src=img/32/glob.png><br>City</th>
<th><img src=img/32/bup.png><br>Availability</th>
</tr>
<?
	if($ord){
		krsort($dcity);
	}else{
		ksort($dcity);
	}
	$row = 0;
	foreach (array_keys($dcity) as $cty){
		if($dcity[$cty]['md']){
			$l = explode($locsep, $cty);
			if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
			$row++;
			$img  = CtyImg($dcity[$cty]['nd']);
			$tit  = $dcity[$cty]['md']." monitored devices of ". $dcity[$cty]['nd']." in total";
			$av  = sprintf("%01.2f",$dcity[$cty]['su']/$dcity[$cty]['md']);
			$cbar = Bar($av,-99);
			$ur = rawurlencode($l[0]);
			$uc = rawurlencode($l[1]);
			echo "<tr bgcolor=#$bg>\n";
			echo "<th bgcolor=#$bi><a href=Topology-Table.php?reg=$ur&cty=$uc><img src=img/$img.png border=0 title=\"$tit\"></a><p>$l[1]</th>\n";
			echo "<td>$cbar $av %</td></tr>\n";
			if($row == $lim){break;}
		}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row cities with $nmon monitored devices in total</td></tr></table>\n";

?>
</td><td></td><td width=50% valign=top align=center>

<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th  width=20%><img src=img/32/glob.png><br>Building</th>
<th><img src=img/32/bup.png><br>Availability</th>
</tr>

<?
	if($ord){
		krsort($dbuild);
	}else{
		ksort($dbuild);
	}
	$row = 0;
	foreach (array_keys($dbuild) as $cty){
		if($ord){
			krsort($dbuild[$cty]);
		}else{
			ksort($dbuild[$cty]);
		}
		$l = explode($locsep, $cty);
		$ur = rawurlencode($l[0]);
		$uc = rawurlencode($l[1]);
		foreach (array_keys($dbuild[$cty]) as $bld){
			if($dbuild[$cty][$bld]['md']){
				if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
				$row++;
				$img  = BldImg($dbuild[$cty][$bld]['nd'],$bld);
				$tit  = $dbuild[$cty][$bld]['md']." monitored devices of ". $dbuild[$cty][$bld]['nd']." in total";

				$av  = sprintf("%01.2f",$dbuild[$cty][$bld]['su']/$dbuild[$cty][$bld]['md']);
				$cbar = Bar($av,-99);
				$ub = rawurlencode($bld);
				echo "<tr bgcolor=#$bg>\n";
				echo "<th bgcolor=#$bi><a href=Topology-Table.php?reg=$ur&cty=$uc&bld=$ub><img src=img/$img.png border=0 title=\"$tit\"></a><p>$bld</th>\n";
				echo "<td>$cbar $av %</td></tr>\n";
				if($row == $lim){break;}
			}
	
		}
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row buildings with $nmon monitored devices in total</td></tr></table>\n";
	echo '</td></tr></table>';
}

if ( in_array("mss",$rep) ){
?>
<h2>Message Sources</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th width=10%><img src=img/32/say.png><br>Source</th>
<th width=20%><img src=img/32/umgr.png><br>Contact</th>
<th colspan=2><img src=img/32/say.png><br>Messages</th>
</tr>
<?
	$msgord = ($ord)? "level desc" : "source";
	$query	= GenQuery('messages','g','source,level',$msgord);
	$res	= @DbQuery($query,$link);
	if($res){
		while( ($s = @DbFetchRow($res)) ){
			$source{$s[0]}{$s[1]} = $s[2];
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
		die;
	}
	$row = 0;
	foreach (array_keys($source) as $s){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$us  = rawurlencode($s);
		echo "<tr bgcolor=#$bg><th bgcolor=#$bi>\n";
		if( isset($dico[$s]) ){
			echo "<a href=Devices-Status.php?dev=$us>\n";
			echo "<img src=img/dev/$dico[$s].png border=0 title=\"$dtyp[$dv]\"></a>\n";
			echo "<p>$s</th><td align=center>$dcon[$s]</td><td>\n";
		}else{
			echo "<img src=img/dev/genh.png><p>$s</th><td align=center>-</td><td>\n";
		}
		$tmsg = 0;
		foreach (array_keys($source[$s]) as $lvl){
			$nmsg = $source[$s][$lvl];
			$tmsg += $nmsg;
			$mbar = Bar($nmsg,0);
			echo "<img src=img/16/$mico[$lvl].png title=\"$mlvl[$lvl]\"> $mbar \n";
			echo "<a href=Monitoring-Messages.php?ina=source&opa==&sta=$us&cop=AND&inb=level&opb==&stb=$lvl>$nmsg</a> $mlvl[$lvl]<br>\n";
		}
		echo "</td><td><a href=Monitoring-Messages.php?ina=source&opa==&sta=$us>$tmsg</a> total</td></tr>\n";
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row sources ($query)</td></tr></table>\n";
}

if ( in_array("tup",$rep) ){
?>
<h2>Uptimes</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2  width=10%><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/umgr.png><br>Contact</th>
<th><img src=img/32/clock.png><br>Uptime</th>
</tr>
<?
	if($ord){
		asort($topup);
	}else{
		arsort($topup);
	}
	$row = 0;
	foreach ($topup as $dv => $ticks){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$ud  = rawurlencode($dv);
		$upt = $ticks / 8640000;
		$ubar = Bar($upt,365);
		echo "<tr bgcolor=#$bg>";
		echo "<th bgcolor=#$bi><a href=Devices-Status.php?dev=$ud><img src=img/dev/$dico[$dv].png border=0 title=\"$dtyp[$dv]\"><p></a>$dv</th>\n";
		echo "<td><a href=telnet://$dip[$dv]>$dip[$dv]</td><td>$dcon[$dv]</td><td>$ubar ".sprintf("%01.2f",$upt)." Days</td></tr>\n";
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row uptime sources</td></tr></table>\n";
}

}
include_once ("inc/footer.php");
?>
