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
*/

error_reporting(E_ALL ^ E_NOTICE);

$bg1	= "EEDDBB";
$bg2	= "FFEECC";
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

$cpos = strpos($locformat, "c");
$bpos = strpos($locformat, "b");
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
<INPUT type="checkbox" name="ord" <?=($ord)?"checked":""?> > alternative order
</th>
</SELECT></th>

<th width=80><input type="submit" name="shw" value="Show"></th>
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
$query	= GenQuery('devices');
$res	= @DbQuery($query,$link);
if($res){
	$ndev = 0;
	while( ($d = @DbFetchRow($res)) ){
		$dip[$d[0]]  = long2ip($d[1]);
		$dtyp[$d[0]] = $d[3];
		$dico[$d[0]] = $d[18];
		$dcon[$d[0]] = $d[11];
		$l = explode($locsep, $d[10]);
		if(!($cpos === false) ){
			$c = $l[$cpos];
		}else{
			$c = 'Campus';
		}
		if($mavl[$d[0]]){
			$dcity[$c]['su'] += $mavl[$d[0]];
;			$dcity[$c]['md']++;
		}
		$dcity[$c]['nd']++;
		if(!($bpos === false) ){
			$b = $l[$bpos];
		}else{
			$b = 'Building';
		}
		if($mavl[$d[0]]){
			$dbuild[$c][$b]['su'] += $mavl[$d[0]];
;			$dbuild[$c][$b]['md']++;
		}
		$dbuild[$c][$b]['nd']++;
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
<th colspan=2  width=10%><img src=img/32/dev.png><br>Device</th>
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
<table cellspacing=10 width=100%>
<tr><td width=50% valign=top align=center>

<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th width=10%><img src=img/32/glob.png><br>City</th>
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
			if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
			$row++;
			$img  = CtyImg($dcity[$cty]['nd']);
			$tit  = $dcity[$cty]['md']." monitored devices of ". $dcity[$cty]['nd']." in total";
			$ucty = str_replace(" ","%20",$cty);

			$av  = sprintf("%01.2f",$dcity[$cty]['su']/$dcity[$cty]['md']);
			$cbar = Bar($av,-99);
			echo "<tr bgcolor=#$bg>\n";
			echo "<th bgcolor=#$bi><a href=Devices-Table.php?cty=$ucty><img src=img/$img.png border=0 title=\"$tit\"></a><p>$cty</th>\n";
			echo "<td>$cbar $av %</td></tr>\n";
			if($row == $lim){break;}
		}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row cities with $nmon monitored devices in total</td></tr></table>\n";

?>
</td><td width=50% valign=top align=center>

<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th  width=10%><img src=img/32/glob.png><br>Building</th>
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
		foreach (array_keys($dbuild[$cty]) as $bld){
			if($dbuild[$cty][$bld]['md']){
				if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
				$row++;
				$img  = BldImg($dbuild[$cty][$bld]['nd'],$bld);
				$tit  = $dbuild[$cty][$bld]['md']." monitored devices of ". $dbuild[$cty][$bld]['nd']." in total";

				$av  = sprintf("%01.2f",$dbuild[$cty][$bld]['su']/$dbuild[$cty][$bld]['md']);
				$cbar = Bar($av,-99);

				$ucty = str_replace(" ","%20",$cty);
				$ubld = str_replace(" ","%20",$bld);

				echo "<tr bgcolor=#$bg>\n";
				echo "<th bgcolor=#$bi><a href=Devices-Table.php?cty=$ucty&bld=$ubld><img src=img/$img.png border=0 title=\"$tit\"></a><p>$bld</th>\n";
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
<th><img src=img/32/umgr.png><br>Contact</th>
<th><img src=img/32/impt.png><br>Messages</th>
</tr>
<?
	$rord = ($ord)? " desc" : "";
	$query	= GenQuery('messages','c','source,level',"source$rord");
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
		$img = isset($dico[$s]) ? $dico[$s] : "genh";
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi><a href=Monitoring-Messages.php?ina=source&opa==&sta=$s><img src=img/dev/$img.png border=0 title=\"$dtyp[$dv]\"><p></a>$s</th>\n";
		echo "<td>$dcon[$s]</td><td>";
		foreach (array_keys($source[$s]) as $lvl){
			$nmsg = $source[$s][$lvl];
			$mbar = Bar($nmsg,0);
			echo "<a href=Monitoring-Messages.php?ina=source&opa==&sta=$s&cop=AND&inb=level&opb==&stb=$lvl><img src=img/16/$mico[$lvl].png title=\"$mlvl[$lvl]\" border=0></a> $mbar $nmsg $mlvl[$lvl]<br>\n";
		}
		echo "</td></tr>\n";
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
