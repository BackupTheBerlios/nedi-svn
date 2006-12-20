<?
/*
#============================================================================
# Program: Reports-Modules.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 26/06/06	initial version.
*/

error_reporting(E_ALL ^ E_NOTICE);

$bg1	= "d0d6dd";
$bg2	= "e0e6ee";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");

$_GET = sanitize($_GET);
$rep = isset($_GET['rep']) ? $_GET['rep'] : array();
$lim = isset($_GET['lim']) ? $_GET['lim'] : 10;
$ord = isset($_GET['ord']) ? "checked" : "";
?>
<h1>Module Reports</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/dmsc.png border=0 title="Device Module based reports">
</a></th>
<th>Select Report(s)</th>
<th>
<SELECT MULTIPLE name="rep[]" size=4>
<OPTION VALUE="mty" <? if(in_array("mty",$rep)){echo "selected";} ?> >Models
<OPTION VALUE="mli" <? if(in_array("mli",$rep)){echo "selected";} ?> >Listing
</SELECT>

</th>
<th>Limit
<SELECT size=1 name="lim">
<OPTION VALUE="10" <?=($lim == "10")?"selected":""?> >10
<OPTION VALUE="20" <?=($lim == "20")?"selected":""?> >20
<OPTION VALUE="50" <?=($lim == "50")?"selected":""?> >50
<OPTION VALUE="100" <?=($lim == "100")?"selected":""?> >100
<OPTION VALUE="500" <?=($lim == "500")?"selected":""?> >500
<OPTION VALUE="0" <?=($lim == "0")?"selected":""?> >None!
</SELECT>
</th>
<th>
<INPUT type="checkbox" name="ord"  <?=$ord?> > alternative order
</th>
</SELECT></th>
<th width=80><input type="submit" name="gen" value="Show"></th>
</tr></table></form><p>
<?
if($rep){

$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
if ( in_array("mty",$_GET['rep']) ){
?>
<h2>Model Distribution</h2>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th width=10%><img src=img/32/fiap.png><br>Type</th>
<th width=70%><img src=img/32/dev.png><br>Devices</th>
<th width=20%><img src=img/32/form.png><br>Modules</th>
<?
	$query	= GenQuery('modules');
	$res	= @DbQuery($query,$link);
	if($res){
		$nmod = 0;
		$nummo	= array();
		while( ($m = @DbFetchRow($res)) ){
			if( preg_match("/^[0-9]+$/",$m[2]) ){
				$mdl = $m[3];
			}else{
				$mdl = $m[2];
			}
			$nummo[$mdl]++;
			$modev[$mdl][$m[0]]++;
			$nmod++;
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
		die;
	}
	if($ord){
		ksort($nummo);
	}else{
		arsort($nummo);
	}
	$row = 0;
	foreach ($nummo as $mdl => $n){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$tbar = Bar($n,0);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi width=10%>$mdl</th>\n";
		echo "<td>";
		foreach ($modev[$mdl] as $dv => $ndv){
			$ud = rawurlencode($dv);
			echo "<a href=Devices-Status.php?dev=$ud>$dv</a>:<b>$ndv</b> ";
		}
		echo "</td>\n";
		echo "<td>$tbar $n</td></tr>\n";
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row module types of $nmod modules in total</td></tr></table>\n";
}

if ( in_array("mli",$_GET['rep']) ){
?>
<h2>Listing</h2>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2><img src=img/32/dev.png><br>Device / Slot</th>
<th><img src=img/32/fiap.png><br>Model</th>
<th><img src=img/32/info.png><br>Description</th>
<th><img src=img/32/form.png><br>Serial Number</th>
<th><img src=img/32/nic.png><br>HW</th>
<th><img src=img/32/mem.png><br>FW</th>
<th><img src=img/32/cog.png><br>SW</th>
<?
	if($ord){
		$sort = "model";
	}else{
		$sort = "";
	}
	$query	= GenQuery('modules','s','*',"$sort");
	$res	= @DbQuery($query,$link);
	if($res){
		$row = 0;
		while( ($m = @DbFetchRow($res)) ){
			if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
			$row++;
			$ud = rawurlencode($m[0]);
			echo "<tr bgcolor=#$bg><th bgcolor=#$bi><a href=Devices-Status.php?dev=$ud>$m[0]</a></th>\n";
			echo "<td>$m[1]</td><td>$m[2]</td><td>$m[3]</td><td>$m[4]</td><td>$m[5]</td><td>$m[6]</td><td>$m[7]</td></tr>\n";
			if($row == $lim){break;}
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
		die;
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row modules shown</td></tr></table>\n";
}

}

include_once ("inc/footer.php");
?>
