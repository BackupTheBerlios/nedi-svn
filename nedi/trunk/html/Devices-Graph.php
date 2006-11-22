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
# 2/11/06		cosmetic changes
# 8/11/06		medium graph option
*/

$bg1	= "BBDDDD";
$bg2	= "CCEEEE";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");

$_GET = sanitize($_GET);
$dv = isset($_GET['dv']) ? $_GET['dv'] : "";
$if = isset($_GET['if']) ? $_GET['if'] : array();
$cpu = isset($_GET['cpu']) ? $_GET['cpu'] : "";
$mem = isset($_GET['mem']) ? $_GET['mem'] : "";
$tmp = isset($_GET['tmp']) ? $_GET['tmp'] : "";
$dur = isset($_GET['dur']) ? $_GET['dur'] : "";
$sze = isset($_GET['sze']) ? "m" : "l";
?>
<h1>Device Graphs</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>" name="traf">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>><img src=img/32/chart.png border=0 title="Hold down Ctrl to select up to 6 interfaces, for stacked graphs (e.g. for channels)"></a></th>
<th>
Device
</th><th>
<select size=6 name="dv" onchange="this.form.submit();">
<?
$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('devices');
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
<?
if ($dv) {
	$query	= GenQuery('interfaces','s','*','name','',array('device'),array('='),array($dv) );
	$res	= @DbQuery($query,$link);
	if($res){
?>
<select multiple size=6 name="if[]">
<?
		while( ($i = @DbFetchRow($res)) ){
			echo "<OPTION VALUE=\"$i[1]\" ";
			if(in_array($i[1],$if)){echo "selected";}
			echo " >$i[1]\n";
		}
		@DbFreeResult($res);
		echo "</select>";
	}
}
?>
</th>
<td>
<INPUT type="checkbox" name="cpu" <?=($cpu)?"checked":""?> > CPU<br>
<INPUT type="checkbox" name="mem" <?=($mem)?"checked":""?> > Mem<br>
<INPUT type="checkbox" name="tmp" <?=($tmp)?"checked":""?> > Temp<br>
</td>
<th>
Duration
<SELECT size=1 name="dur">
<OPTION VALUE="7" <?=($dur == "7")?"selected":""?> >Week
<OPTION VALUE="30" <?=($dur == "30")?"selected":""?> >Month
<OPTION VALUE="90" <?=($dur == "90")?"selected":""?> >Quarter
<OPTION VALUE="180" <?=($dur == "180")?"selected":""?> >Semester
<OPTION VALUE="360" <?=($dur == "360")?"selected":""?> >Year
</SELECT>
</th>
<th>
<INPUT type="checkbox" name="sze" <?=($sze == "m")?"checked":""?> > Medium Graphs<br>
</th>
<th width=80><input type="submit" value="Show"></th>
</tr></table></form><p><center>
<?
$udev = rawurlencode($dv);
if($cpu ){
	echo "<img src=inc/drawrrd.php?dv=$udev&s=$sze&t=cpu&dur=$dur>\n";
}
if($mem ){
	echo "<img src=inc/drawrrd.php?dv=$udev&s=$sze&t=mem&dur=$dur>\n";
}
if($tmp ){
	echo "<img src=inc/drawrrd.php?dv=$udev&s=$sze&t=tmp&dur=$dur>\n";
}
if( isset($if[0]) ){
	$ifs = 'if[]='. implode('&if[]=', $if);

	echo "<p><img src=inc/drawrrd.php?dv=$udev&$ifs&s=$sze&t=trf&dur=$dur>\n";
	echo "<img src=inc/drawrrd.php?dv=$udev&$ifs&s=$sze&t=err&dur=$dur>\n";
}
include_once ("inc/footer.php");
?>