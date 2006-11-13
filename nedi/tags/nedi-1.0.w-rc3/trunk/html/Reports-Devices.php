<?

/*
#============================================================================
# Program: Reports-Devices.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 21/04/05	initial version.
# 20/03/06	new SQL query support
*/

error_reporting(E_ALL ^ E_NOTICE);

$bg1	= "D0D6DD";
$bg2	= "E0E6EE";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");
include_once ('inc/libdev.php');

$_GET = sanitize($_GET);
$rep = isset($_GET['rep']) ? $_GET['rep'] : array();
$lim = isset($_GET['lim']) ? $_GET['lim'] : 10;
$ord = isset($_GET['ord']) ? "checked" : "";
?>
<h1>Device Reports</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/dtap.png border=0 title="Device based statistics">
</a></th>
<th>Select Report(s)</th>
<th>
<SELECT MULTIPLE name="rep[]" size=4>
<OPTION value="typ" <? if(in_array("typ",$rep)){echo "selected";} ?> >Device Types
<OPTION value="vtp" <? if(in_array("vtp",$rep)){echo "selected";} ?> >VTP Domain
<OPTION value="sft" <? if(in_array("sft",$rep)){echo "selected";} ?> >Software
<OPTION VALUE="ust" <? if(in_array("ust",$rep)){echo "selected";} ?> >Update Stats 

</SELECT></th>
</th>
<th>Limit
<SELECT size=1 name="lim">
<? selectbox("limit",$lim);?>
</SELECT>
</th>
<th>
<INPUT type="checkbox" name="ord" <?=$ord?> > alternative order
</th>
</SELECT></th>

<th width=80><input type="submit" name="gen" value="Show"></th>
</tr></table></form><p>
<?
if($rep){
$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('devices');
$res	= @DbQuery($query,$link);
if($res){
	$ndev = 0;
	while( ($d = @DbFetchRow($res)) ){
		$dtyp[$d[3]]++;
		$dico[$d[3]] = $d[18];
		$fseen[$d[4]]++;
		$lseen[$d[5]]++;
		$dops[$d[8]]++;
		$dbim[$d[9]]++;
		$dvtp[$d[12]]++;
		$ndev++;
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
	die;
}

if ( in_array("typ",$rep) ){
?>
<h2>Device Types</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2><img src=img/32/fiap.png><br>Type</th>
<th><img src=img/32/dev.png><br>Devices</th>
<?
	$ntyp = 0;
	if($ord){
		arsort($dtyp);
	}else{
		ksort($dtyp);
	}
	$row = 0;
	foreach ($dtyp as $typ => $n){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$img	= $dico[$typ];
		$tbar	= Bar($n,0);
		$utyp	= rawurlencode($typ);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi width=10%><img src=img/dev/$img.png title=\"$typ\"></th>\n";
		echo "<td><a href=Devices-List.php?ina=type&opa==&sta=$utyp>$typ</a></td>\n";
		echo "<td>$tbar $n</td></tr>\n";
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row device types of $ndev devices in total</td></tr></table>\n";
}

if ( in_array("vtp",$rep) ){
?>
<h2>VTP Domains</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th><img src=img/32/stat.png><br>VTP Domain</th>
<th><img src=img/32/dev.png><br>Devices</th>
<?
	if($ord){
		arsort($dvtp);
	}else{
		ksort($dvtp);
	}
	$row = 0;
	foreach ($dvtp as $vtp => $n){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$op="=";
		if(!$vtp){$vtp="^$";$op="regexp";}
		$tbar = Bar($n,0);
		$uvtp = rawurlencode($vtp);
		echo "<tr bgcolor=#$bg>\n";
		echo "<td><a href=Devices-List.php?ina=vtpdomain&opa=$op&sta=$vtp>$vtp</a></td><td>\n";
		echo "$tbar $n</td></tr>\n";
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row VTP domains of $ndev devices in total</td></tr></table>\n";
}

if ( in_array("sft",$rep) ){
?>
<table cellspacing=10 width=100%>
<tr><td width=50% valign=top>

<h2>Operating Systems</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th><img src=img/32/cog.png><br>OS</th>
<th><img src=img/32/dev.png><br>Devices</th>
<?
	ksort($dops);
	$row = 0;
	foreach ($dops as $ops => $n){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		if(!$ops){$ops="^$";}
		$tbar = Bar($n,0);
		$uops = rawurlencode($ops);
		echo "<tr bgcolor=#$bg>\n";
		echo "<td><a href=Devices-List.php?ina=os&opa==&sta=$uops>$ops</a></td>\n";
		echo "<td>$tbar $n</td></tr>\n";
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row operating systems</td></tr></table>\n";
?>
</td><td width=50% valign=top align=center>

<h2>Boot Images</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th><img src=img/32/foto.png><br>Boot Image</th>
<th><img src=img/32/dev.png><br>Devices</th>
<?
	ksort($dbim);
	$row = 0;
	foreach ($dbim as $bim => $n){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$o = "=";
		if(!$bim){$bim="^$";$o="regexp";}
		$tbar = Bar($n,0);
		$ubim = rawurlencode($bim);
		echo "<tr bgcolor=#$bg>\n";
		echo "<td><a href=Devices-List.php?ina=bootimage&opa=$o&sta=$ubim>$bim</a></td>\n";
		echo "<td>$tbar $n</td></tr>\n";
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row boot images of $ndev devices in total</td></tr></table>\n";
	echo '</td></tr></table>';
}

if ( in_array("ust",$rep) ){
?>
<h2>Devices Update Statistic</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th width=33%><img src=img/32/clock.png><br>Timestamp</th>
<th><img src=img/32/eyes.png><br>Events</th>
<?

	foreach($fseen as $k => $v){
		$devup[$k]['fs'] = $v;
	}
	foreach($lseen as $k => $v){
		$devup[$k]['ls'] = $v;
	}
	if($ord){
		ksort ($devup);
	}else{
		krsort ($devup);
	}
	$row = 0;
	foreach ( array_keys($devup) as $d ){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$devup[$d]['fs'] = isset($devup[$d]['fs']) ? $devup[$d]['fs'] : 0;
		$devup[$d]['ls'] = isset($devup[$d]['ls']) ? $devup[$d]['ls'] : 0;
		if(!$devup[$d]['fs']){$devup[$d]['fs'] = 0;}
		if(!$devup[$d]['ls']){$devup[$d]['ls'] = 0;}
		$fbar = Bar($devup[$d]['fs'],100000);
		$lbar = Bar($devup[$d]['ls'],1);
		$fd   = str_replace(" ","%20",date("m/d/Y H:i:s",$d));

		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bg1>".date("r",$d)."</th>\n";
		echo "<td>$fbar <a href=Devices-List.php?ina=firstseen&opa==&sta=\"$fd\">".$devup[$d]['fs']."</a> first seen<br>\n";
		echo "$lbar <a href=Devices-List.php?ina=lastseen&opa==&sta=\"$fd\">".$devup[$d]['ls']."</a> last seen</td></tr>\n";
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row updates from $ndev devices in total</td></tr></table>\n";
}

}
include_once ("inc/footer.php");
?>
