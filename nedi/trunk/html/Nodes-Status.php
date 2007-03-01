<?
/*
#============================================================================
# Program: Nodes-Status.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 14/04/05	initial version.
#  9/05/05	improved probing & cosmetic changes.
# 17/03/06	new SQL query support
# 09/01/07	Minor improvements.
# 02/28/07	Added IP & IF tracking tables.
*/

$bg1	= "BBDDCC";
$bg2	= "CCEEDD";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");
include_once ('inc/libnod.php');

$_GET = sanitize($_GET);
$mac = isset($_GET['mac']) ? $_GET['mac'] : "";
$wol = isset($_GET['wol']) ? $_GET['wol'] : "";
$del = isset($_GET['del']) ? $_GET['del'] : "";
?>
<h1>Node Status</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>><img src=img/32/ngrn.png border=0 title="DB info and a little portscan (22,23,80, 137 & 443) for nodes"></a></th>
<th>MAC Address <input type="text" name="mac" value="<?=$mac?>" size="12"></th>
<th width=80><input type="submit" value="Show"></th>
</tr></table></form><p>
<?

if ($mac){

	$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
	$query	= GenQuery('nodes','s','*','','',array('mac'),array('='),array($mac));
	$res	= @DbQuery($query,$link);
	$nnod	= @DbNumRows($res);
	if ($nnod != 1) {
		echo "<h4>$mac $n1rmsg</h4>";
		@DbFreeResult($res);
		die;
	}else{
		$n		= @DbFetchRow($res);
		@DbFreeResult($res);

		$name		= preg_replace("/^(.*?)\.(.*)/","$1", $n[0]);
		$ip		= long2ip($n[1]);
		$au		= date("r",$n[12]);
		list($a1c,$a2c) = Agecol($n[12],$n[12],1);
		$img		= Nimg("$n[2];$n[3]");
		$fs		= date("r",$n[4]);
		$ls		= date("r",$n[5]);
		$ud 		= urlencode($n[6]);
		list($fc,$lc)	= Agecol($n[4],$n[5],0);

		if($n[7]){
			$query	= GenQuery('interfaces','s','*','','',array('device','name'),array('=','='),array($n[6],$n[7]),array('AND') );
			$res	= @DbQuery($query,$link);
			$nif	= @DbNumRows($res);
			if ($nif != 1) {
				echo "<h4>$query $n1rmsg</h4>";
			}else{
				$if	= @DbFetchRow($res);
				if ($if[8] == "2"){
					$ifimg	= "<img src=img/bulbr.png title=\"Disabled!\">";
				}else{
					$ifimg = "<img src=img/bulbg.png title=\"Enabled\">";
				}
			}
			@DbFreeResult($res);
			$iu		= date("r",$n[10]);
			list($i1c,$i2c) = Agecol($n[10],$n[10],1);
		}
		$vl[2] = "-";
		if($n[8]){
			$query	= GenQuery('vlans','s','*','','',array('device','vlanid'),array('=','='),array($n[6],$n[8]),array('AND') );
			$res	= @DbQuery($query,$link);
			$nvl	= @DbNumRows($res);
			if ($nvl != 1) {
			}else{
				$vl	= @DbFetchRow($res);
			}
			@DbFreeResult($res);
		}
	}
?>
<table cellspacing=10 width=100%>
<tr><td width=50% valign=top>
<h2>Database Info</h2><p>
<table bgcolor=#666666 <?=$tabtag?> >
<tr><th bgcolor=#<?=$bia?> width=120><a href=Nodes-Status.php?mac=<?=$n[2]?> ><img src=img/oui/<?=$img?> title="<?=$n[3]?>" vspace=8 border=0></a><br><?=$name?></th>
<td bgcolor=#<?=$bg2?>>
<a href=Devices-Status.php?dev=<?=$ud?> ><img src=img/16/hwif.png hspace=8 border=0 title="Status of related device"></a>

<?
if(preg_match("/dsk/",$_SESSION['group']) ){
	echo "<img src=img/sep.png hspace=12><a href=Nodes-Stolen.php?na=$n[0]&ip=$n[1]&stl=$n[2]&dev=$n[6]&ifn=$n[7]><img src=img/16/fiqu.png hspace=8 border=0  title=\"Mark as stolen!\"></a>";
	echo "<a href=$_SERVER[PHP_SELF]?wol=$n[2]><img src=img/16/powr.png hspace=8 border=0 title=\"Wake this node\"></a>";
}
if(preg_match("/adm/",$_SESSION['group']) ){
	echo "<img src=img/sep.png hspace=12><a href=$_SERVER[PHP_SELF]?del=$n[2]><img src=img/16/bcnl.png hspace=8 border=0 onclick=\"return confirm('Delete node $n[2]?')\" title=\"Delete this node!\"></a>";
}
?>
</td></tr>
<tr><th bgcolor=#<?=$bg1?>>MAC Address</th>	<td bgcolor=#<?=$bgb?>><b><?=rtrim(chunk_split($n[2],2,"-"),"-")?></b> or <b><?=rtrim(chunk_split($n[2],4,"."),".")?></b></td></tr>
<tr><th bgcolor=#<?=$bg1?>>NIC Vendor</th>	<td bgcolor=#<?=$bgb?>><?=$n[3]?></td></tr>
<tr><th bgcolor=#<?=$bg1?>>IP Address</th>	<td bgcolor=#<?=$bga?>><?=$ip?> (<?=($n[1])?gethostbyaddr($ip):"";?>)</td></tr>
<tr><th bgcolor=#<?=$bg1?>>IP Update</th>	<td bgcolor=#<?=$a1c?>><?=$au?> (<?=$n[13]?> Changes / <?=$n[14]?> Lost)</td></tr>
<tr><th bgcolor=#<?=$bg1?>>Device</th>		<td bgcolor=#<?=$bga?>><?=$n[6]?></td></tr>
<tr><th bgcolor=#<?=$bg1?>>Interface</th>	<td bgcolor=#<?=$bgb?>><?=$ifimg?> <?=$n[7]?> (<?=ZFix($if[9])?>-<?=$if[10]?>) <i><?=$if[7]?> <?=$if[16]?></i></td></tr>
<tr><th bgcolor=#<?=$bg1?>>Vlan</th>		<td bgcolor=#<?=$bga?>><?=$n[8]?> <?=$vl[2]?></td></tr>
<tr><th bgcolor=#<?=$bg1?>>Traffic</th>		<td bgcolor=#<?=$bgb?>>Bytes: <?=$if[12]?> Errors: <?=$if[13]?></td></tr>
<tr><th bgcolor=#<?=$bg1?>>IF Update</th>	<td bgcolor=#<?=$i1c?>><?=$iu?> (Changes <?=$n[11]?> / Metric <?=$n[9]?>)</td></tr>
<tr><th bgcolor=#<?=$bg1?>>First Seen</th>	<td bgcolor=#<?=$fc?>><?=$fs?></td></tr>
<tr><th bgcolor=#<?=$bg1?>>Last Seen</th>	<td bgcolor=#<?=$lc?>><?=$ls?></td></tr>

</table>
</td><td width=50% valign=top align=center>
<?
flush();
if($n[1]){
?>
<h2>Realtime Info</h2><p>
<table bgcolor=#666666 <?=$tabtag?> >
<tr><th bgcolor=#<?=$bg1?> width=120><img src=img/32/nwin.png><br>Netbios</th><td bgcolor=#<?=$bgb?>><?=NbtStat($ip)?></td></tr>
<tr><th bgcolor=#<?=$bg1?> width=120><a href=http://<?=$ip?> target=window><img src=img/32/glob.png border=0></a><br>HTTP</th><td bgcolor=#<?=$bga?>><?=CheckTCP($ip,'80'," \r\n\r\n")?></td></tr>
<tr><th bgcolor=#<?=$bg1?> width=120><a href=https://<?=$ip?> target=window><img src=img/32/glok.png border=0></a><br>HTTPS</th><td bgcolor=#<?=$bga?>><?=CheckTCP($ip,'443','')?></td></tr>
<tr><th bgcolor=#<?=$bg1?> width=120><a href=ssh://<?=$ip?>><img src=img/32/lokc.png border=0></a><br>SSH</th><td bgcolor=#<?=$bga?>><?=CheckTCP($ip,'22','')?></td></tr>
<tr><th bgcolor=#<?=$bg1?> width=120><a href=telnet://<?=$ip?>><img src=img/32/kons.png border=0></a><br>Telnet</th><td bgcolor=#<?=$bga?>><?=CheckTCP($ip,'23','\n')?></td></tr>
</table>
<?
}else{
	echo "<h4>No IP!</h4>";
}
echo'</td></tr>';
if($rrdstep){
	$rsh = $rrdstep / 3600;
	if($d = urlencode($n[6]) and $if = urlencode($n[7]) ){
?>
<tr><td align=center>
<h2>Current Interface Traffic (<?=$rsh?>h average)</h2>
<a href=Devices-Graph.php?dv=<?=$d?>&if%5B%5D=<?=$if?>><img src=inc/drawrrd.php?dv=<?=$d?>&if%5B%5D=<?=$if?>&s=m&t=trf border=0></a>
</td><td align=center>
<h2>Current Interface Errors (<?=$rsh?>h average)</h2>
<img src=inc/drawrrd.php?dv=<?=$d?>&if%5B%5D=<?=$if?>&s=m&t=err border=0>
</td></tr>
<?
	}
}
?>
<tr><td align=center valign=top>
<h2>IP Tracking</h2>

<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2><img src=img/32/clock.png><br>Updated</th>
<th><img src=img/32/say.png><br>Name</th>
<th><img src=img/32/form.png><br>IP Address</th>
<?

$query	= GenQuery('nodiplog','s','*','ipupdate','',array('mac'),array('='),array($n[2]) );
$res	= @DbQuery($query,$link);

$row = 0;
while( $l = @DbFetchRow($res) ){
	if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
	$row++;
	echo "<tr bgcolor=#$bg><th bgcolor=#$bi>$row</th>\n";
	echo "<td>". date("r",$l[1]) ."</td><td>$l[2]</td><td>". long2ip($l[3]) ."</td></tr>\n";
}
@DbFreeResult($res);

echo "</table><table bgcolor=#666666 $tabtag >\n";
echo "<tr bgcolor=#$bg2><td>$row IP changes in total</td></tr></table>\n";

?>
</td><td align=center valign=top>
<h2>IF Tracking</h2>

<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2><img src=img/32/clock.png><br>Updated</th>
<th><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/dumy.png><br>IF</th>
<th><img src=img/32/stat.png><br>Vlan</th>
<?

$query	= GenQuery('nodiflog','s','*','ifupdate','',array('mac'),array('='),array($n[2]) );
$res	= @DbQuery($query,$link);

$row = 0;
while( $l = @DbFetchRow($res) ){
	if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
	$row++;
	$uld = urlencode($l[2]);
	echo "<tr bgcolor=#$bg><th bgcolor=#$bi>$row</th><td>". date("r",$l[1]) ."</td>\n";
	echo "<td><a href=Devices-Status.php?dev=$uld>$l[2]</a></td><td>$l[3]</td><td>$l[4]</td></tr>\n";
}
@DbFreeResult($res);

echo "</table><table bgcolor=#666666 $tabtag >\n";
echo "<tr bgcolor=#$bg2><td>$row IF changes in total</td></tr></table>\n";

echo '</td></tr></table>';
include_once ("inc/footer.php");

}elseif ($wol){
	if(preg_match("/dsk/",$_SESSION['group']) ){
		$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
		$query	= GenQuery('nodes','s','*','','',array('mac'),array('='),array($wol));
		$res	= @DbQuery($query,$link);
		$nnod	= @DbNumRows($res);
		if ($nnod != 1) {
			echo "<h4>$wol $n1rmsg</h4>";
			@DbFreeResult($res);
			die;
		}else{
			$n		= @DbFetchRow($res);
			@DbFreeResult($res);
			$ip		= long2ip($n[1]);
		}
		wake($ip,$wol, 9);
	}else{
		echo $nokmsg;
	}
?>
<h5>Magic Packet sent to <?=$ip?></h5>
<script language="JavaScript"><!--
setTimeout("history.go(-1)",10000);
//--></script>
<?

}elseif ($del){
	if(preg_match("/adm/",$_SESSION['group']) ){
		$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
		$query	= GenQuery('nodes','d','','','',array('mac'),array('='),array($del) );
		if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Node $del $delokmsg</h3>";}
		$query	= GenQuery('nodiplog','d','','','',array('mac'),array('='),array($del) );
		if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Node IP log $del $delokmsg</h3>";}
		$query	= GenQuery('nodiflog','d','','','',array('mac'),array('='),array($del) );
		if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Node IF log $del $delokmsg</h3>";}
?>
<script language="JavaScript"><!--
setTimeout("history.go(-2)",2000);
//--></script>
<?
	}else{
		echo $nokmsg;
	}
}

?>
