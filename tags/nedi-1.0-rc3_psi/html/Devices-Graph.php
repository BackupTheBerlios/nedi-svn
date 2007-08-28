<?
/*
#============================================================================
# Program: Devices-Graph.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 27/03/06	initial version.
# 15/05/06	new concept towards CPU and Mem graphing
# 02/11/06	cosmetic changes
# 08/11/06	medium graph option
# 05/03/07	support for 8  stacks
# 25/05/07	Minor Improvements, fixed urlencoding (let me know, if you still find non working ones!)
*/

$bg1	= "BBDDDD";
$bg2	= "CCEEEE";

include_once ("inc/header.php");

$_GET = sanitize($_GET);
$dv = isset($_GET['dv']) ? $_GET['dv'] : "";
$if = isset($_GET['if']) ? $_GET['if'] : array();
$cpu = isset($_GET['cpu']) ? $_GET['cpu'] : "";
$mem = isset($_GET['mem']) ? $_GET['mem'] : "";
$tmp = isset($_GET['tmp']) ? $_GET['tmp'] : "";
$dur = isset($_GET['dur']) ? $_GET['dur'] : 7;
$sze = isset($_GET['sze']) ? $_GET['sze'] : "l";
?>
<h1>Device Graphs</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>" name="graf">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>><img src=img/32/dlog.png border=0 title="Hold down Ctrl to select up to 6 interfaces, for stacked graphs (e.g. for channels)"></a></th>
<th>
<select size=6 name="dv" onchange="this.form.submit();">
<?
$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('devices','s','name');
$res	= @DbQuery($query,$link);
if($res){
	while( ($d = @DbFetchRow($res)) ){
		echo "<option value=\"$d[0]\" ";
		if($dv == $d[0]){
			echo "selected";
		}
		echo " >$d[0]\n";
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}
?>
</select>
<select multiple size=6 name="if[]">
<?
if ($dv) {
	$query	= GenQuery('interfaces','s','ifname,alias,comment','ifname','',array('device'),array('='),array($dv) );
	$res	= @DbQuery($query,$link);
	if($res){
		while( ($i = @DbFetchRow($res)) ){
			echo "<OPTION VALUE=\"$i[0]\" ";
			if(in_array($i[0],$if)){echo "selected";}
			echo " >$i[0] " . substr("$i[1] $i[2]\n",0,30);
		}
		@DbFreeResult($res);
	}
}
?>
</select>
</th>
<td>
<INPUT type="checkbox" name="cpu" <?=($cpu)?"checked":""?> > CPU<br>
<INPUT type="checkbox" name="mem" <?=($mem)?"checked":""?> > Mem<br>
<INPUT type="checkbox" name="tmp" <?=($tmp)?"checked":""?> > Temp<br>
</td>
<th valign=top>
Duration<p>
<SELECT size=1 name="dur">
<OPTION VALUE="1">Day
<OPTION VALUE="7" <?=($dur == "7")?"selected":""?> >Week
<OPTION VALUE="30" <?=($dur == "30")?"selected":""?> >Month
<OPTION VALUE="90" <?=($dur == "90")?"selected":""?> >Quarter
<OPTION VALUE="180" <?=($dur == "180")?"selected":""?> >Semester
<OPTION VALUE="360" <?=($dur == "360")?"selected":""?> >Year
</SELECT>
</th>
<th valign=top>
Size<p>
<SELECT size=1 name="sze">
<OPTION VALUE="l">large
<OPTION VALUE="m" <?=($sze == "m")?"selected":""?> >medium
<OPTION VALUE="s" <?=($sze == "s")?"selected":""?> >small
</SELECT>
</th>
<th width=80><input type="submit" value="Show"></th>
</tr></table></form><p><center>
<?
$ud = rawurlencode($dv);
if($cpu ){
	echo "<img src=inc/drawrrd.php?dv=$ud&s=$sze&t=cpu&dur=$dur>\n";
}
if($mem ){
	echo "<img src=inc/drawrrd.php?dv=$ud&s=$sze&t=mem&dur=$dur>\n";
}
if($tmp ){
	echo "<img src=inc/drawrrd.php?dv=$ud&s=$sze&t=tmp&dur=$dur>\n";
}
if( isset($if[0]) ){
	$ifs = "";
	foreach ( $if as $i){
		$ifs .= '&if[]='.rawurlencode($i);
	}
	echo "<p><img src=inc/drawrrd.php?dv=$ud$ifs&s=$sze&t=trf&dur=$dur vspace=8>\n";
	echo "<img src=inc/drawrrd.php?dv=$ud$ifs&s=$sze&t=err&dur=$dur vspace=8>\n";
}
include_once ("inc/footer.php");
?>