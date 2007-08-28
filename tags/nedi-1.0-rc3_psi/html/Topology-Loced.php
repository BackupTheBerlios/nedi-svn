<?
/*
#============================================================================
# Program: Topology-Loced.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 26/05/07		initial version.
# 13/07/07		new location scheme
*/

$bg1	= "66aa88";
$bg2	= "77bb99";

include_once ("inc/header.php");
include_once ('inc/libdev.php');

$_GET = sanitize($_GET);
$id   = isset($_GET['id']) ? $_GET['id'] : "";
$reg  = isset($_GET['reg']) ? $_GET['reg'] : "";
$cty  = isset($_GET['cty']) ? $_GET['cty'] : "";
$bld  = isset($_GET['bld']) ? $_GET['bld'] : "";
$x    = isset($_GET['x']) ? $_GET['x'] : 0;
$y    = isset($_GET['y']) ? $_GET['y'] : 0;
$com  = isset($_GET['com']) ? $_GET['com'] : "";
$do   = isset($_GET['do']) ? $_GET['do'] : "";
$loco = 0;
$mapbg= TopoMap();
$now  = date($datfmt);

$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('locations');
$res	= @DbQuery($query,$link);
if($res){
	while( ($l = @DbFetchRow($res)) ){
		$loc[$loc[0]][$loc[1]][$loc[2]]['x'] = $l[3];
		$loc[$loc[0]][$loc[1]][$loc[2]]['y'] = $l[4];
		$loc[$loc[0]][$loc[1]][$loc[2]]['c'] = $l[5];
	}
	@DbFreeResult($res);
}else{
	echo @DbError($link);
}


$query	= GenQuery('devices','s','location');
$res	= @DbQuery($query,$link);
if($res){
	while( ($d = @DbFetchRow($res)) ){
		$l = explode($locsep, $d[0]);
		$lopt[$l[0]][$l[1]][$l[2]]++;
	}
	@DbFreeResult($res);
}else{
	echo @DbError($link);
}

if ($do == 'Create' and $reg){
	$query	= GenQuery('locations','i','','','',array('region','city','building','x','y','comment'),'',array($reg,$cty,$bld,$x,$y,$com) );
	if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Location $reg $cty $bld - $upokmsg</h3>";}
}elseif ($do == 'Update' and $id){
	$query	= GenQuery('locations','u','id',$id,'',array('region','city','building','x','y','comment'),'',array($reg,$cty,$bld,$x,$y,$com) );
	if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Location $reg $cty $bld - $upokmsg</h3>";}
}elseif($do == 'Delete' and $id){
	$query	= GenQuery('locations','d','','','',array('id'),array('='),array($id) );
	if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Location $reg $cty $bld $delokmsg</h3>";}
}

if($bld){
	$query	= GenQuery('locations','s','id,x,y,comment','','',array('region','city','building'),array('=','=','='),array($reg,$cty,$bld),array('AND','AND'));
	$mapbg = TopoMap($reg,$cty);
	$nam = $bld;
	$com = "Building with ".$lopt[$reg][$cty][$bld] ." devices on $now";
}elseif($cty){
	$query	= GenQuery('locations','s','id,x,y,comment','','',array('region','city','building'),array('=','=','='),array($reg,$cty,''),array('AND','AND'));
	$mapbg = TopoMap($reg);
	$nam = $cty;
	$com = "City with ".count(array_keys($lopt[$reg][$cty]))." buildings on $now";
}elseif($reg){
	$query	= GenQuery('locations','s','id,x,y,comment','','',array('region','city','building'),array('=','=','='),array($reg,'',''),array('AND','AND'));
	$nam = $reg;
	$com = "Region with ".count(array_keys($lopt[$reg]))." cities on $now";
}else{
	$query = "";
}

if($query){
	$res	= @DbQuery($query,$link);
	$nloc	= @DbNumRows($res);
	if ($nloc == 1) {
		list($id,$x,$y,$com) = @DbFetchRow($res);
		$loco = 1;
	}
}

?>
<h1>Location Editor</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>" name="lof">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>><img src=img/32/home.png border=0 title="Make sure you have your maps and locations (on the devices) setup, before editing!"></a></th>
<th valign=top><h3>Region</h3><select size=4 name="reg" onchange="document.lof.cty.selectedIndex = -1; document.lof.bld.selectedIndex = -1; document.lof.x.value = ''; document.lof.y.value = ''; this.form.submit();">
<?
ksort($lopt);
foreach(array_keys($lopt) as $r){
	echo "<option value=\"$r\"".(($reg == $r)?"selected":"").">$r\n";
}
?>
</select></th>
<th valign=top><h3>City</h3><select size=4 name="cty" onchange="document.lof.bld.selectedIndex = -1; document.lof.x.value = ''; document.lof.y.value = ''; this.form.submit();">
<?
if($reg){
ksort($lopt[$reg]);
	foreach(array_keys($lopt[$reg]) as $c){
		echo "<option value=\"$c\"".(($cty == $c)?"selected":"").">$c\n";
	}
}
?>
</select></th>
<th valign=top><h3>Building</h3><select size=4 name="bld" onchange="document.lof.x.value = ''; document.lof.y.value = ''; this.form.submit();">
<?
if($cty){
ksort($lopt[$reg][$cty]);
	foreach(array_keys($lopt[$reg][$cty]) as $b){
		echo "<option value=\"$b\"".(($bld == $b)?"selected":"").">$b\n";
	}
}
?>
</select></th>
<th width=400 align=right valign=top>
<h3><?=$nam?> Properties</h3>
Coordinates
X <input type="text" name="x" size=6 value="<?=$x?>" >
Y <input type="text" name="y" size=6 value="<?=$y?>" >
<p>
Comment
<input type="text" name="com" size=40 value="<?=$com?>" onfocus=select();>
</th>
<th width=80 valign=top>
<h3>Action</h3>
<? 
if($loco){
	echo "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
	echo '<input type="submit" name="do" value="Update"><p>';
	echo '<input type="submit" name="do" value="Delete">';
}else{
	echo '<input type="submit" name="do" value="Create"><p>';
}
$bgsize = getimagesize ("log/$mapbg");
?>

</th>
</tr></table></form><p>
<h2>Click on map for coordinates</h2>
<p>
<center>
<div id="map" onclick="getcoord(event)" style = "background-image:url('log/<?=$mapbg?>');width:<?=$bgsize[0]?>;height:<?=$bgsize[1]?>;border:1px solid black">
<img src="img/32/bcnl.png" id="loc" style="position:relative;visibility:hidden;z-index:2;"></div>
</center>
<script language="JavaScript">
<? if($x and $y){?>
document.getElementById("loc").style.left = (<?=$x?>-<?=$bgsize[0]/2?>) ;
document.getElementById("loc").style.top = (<?=$y?>-15) ;
document.getElementById("loc").style.visibility = "visible" ;
<?}?>
function getcoord(event){
	mapx = event.offsetX?(event.offsetX):event.pageX-document.getElementById("map").offsetLeft;
	mapy = event.offsetY?(event.offsetY):event.pageY-document.getElementById("map").offsetTop;
	document.lof.x.value = mapx;
	document.lof.y.value = mapy;
	document.getElementById("loc").style.visibility = "visible" ;
	document.getElementById("loc").style.left = (mapx-<?=$bgsize[0]/2?>) ;
	document.getElementById("loc").style.top = (mapy-15) ;
}
</script>
<?
include_once ("inc/footer.php");
?>