<?
/*
#============================================================================
# Program: Report-Networks.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 21/04/05	initial version.
# 31/03/05	decimal IPs refined algorithm
# 20/03/06	new SQL query support
# 04/01/07	minor cosmetic enhancements and loopback indication
*/

error_reporting(E_ALL ^ E_NOTICE);

$bg1	= "77BBAA";
$bg2	= "88CCBB";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");

$_GET = sanitize($_GET);
$opr = isset($_GET['opr']) ? $_GET['opr'] : "";
$ipf = isset($_GET['ipf']) ? $_GET['ipf'] : "";
?>
<h1>Network Report</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>" name="netlist">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=<?=$bg1?> ><th width=80><a href=<?=$_SERVER['PHP_SELF']?> >
<img src=img/32/dnet.png border=0 title="Lists Networks, using filter IP[/Prefix] and detects mask inconsistencies">
</a></th>
<th>
IP Address
<SELECT size=1 name="opr">
<OPTION VALUE="=" <?=($opr == "=")?"selected":""?> >equal
<OPTION VALUE="!=" <?=($opr == "!=")?"selected":""?> >not equal
</SELECT>
<input type="text" name="ipf" value="<?=$ipf?>" size="20"> 
</th>
<th width=80>
<input type="submit" name="shw" value="Show">
</th>
</tr></table></form>
<?
if (isset($ipf) ) {
	$query	= GenQuery('networks','s','*','ip','',array('ip'),array('='),array($ipf) );
	$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
	$res	= @DbQuery($query,$link);
	if ($res) {
		while( ($n = @DbFetchRow($res)) ){
			$n[2]	= ip2long(long2ip($n[2]));								// Hack to fix signing issue for 32bit vars in PHP!
			$n[3]	= ip2long(long2ip($n[3]));
			$dnet	= sprintf("%u",$n[2] & $n[3]);

			if( isset($nets[$dnet]) ){
				if($nets[$dnet] != $n[3]){
					$devs[$dnet][$n[0]]	= "<span style=\"color : purple\">" .long2ip($n[3]) . "</span> on $n[1]";
				}else{
					if($devs[$dnet][$n[0]]){
						$devs[$dnet][$n[0]]	= "<span style=\"color : green\">multiple</span> on $n[1]";
					}else{
						$devs[$dnet][$n[0]]	= "<span style=\"color : green\">ok</span> on $n[1]";
					}
				}
			}else{
				$nets[$dnet]		= $n[3];
				if($n[3] == -1){
					$devs[$dnet][$n[0]]	= "<span style=\"color : yellow\">Loopback</span> on $n[1]";
				}else{
					$devs[$dnet][$n[0]]	= "<span style=\"color : blue\">mask base</span> on $n[1]";
				}

				$nquery	= GenQuery('nodes','s','*','ip','',array("ip & $n[3]"),array('='),array($dnet) );
				$nodres	= @DbQuery($nquery,$link);
				while( ($nod = @DbFetchRow($nodres)) ){
					if($n[3]){$pop[$dnet]++;}							//  Only count if mask is not 0!
					$age[$dnet] += $nod[5] - $nod[4];
				}
				@DbFreeResult($nodres);
			}
		}
		@DbFreeResult($res);

		if($nets){
?>
<table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>>
<th colspan=2><img src=img/32/net.png><br>Network</th>
<th width=30%><img src=img/32/dev.png><br>Devices</th>
<th><img src=img/32/cubs.png><br>Population</th>
<th><img src=img/32/clock.png><br>Average Node Age</th>
</tr>
<?
			$row = 0;
			foreach(array_keys($nets) as $dn ){
				if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
				$row++;
				$net	= long2ip($dn);
				list($pfix,$mask,$bmsk)	= Masker($nets[$dn]);
				list($ntimg,$ntit)	= Nettype( $net );
				if( isset($pop[$dn]) ){
					$avage = intval($age[$dn] / ($pop[$dn] * 86400));
				}else{
					$pop[$dn]	= 0;
					$avage		= 0;
				}
				$pbar = Bar($pop[$dn],110);
				$abar = Bar($avage);
				$dvs = "";
				foreach( array_keys($devs[$dn]) as $dv ){
					$du = rawurlencode($dv);
					$dvs .= "<a href=Devices-Status.php?dev=$du>$dv</a> ".$devs[$dn][$dv]."<br>\n";
				}
				echo "<tr bgcolor=#$bg>";
				echo "<td bgcolor=$bi width=20 align=center><img src=img/16/$ntimg title=$ntit></td>\n";
				echo "<td><a href=Devices-Map.php?ina=network&flt=$net%2F$pfix&draw=1>$net/$pfix</a></td>\n";
				echo "<td>$dvs</td><td>$pbar <a href=Nodes-List.php?ina=ip&opa==&sta=$net/$pfix&ord=ip>$pop[$dn]</a></td>\n";
				echo "<td>$abar $avage days</td>\n";
				echo "</tr>\n";
			}
			echo "</table><table bgcolor=#666666 $tabtag >\n";
			echo "<tr bgcolor=#$bg2><td>$row networks ($query)</td></tr></table>\n";
		}else{
			echo $resmsg;
		}
	}
}
include_once ("inc/footer.php");
?>
