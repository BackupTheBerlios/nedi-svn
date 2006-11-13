<?
/*
#============================================================================
# Program: Nodes-Stolen.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 19/04/05	initial version.
# 07/03/06	moved to nodes, renamed time field.
# 20/03/06	new SQL query support
*/

$bg1	= "ccdd88";
$bg2	= "ddeeaa";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");
include_once ('inc/libnod.php');

$_GET = sanitize($_GET);
$na = isset($_GET['na']) ? $_GET['na'] : "";
$ip = isset($_GET['ip']) ? $_GET['ip'] : "";
$ord = isset($_GET['ord']) ? $_GET['ord'] : "";
$del = isset($_GET['del']) ? $_GET['del'] : "";
$stl = isset($_GET['stl']) ? $_GET['stl'] : "";
$dev = isset($_GET['dev']) ? $_GET['dev'] : "";
$ifn = isset($_GET['ifn']) ? $_GET['ifn'] : "";

$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);

if ($stl){
	$now = time();
	$query	= GenQuery('stolen','i','','','',array('name','ip','mac','device','ifname','who','time'),'',array($na,$ip,$stl,$dev,$ifn,$_SESSION['user'],$now) );
	if( !@DbQuery($query,$link) ){echo "<h4 align=center>".DbError($link)."</h4>";}else{echo "<h3>$stl $upokmsg</h3>";}
}elseif ($del){
	$query	= GenQuery('stolen','d','','','',array('mac'),array('='),array($del) );
	if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>$del $delokmsg</h3>";}
}
?>
<h1>Stolen Nodes</h1>
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/fiqu.png border=0 title="The upper line show current DB info, the lower reflects the state, when it was reported.">
</a></th>
<th>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
Order by
<SELECT name="ord" size=1 onChange=submit();>
<OPTION VALUE="name" <?=($ord == "name")?"selected":""?> >Name
<OPTION VALUE="ip" <?=($ord == "ip")?"selected":""?> >IP Address
<OPTION VALUE="mac" <?=($ord == "mac")?"selected":""?> >MAC Address
<OPTION VALUE="device" <?=($ord == "device")?"selected":""?> >Device
<OPTION VALUE="time" <?=($ord == "updated")?"selected":""?> >Reported on
</select>
</form>
</th>
<th>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
Name <input type="text" name="na" value="<?=$na?>" size="8">
IP <input type="text" name="ip" value="<?=$ip?>" size="15">
MAC <input type="text" name="stl" value="<?=$stl?>" size="12">
on Device <input type="text" name="dev" value="<?=$dev?>" size="10"> /
IF <input type="text" name="ifn" value="<?=$ifn?>" size="4">

</th>
<th width=80><input type="submit" value="Mark">
</form>
</th>
</tr></table><p>
<?
$query	= GenQuery('stolen','s','*',$ord);
$res	= @DbQuery($query,$link);
if($res){
?>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=3><img src=img/32/ngrn.png><br>Node Info</th>
<th colspan=2><img src=img/32/dev.png><br>Device - IF</th>
<th><img src=img/32/eyes.png><br>Last Seen / Reported on</th>
<th><img src=img/32/user.png><br>Action / Reported by</th>

<?
	$row = 0;
	while( ($s = @DbFetchRow($res)) ){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$nquery	= GenQuery('nodes','s','*','','',array('mac'),array('='),array($s[2]));
		$nres	= @DbQuery($nquery,$link);
		$nnod	= @DbNumRows($nres);
		if ($nnod == 1) {
			$n	= @DbFetchRow($nres);
			@DbFreeResult($nres);
			$dbna	= preg_replace("/^(.*?)\.(.*)/","$1", $n[0]);
			$dbip	= long2ip($n[1]);
			$img	= Nimg("$n[2];$n[3]");
			$ls	= date("r",$n[5]);
			list($fc,$lc) = Agecol($n[4],$n[5],$row % 2);
		}else{
			$img	= "gen.png";
			$ls	= "Not in Nodes";
			list($fc,$lc) = Agecol(1,10000000,$row % 2);
		}
		$na	= preg_replace("/^(.*?)\.(.*)/","$1", $s[0]);
		$ip	= long2ip($s[1]);
		$sup	= date("r",$s[6]);
		$simg	= "";
		list($s1c,$s2c) = Agecol($s[6],$s[6],$row % 2);
		if ($n[5] > $s[6]){$simg = "<img src=img/16/lokc.png hspace=8 title=\"Reappeared!\">";}

		echo "<tr bgcolor=#$bg>";
		echo "<th bgcolor=#$bia width=120 rowspan=2><a href=Nodes-Status.php?mac=$n[2]><img src=img/oui/$img title=\"$n[3]\" vspace=8 border=0></a><br>$s[2]\n";
		echo "<td>$dbna</td><td>$dbip</td><td>$n[6]</td><td>$n[7]</td><td bgcolor=#$lc>$ls</td>\n";
		echo "<th>$simg <a href=$_SERVER[PHP_SELF]?del=$s[2]><img src=img/16/bcnl.png border=0 hspace=8 onclick=\"return confirm('Delete node $s[2]?')\"></a></th>\n";
		echo "</tr><tr bgcolor=#$bg><td>$na</td><td>$ip</td><td>$s[3]</td><td>$s[4]</td><td bgcolor=#$s1c>$sup</td><td align=center>$s[5]</td>\n";
		echo "";
		echo "</tr>\n";
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}
echo "</table><table bgcolor=#666666 $tabtag >\n";
echo "<tr bgcolor=#$bg2><td>$row stolen nodes ($query)</td></tr></table>\n";

include_once ("inc/footer.php");
?>
