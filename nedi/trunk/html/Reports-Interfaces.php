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
# 22/03/07	relative counter support, new option scheme
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
$alt = isset($_GET['alt']) ? "checked" : "";
$opt = isset($_GET['opt']) ? "checked" : "";
?>
<h1>Interface Reports</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/ddum.png border=0 title="Use alternative and optimize for more specific details">
</a></th>
<th>Select Report(s)</th>
<th>
<SELECT MULTIPLE name="rep[]" size=4>
<OPTION VALUE="uif" <? if(in_array("uif",$rep)){echo "selected";} ?> >Used Interfaces
<OPTION VALUE="dif" <? if(in_array("dif",$rep)){echo "selected";} ?> >Disabled Interfaces
<OPTION VALUE="itr" <? if(in_array("itr",$rep)){echo "selected";} ?> >Traffic
<OPTION VALUE="lmi" <? if(in_array("lmi",$rep)){echo "selected";} ?> >Link Mismatch
</SELECT>

</th>
<th>Limit
<SELECT size=1 name="lim">
<? selectbox("limit",$lim);?>
</SELECT>
</th>
<th>
<INPUT type="checkbox" name="opt"  <?=$opt?> title="Make traffic relative to IF speed and errors relative to traffic"> optimize
</th>
<th>
<INPUT type="checkbox" name="alt"  <?=$alt?> title="Show ethernet only on used IFs, incomplete link mismatches and total traffic counters"> alternative
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
	$ndif = 0;
	$nummo	= array();
	while( ($i = @DbFetchRow($res)) ){
		if($alt){
			if($i[4] == 6){								# alternatively only show ethernet IF or absolute counters
				$numif[$i[0]]++;
				if($i[12] > 70){$nactif[$i[0]]++;}
			}
			if($opt){
				if($i[12]){$topier["$i[0];;$i[1]"] = $i[13]/$i[12];}		# Using a flat array for  value based sorting
				if($i[14]){$topoer["$i[0];;$i[1]"] = $i[15]/$i[14];}		# Normalize errors to traffic...
				if($i[9]){							# ...and traffic to IF speed if available
					$topino["$i[0];;$i[1]"] = $i[12]/$i[9];
					$topoto["$i[0];;$i[1]"] = $i[14]/$i[9];
				}
			}else{
				$topino["$i[0];;$i[1]"] = $i[12];
				$topier["$i[0];;$i[1]"] = $i[13];
				$topoto["$i[0];;$i[1]"] = $i[14];
				$topoer["$i[0];;$i[1]"] = $i[15];
			}
		}else{
			$numif[$i[0]]++;
			if($i[12] > 70){$nactif[$i[0]]++;}
			if($opt){
				if($i[16]){$topier["$i[0];;$i[1]"] = $i[17]/$i[16];}
				if($i[18]){$topoer["$i[0];;$i[1]"] = $i[19]/$i[18];}
				if($i[9]){
					$topino["$i[0];;$i[1]"] = $i[16]/$i[9]/8;
					$topoto["$i[0];;$i[1]"] = $i[18]/$i[9]/8;
				}
			}else{
				$topino["$i[0];;$i[1]"] = $i[16];
				$topier["$i[0];;$i[1]"] = $i[17];
				$topoto["$i[0];;$i[1]"] = $i[18];
				$topoer["$i[0];;$i[1]"] = $i[19];
			}
		}
		$ifal["$i[0];;$i[1]"] = $i[7];
		$ifsp["$i[0];;$i[1]"] = $i[9];
		$ifdu["$i[0];;$i[1]"] = $i[10];
		$ifvl["$i[0];;$i[1]"] = $i[11];
		if($i[8] == 2){$ndif++;$disif[$i[0]] .= "$i[1] ";}
		$nif++;
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
	die;
}

if ( in_array("uif",$rep) ){
	foreach ($numif as $dv => $ni){
		$ainorm[$dv] = intval(100 * $nactif[$dv] / $ni);
	}
	arsort($ainorm);

?>
<table cellspacing=10 width=100%>
<tr><td width=50% valign=top align=center>

<h2>Most Used Devices</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2 width=25%><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/dumy.png><br>Total Interfaces</th>
<th><img src=img/32/cnic.png><br>Used Interfaces</th>
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
	echo "<tr bgcolor=#$bg2><td>$row most used devices shown</td></tr></table>\n";
?>
</td><td width=50% valign=top align=center>

<h2>Least Used Devices</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2 width=25%><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/dumy.png><br>Total Interfaces</th>
<th><img src=img/32/cnic.png><br>Used Interfaces</th>
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
	echo "<tr bgcolor=#$bg2><td>$row least used devices shown</td></tr></table></td></tr></table>\n";
}

if ( in_array("dif",$rep) ){
?>
<h2>Disabled Interfaces</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2 width=25%><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/bstp.png><br>Disabled Interfaces</th>
<?
	if($ndif){
		if($alt){
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
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$ndif disabled interfaces on $row devices in total</td></tr></table>\n";
}

if ( in_array("itr",$rep) ){
	if($opt){$relt = "relative";}else{$relt = "absolute";}
	if($alt){$tott = "in total";}else{$tott = "recently";}

?>
<table cellspacing=10 width=100%>
<tr><td width=50% valign=top align=center>

<h2>Inbound Traffic</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2 width=25%><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/dumy.png><br>Interface</th>
<th><img src=img/32/tap.png><br>Octets</th>
<?
	arsort($topino);
	$row = 0;
	foreach ($topino as $di => $io){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$d = explode(';;', $di);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi>$row</th><td><a href=Devices-Status.php?dev=$d[0]>$d[0]</a></td>\n";
		echo "<td><a href=Nodes-List.php?ina=device&opa==&sta=$d[0]&cop=AND&inb=ifname&opb==&stb=$d[1]>$d[1]</a> $ifal[$di] (";
		echo Zfix($ifsp[$di]) .")</td>\n";
		if($rrdstep){
			if($dn = rawurlencode($d[0]) and $if = rawurlencode($d[1]) ){
				echo "<td align=center>\n";
				echo "<a href=Devices-Graph.php?dv=$dn&if%5B%5D=$if>";
				echo "<img src=inc/drawrrd.php?dv=$dn&if%5B%5D=$if&s=s&t=trf border=0 title=\"$io\"></a>\n";
			}else{
				echo "<td></td>";
			}
		}else{
			echo "<td>$io</td></tr>\n";
		}
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>Top $lim Interfaces by $relt traffic $tott</td></tr></table>\n";
?>
</td><td width=50% valign=top align=center>

<h2>Inbound Errors</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2 width=25%><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/dumy.png><br>Interface</th>
<th><img src=img/32/err.png><br>Errors</th>
<?
	arsort($topier);
	$row = 0;
	foreach ($topier as $di => $ie){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$d = explode(';;', $di);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi>$row</th><td><a href=Devices-Status.php?dev=$d[0]>$d[0]</a></td>\n";
		echo "<td><a href=Nodes-List.php?ina=device&opa==&sta=$d[0]&cop=AND&inb=ifname&opb==&stb=$d[1]>$d[1]</a> $ifal[$di]</td>\n";
		if($rrdstep){
			if($dn = rawurlencode($d[0]) and $if = rawurlencode($d[1]) ){
				echo "<td align=center>\n";
				echo "<a href=Devices-Graph.php?dv=$dn&if%5B%5D=$if>";
				echo "<img src=inc/drawrrd.php?dv=$dn&if%5B%5D=$if&s=s&t=err border=0 title=\"$ie\"></a>\n";
			}else{
				echo "<td></td>";
			}
		}else{
			echo "<td>$ie</td></tr>\n";
		}
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>Top $lim Interfaces by $relt errors $tott</td></tr></table></td></tr><tr><td>\n";
?>
<h2>Outbound Traffic</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2 width=25%><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/dumy.png><br>Interface</th>
<th><img src=img/32/tap.png><br>Octets</th>
<?
	arsort($topoto);
	$row = 0;
	foreach ($topoto as $di => $oo){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$d = explode(';;', $di);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi>$row</th><td><a href=Devices-Status.php?dev=$d[0]>$d[0]</a></td>\n";
		echo "<td><a href=Nodes-List.php?ina=device&opa==&sta=$d[0]&cop=AND&inb=ifname&opb==&stb=$d[1]>$d[1]</a> $ifal[$di] (";
		echo Zfix($ifsp[$di]) .")</td>\n";
		if($rrdstep){
			if($dn = rawurlencode($d[0]) and $if = rawurlencode($d[1]) ){
				echo "<td align=center>\n";
				echo "<a href=Devices-Graph.php?dv=$dn&if%5B%5D=$if>";
				echo "<img src=inc/drawrrd.php?dv=$dn&if%5B%5D=$if&s=s&t=trf border=0 title=\"$oo\"></a>\n";
			}else{
				echo "<td></td>";
			}
		}else{
			echo "<td>$oo</td></tr>\n";
		}
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>Top $lim Interfaces by $relt traffic $tott</td></tr></table>\n";
?>
</td><td width=50% valign=top align=center>

<h2>Outbound Errors</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2 width=25%><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/dumy.png><br>Interface</th>
<th><img src=img/32/err.png><br>Errors</th>
<?
	arsort($topoer);
	$row = 0;
	foreach ($topoer as $di => $oe){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$d = explode(';;', $di);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi>$row</th><td><a href=Devices-Status.php?dev=$d[0]>$d[0]</a></td>\n";
		echo "<td><a href=Nodes-List.php?ina=device&opa==&sta=$d[0]&cop=AND&inb=ifname&opb==&stb=$d[1]>$d[1]</a> $ifal[$di]</td>\n";
		if($rrdstep){
			if($dn = rawurlencode($d[0]) and $if = rawurlencode($d[1]) ){
				echo "<td align=center>\n";
				echo "<a href=Devices-Graph.php?dv=$dn&if%5B%5D=$if>";
				echo "<img src=inc/drawrrd.php?dv=$dn&if%5B%5D=$if&s=s&t=err border=0 title=\"$oe\"></a>\n";
			}else{
				echo "<td></td>";
			}
		}else{
			echo "<td>$oe</td></tr>\n";
		}
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>Top $lim Interfaces by $relt errors $tott</td></tr></table>\n";


	echo '</td></tr></table>';
}

if ( in_array("lmi",$rep) ){
?>
<h2>Link Mismatch</h2><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th width=80><img src=img/32/fiqu.png title="Speed,Vlan or Duplex are tested"><br>Mismatch</th>
<th><img src=img/32/dev.png title="Check alt-order to show incomplete entries as well"><br>Device</th>
<th colspan=2><img src=img/32/dumy.png title="Bold value is link related and tested here..."><br>Interface</th>
<th width=60><img src=img/32/fiap.png title="C=CDP,M=Mac,O=Oui,V=VoIP,L=LLDP,S=static"><br>Type</th>
<th><img src=img/32/dev.png><br>Device</th>
<th colspan=2><img src=img/32/dumy.png title="...IF related value in () just for reference"><br>Interface</th>
<?
	$libw	= array();
	$query	= GenQuery('links');
	$res	= @DbQuery($query,$link);
	$nli    = @DbNumRows($res);
	if($res){
		$row = 0;
		while( ($l = @DbFetchRow($res)) ){
			$libw[$l[1]][$l[2]][$l[3]][$l[4]] = $l[5];			# Bandwidth is the only value, which is constructed from local IF in SNMP::CDP/LLDP
			$lity[$l[1]][$l[2]][$l[3]][$l[4]] = $l[6];
			$lidu[$l[1]][$l[2]][$l[3]][$l[4]] = $l[8];			# Duplex and Vlan are read via CDP from remote side
			$livl[$l[1]][$l[2]][$l[3]][$l[4]] = $l[9];
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
		die;
	}
	$row = 0;
	foreach(array_keys($libw) as $dv){
		foreach(array_keys($libw[$dv]) as $if){
			foreach(array_keys($libw[$dv][$if]) as $nb){
				foreach(array_keys($libw[$dv][$if][$nb]) as $ni){
					$ud = urlencode($dv);
					$un = urlencode($nb);
					if($alt or $libw[$dv][$if][$nb][$ni] and $libw[$nb][$ni][$dv][$if]){
						if($libw[$dv][$if][$nb][$ni] != $libw[$nb][$ni][$dv][$if]){
							if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
							$row++;
							echo "<tr bgcolor=#$bg>\n";
							echo "<th bgcolor=$bi><img src=img/spd.png title=\"bandwidth\"></th>\n";
							echo "<td><a href=Devices-Status.php?dev=$ud>$dv</a></td><td>$if (".Zfix($ifsp["$dv;;$if"]).")</td>\n";
							echo "<th>".Zfix($libw[$dv][$if][$nb][$ni])."</th>\n";
							echo "<th bgcolor=$bi>".$lity[$dv][$if][$nb][$ni]."</th>\n";
							echo "<td><a href=Devices-Status.php?dev=$un>$nb</a></td><td>$ni (".Zfix($ifsp["$nb;;$ni"]).")</td>\n";
							echo "<th>".Zfix($libw[$nb][$ni][$dv][$if])."</th></tr>\n";
						}
					}
					if ($alt or strlen($lidu[$dv][$if][$nb][$ni]) == 2 and strlen($lidu[$nb][$ni][$dv][$if]) == 2){ 
						if($lidu[$dv][$if][$nb][$ni] != $lidu[$nb][$ni][$dv][$if]){
							if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
							$row++;
							echo "<tr bgcolor=#$bg>\n";
							echo "<th bgcolor=$bi><img src=img/dpx.png title=\"duplex\"></th>\n";
							echo "<td><a href=Devices-Status.php?dev=$ud>$dv</a></td><td>$if (".$ifdu["$dv;;$if"].")</td>\n";
							echo "<th>".$lidu[$dv][$if][$nb][$ni]."</th>\n";
							echo "<th bgcolor=$bi>".$lity[$dv][$if][$nb][$ni]."</th>\n";
							echo "<td><a href=Devices-Status.php?dev=$un>$nb</a></td><td>$ni (".$ifdu["$nb;;$ni"].")</td>\n";
							echo "<th>".$lidu[$nb][$ni][$dv][$if]."</th></tr>\n";
						}
					}
					if($alt or $livl[$dv][$if][$nb][$ni] and $livl[$nb][$ni][$dv][$if]){
						if($livl[$dv][$if][$nb][$ni] != $livl[$nb][$ni][$dv][$if]){
							if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
							$row++;
							echo "<tr bgcolor=#$bg>\n";
							echo "<th bgcolor=$bi><img src=img/16/stat.png title=\"vlan\"></th>\n";
							echo "<td><a href=Devices-Status.php?dev=$ud>$dv</a></td><td>$if (Vlan".$ifvl["$dv;;$if"].")</td>\n";
							echo "<th>Vlan".$livl[$dv][$if][$nb][$ni]."</th>\n";
							echo "<th bgcolor=$bi>".$lity[$dv][$if][$nb][$ni]."</th>\n";
							echo "<td><a href=Devices-Status.php?dev=$un>$nb</a></td><td>$ni (Vlan".$ifvl["$nb;;$ni"].")</td>\n";
							echo "<th>Vlan".$livl[$nb][$ni][$dv][$if]."</th></tr>\n";
						}
					}
					if($row == $lim){break;}
				}
				if($row == $lim){break;}
			}
			if($row == $lim){break;}
		}
		if($row == $lim){break;}
	}
	if($alt){$nli .= " <b>incomplete</b>";}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row canditates of $nli links in total</td></tr></table>\n";
}
}

include_once ("inc/footer.php");
?>
