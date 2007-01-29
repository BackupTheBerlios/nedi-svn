<?

/*
#============================================================================
# Program: Devices-Config.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 25/02/05	initial version.
# 04/03/05	revised backend
# 13/05/05	added line#
# 10/03/06	new SQL query support
# 29/01/07	suppres motd char, sorting
*/

$bg1	= "99AACC";
$bg2	= "AABBDD";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");

$_GET = sanitize($_GET);
$shl = isset($_GET['shl']) ? $_GET['shl'] : "";
$shc = isset($_GET['shc']) ? $_GET['shc'] : "";
$sln = isset($_GET['sln']) ? $_GET['sln'] : "";
$smo = isset($_GET['smo']) ? $_GET['smo'] : "";
$dch = isset($_GET['dch']) ? $_GET['dch'] : "";
$dco = isset($_GET['dco']) ? $_GET['dco'] : "";
$str = isset($_GET['str']) ? $_GET['str'] : "";
$ord = isset($_GET['ord']) ? $_GET['ord'] : "";

$cols = array(	"device"=>"Device",
		"config"=>"Configuration",
		"changes"=>"Changes",
		"time"=>"Update",
		);

$target	='config';
$opa	= 'regexp';
if($shl == 'Inverted'){
	$opa	= 'not regexp';
}elseif($shl == 'Changes'){
	$target	='changes';
}

?>
<h1>Devices Config</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>" name="cfg">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/cfg2.png border=0 title="Review IOS and CatOS configurations.">
</a></th>
<th>Search <input type="text" name="str" value="<? echo $str?>" size="32">
<select size=1 name="sd" onchange="document.cfg.str.value=document.cfg.sd.options[document.cfg.sd.selectedIndex].value">
<option value="">or select
<option value="">----------
<?
$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('configs');
$res	= @DbQuery($query,$link);
if($res){
	while( ($d = @DbFetchRow($res)) ){
		$na = preg_replace("/^(.*?)\.(.*)/","$1", $d[0]);
		echo "<OPTION VALUE=\"$na\">$na\n";
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}
?>
</SELECT>
</th><th width=80>
<input type="submit" value="Normal" name="shl" style="width:72px"><br>
<input type="submit" value="Inverted" name="shl" style="width:72px"><br>
<input type="submit" value="Changes" name="shl" style="width:72px">
</th>
</table></form><p>
<?
if ($dch){
	if(preg_match("/adm/",$_SESSION['group']) ){
		$query	= GenQuery('configs','u','device',$dch,'',array('changes'),'',array('') );
		if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3> $dch changes $delokmsg</h3>";}
	}else{
		echo $nokmsg;
	}
	$shc = $dch;
}
if ($dco){
	if(preg_match("/adm/",$_SESSION['group']) ){
		$query	= GenQuery('configs','d','','','',array('device'),array('='),array($dco) );
		if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3> $dco config $delokmsg</h3>";}
?><script language="JavaScript"><!--
setTimeout("history.go(-2)",2000);
//--></script><?		

	}else{
		echo $nokmsg;
	}
}

if ($shl){
?>
<h2>List</h2>
<table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bg2?> >
<?
ColHead('device',80);
ColHead('config');
ColHead('changes');
?><th>OS</th><?
ColHead('time');

	$query	= GenQuery('devices');
	$res	= @DbQuery($query,$link);
	if($res){
		while( ($d = @DbFetchRow($res)) ){
			$devty[$d[0]] = $d[3];
			$devos[$d[0]] = $d[8];
			$devic[$d[0]] = $d[18];
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
	}

	$query	= GenQuery('configs','s','*',$ord,'',array($target),array($opa),array($str));

	$res	= @DbQuery($query,$link);
	if($res){
		$row = 0;
		while( ($con = @DbFetchRow($res)) ){
			if ($row % 2){$bg = $bgb; $bi = $bib;}else{$bg = $bga; $bi = $bia;}
			$row++;
			$img	= $devic[$con[0]];
			$typ	= $devty[$con[0]];
			$cfg	= substr(implode("\n",preg_grep("/$str/i",split("\n",$con[1]) ) ),0,80 ) . "...";
			$chg	= substr(implode("\n",preg_grep("/$str/i",split("\n",$con[2]) ) ),0,80 );
			$ucon	= urlencode($con[0]);
			echo "<tr bgcolor=#$bg>\n";
			echo "<th bgcolor=#$bi><a href=Devices-Status.php?dev=$ucon><img src=img/dev/$img.png title=\"$typ\" border=0></a><br>\n";
			echo "<a href=Nodes-List.php?ina=device&opa==&sta=$ucon&ord=device><b>$con[0]</b></a>\n";

			echo "<td><a href=$_SERVER[PHP_SELF]?shc=$ucon><pre>$cfg</pre></a></td>\n";
			$cu	= date("j.M (G:i)",$con[3]);
			list($u1c,$u2c) = Agecol($con[3],$con[3],$row % 2);
			echo "<td><pre>$chg</pre></td><td>".$devos[$con[0]]."</td><td bgcolor=#$u1c>$cu</td>\n";
			echo "</td></tr>\n";
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
	}
	echo "</table><table bgcolor=#666666 $tabtag>\n";
	echo "<tr bgcolor=#$bg2><td>$row results using $query</td></tr></table>\n";

}elseif($shc){

	echo "<h2>$shc</h2>\n";

	$query	= GenQuery('configs','s','*','','',array('device'),array('='),array($shc));
	$res	= @DbQuery($query,$link);
	$cfgok	= @DbNumRows($res);
	if ($cfgok == 1) {
		$cfg = @DbFetchRow($res);
		@DbFreeResult($res);
	}else{
		echo "<h4>$shc $n1rmsg ($cfgok)</h4>";
		die;
	}
	$lnr = "";
	$config = "";
	$ucfg	= urlencode($cfg[0]);

#	$fd =  @fopen("log/$cfg[0].conf","w");
#	fwrite($fd,$cfg[1]);
#	fclose($fd);

	foreach ( split("\n",$cfg[1]) as $l ){
		$lnr++;
		if( preg_match("/^([!#])(.*)$/",$l) )
			$l = "<font style='color: grey'>$l</font>";
		elseif( preg_match("/^service|snmp|logging|system location|system contact|ntp|^clock/",$l) )
			$l = "<font style='color: blue'>$l</font>";
		elseif( preg_match("/^\s*no|shutdown|access-list|access-class| permit/",$l) )
			$l = "<font style='color: darkred'>$l</font>";
		elseif( preg_match("/^hostname|description|system name/",$l) )
			$l = "<font style='color: dimgrey'>$l</font>";
		elseif( preg_match("/^interface|^line|set port/",$l) )
			$l = "<font style='color: orange'>$l</font>";
		elseif( preg_match("/^ ip/",$l) )
			$l = "<font style='color: maroon'>$l</font>";
		elseif( preg_match("/^ standby.*|trunk|channel/",$l) )
			$l = "<font style='color: sienna'>$l</font>";
		elseif( preg_match("/username|password|^enable|set password|set enablepass/",$l) )
			$l = "<font style='color: red'>$l</font>";
		elseif( preg_match("/^aaa|.*radius|.*authentication/",$l) )
			$l = "<font style='color: steelblue'>$l</font>";
		elseif( preg_match("/ip route|^ passive-interface|default-gateway/",$l) )
			$l = "<font style='color: olive'>$l</font>";
		elseif( preg_match("/^router|^ network|vlan/",$l) )
			$l = "<font style='color: green'>$l</font>";
		elseif($smo)
			$l = preg_replace("/ (\^)([\w])$/"," $1",$l);
		if($sln)
			$config .= sprintf("<i>%3d</i> $l\n",$lnr);
		else
			$config .= "$l\n";
	}
	$charr	= split("\n",$cfg[2]);
	$charr	= preg_replace("/^#(.*)$/","<font style='color: grey'>#$1</font>",$charr);
	$charr	= preg_replace("/(^\s*[0-9]{1,3}\-.*)$/","<font style='color: indianred'>$1</font>",$charr);
	$charr	= preg_replace("/(^\s*[0-9]{1,3}\+.*)$/","<font style='color: seagreen'>$1</font>",$charr);
	$changs	= implode("\n",$charr);
?>
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg2?> >
<th><img src=img/32/dtxt.png><br>Configuration (from <?=date("j. M Y",$cfg[3])?>)</th><th><img src=img/32/cfg.png><br>Changes</th></tr>
<tr><td align=left valign=top  bgcolor=#<?=$bia?> >
<?
if(preg_match("/adm/",$_SESSION['group']) )
	echo "<a href=$_SERVER[PHP_SELF]?dco=$ucfg><img src=img/16/bcnl.png align=right border=0 onclick=\"return confirm('Delete config for $cfg[0]?')\" title=\"Delete config!\"></a>\n";
#if ( file_exists("log/$ucfg.cfg") )
#	echo "<a href=\"log/$ucfg.conf\"><img src=img/16/flop.png align=right border=0 title=\"Save link as...\"></a>\n";
echo "<a href=$_SERVER[PHP_SELF]?shc=$ucfg&sln=" . (!$sln) . "><img src=img/16/form.png align=right border=0 title=\"Toggle Line#\"></a>\n";
echo "<a href=$_SERVER[PHP_SELF]?shc=$ucfg&smo=" . (!$smo) . "><img src=img/16/say.png align=right border=0 title=\"Suppress motd character\"></a>\n";
echo "<ul><pre>$config</pre></ul>\n";
echo "</td><td align=left valign=top  bgcolor=#$bib >";
if(preg_match("/adm/",$_SESSION['group']) )
	echo "<a href=$_SERVER[PHP_SELF]?dch=$ucfg><img src=img/16/bcnl.png align=right border=0 onclick=\"return confirm('Clear changes for $cfg[0]?')\" title=\"Clear changes!\"></a>\n";
?>
<p><pre>
<?=$changs?>
</pre></td></tr></table>
<?
}

include_once ("inc/footer.php");
?>
