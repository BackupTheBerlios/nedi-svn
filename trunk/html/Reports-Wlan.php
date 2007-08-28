<?
/*
#============================================================================
# Program: Reports-Wlan.php
# Programmer: Remo Rickli
#
# DATE     COMMENT
# -------- ------------------------------------------------------------------
# 20/04/05 v0.1		initial version.
*/

error_reporting(E_ALL ^ E_NOTICE);

$bg1	= "DFDFD0";
$bg2	= "EFEFE0";

include_once ("inc/header.php");
include_once ('inc/libnod.php');

$_GET = sanitize($_GET);
$ord = isset($_GET['ord']) ? $_GET['ord'] : "";
$all = isset($_GET['all']) ? "checked" : "";
?>
<h1>Wlan Access Points</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/dmobi.png border=0 title="Lists potential APs on ports seeing more than 1 MAC address (show all overrides this).">
</a></th>
<th>Order by
<SELECT name="ord" size=1>
<OPTION VALUE="name" <?=($ord == "name")?"selected":""?> >Name
<OPTION VALUE="ip" <?=($ord == "ip")?"selected":""?> >IP address
<OPTION VALUE="device" <?=($ord == "device")?"selected":""?> >Device

</SELECT>
</th>
<th>
<INPUT type="checkbox" name="all" <?=$all?> > Show all
</th>
<th width=80><input type="submit" value="Show"></th>
</tr></table></form><p>
<?
$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('wlan');
$res	= @DbQuery($query,$link);
if($res){
	$nwmac = 0;
	while( ($w = @DbFetchRow($res)) ){
		$nwmac++;
		$wlap[] = "$w[0]";
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
	die;
}

?>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=4><img src=img/32/mobil.png><br>Name - IP - MAC Match</th>
<th colspan=3><img src=img/32/dev.png><br>Device - IF - Nodes</th>
<th colspan=2><img src=img/32/clock.png><br>First Seen / Last Seen</th>

<?

$query	= GenQuery('nodes');
$res	= @DbQuery($query,$link);
while( ($n = @DbFetchRow($res)) ){
	$macs["$n[6];;$n[7]"]++;
}

	$nap = 0;
	$nno = 0;
	$query	= GenQuery('nodes','s','*',$ord);
	$res	= @DbQuery($query,$link);
	while( ($n = @DbFetchRow($res)) ){
		if($macs["$n[6];;$n[7]"] > 1 or $all){
			$m = substr($n[2],0,8);
			if(in_array("$m", $wlap,1) ){
				if ($row == "1"){ $row = "0"; $bg = $bga; $bi = $bia; }
				else{ $row = "1"; $bg = $bgb; $bi = $bib; }	

				$name	= preg_replace("/^(.*?)\.(.*)/","$1", $n[0]);
				$ip	= long2ip($n[1]);
				$img	= Nimg("$n[2];$n[3]");
				$fs	= date("j.M G:i",$n[4]);
				$ls	= date("j.M G:i",$n[5]);
				$pbar	= Bar($macs[$n[6]][$n[7]],5);
				$ud	= rawurlencode($n[6]);
				list($fc,$lc)	= Agecol($n[4],$n[5],$row);

				echo "<tr bgcolor=#$bg>\n";
				echo "<th bgcolor=#$bi><a href=Nodes-Status.php?mac=$n[2]><img src=img/oui/$img title=\"$n[3] ($n[2])\" border=0></a></th>\n";
				echo "<td>$name</td><td>$ip</td><td>$m</td><td>$n[6]</td><td><a href=Nodes-List.php?ina=device&opa==&sta=$ud&cop=AND&inb=ifname&opb==&stb=$n[7]&>$n[7]</a></td><td>$pbar".$macs["$n[6];;$n[7]"]."</td>\n";
				echo "<td bgcolor=#$fc>$fs</td><td bgcolor=#$lc>$ls</td>";
	
				echo "</tr>\n";
				$nap++;
			}
		}
		$nno++;
	}
echo "</table><table bgcolor=#666666 $tabtag >\n";
echo "<tr bgcolor=#$bg2><td>$nap out of $nno nodes matching $nwmac MAC samples</td></tr></table>\n";

include_once ("inc/footer.php");
?>
