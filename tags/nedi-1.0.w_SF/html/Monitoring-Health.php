<?
/*
#============================================================================
# Program: Monitoring-Health.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 08/06/05	initial version.
# 10/03/06	new SQL query support
*/

error_reporting(E_ALL ^ E_NOTICE);

$bg1	= "DDBBAA";
$bg2	= "EEDDBB";
$btag	= "";
$maxcol	= 8;
$nocache= 0;
$calendar= 0;
$refresh = 1;

include_once ("inc/header.php");
include_once ('inc/libdev.php');
include_once ('inc/libmon.php');

$_GET = sanitize($_GET);
$cty = isset( $_GET['cty']) ? $_GET['cty'] : "";
$bld = isset( $_GET['bld']) ? $_GET['bld'] : "";

$cpos = strpos($locformat, "c");
$bpos = strpos($locformat, "b");
$fpos = strpos($locformat, "f");
$rpos = strpos($locformat, "r");
$kpos = strpos($locformat, "k");

if(!($cpos === false) and $cty){
	$loc[$cpos] = $cty;
}
if(!($bpos === false and $bld) ){
	$loc[$bpos] = $bld;
}
$locstr = implode($locsep, $loc);
 
?>
<h1>Monitoring Health</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/neth.png border=0 title="Monitoring overview and easy access to its setup.">
</a></th>
<th>Test: 
<a href=inc/alarm1.mp3 controls=true><img src=img/16/spkr.png border=0 title='little alarm' hspace=20></a>
<a href=inc/alarm2.mp3 controls=true><img src=img/16/spkr.png border=0 title='serious alarm' hspace=20></a>
<a href=inc/alarm3.mp3 controls=true><img src=img/16/spkr.png border=0 title='panic alarm' hspace=20></a>
</th>
</tr></table></form>
<?
$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('monitoring');
$res	= @DbQuery($query,$link);
if($res){
	$nmon= 0;
	$mal = 0;
	$ssn = 0;
	$man = 0;
	$lck = 0;
	$loq = 0;
	while( ($m = @DbFetchRow($res)) ){
		$mon[$m[0]]['al'] = $m[1];
		if($m[1]){$mal++;}
		if($m[3]){$ssn++;}
		if($m[4]){$man++;}
		if($m[5] > $lck){$lck = $m[5];}
		$loq += $m[7];
		$nmon++;
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}

$query	= GenQuery('devices','s','*','','',array('location'),array('regexp'),array($locstr) );
$res	= @DbQuery($query,$link);
if($res){
	$ndev = 0;
	while( ($d = @DbFetchRow($res)) ){
		$ndev++;
		$l = explode($locsep, $d[10]);
		if($cpos === false){
			$c = 'Campus';
		}else{
			$c = $l[$cpos];
		}
		if($bpos === false){
			$b = 'Building';
		}else{
			$b = $l[$bpos];
		}
		if( isset($mon[$d[0]]['al']) ){
			$dcity[$c]['mn']++;
			$dbuild[$c][$b]['mn']++;
			$dcity[$c]['al'] += $mon[$d[0]]['al'];
			$dbuild[$c][$b]['al'] += $mon[$d[0]]['al'];
			$mn = 1;
		}else{
			$mn = 0;
		}
		$dcity[$c]['nd']++;
		$dbuild[$c][$b]['nd']++;
	 	if($cty and $bld){
			$dev[$l[$fpos]][$l[$rpos]][$d[0]]['ip'] = $d[1];
			$dev[$l[$fpos]][$l[$rpos]][$d[0]]['ic'] = $d[18];
			$dev[$l[$fpos]][$l[$rpos]][$d[0]]['co'] = $d[11];
			$dev[$l[$fpos]][$l[$rpos]][$d[0]]['rk'] = $l[$kpos];
			$dev[$l[$fpos]][$l[$rpos]][$d[0]]['al'] = $mon[$d[0]]['al'];
			$dev[$l[$fpos]][$l[$rpos]][$d[0]]['mn'] = $mn;
		}
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}
if($mal == 1){
	echo "<EMBED SRC=inc/alarm1.mp3 VOLUME=100 HIDDEN=true>\n";
}elseif($mal == 2){
	echo "<EMBED SRC=inc/alarm2.mp3 VOLUME=100 HIDDEN=true>\n";
}elseif($mal > 2){
	echo "<EMBED SRC=inc/alarm3.mp3 VOLUME=100 HIDDEN=true>\n";
}

?>
<h2 align=center>Summary</h2>
<table cellspacing=10 width=100%>
<tr><td valign=top>
<h3>Statistics</h3><p>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th><img src=img/32/db.png><br>Item</th>
<th><img src=img/32/form.png><br>Value</th>
<tr bgcolor=#<?=$bib?> ><th>Current Problems</th><td align=center><?=($mal)?"<h4>$mal</4>":"<h5>0</5>"?></td></tr>
<tr bgcolor=#<?=$bga?> ><th>Total Lost Queries</th><td align=center><?=$loq?></td></tr>
<tr bgcolor=#<?=$bga?> ><th>Last Check</th><td align=center><?=date("D G:i",$lck)?></td></tr>
<tr bgcolor=#<?=$bgb?> ><th>Devices using SMS</th><td align=center><?=$ssn?></tr>
<tr bgcolor=#<?=$bgb?> ><th>Devices using Mail</th><td align=center><?=$man?></td></tr>
<tr bgcolor=#<?=$bgb?> ><th>Monitored Devices</th><td align=center><?=$nmon?></tr>
<tr bgcolor=#<?=$bg2?> ><th>Total Devices</th><td align=center><?=$ndev?></td></tr>
</table>

</td><td valign=top align=center>

<h3>Messages</h3><p>
<?
$query	= GenQuery('messages','g','level','level desc');
$res	= @DbQuery($query,$link);
if($res){
	$nlev = @DbNumRows($res);
	if($nlev){
?>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th width=80><img src=img/32/impt.png><br>Level</th>
<th><img src=img/32/eyes.png><br>Events</th>
<?
		$row = 0;
		while( ($msg = @DbFetchRow($res)) ){
			if ($row == "1"){ $row = "0"; $bg = $bga; $bi = $bia; }
			else{ $row = "1"; $bg = $bgb; $bi = $bib; }	
			$mbar = Bar($msg[1],0);
			echo "<tr bgcolor=#$bg>\n";
			echo "<th bgcolor=#$bi><a href=Monitoring-Messages.php?lvl=$msg[0]><img src=img/32/" . $mico[$msg[0]] . ".png border=0 title=" . $mlvl[$msg[0]] . "></a></th><td>$mbar $msg[1]</td></tr>\n";
		}
		echo "<tr bgcolor=#$bg2><td colspan=2>$nlev Levels ($query)</td></tr></table>\n";
	}else{
		echo '<h5>No Messages</h5>';	
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}
echo '</td></tr></table>';

if ($cpos !== false and !$cty){
	Cities();
}elseif ($bpos !== false and !$bld){
	if(!$cty){$cty = $c;}
	Buildings($cty);
}elseif ($fpos !== false){
	Floors($cty,$bld);
}else{
	echo $resmsg;	
}
include_once ("inc/footer.php");

// Draw cities
function Cities(){

	global $dcity,$maxcol,$bg1,$tabtag;

	echo "<h2 align=center>Corporate Network</h2>\n";

	ksort($dcity);

	echo "<table bgcolor=#$bg1 $tabtag ><tr>\n";

	$col = 0;
	foreach (array_keys($dcity) as $city){

		$nd = $dcity[$city]['nd'];
		$mn = isset( $dcity[$city]['mn']) ? $dcity[$city]['mn'] : 0;
		$al = isset( $dcity[$city]['al']) ? $dcity[$city]['al'] : 0;
		list($bgm,$stat) = GetStatus($nd,$mn,$al);

		if ($col == $maxcol){
			$col = 0;
			echo "</tr><tr>";
		}
		$ci = CtyImg($nd);
		$ucity = str_replace(" ","%20",$city);
                echo "\t<th bgcolor=#$bgm valign=bottom><a href=$_SERVER[PHP_SELF]?cty=$ucity><img src=img/$ci.png title=\"$mn monitored, $stat\" border=0></a>\n";
		echo "<br><a href=Monitoring-Setup.php?loc=$city>$city</a></th>\n";
                $col++;
	}
	echo "<tr></table>\n";
}

// Draw building table of a city
function Buildings($city){

	global $dcity,$dbuild,$maxcol,$redbuild,$bg2,$tabtag;

	
	echo "<br><h2>$city</h2>\n";
	echo "<table bgcolor=#$bg2 $tabtag ><tr>\n";

	$col = 0;
	ksort($dbuild[$city]);
	foreach (array_keys($dbuild[$city]) as $bld){

		$nd = $dbuild[$city][$bld]['nd'];
		$mn = isset( $dbuild[$city][$bld]['mn']) ? $dbuild[$city][$bld]['mn'] : 0;
		$al = isset( $dbuild[$city][$bld]['al']) ? $dbuild[$city][$bld]['al'] : 0;
		list($bgm,$stat) = GetStatus($nd,$mn,$al);

		if ($col == $maxcol){
			$col = 0;
			echo "</tr><tr>";
		}
		$bi = BldImg($nd,$bld);
		$ucity = str_replace(" ","%20",$city);
		$ubld = str_replace(" ","%20",$bld);

		echo "\t<th bgcolor=#$bgm valign=bottom>\n";
		echo "<a href=$_SERVER[PHP_SELF]?cty=$ucity&bld=$ubld><img src=img/$bi.png title=\"$mn monitored, $stat\" border=0></a>\n";
		echo "<br><a href=Monitoring-Setup.php?loc=$bld>$bld</a>\n";
		echo "</th>\n";
		$col++;
	}
	echo "<tr></table>\n";
}

// Draw floor table of a building
function Floors($cty,$bld){

	global $maxcol,$dev,$bg1,$bg2,$tabtag;

	echo "<h2>$cty - $bld</h2>\n";
	echo "<table bgcolor=#$bg1 $tabtag >\n";
	
	uksort($dev, "floorsort");
	foreach (array_keys($dev) as $fl){
		ksort( $dev[$fl] );
		echo "<tr>\n\t<td bgcolor=$bg2 width=80><h2><img src=img/stair.png><br>$fl</h2></td>\n";
		$col = 0;
		foreach (array_keys($dev[$fl]) as $rm ){
			foreach (array_keys($dev[$fl][$rm]) as $d){
				$di = $dev[$fl][$rm][$d]['ic'];
				$ip = long2ip($dev[$fl][$rm][$d]['ip']);
				$co = $dev[$fl][$rm][$d]['co'];
				$rk = $dev[$fl][$rm][$d]['rk'];
				$mn = $dev[$fl][$rm][$d]['mn'];
				$al = $dev[$fl][$rm][$d]['al'];
		
				list($bgm,$stat) = GetStatus(1,$mn,$al);
				if ($col == $maxcol){
					$col = 0;
					echo "</tr><tr><td>&nbsp;</td>\n";
				}
				echo "<td bgcolor=#$bgm valign=top>\n";
				echo "<b>$rm</b> $rk<p><center>\n";
				echo "<a href=Devices-Status.php?dev=$d><img src=img/dev/$di.png border=0 vspace=4 title=\"$stat\"></a><br>\n";
				echo "<a href=Monitoring-Messages.php?ina=source&opa==&sta=$d><b>$d</b></a><p>\n";
				echo "<a href=telnet://$ip>$ip</a><br>\n";
				echo"$co</center></td>\n";
				$col++;
			}
		}
	}
	echo "<tr></table>\n";
}
?>
