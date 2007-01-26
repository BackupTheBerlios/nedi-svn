<?

/*
#============================================================================
# Program: Devices-Status.php
# Programmer: Remo Rickli
#
# DATE		 COMMENT
# -------- ------------------------------------------------------------------
# 14/04/05	initial version.
# 1/03/06		offline functions, traffic graphs
# 16/03/06	new SQL query support
# 17/01/07	Minor improvements.
*/

$bg1	= "99BBEE";
$bg2	= "AACCFF";
$btag	= "";
$nocache = 0;
$calendar= 0;
$refresh = 0;
$hs	 = 3;

include_once ("inc/header.php");
include_once ('inc/libdev.php');

$_GET = sanitize($_GET);
$shd = isset($_GET['dev']) ? $_GET['dev'] : "";
$dld = isset($_GET['del']) ? $_GET['del'] : "";
$shg = isset($_GET['shg']) ? $_GET['shg'] : "";
$shp = isset($_GET['shp']) ? $_GET['shp'] : "";

?>
<h1>Device Status</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/hwif.png border=0 title="Only works properly, when interfaces in db are up to date!">
</a></th>
<th>
Device <SELECT size=1 name="dev">
<OPTION VALUE="">------------
<?
$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('devices','s','*','name');
$res	= @DbQuery($query,$link);
if($res){
	while( $d = @DbFetchRow($res) ){
		echo "<option value=\"$d[0]\"";
		if($shd == $d[0]){echo "selected";}
		echo ">$d[0]\n";
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
	die;
}
echo '</SELECT></th>';
if($rrdstep){
	echo '<th><input type="checkbox" name="shg" ';
	if($shg){echo "checked";}
	echo "> Traffic Graphs";
}
?>
<th>
<input type="checkbox" name="shp" <?=($shp)?"checked":""?>> Population
</th>
<th width=80>
<input type="submit" value="Show" name="show">
</th>
</tr></table></form><p>
<?
if ($shd){
	$query	= GenQuery('devices','s','*','','',array('name'),array('='),array($shd) );
	$res	= @DbQuery($query,$link);
	$ndev	= @DbNumRows($res);
	if ($ndev != 1) {
		echo "<h4>$_GET[dev] $n1rmsg ($ndev)</h4>";
		@DbFreeResult($res);
		die;
	}
	$dev	= @DbFetchRow($res);
	@DbFreeResult($res);

	$query	= GenQuery('networks','s','*','','',array('device'),array('='),array($shd) );
	$res	= @DbQuery($query,$link);
	while( $n = @DbFetchRow($res) ){
		$net[$n[1]][$n[2]] = ip2long(long2ip($n[3]));		// thanks again PHP (for increased grey hair count to fix netmask)!
	}
	@DbFreeResult($res);

	$ud		= rawurlencode($dev[0]);
	$ip		= ($dev[1]) ? long2ip($dev[1]) : 0;
	$oi		= ($dev[19]) ? long2ip($dev[19]) : 0;
	$img		= $dev[18];
	list($fc,$lc)	= Agecol($dev[4],$dev[5],0);
	$fs		= date("r",$dev[4]);
	$ls		= date("r",$dev[5]);

	$sv		= Syssrv($dev[6]);
	$os		= $dev[8];
	$comm		= $dev[15];
	$ver		= $dev[14] & 127;
?>
<table cellspacing=10 width=100%>
<tr><td width=50% valign=top>
<h2>General Info</h2><p>
<table bgcolor=#666666 <?=$tabtag?> >
<tr>
<th bgcolor=#<?=$bia?> width=140><a href=<?=$_SERVER['PHP_SELF']?>?dev=<?=$ud?> ><img src=img/dev/<?=$img?>.png title="<?=$dev[3]?>" vspace=4 border=0></a><br><?=$dev[0]?></th>
<td bgcolor=#<?=$bg2?>>
<table width=100%><tr><td>
<?
	if ($ip){
?>
<a href=telnet://<?=$ip?>><img src=img/16/kons.png hspace=<?=$hs?> border=0 title="Telnet to device"></a>
<a href=http://<?=$ip?> target=window><img src=img/16/glob.png hspace=<?=$hs?> border=0 title="HTTP to device"></a>
<a href=https://<?=$ip?> target=window><img src=img/16/glok.png hspace=<?=$hs?> border=0 title="HTTPS to device"></a>
<img src=img/sep.png hspace=6>
<a href=Monitoring-Messages.php?ina=source&opa==&sta=<?=$ud?>><img src=img/16/info.png hspace=<?=$hs?> border=0 title="Messages"></a>
<?
		if ($ver){
?>
<a href=Devices-Config.php?shc=<?=$ud?> ><img src=img/16/cfg2.png hspace=<?=$hs?> border=0 title="Config of device"></a>
<a href=Devices-Graph.php?dv=<?=$ud?> ><img src=img/16/chart.png hspace=<?=$hs?> border=0 title="Graphs of device"></a>
<a href=Nodes-List.php?ina=device&opa==&sta=<?=$ud?>&ord=device><img src=img/16/cubs.png hspace=<?=$hs?> border=0 title="Nodes on device"></a>
<?
			if($dev[6] & 2){
?>
<a href=Realtime-Spanningtree.php?dev=<?=$ud?> ><img src=img/16/tabt.png hspace=<?=$hs?> border=0 title="Spanningtree info on switch"></a>
<?
			}
			if($dev[6] > 3){
?>
<a href=Realtime-Routes.php?rtr=<?=$ud?> ><img src=img/16/rout.png hspace=<?=$hs?> border=0 title="Routes on device"></a>
<a href=Realtime-Multicast.php?rtr=<?=$ud?> ><img src=img/16/cam.png hspace=<?=$hs?> border=0 title="Multicast routes on device"></a>
<?
			}
		}
?>
<img src=img/sep.png hspace=6>
<?
		if(preg_match("/adm/",$_SESSION['group']) ){
			if($os == "IOS"){
				$shlog = "sh log";
			}elseif($os == "CatOS"){
				$shlog = "sh logg buf";
			}else{
				$shlog = "";
			}
			if($shlog){
?>
</td><td>&nbsp;<form method="post" action="Devices-Write.php">
<input type="hidden" name="sta" value="<?=$dev[0]?>">
<input type="hidden" name="cmd" value="<?=$shlog?>">
<input type="hidden" name="ina" value="name">
<input type="hidden" name="opa" value="=">
<input type="hidden" name="scm" value="1">
<input type="image" src="img/16/wrte.png" hspace=<?=$hs?> value="Submit" title="Show log">
</form></td><td>
<?
			}
		}
	}
	if(preg_match("/adm/",$_SESSION['group']) ){
?>
<a href=Other-Linked.php?dv=<?=$ud?> ><img src=img/16/wglb.png hspace=<?=$hs?> border=0 title="Edit Links"></a>
<a href=<?=$_SERVER['PHP_SELF']?>?del=<?=$ud?>><img src=img/16/bcnl.png hspace=<?=$hs?> border=0 onclick="return confirm('Schedule for deletion?')"></a>
<?
	}
?>
</td></tr></table>
</td></tr>
<tr><th bgcolor=#<?=$bg1?>>Main IP</th>		<td bgcolor=#<?=$bga?>><?=$ip?></td></tr>
<tr><th bgcolor=#<?=$bg1?>>Original IP</th>	<td bgcolor=#<?=$bga?>><?=$oi?></td></tr>
<tr><th bgcolor=#<?=$bg1?>>Services</th>	<td bgcolor=#<?=$bgb?>><?=$sv?></td></tr>
<tr><th bgcolor=#<?=$bg1?>>Bootimage</th>	<td bgcolor=#<?=$bga?>><?=$dev['9']?> (<?=$os?>)</td></tr>
<tr><th bgcolor=#<?=$bg1?>>Serial #</th>	<td bgcolor=#<?=$bgb?>><?=$dev['2']?></td></tr>
<tr><th bgcolor=#<?=$bg1?>>Description</th>	<td bgcolor=#<?=$bga?>><?=$dev['7']?></td></tr>
<tr><th bgcolor=#<?=$bg1?>>Location</th>	<td bgcolor=#<?=$bgb?>><?=$dev['10']?></td></tr>
<tr><th bgcolor=#<?=$bg1?>>Contact</th>		<td bgcolor=#<?=$bga?>><?=$dev['11']?></td></tr>
<tr><th bgcolor=#<?=$bg1?>>VTP Info</th>	<td bgcolor=#<?=$bgb?>>Domain:<?=$dev['12']?> <?=VTPmod($dev['13'])?></td></tr>
<tr><th bgcolor=#<?=$bg1?>>SNMP Access</th>	<td bgcolor=#<?=$bga?>><?=$dev['15']?> (Version <?=($ver  . (($dev[14] & 128)?" using HC-counters":""));?>)</td></tr>
<tr><th bgcolor=#<?=$bg1?>>CLI Access</th>	<td bgcolor=#<?=$bga?>><?=$dev['17']?> (Port <?=($dev['16'])?$dev['16']:"-"?>)</td></tr>
<tr><th bgcolor=#<?=$bg1?>>First Seen</td>	<td bgcolor=#<?=$fc?>><?=$fs?></td></tr>
<tr><th bgcolor=#<?=$bg1?>>Last Seen</td>	<td bgcolor=#<?=$lc?>><?=$ls?></td></tr>
<?
	if($ver and $rrdstep){
		echo "<tr><th bgcolor=#$bg2>System</th><th bgcolor=#$bgb>";
		echo "<a href=Devices-Graph.php?dv=$ud&cpu=on><img src=inc/drawrrd.php?dv=$ud&t=cpu&s=s border=0 title=\"CPU load\">";
		echo "<a href=Devices-Graph.php?dv=$ud&mem=on><img src=inc/drawrrd.php?dv=$ud&t=mem&s=s border=0 title=\"Available Memory\">";
		echo "<a href=Devices-Graph.php?dv=$ud&tmp=on><img src=inc/drawrrd.php?dv=$ud&t=tmp&s=s border=0 title=\"Temperature\">";
		echo "</th></tr>";
	}

	flush();
	if ($ver){
		echo "<tr><th bgcolor=#$bg2>Uptime</th><td bgcolor=#$bgb>";
		error_reporting(1);
		snmp_set_quick_print(1);
		$uptime	= snmpget("$ip","$comm",".1.3.6.1.2.1.1.3.0",($timeout * 100000) );
		if ($uptime){
			sscanf($uptime, "%d:%d:%d:%d.%d",$upd,$uph,$upm,$ups,$ticks);
			$upmin	= $upm + 60 * $uph + 1440 * $upd;
			if ($upd  < 1) {echo "<img src=img/16/impt.png hspace=10> ";} else { echo "<img src=img/16/bchk.png hspace=10> ";}
			echo sprintf("%d D %d:%02d:%02d",$upd,$uph,$upm,$ups)."</td></tr>\n";
		}else{
			echo $toumsg;
			echo "</td></tr>\n";
		}
	}
	flush();
?>
</table>

<h2>Vlans</h2>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th valign=bottom width=140><img src=img/32/stat.png><br>Vlan</th>
<th valign=bottom><img src=img/32/say.png><br>Name</th></tr>
<?
	if($dev['13'] == 1){
		echo "<tr bgcolor=#$bga><th colspan=2>not shown on VTP clients!</th></tr>\n";
	}else{
		$query	= GenQuery('vlans','s','*','vlanid','',array('device'),array('='),array($shd) );
		$res	= @DbQuery($query,$link); 
		$row  = 0;
		if ( @DbNumRows($res) ){
			while( $v = @DbFetchRow($res) ){
				if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
				$row++;
				echo "<tr bgcolor=#$bg>";
				echo "<th bgcolor=#$bi>$v[1]</th><td>$v[2]</td></tr>\n";
				$nvlan++;
			}
			@DbFreeResult($res);
		}else{
			echo "<tr bgcolor=#$bga><th colspan=2>$resmsg</th></tr>\n";
		}
		echo "</table><table bgcolor=#666666 $tabtag >\n";
		echo "<tr bgcolor=#$bg2><td>$row Vlans</td></tr></table>\n";
		flush();
	}
?>



</td><td width=50% valign=top align=center>

<h2>Modules</h2>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th valign=bottom colspan=3><img src=img/32/nic.png title="Slot, Model and Description"><br>Module</th>
<th valign=bottom><img src=img/32/form.png><br>Serial</th>
<th valign=bottom colspan=3 title="HW / FW / SW"><img src=img/32/cog.png><br>Version</th>
</tr>
<?
	$query	= GenQuery('modules','s','*','slot','',array('device'),array('='),array($shd) );
	$res	= @DbQuery($query,$link); 
	$row  = 0;
	if ( @DbNumRows($res) ){
		while( $m = @DbFetchRow($res) ){
			if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
			$row++;
			echo "<tr bgcolor=#$bg>";
			echo "<th bgcolor=#$bi>$m[1]</th><th>$m[2]</th><td>$m[3]</td><td>$m[4]</td><td>$m[5]</td><td>$m[6]</td><td>$m[7]</td></tr>\n";
		}
		@DbFreeResult($res);
	}else{
		echo "<tr bgcolor=#$bga><th colspan=7>$resmsg</th></tr>\n";
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row Modules</td></tr></table>\n";
	flush();
?>

<h2>Links</h2>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th valign=bottom><img src=img/32/dumy.png><br>Interface</th>
<th valign=bottom><img src=img/32/dev.png><br>Neighbour</th>
<th><img src=img/32/tap.png><br>Bandwidth</th>
<th><img src=img/32/powr.png title="PoE consumption in mW"><br>Power</th>
<th><img src=img/32/fiap.png title="C=CDP,M=Mac,O=Oui,V=VoIP,L=LLDP,S=static"><br>Type</th></tr>
<?
	$query	= GenQuery('links','s','*','','',array('device'),array('='),array($shd) );
	$res	= @DbQuery($query,$link);
	$row  = 0;
	$tpow = 0;							# China in your hand ;-)
	if ( @DbNumRows($res) ){
		while( $l = @DbFetchRow($res) ){
			if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
			$row++;
			$tpow += $l[7];
			$ul = rawurlencode($l[3]);
			echo "<tr bgcolor=#$bg>";
			echo "<th bgcolor=#$bi>$l[2]</th><td><a href=$_SERVER[PHP_SELF]?dev=$ul>$l[3]</a> on $l[4] (Vlan$l[9] $l[8])</td>";
			echo "<td align=right>" . Zfix($l[5]) . "</td><td align=right>$l[7]</td>";
			echo "<td align=center>$l[6]</td></tr>\n";
		}
		@DbFreeResult($res);
	}else{
		echo "<tr bgcolor=#$bga><th colspan=5>$resmsg</th></tr>\n";
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row Links sucking ". $tpow / 1000 . " W total</td></tr></table>\n";
	flush();
?>

</td></tr>
</table>
<h2>Interfaces</h2><p>
<?

	if ($shp ){
		$query	= GenQuery('nodes','g','ifname','','',array('device'),array('='),array($shd) );
		$res	= @DbQuery($query,$link);
		if($res){
			while( ($nc = @DbFetchRow($res)) ){
				$ncount[$nc[0]] = $nc[1];
			}
		}
	}

	$query	= GenQuery('interfaces','s','*','ifidx','',array('device'),array('='),array($shd) );
	$res	= @DbQuery($query,$link);
	$nif = 0;
	while( $i = @DbFetchRow($res) ){
		$ifn[$i[2]] = $i[1];
		$ift[$i[2]] = $i[4];
		$ifa[$i[2]] = $i[8];
		$ifs[$i[2]] = ZFix($i[9]);
		$ifd[$i[2]] = $i[10];
		$ifi[$i[2]] = "$i[6] <i>$i[7]</i> <b>$i[16]</b>";
		$ifv[$i[2]] = $i[11];
		$ifm[$i[2]] = $i[5];
		$ino[$i[2]] = $i[12];
		$ine[$i[2]] = $i[13];
		$oto[$i[2]] = $i[14];
		$ote[$i[2]] = $i[15];
		$nif++;
	}
	@DbFreeResult($res);

	if(!$nif){
		echo "</table>\n";
		echo $resmsg;
		echo "<div align=center>$query</dev>";
		include_once ("inc/footer.php");
		die;
	}
?>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2 valign=bottom><img src=img/16/dumy.png title="Realtime, operational status"><br>Name</th>
<th valign=bottom><img src=img/16/stat.png title="DB value"><br>Vlan</th>
<th valign=bottom><img src=img/16/find.png title="DB value"><br>Info</th>
<th valign=bottom><img src=img/spd.png title="DB value"><br>Speed</th>
<th valign=bottom><img src=img/dpx.png title="DB value"><br>Duplex</th>
<th valign=bottom><img src=img/cal.png title="Realtime, last status change"><br>Last Chg</th>
<? 
if($shp){
?>
<th valign=bottom><img src=img/16/cubs.png title="Population"><br>Pop</th>
<?
}
if($rrdstep and $shg){
	echo '<th valign=bottom><img src=img/16/3d.png><br>Traffic/Errors</th>';
}else{
 ?>
<th valign=bottom><img src=img/dl.png title="DB value"><br>In Octets</th>
<th valign=bottom><img src=img/ul.png title="DB value"><br>Out Octets</th>
<th valign=bottom><img src=img/xr.png title="DB value"><br>In Err</th>
<th valign=bottom><img src=img/xg.png title="DB value"><br>Out Err</th>
<?
}
?>
<th valign=bottom><img src=img/netg.png title="DB value"><br>Address</th>
<?
	if($uptime){
		foreach (snmprealwalk("$ip","$comm",".1.3.6.1.2.1.2.2.1.8") as $ix => $val){
			$ifost[substr(strrchr($ix, "."), 1 )] = $val;
		}
		foreach (snmprealwalk("$ip","$comm",".1.3.6.1.2.1.2.2.1.9") as $ix => $val){
			$iflac[substr(strrchr($ix, "."), 1 )] = $val;
		}
	}
	$row = 0;
	foreach ( $ifn as $i => $in){
		if ($row == "1"){ $row = "0"; $off=180;}else{ $row = "1"; $off=195; }
		$bg3= sprintf("%02x",$off);
		$rs = $gs = $bg3;
		$bg = $bg3.$bg3.$bg3;
		$bio= $bie = $bg3;
		$ui = rawurlencode($in);
		if ($ifa[$i] == "1"){$gs = sprintf("%02x",60 + $off);}
		if ($ifost[$i] == "2" or $ifost[$i] == "down"){$rs = sprintf("%02x",60 + $off);}
		if ($ino[$i] > 70){											// Ignore the first 70  packets.
			$bio = sprintf("%02x","40" + $off);
			$ier = $ine[$i] * $ine[$i]/ $ino[$i];
		            if ($ier > 55){$ier = 55;}
		            $bie = sprintf("%02x", $ier + $off);
		}
		$boo = $boe = $bg;
		if ($oto[$i] > 70){											// Ignore the first 70  packets.
			$boo = sprintf("%02x","40" + $off);
			$oer = $ote[$i] * $ote[$i]/ $oto[$i];
		            if ($oer > 55){$oer = 55;}
		            $boe = sprintf("%02x", $oer + $off);
		}
		sscanf($iflac[$i], "%d:%d:%d:%d.%d",$lcd,$lch,$lcm,$lcs,$ticks);
		$il		= $upmin - ($lcm + 60 * $lch + 1440 * $lcd);
		if($il <= 0){
			$iflch	= "-";
			$bl	= $bg3;
		}else{
			$ild	= intval($il / 1440);
			$ilh	= intval(($il - $ild * 1440)/60);
			$ilm	= intval($il - $ild * 1440 - $ilh * 60);
			$iflch	= sprintf("%d D %d:%02d",$ild,$ilh,$ilm);
			$rblcm	= $off + 1000/($il + 1);
			if($rblcm > 255){$rblcm = 255;}
			$bl		= sprintf("%02x",$rblcm );
		}
		list($ifimg,$iftit)	= Iftype($ift[$i]);

		echo "<tr bgcolor=#$bg>";
		echo "<th bgcolor=#$rs$gs$bg3><img src=img/$ifimg title=\"$i - $iftit\" vspace=8></th>\n";
		echo "<td><a href=Nodes-List.php?ina=device&opa==&sta=$ud&cop=AND&inb=ifname&opb==&stb=$ui>$in</td>\n";
		echo "<td align=center>$ifv[$i]</td><td>$ifi[$i]</td>\n";
		echo "<td align=right>$ifs[$i]</td><td align=center>$ifd[$i]</td>\n";
		echo "<td align=right bgcolor=#$bl$bg3$bg3>$iflch</td>\n";

		if($shp){
			if($ncount[$in]){
				echo "<td>" . Bar($ncount[$in],8) . " $ncount[$in]</td>\n";
			}else{
				echo "<td>-</td>\n";}
			}
		if($rrdstep and $shg){
			if($ud and $ui ){
				echo "<td nowrap align=center>\n";
				echo "<a href=Devices-Graph.php?dv=$ud&if%5B%5D=$ui><img src=inc/drawrrd.php?dv=$ud&if%5B%5D=$ui&s=s&t=trf border=0>\n";
				echo "<img src=inc/drawrrd.php?dv=$ud&if%5B%5D=$ui&s=s&t=err border=0></a>\n";
			}else{
				echo "<td></td>";
			}
		}else{
			echo "<td bgcolor=#$bg3$bg3$bio align=right>".$ino[$i]."</td>\n";
			echo "<td bgcolor=#$bg3$bg3$boo align=right>".$oto[$i]."</td>\n";
			echo "<td bgcolor=#$bie$bg3$bg3 align=right>".$ine[$i]."</td>\n";
			echo "<td bgcolor=#$boe$bg3$bg3 align=right>".$ote[$i]."</td>\n";
		}
		echo "<td>";
		if($ifm[$i]){echo "<a href=Nodes-Status.php?mac=$ifm[$i]>$ifm[$i]</a><br>";}
		foreach ($net[$in] as $ip => $dmsk){
			list($pfix,$msk,$bmsk)	= Masker($dmsk);
			echo long2ip($ip)."/$pfix ";
		}
		echo "</td></tr>\n";
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$nif Interfaces</td></tr></table>\n";
}elseif ($dld){
	if(preg_match("/adm/",$_SESSION['group']) ){
		$now = time();
		$query	= GenQuery('devdel','i','','','',array('device','user','time'),'',array($dld,$_SESSION['user'],$now) );
		if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Device $_GET[del] $upokmsg</h3>";}
?>
<script language="JavaScript"><!--
setTimeout("history.go(-2)",2000);
//--></script>
<?
	}else{
		echo $nokmsg;
	}
}
include_once ("inc/footer.php");
