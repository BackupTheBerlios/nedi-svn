<?

/*
#============================================================================
# Program: Monitoring-Timeline.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 29/07/05	initial version.
#  8/09/05	improved querying
# 17/03/06	new SQL query support
*/

$bg1	= "CCCC99";
$bg2	= "DDDDAA";
$calendar= 1;

include_once ("inc/header.php");
include_once ('inc/libmon.php');

$_GET = sanitize($_GET);
$fdy = isset($_GET['fdy']) ? $_GET['fdy'] : date('m/d/Y') . " 0:00";
$tdy = isset($_GET['tdy']) ? $_GET['tdy'] : date('m/d/Y H:i:s');
$gra = isset($_GET['gra']) ? $_GET['gra'] : 3600;
$det = isset($_GET['det']) ? "checked" : "";

?>
<h1>Monitoring Timeline</h1>
<form method="get" name="tline" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/dprt.png border=0 title="Review messages per time interval...">
</a></th>
<th>
From
<a href="javascript:show_calendar('tline.fdy');">
<img src="img/cal.png" border=0 hspace=8></a><input type=text name="fdy" value="<?=$fdy?>" size=20>
to
<a href="javascript:show_calendar('tline.tdy');">
<img src="img/cal.png" border=0 hspace=8></a><input type=text name="tdy" value="<?=$tdy?>" size=20>
</th>
<th>
 Granularity  
<SELECT size=1 name="gra">
<OPTION VALUE="3600">Hour
<OPTION VALUE="86400" <?=($gra == "86400")?"selected":""?>>Day
<OPTION VALUE="604800" <?=($gra == "604800")?"selected":""?>>Week
<OPTION VALUE="2592000" <?=($gra == "2592000")?"selected":""?>>Month
</SELECT>
</th>
<th><INPUT type="checkbox" name="det" <?=$det?>> Detailed</th>
<th width=80>
<input type="submit" name="tml" value="Show">
</th>
</tr>
</table></form><p>
<table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bg2?> >
<th width=80><img src=img/32/clock.png><br>Time</th>
<th><img src=img/32/say.png><br>Messages</th>
</tr>

<?
$from	= strtotime($fdy);
$to	= strtotime($tdy);

$istart	= $from;
$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$tmsg = 0;
$row = 0;
while($istart < $to){
	$iend = $istart + $gra;
	if ($row == "1"){ $row = "0"; $bg = $bga; $bi = $bia; }
	else{ $row = "1"; $bg = $bgb; $bi = $bib; }
	$fs   = rawurlencode(date("m/d/Y H:i:s",$istart));
	$fe   = rawurlencode(date("m/d/Y H:i:s",$iend));

	echo "<tr bgcolor=#$bg>\n";
	echo "<th bgcolor=#$bi><a href=Monitoring-Messages.php?ina=time&opa=%3E=&sta=$fs&cop=AND&inb=time&opb=%3C&stb=$fe>".date("j.M G:i",$istart)."</a></th><td>\n";
	if($det){
		$query	= GenQuery('messages','g','level','','',array('time','time'),array('>=','<'),array($istart,$iend),array('AND'));
		$res	= @DbQuery($query,$link);
		if($res){
			$nmsg = 0;
			while( $m = @DbFetchRow($res) ){
				$mbar = Bar($m[1],0,1);
				echo "<a href=Monitoring-Messages.php?ina=time&opa=%3E=&sta=$fs&cop=AND&inb=time&opb=%3C&stb=$fe&lvl=$m[0]>";
				echo "<img src=img/16/" . $mico[$m[0]] . ".png title=\"" . $mlvl[$m[0]] . "\" border=0></a>$mbar \n";
				$nmsg += $m[1];
			}
			if(!$nmsg){
				echo "<img src=img/16/fogy.png title=\"All Messages\"> ";
			}
			$tmsg += $nmsg;
			echo "$nmsg</td></tr>\n";
			@DbFreeResult($res);
		}else{
			print @DbError($link);
		}
	}else{
		$query	= GenQuery('messages','s','id','','',array('time','time'),array('>=','<'),array($istart,$iend),array('AND'));
		$res	= @DbQuery($query,$link);
		if($res){
			$m = @DbNumRows($res);
			$mbar = "";
			if($m){
				$mbar = Bar($m,0);
			}
			echo "<img src=img/16/fogy.png title=\"All Messages\">$mbar $m</td></tr>\n";
			$tmsg += $m;
			@DbFreeResult($res);
		}else{
			print @DbError($link);
		}
	}
	$istart = $iend;
	flush();
}
echo "</table><table bgcolor=#666666 $tabtag >\n";
echo "<tr bgcolor=#$bg2><td>Total of $tmsg messages shown</td></tr></table>\n";

include_once ("inc/footer.php");
?>