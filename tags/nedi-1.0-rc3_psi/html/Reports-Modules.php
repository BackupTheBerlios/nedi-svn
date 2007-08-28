<?
/*
#============================================================================
# Program: Reports-Modules.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 26/06/06	initial version
# 03/07/07	minor changes & new inventory
*/

error_reporting(E_ALL ^ E_NOTICE);

$bg1	= "D0D0D7";
$bg2	= "E0E0E7";

include_once ("inc/header.php");

$_GET = sanitize($_GET);
$rep = isset($_GET['rep']) ? $_GET['rep'] : array();
$flt = isset($_GET['flt']) ? $_GET['flt'] : "";
$ord = isset($_GET['ord']) ? "checked" : "";
?>
<h1>Module Reports</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/dcog.png border=0 title="Device Module based reports">
</a></th>
<th>Select Report(s)</th>
<th>
<SELECT MULTIPLE name="rep[]" size=4>
<OPTION VALUE="dis" <?=(in_array("dis",$rep))?"selected":""?> >Distribution
<OPTION VALUE="inv" <?=(in_array("inv",$rep))?"selected":""?> >Inventory
</SELECT>
</th>
<th>Filter
<input type="text" name="flt" value="<?=$flt?>" size="20" title="Filter module-models or device-types">
</th>
</th>
<th>
<INPUT type="checkbox" name="ord"  <?=$ord?> title="Sort by model or type"> alternative
</th>
</SELECT></th>
<th width=80><input type="submit" name="do" value="Show"></th>
</tr></table></form><p>
<?
$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);

if ( in_array("dis",$rep) ){
?>
<h2>Model Distribution</h2>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th width=10%><img src=img/32/fiap.png><br>Model</th>
<th><img src=img/32/info.png><br>Description</th>
<th width=70%><img src=img/32/dev.png><br>Installed on</th>
<th width=20%><img src=img/32/form.png><br>Total Count</th>
<?
	$query	= GenQuery('modules','s','device,model,description','','',array('model'),array('regexp'),array($flt) );
	$res	= @DbQuery($query,$link);
	if($res){
		$nmod = 0;
		$nummo	= array();
		while( ($m = @DbFetchRow($res)) ){
			$nummo[$m[1]]++;
			$modev[$m[1]][$m[0]]++;
			$modes[$m[1]] = $m[2];
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
		$um = rawurlencode($mdl);
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi width=10%><a href=Devices-Modules.php?ina=model&opa==&sta=$um><b>$mdl</b></a></th>\n";
		echo "<td>$modes[$mdl]</td><td>";
		foreach ($modev[$mdl] as $dv => $ndv){
			$ud = rawurlencode($dv);
			echo "<a href=Devices-Status.php?dev=$ud>$dv</a>:<b>$ndv</b> ";
		}
		echo "</td>\n";
		echo "<td>$tbar $n</td></tr>\n";
		if($row == $lim){break;}
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row module types of $nmod modules in total</td></tr></table>\n";
}

if ( in_array("inv",$rep) ){
?>
<h2>Inventory <?=($flt) ? " on \"$flt\" devices":"";?></h2>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2><img src=img/32/dev.png><br>Device / Slot</th>
<th><img src=img/32/find.png><br>Info</th>
<th><img src=img/32/form.png><br>Serial Number</th>
<th><img src=img/32/nic.png><br>HW</th>
<th><img src=img/32/mem.png><br>FW</th>
<th><img src=img/32/dsw.png><br>SW</th>
<?
	if($ord){
		$sort = "type";
	}else{
		$sort = "name";
	}
	$query	= GenQuery('devices','s','name,type,serial,os,bootimage',$sort,'',array('type'),array('regexp'),array($flt) );
	$res	= @DbQuery($query,$link);
	if($res){
		$dev = 0;
		$row = 0;
		while( $d = @DbFetchRow($res) ){
			if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
			$dev++;
			$row++;
			$ud = rawurlencode($d[0]);
			echo "<tr bgcolor=#$bg><th bgcolor=#$bia><a href=Devices-Status.php?dev=$ud><b>$d[0]</b></a></th>\n";
			echo "<td align=right>-</td><td><b>$d[1]</b></td><td>$d[2]</td><td>-</td><td>$d[3]</td><td>$d[4]</td></tr>\n";
			$mquery	= GenQuery('modules','s','*','slot','',array('device'),array('='),array($d[0]));
			$mres	= @DbQuery($mquery,$link);
			if($mres){
				while( ($m = @DbFetchRow($mres)) ){
					if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
					$row++;
					echo "<tr bgcolor=#$bg><td bgcolor=#$bib></td>\n";
					echo "<td align=right>$m[1]</td><td><b>$m[2]</b> $m[3]</td><td>$m[4]</td><td>$m[5]</td><td>$m[6]</td><td>$m[7]</td></tr>\n";
				}
				@DbFreeResult($mres);
			}else{
				print @DbError($link);
				die;
			}
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
		die;
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$dev devices showing $row assets in total</td></tr></table>\n";
}

include_once ("inc/footer.php");
?>
