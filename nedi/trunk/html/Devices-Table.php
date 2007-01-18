<?

/*
#============================================================================
# Program: Devices-Table.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 6/05/05		initial version.
# 17/03/06		new SQL query support
*/

error_reporting(E_ALL ^ E_NOTICE);

$bg1	= "66AACC";
$bg2	= "77BBDD";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;
$maxcol	= 6;

include_once ("inc/header.php");
include_once ('inc/libdev.php');

$_GET = sanitize($_GET);
$cty = isset($_GET['cty']) ? $_GET['cty'] : "";
$bld = isset($_GET['bld']) ? $_GET['bld'] : "";

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
$locstr = preg_replace("/\./","\.",$locstr);
$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('devices','s','*','','',array('location'),array('regexp'),array($locstr) );
$res	= @DbQuery($query,$link);

if($res){
	while( ($d = @DbFetchRow($res)) ){
		$ndev++;
		$l = explode($locsep, $d[10]);
		if(!($cpos === false) ){
			$c = $l[$cpos];
		}else{
			$c = 'Campus';
		}
		$dcity[$c]++;
		if(!($bpos === false) ){
			$b = $l[$bpos];
		}else{
			$b = 'Building';
		}
		$dbuild[$c][$b]['nd']++;
		if($d[6] > 3){$dbuild[$c][$b]['nr']++;}
	 	if($cty and $bld){
			$dev[$l[$fpos]][$l[$rpos]][$d[0]]['ip'] = $d[1];
			$dev[$l[$fpos]][$l[$rpos]][$d[0]]['ty'] = $d[3];
			$dev[$l[$fpos]][$l[$rpos]][$d[0]]['co'] = $d[11];
			$dev[$l[$fpos]][$l[$rpos]][$d[0]]['rk'] = $l[$kpos];
			$dev[$l[$fpos]][$l[$rpos]][$d[0]]['mn'] = $mon[$d[0]]['mn'];
			$dev[$l[$fpos]][$l[$rpos]][$d[0]]['al'] = $mon[$d[0]]['al'];
			$dev[$l[$fpos]][$l[$rpos]][$d[0]]['ic'] = $d[18];
		}
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}

?>
<h1>Device Table</h1>

<form method="get" action="<?=$_SERVER['PHP_SELF']?>" name="dtab">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>><img src=img/32/tabi.png border=0 title="Drill down to a single device with this tabular map"></a></th>
<td>&nbsp;</td>
</tr></table></table><p>
<?

if ($cpos !== false and !$cty){
	Cities();
}elseif ($bpos !== false and !$bld){
	if($cty){$cty = $cty;}else{$cty = $c;}
	Buildings($cty);
}elseif ($fpos !== false){
	Floors($cty,$bld);
}else{
	echo $resmsg;	
}

include_once ("inc/footer.php");

#============================================================================

function Cities(){

	global $dcity,$maxcol,$bg1,$tabtag;

	echo "<h2>Corporate Network</h2>\n";

	ksort($dcity);

	echo "<table bgcolor=#$bg1 $tabtag ><tr>\n";

	$col = 0;
	foreach (array_keys($dcity) as $city){

		if ($col == $maxcol){
			$col = 0;
			echo "</tr><tr>";
		}
		$ci = CtyImg($dcity[$city]);
		$ucity = str_replace(" ","%20",$city);
                echo "\t<th bgcolor=#FFFFFF valign=bottom><a href=$_SERVER[PHP_SELF]?cty=$ucity><img src=img/$ci.png title=\"$dcity[$city] devices\" border=0></a><br>$city</th>\n";
                $col++;
	}
	echo "<tr></table>\n";
}

function Buildings($cty){

	global $dbuild,$bpos,$maxcol,$redbuild,$bg2,$tabtag;

	ksort($dbuild[$cty]);
	
	echo "<h2>$cty</h2>\n";
	echo "<table bgcolor=#$bg2 $tabtag ><tr>\n";

	$col = 0;
	foreach (array_keys($dbuild[$cty]) as $bld){

		$nr =  $dbuild[$cty][$bld]['nr'];
		$nd =  $dbuild[$cty][$bld]['nd'];

		if ($col == $maxcol){
			$col = 0;
			echo "</tr><tr>";
		}
		$bi = BldImg($nd,$bld);

		if($nr > 1){
			$ri = "<img src=img/dev/rsb2.png title=\"$nr routers\" border=0>";
		}elseif($nr == 1){
			$ri = "<img src=img/dev/rsbl.png title=\"1 router\" border=0>";
		}else{
			$ri = "";
		}
		$ucity = urlencode($cty);
		$ubld  = urlencode($bld);

		echo "\t<td bgcolor=#FFFFFF valign=bottom align=center>\n";
		echo "<a href=$_SERVER[PHP_SELF]?cty=$ucity&bld=$ubld><img src=img/$bi.png title=\"$nd devices\" border=0>$ri</a>\n";
		echo "<br><a href=Devices-List.php?ina=location&opa=regexp&sta=$bld>$bld</a>\n";
		echo "</td>\n";
		$col++;
	}
	echo "<tr></table>\n";
}

function Floors($cty,$bld){

	global $dev,$maxcol,$bg1,$bg2,$tabtag;

	echo "<h2>$cty - $bld</h2>\n";
	echo "<table bgcolor=#$bg1 $tabtag >\n";
	uksort($dev, "floorsort");
	$row = 0;
	foreach (array_keys($dev) as $fl){
		echo "<tr>\n\t<td bgcolor=$bg2 width=80><h2><img src=img/stair.png><br>$fl</h2></td>\n";
		$col = 0;
		ksort( $dev[$fl] );
		foreach (array_keys($dev[$fl]) as $rm){
			if ($row == "1"){ $row = "0"; $bi = "FFFFFF"; }
			else{ $row = "1"; $bi = "F0F0F0"; }	

			foreach (array_keys($dev[$fl][$rm]) as $d){
				$di = $dev[$fl][$rm][$d]['ic'];
				$co = $dev[$fl][$rm][$d]['co'];
				$rk = $dev[$fl][$rm][$d]['rk'];
				$ip = long2ip($dev[$fl][$rm][$d]['ip']);
				if ($col == $maxcol){
					$col = 0;
					echo "</tr><tr><td>&nbsp;</td>\n";
				}
				$ud = urlencode($d);
				echo "<td bgcolor=#$bi valign=top><b>$rm</b> $rk<p><center>\n";
				echo "<a href=Devices-Status.php?dev=$ud><img src=img/dev/$di.png border=0 vspace=4 ></a><br>\n";
				echo "<a href=Nodes-List.php?ina=device&opa==&sta=$d&ord=device><b>$d</b></a><p>\n";
				echo "<a href=telnet://$ip>$ip</a><br>\n";
				echo"$co</td>\n";
				$col++;
			}
		}
	}
	echo "<tr></table>\n";
}

?>
