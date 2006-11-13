<?
/*
#============================================================================
# Program: Reports-Interfaces.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 21/04/05	initial version.
# 20/03/06	new SQL query support
*/

error_reporting(E_ALL ^ E_NOTICE);

$bg1	= "D0D6EE";
$bg2	= "E0E6FF";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");

$_GET = sanitize($_GET);
$rep = isset($_GET['rep']) ? $_GET['rep'] : array();
$lim = isset($_GET['lim']) ? $_GET['lim'] : 10;
$ord = isset($_GET['ord']) ? "checked" : "";
?>
<h1>Interface Reports</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/ddum.png border=0 title="Device interface based statistics">
</a></th>
<th>Select Report(s)</th>
<th>
<SELECT MULTIPLE name="rep[]" size=4>
<OPTION VALUE="itr" <? if(in_array("itr",$rep)){echo "selected";} ?> >Total Traffic
<OPTION VALUE="aif" <? if(in_array("aif",$rep)){echo "selected";} ?> >Active Interfaces
<OPTION VALUE="dif" <? if(in_array("dif",$rep)){echo "selected";} ?> >Disabled Interfaces
</SELECT>

</th>
<th>Limit
<SELECT size=1 name="lim">
<? selectbox("limit",$lim);?>
</SELECT>
</th>
<th>
<INPUT type="checkbox" name="ord"  <?=$ord?> > alternative order
</th>
</SELECT></th>
<th width=80><input type="submit" name="gen" value="Show"></th>
</tr></table></form><p>
<?
if($rep){

$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('interfaces');
$res	= @DbQuery($query,$link);
if($res){
	$nif = 0;
	while( ($i = @DbFetchRow($res)) ){
		$numif[$i[0]]++;
		$topino["$i[0];;$i[1]"] = $i[12];
		$topier["$i[0];;$i[1]"] = $i[13];
		$topoto["$i[0];;$i[1]"] = $i[14];
		$topoer["$i[0];;$i[1]"] = $i[15];
		if($i[12] > 70){$nactif[$i[0]]++;}
		if($i[8] == 2){$ndif++;$disif[$i[0]] .= "$i[1] ";}
		$nif++;
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
	die;
}

if ( in_array("aif",$rep) ){
	foreach ($numif as $dv => $ni){
		$ainorm[$dv] = intval(100 * $nactif[$dv] / $ni);
	}
	arsort($ainorm);

?>
<table cellspacing=10 width=100%>
<tr><td width=50% valign=top align=center>

<h2>Most Active Interfaces</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2 width=25%><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/dumy.png><br>Total Interfaces</th>
<th><img src=img/32/cnic.png><br>Active Interfaces</th>
<?
	$row = 0;
	foreach ($ainorm as $dv => $up){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$ubar	= Bar($up,48);
		$ud	= rawurlencode($dv);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi>$row</th><td><a href=Devices-Status.php?dev=$ud>$dv</a></td>\n";
		echo "<td align=center>".$numif[$dv]."</td><td>$ubar $up % (".$nactif[$dv].")</td></tr>\n";
		if($row == $_GET['lim']){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$nif interfaces on $row devices in total</td></tr></table>\n";
?>
</td><td width=50% valign=top align=center>

<h2>Least Active Interfaces</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2 width=25%><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/dumy.png><br>Total Interfaces</th>
<th><img src=img/32/cnic.png><br>Active Interfaces</th>
<?
	asort($ainorm);
	$row = 0;
	foreach ($ainorm as $dv => $up){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$ubar	= Bar($up,48);
		$ud	= rawurlencode($dv);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi>$row</th><td><a href=Devices-Status.php?dev=$ud>$dv</a></td>\n";
		echo "<td align=center>".$numif[$dv]."</td><td>$ubar $up % (".$nactif[$dv].")</td></tr>\n";
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$nif interfaces on $row devices in total</td></tr></table></td></tr></table>\n";
}

if ( in_array("dif",$rep) ){
?>
<h2>Disabled Interfaces</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2 width=25%><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/bstp.png><br>Disabled Interfaces</th>
<?
	if($ord){
		krsort($disif);
	}else{
		ksort($disif);
	}
	$row = 0;
	foreach ($disif as $dv => $di){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$ud	= rawurlencode($dv);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi>$row</th><td><a href=Devices-Status.php?dev=$ud>$dv</a></td>\n";
		echo "<td>$di</td></tr>\n";
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$ndif disabled interfaces on $row devices in total</td></tr></table>\n";
}

if ( in_array("itr",$rep) ){
?>
<table cellspacing=10 width=100%>
<tr><td width=50% valign=top align=center>

<h2>Input Traffic</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2 width=25%><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/dumy.png><br>Interface</th>
<th><img src=img/32/tap.png><br>Octets</th>
<?
	if($ord){
		asort($topino);
	}else{
		arsort($topino);
	}
	$row = 0;
	foreach ($topino as $di => $io){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$d = explode(';;', $di);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi>$row</th><td><a href=Devices-Status.php?dev=$d[0]>$d[0]</a></td>\n";
		echo "<td><a href=Nodes-List.php?ina=device&opa==&sta=$d[0]&cop=AND&inb=ifname&opb==&stb=$d[1]>$d[1]</a></td>\n";
		if($rrdstep){
			if($dn = rawurlencode($d[0]) and $if = rawurlencode($d[1]) ){
				echo "<td align=center>\n";
				echo "<a href=Devices-Graph.php?dv=$dn&if%5B%5D=$if>";
				echo "<img src=inc/drawrrd.php?dv=$dn&if%5B%5D=$if&s=s&t=trf border=0 title=\"$io octets\"></a>\n";
			}else{
				echo "<td></td>";
			}
		}else{
			echo "<td>$io</td></tr>\n";
		}
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>Top $lim Interfaces by in octets</td></tr></table>\n";
?>
</td><td width=50% valign=top align=center>

<h2>Input Errors</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2 width=25%><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/dumy.png><br>Interface</th>
<th><img src=img/32/err.png><br>Errors</th>
<?
	if($ord){
		asort($topier);
	}else{
		arsort($topier);
	}
	$row = 0;
	foreach ($topier as $di => $ie){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$d = explode(';;', $di);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi>$row</th><td><a href=Devices-Status.php?dev=$d[0]>$d[0]</a></td>\n";
		echo "<td><a href=Nodes-List.php?ina=device&opa==&sta=$d[0]&cop=AND&inb=ifname&opb==&stb=$d[1]>$d[1]</a></td>\n";
		if($rrdstep){
			if($dn = rawurlencode($d[0]) and $if = rawurlencode($d[1]) ){
				echo "<td align=center>\n";
				echo "<a href=Devices-Graph.php?dv=$dn&if%5B%5D=$if>";
				echo "<img src=inc/drawrrd.php?dv=$dn&if%5B%5D=$if&s=s&t=err border=0 title=\"$ie errors\"></a>\n";
			}else{
				echo "<td></td>";
			}
		}else{
			echo "<td>$ie</td></tr>\n";
		}
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>Top $lim Interfaces by in errors</td></tr></table></td></tr><tr><td>\n";
?>
<h2>Output Traffic</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2 width=25%><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/dumy.png><br>Interface</th>
<th><img src=img/32/tap.png><br>Octets</th>
<?
	if($ord){
		asort($topoto);
	}else{
		arsort($topoto);
	}
	$row = 0;
	foreach ($topoto as $di => $oo){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$d = explode(';;', $di);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi>$row</th><td><a href=Devices-Status.php?dev=$d[0]>$d[0]</a></td>\n";
		echo "<td><a href=Nodes-List.php?ina=device&opa==&sta=$d[0]&cop=AND&inb=ifname&opb==&stb=$d[1]>$d[1]</a></td>\n";
		if($rrdstep){
			if($dn = rawurlencode($d[0]) and $if = rawurlencode($d[1]) ){
				echo "<td align=center>\n";
				echo "<a href=Devices-Graph.php?dv=$dn&if%5B%5D=$if>";
				echo "<img src=inc/drawrrd.php?dv=$dn&if%5B%5D=$if&s=s&t=trf border=0 title=\"$oo octets\"></a>\n";
			}else{
				echo "<td></td>";
			}
		}else{
			echo "<td>$oo</td></tr>\n";
		}
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>Top $lim Interfaces by in octets</td></tr></table>\n";
?>
</td><td width=50% valign=top align=center>

<h2>Output Errors</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2 width=25%><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/dumy.png><br>Interface</th>
<th><img src=img/32/err.png><br>Errors</th>
<?
	if($ord){
		asort($topoer);
	}else{
		arsort($topoer);
	}
	$row = 0;
	foreach ($topoer as $di => $oe){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$d = explode(';;', $di);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi>$row</th><td><a href=Devices-Status.php?dev=$d[0]>$d[0]</a></td>\n";
		echo "<td><a href=Nodes-List.php?ina=device&opa==&sta=$d[0]&cop=AND&inb=ifname&opb==&stb=$d[1]>$d[1]</a></td>\n";
		if($rrdstep){
			if($dn = rawurlencode($d[0]) and $if = rawurlencode($d[1]) ){
				echo "<td align=center>\n";
				echo "<a href=Devices-Graph.php?dv=$dn&if%5B%5D=$if>";
				echo "<img src=inc/drawrrd.php?dv=$dn&if%5B%5D=$if&s=s&t=err border=0 title=\"$oe errors\"></a>\n";
			}else{
				echo "<td></td>";
			}
		}else{
			echo "<td>$oe</td></tr>\n";
		}
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>Top $lim Interfaces by in errors</td></tr></table>\n";


	echo '</td></tr></table>';
}
}

include_once ("inc/footer.php");
?>
