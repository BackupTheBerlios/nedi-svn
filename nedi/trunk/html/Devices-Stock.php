<?

/*
#============================================================================
# Program: Devices-Stock.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -------- ------------------------------------------------------------------
# 22/02/05	initial version.
# 04/03/05	Revised backend
# 10/03/05	Revised authentication
# 07/03/06	renamed time field, added icons
# 17/03/06	new SQL query support
*/

$bg1	= "88BBCC";
$bg2	= "AACCDD";
$btag	= "onload=document.add.ser.focus();";
$nocache= 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");
include_once ('inc/libdev.php');
$_GET = sanitize($_GET);
?>
<h1>Devices Stock</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>" name=add>
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/pkg.png border=0 title="Devices will be removed as found in discovery via SN#">
</a></th>
<th>Serial#: <input type="text" name="ser" size="30" OnFocus=select();>
<th>Type: <input type="text" name="typ" size="30" OnFocus=select();>
<th width=80><input type="submit" value="Add" name="add"></th></tr>
</table></form><p>
<?
$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);

if( isset($_GET['add']) or isset($_GET['del']) ){
	if( preg_match("/adm/",$_SESSION['group']) ){
		if( isset($_GET['del']) ){
			$query	= GenQuery('stock','d','','','',array('serial'),array('='),array($_GET['del']) );
			if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Device $_GET[del] $delokmsg</h3>";}
		}elseif ($_GET['add'] and $_GET['ser'] and $_GET['typ'] ){
			$now = time();
			$query	= GenQuery('stock','i','','','',array('serial','type','user','time'),'',array($_GET['ser'],$_GET['typ'],$_SESSION['user'],$now) );
			if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Device $_GET[ser] $upokmsg</h3>";}
		}
	}else{
		echo $nokmsg;
	}
}
?>
<h2>Type Inventory</h2>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th><img src=img/32/fiap.png><br>Type</th>
<th><img src=img/32/form.png><br>Quantity</th>

<?
$query	= GenQuery('stock','g','type');
$res	= @DbQuery($query,$link);
if($res){
	$row = 0;
	while( ($dev = @DbFetchRow($res)) ){
		if ($row % 2){$bg = $bgb; $bi = $bib;}else{$bg = $bga; $bi = $bia;}
		$row++;
		$stbar = Bar($dev[1],0);
		echo "<tr bgcolor=#$bg>\n";
		echo "<td>$dev[0]</td><td>\n";
		echo "$stbar $dev[1]</td></tr>\n";
	}
}
echo "</table><table bgcolor=#666666 $tabtag >\n";
echo "<tr bgcolor=#$bg2><td>$row results using $query</td></tr></table>\n";

echo "<h2>Devices</h2>\n";
echo "<table bgcolor=#666666 $tabtag >\n";
echo "<tr bgcolor=#$bg2>\n";
echo "<th><img src=img/32/form.png><br>Serial #</th><th><img src=img/32/fiap.png><br>Type</th>\n";
echo "<th><img src=img/32/smil.png><br>Added by</th><th><img src=img/32/clock.png><br>Added on</th><th><img src=img/32/idea.png><br>Action</th></tr>\n";

$query	= GenQuery('stock','s','*','type');
$res	= @DbQuery($query,$link);
if($res){
	$row = 0;
	while( ($dev = @DbFetchRow($res)) ){
		if ($row % 2){$bg = $bgb; $bi = $bib;}else{$bg = $bga; $bi = $bia;}
		$row++;
		$img = "genh.png";
		$ud  = rawurlencode($dev[0]);
		$da  = date("j.M (G:i)",$dev[3]);
		list($a1c,$a2c) = Agecol($dev[3],$dev[3],$row % 2);
		echo "\t<tr bgcolor=#$bg>\n";
		echo "\t\t<td>$dev[0]</td><td>$dev[1]</td><td>$dev[2]</td><td bgcolor=#$a1c>$da</td>\n";
		echo "\t\t<td align=center><a href=$_SERVER[PHP_SELF]?del=$ud><img src=img/16/bcnl.png border=0 onclick=\"return confirm('Delete $dev[0] from stock?')\" title=\"Delete this device!\"></a></td>\n";
		echo "\t</tr>\n";
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}
echo "</table><table bgcolor=#666666 $tabtag >\n";
echo "<tr bgcolor=#$bg2><td>$row results using $query</td></tr></table>\n";

include_once ("inc/footer.php");
?>
