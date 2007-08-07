<?
/*
#============================================================================
# Program: Monitoring-Health.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 08/06/05		initial version.
# 10/03/06		new SQL query support
# 17/04/07		extended monitoring
# 12/07/07		new location and function scheme
*/

error_reporting(E_ALL ^ E_NOTICE);

$bg1	= "DDBBAA";
$bg2	= "EEDDBB";
$refresh= 1;
$maxcol	= 6;
$lim	= 5;

include_once ("inc/header.php");
include_once ('inc/libdev.php');
include_once ('inc/libmon.php');

$_GET = sanitize($_GET);
$reg = isset($_GET['reg']) ? $_GET['reg'] : "";
$cty = isset($_GET['cty']) ? $_GET['cty'] : "";
$bld = isset($_GET['bld']) ? $_GET['bld'] : "";

?><h1>Monitoring Health</h1><?
$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('monitoring');
$res	= @DbQuery($query,$link);
if($res){
	$nmon= 0;
	$mal = 0;
	$lck = 0;
	while( ($m = @DbFetchRow($res)) ){
		$deval[$m[0]] = $m[1];
		if($m[1]){$mal++;}
		if($m[5] > $lck){$lck = $m[5];}
		$nmon++;
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}
?>
<table STYLE="table-layout:fixed" bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src="img/32/neth.png" border=0 title="Health at a glance.">
</a></th>
<th valign=top>
<h3>Uptime Polling</h3><p>
<img src="img/32/dev.png" title="Polling <?=$nmon?> devices" hspace=8><?
if($mal == 0){
	echo "<img src=\"img/32/bchk.png\" title=\"Last check ".date($datfmt,$lck)."\">";
}else{
	if($mal == 1){
		echo "<img src=\"img/32/bomb.png\" title=\"1 active incident\">";
		echo "<EMBED SRC=inc/alarm1.mp3 VOLUME=100 HIDDEN=true>\n";
	}elseif($mal < 10){
		echo "<img src=\"img/32/impt.png\" title=\"$mal active incidents\">";
		echo "<EMBED SRC=inc/alarm2.mp3 VOLUME=100 HIDDEN=true>\n";
	}else{
		echo "<img src=\"img/32/bstp.png\" title=\"$mal active incidents!\">";
		echo "<EMBED SRC=inc/alarm3.mp3 VOLUME=100 HIDDEN=true>\n";
	}
	
?>
<p><table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=<?=$bg2?>>
<th><img src=img/16/dev.png><br>Device</th><th><img src=img/16/clock.png><br>Downtime</th>
<?
	$row = 0;
	foreach(array_keys($deval) as $d){
		if($deval[$d]){
			if ($row % 2){$bg = $bgb; $bi = $bib;}else{$bg = $bga; $bi = $bia;}
			$row++;
			list($bgm,$stat) = StatusBg(1,1,$deval[$d],$bi);
			$ud	= rawurlencode($d);
			echo "<tr bgcolor=#$bgm><th><a href=Devices-Status.php?dev=$ud&shg=on&shp=on>$d</a></td>\n";
			echo "<td>$stat</td></tr>\n";
		}
	}
	echo "</table>\n";	
}
?>
</td><th valign=top>
<h3>Interface Traffic</h3><p>
<?
StatusIf('it');
StatusIf('ot');
?>
</th><th valign=top>
<h3>Interface Errors</h3><p>
<?
StatusIf('ie');
StatusIf('oe');
?>
</th><th valign=top>
<h3>System</h3><p>

<?
StatusSys('cpu');
StatusSys('mem');
StatusSys('tmp');
?>
</th></tr></table>
<p>
<table width=100%>
<tr><th width=20% valign=top>
<h2>Message Statistics</h2>
<?
$query	= GenQuery('messages','g','level','level desc');
$res	= @DbQuery($query,$link);
if($res){
	$nlev = @DbNumRows($res);
	if($nlev){
?>
<p><table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th width=40><img src=img/16/info.png><br>Level</th>
<th><img src=img/16/say.png><br>Messages</th>
<?
		$row = 0;
		while( ($msg = @DbFetchRow($res)) ){
			if ($row % 2){$bg = $bgb; $bi = $bib;}else{$bg = $bga; $bi = $bia;}
			$row++;
			$mbar = Bar($msg[1],0,1);
			echo "<tr bgcolor=#$bg>\n";
			echo "<th bgcolor=#$bi><a href=Monitoring-Messages.php?lvl=$msg[0]><img src=img/16/" . $mico[$msg[0]] . ".png border=0 title=" . $mlvl[$msg[0]] . "></a></th><td>$mbar $msg[1]</td></tr>\n";
		}
		echo "</table>\n";
	}else{
		echo '<p><h5>No Messages</h5>';	
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}
?>
</th><td></td><th width=77% valign=top>
<h2>Recent Vital Messages</h2>
<table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bg2?> >
<th width=40><img src=img/16/info.png><br>Level</th>
<th width=100><img src=img/16/clock.png><br>Time</th>
<th><img src=img/16/dev.png><br>Source</th>
<th><img src=img/16/find.png><br>Info</th>
</tr>
<?
$query	= GenQuery('messages','s','*','id desc',$lim,array('level'),array('>'),array('100') );
$res	= @DbQuery($query,$link);
if($res){
	$row  = 0;
	while( ($m = @DbFetchRow($res)) ){
		if ($row % 2){$bg = $bgb; $bi = $bib;}else{$bg = $bga; $bi = $bia;}
		$row++;
		$hint = "";
		$time = date($datfmt,$m[2]);
		$fd   = str_replace(" ","%20",date("m/d/Y H:i:s",$m[2]));
		$usrc = rawurlencode($m[3]);
		echo "<tr bgcolor=#$bg><th bgcolor=$bi><a href=Monitoring-Messages.php?ina=level&opa==&sta=$m[1]>\n";
		echo "<img src=img/16/" . $mico[$m[1]] . ".png title=\"" . $mlvl[$m[1]] . "\" border=0></a></th>\n";
		echo "<td><a href=Monitoring-Messages.php?ina=time&opa==&sta=$fd>$time</a></td><th>\n";
		echo "<a href=Monitoring-Messages.php?ina=source&opa==&sta=$usrc>$m[3]</a></th><td>$m[4]</td></tr>\n";
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}

?>
</table>
</th></tr></table>
<?
TopoTable($reg,$cty,$bld);
if (!$cty){
	TopoCities();
}elseif (!$bld){
	TopoBuilds($reg,$cty);
}else{
	TopoFloors($reg,$cty,$bld);
}
include_once ("inc/footer.php");

?>
