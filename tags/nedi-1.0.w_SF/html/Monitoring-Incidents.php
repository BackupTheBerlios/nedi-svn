<?
/*
#============================================================================
# Program: Monitoring-Incidents.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 08/07/06	initial version.
# 05/01/07	variables cleanup.
# 23/03/07	more cleanup, improved paging
*/

$bg1	 = "ddbb99";
$bg2	 = "eeccaa";
$btag	 = "";
$nocache = 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");
include_once ('inc/libdev.php');
include_once ('inc/libmon.php');

$_GET = sanitize($_GET);
$id = isset($_GET['id']) ? $_GET['id'] : "";
$dli = isset($_GET['dli']) ? $_GET['dli'] : "";
$uca = isset($_GET['uca']) ? $_GET['uca'] : "";
$ucm = isset($_GET['ucm']) ? $_GET['ucm'] : "";
$cmt = isset($_GET['cmt']) ? $_GET['cmt'] : "";
$cat = isset($_GET['cat']) ? $_GET['cat'] : 0;
$lim = isset($_GET['lim']) ? $_GET['lim'] : 10;
$off = (isset($_GET['off']) and !isset($_GET['sho']))? $_GET['off'] : 0;

$nof = $off;
if( isset($_GET['p']) ){
	$nof = abs($off - $lim);
}elseif( isset($_GET['n']) ){
	$nof = $off + $lim;
}
$dlim = "$nof,$lim";

$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
if($dli){
	$query	= GenQuery('incidents','d','','','',array('id'),array('='),array($dli) );
	if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Incident $dli $delokmsg</h3>";}
}elseif($uca){
	$query	= GenQuery('incidents','u','id',$uca,'',array('who','time','category'),'',array($_GET['who'],$_GET['tme'],$cat) );
	if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3> Incident $uca $upokmsg</h3>";}
	$cat = 0;
}elseif($ucm){
	$query	= GenQuery('incidents','u','id',$ucm,'',array('who','comment'),'',array($_GET['who'],$cmt) );
	if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3> Incident $ucm $upokmsg</h3>";}
}
?>
<h1>Monitoring Incidents</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/bomb.png border=0 title="Classify incidents (Show will reset paging in case you get lost)">
</a></th>
<th>
Category Filter <select size=1 name="cat">
<option value="">-
<?
foreach (array_keys($icat) as $ic){
	echo "<option value=\"$ic\" ";
	if($ic == $cat){echo "selected";}
	echo ">$icat[$ic] ($ic)\n";
}
?>
</select>
</th>
<th>Limit
<select size=1 name="lim">
<? selectbox("limit",$lim);?>
</select>
</th>
<th width=80><input type="submit" name="sho" value="Show">
<p>
<input type="hidden" name="off" value="<?=$nof?>">
<input type="submit" name="p" value="<-">
<input type="submit" name="n" value="->">
</th>
</tr></table></form><p>

<table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bg2?> >
<th width=80 colspan=2><img src=img/32/eyes.png><br>Incident</th>
<th colspan=2><img src=img/32/dev.png><br>Device</th>
<th colspan=2><img src=img/32/clock.png><br>Timeframe</th>
<th colspan=2><img src=img/32/smil.png><br>Agent</th>
<th colspan=2><img src=img/32/find.png><br>Info</th>
</tr>

<?
if ($cat){
	$query	= GenQuery('incidents','s','*','id desc',$dlim,array('category'),array('='),array($cat));
}elseif ($id){
	$query	= GenQuery('incidents','s','*','','',array('id'),array('='),array($id));
}else{
	$query	= GenQuery('incidents','s','*','id desc',$dlim);
}
$res	= @DbQuery($query,$link);
if($res){
	$nin = 0;
	$row = 0;
	while( ($i = @DbFetchRow($res)) ){
		if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
		$row++;
		$fs = date("d.M H:i",$i[4]);
		if($i[5]){
			$dur = intval(($i[5] - $i[4]) / 3600);
			$ls  = date("d.M H:i",$i[5]); # . " ($dur h)";
		}else{
			$ls  = "-";
		}
		if($i[7]){$at = date("d.M H:i",$i[7]);}else{$at = "-";}
		$ud = rawurlencode($i[2]);
		list($fc,$lc) = Agecol($i[4],$i[5],$row % 2);
		echo "<tr bgcolor=#$bg><th>$i[0]</th><th bgcolor=$bi><img src=img/16/" . $mico[$i[1]] . ".png title=\"" . $mlvl[$i[1]] . "\"></th>\n";
		echo "<th><a href=Monitoring-Messages.php?ina=source&opa==&sta=$ud>$i[2]</th><td>$i[3] deps</td>\n";
		echo "<td bgcolor=#$fc>$fs</td><td bgcolor=#$fc>$ls</td><th>$i[6]</th><td>$at</td>";
		?>
<td>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<img src=img/16/<?=Cimg($i[8])?>.png>
<input type="hidden" name="uca" value="<?=$i[0]?>">
<input type="hidden" name="who" value="<?=($i[6])?$i[6]:$_SESSION['user']?>">
<input type="hidden" name="tme" value="<?=($i[7])?$i[7]:time()?>">
<input type="hidden" name="lim" value="<?=$lim?>">
<input type="hidden" name="off" value="<?=$nof?>">
<select size=1 name="cat" onchange="this.form.submit();" title="categorize incident for reporting">
<?
foreach (array_keys($icat) as $ic){
	echo "<option value=\"$ic\" ";
	if($ic == $i[8]){echo "selected";}
	echo ">$icat[$ic]\n";
}
?>
</SELECT>
</td><td>
</form>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" name="who" value="<?=($i[6])?$i[6]:$_SESSION['user']?>">
<input type="hidden" name="ucm" value="<?=$i[0]?>">
<input type="hidden" name="lim" value="<?=$lim?>">
<input type="hidden" name="off" value="<?=$nof?>">
<input type="text" name="cmt" size=40 value="<?=$i[9]?>" onchange="this.form.submit();">
<a href=<?=$_SERVER['PHP_SELF']?>?dli=<?=$i[0]?>><img src=img/16/bcnl.png border=0 hspace=8 onclick="return confirm('Delete incident?');" title="Delete Incident"></a>
</form>
</td>
</tr>
<?
		$nin++;
		if($nin == $lim){break;}
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}
echo "</table><table bgcolor=#666666 $tabtag >\n";
echo "<tr bgcolor=#$bg2><td>$nin incidents with $query</td></tr></table>\n";

include_once ("inc/footer.php");
?>
