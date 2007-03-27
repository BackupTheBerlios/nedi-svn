<?

/*
#============================================================================
# Program: Monitoring-Messages.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 07/06/05	initial version.
# 17/03/06	new SQL query support
# 11/07/06	added paging
# 23/03/07	improved paging
*/

$bg1	= "CC9999";
$bg2	= "DDAAAA";
$btag	= "";
$nocache= 0;
$calendar= 1;
$refresh = 1;

include_once ("inc/header.php");
include_once ('inc/libmon.php');

$_GET = sanitize($_GET);
$sta = isset($_GET['sta']) ? $_GET['sta'] : "";
$stb = isset($_GET['stb']) ? $_GET['stb'] : "";
$ina = isset($_GET['ina']) ? $_GET['ina'] : "";
$inb = isset($_GET['inb']) ? $_GET['inb'] : "";
$opa = isset($_GET['opa']) ? $_GET['opa'] : "";
$opb = isset($_GET['opb']) ? $_GET['opb'] : "";
$cop = isset($_GET['cop']) ? $_GET['cop'] : "";
$lvl = isset($_GET['lvl']) ? $_GET['lvl'] : "";
$lim = isset($_GET['lim']) ? $_GET['lim'] : 10;
$off = isset($_GET['off']) ? $_GET['off'] : 0;

if($lvl){
	$in[] = 'level';
	$op[] = '=';
	$st[] = $lvl;
	if($sta or $cop){$co[] = 'AND';}
}
$in[] = $ina;
$in[] = $inb;
$op[] = $opa;
$op[] = $opb;
$st[] = $sta;
$st[] = $stb;
$co[] = $cop;

$nof = $off;
if( isset($_GET['p']) ){
	$nof = abs($off - $lim);
}elseif( isset($_GET['n']) ){
	$nof = $off + $lim;
}
$dlim = "$nof,$lim";

$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
if( isset($_GET['del']) ){
	if(preg_match("/adm/",$_SESSION['group']) ){
		$query	= GenQuery('messages','d','*','id desc',$dlim,$in,$op,$st,$co );
		if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Messages $delokmsg</h3>";}
	}else{
		echo $nokmsg;
	}
}

$cols = array(	"id"=>"ID",
		"level"=>"Level",
		"time"=>"Time",
		"source"=>"Source",
		"info"=>"Info"
		);

?>
<h1>Monitoring Messages</h1>
<form method="get" name="list" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/say.png border=0 title="View and del messages.">
</a></th>
<th valign=top>Condition A<p>
<SELECT size=1 name="ina">
<?
foreach ($cols as $k => $v){
       $selopt = ($ina == $k)?"selected":"";
       echo "<option value=\"$k\" $selopt >$v\n";
}
?>
</SELECT>
<SELECT size=1 name="opa">
<? selectbox("oper",$opa);?>
</SELECT>
<p><a href="javascript:show_calendar('list.sta');"><img src="img/cal.png" border=0 hspace=8></a>
<input type="text" name="sta" value="<?=$sta?>" size="25">
</th>
<th valign=top>Combination<p>
<SELECT size=1 name="cop">
<? selectbox("comop",$cop);?>
</SELECT>
</th>
<th valign=top>Condition B<p>
<SELECT size=1 name="inb">
<?
foreach ($cols as $k => $v){
       $selopt = ($inb == $k)?"selected":"";
       echo "<option value=\"$k\" $selopt >$v\n";
}
?>
</SELECT>
<SELECT size=1 name="opb">
<? selectbox("oper",$opb);?>
</SELECT>
<p><a href="javascript:show_calendar('list.stb');"><img src="img/cal.png" border=0 hspace=8></a>
<input type="text" name="stb" value="<?=$stb?>" size="25">
</th>
<th valign=top>Level<p>
<SELECT size=1 name="lvl">
<OPTION VALUE="">Any
<?
foreach (array_keys($mlvl) as $ml){
	echo "<option value=\"$ml\" ";
	if($ml == $lvl){echo "selected";}
	echo ">$mlvl[$ml]\n";
}
?>
</SELECT>
</th>
<th valign=top>Limit<p>
<SELECT size=1 name="lim">
<? selectbox("limit",$lim);?>
</SELECT>
</th>

<th width=80>
<input type="submit" name="show" value="Show">
<p>
<input type="hidden" name="off" value="<?=$nof?>">
<input type="submit" name="p" value="<-">
<input type="submit" name="n" value="->">
<p>

<input type="submit" name=del value="Delete" onclick="return confirm('Delete matching messages (paging ignored)?')" >
</th></tr>
</table></form><p>

<table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bg2?> >
<th width=80><img src=img/32/eyes.png><br>Event</th>
<th><img src=img/32/info.png title="Unspecified<50, 100=Notice, 150=Warning, 200=Alert, 250=Emergency"><br>Level</th>
<th width=100><img src=img/32/clock.png><br>Time</th>
<th><img src=img/32/dev.png title="Device (if in devices) or IP (will only produce level <50)"><br>Source</th>
<th><img src=img/32/idea.png title="Action based on message info"><br>Action</th>
<th><img src=img/32/find.png><br>Info</th>
</tr>

<?
$query	= GenQuery('messages','s','*','id desc',$dlim,$in,$op,$st,$co );
$res	= @DbQuery($query,$link);
$nmsg = 0;
if($res){
	$row  = 0;
	while( ($m = @DbFetchRow($res)) ){
		if ($row == "1"){ $row = "0"; $bg = $bga; $bi = $bia; }
		else{ $row = "1"; $bg = $bgb; $bi = $bib; }
		$hint = "";
		$time = date("d.M H:i:s",$m[2]);
		$fd   = str_replace(" ","%20",date("m/d/Y H:i:s",$m[2]));
		$usrc = rawurlencode($m[3]);
		echo "<tr bgcolor=#$bg><th><a href=$_SERVER[PHP_SELF]?ina=id&opa==&sta=$m[0]>$m[0]</a></th>\n";
		echo "<th bgcolor=$bi><a href=$_SERVER[PHP_SELF]?ina=level&opa==&sta=$m[1]><img src=img/16/" . $mico[$m[1]] . ".png title=\"" . $mlvl[$m[1]] . "\" border=0></a></th>\n";
		echo "<td><a href=$_SERVER[PHP_SELF]?ina=time&opa==&sta=$fd>$time</a></td><th><a href=$_SERVER[PHP_SELF]?ina=source&opa==&sta=$usrc>$m[3]</a></th><th>\n";
		if($m[1] < 50){
			echo "<a href=Nodes-List.php?ina=ip&opa==&sta=$m[3]><img src=img/16/cubs.png hspace=8 border=0></a>";
			if($o = strstr($m[4],"client ")){
				$nip = substr( $o,7,strpos($o,"#") - 7 );
				$hint = "<a href=Nodes-List.php?ina=ip&opa==&sta=$nip><img src=img/16/cubs.png hspace=8 border=0></a>";
			}
#		}elseif($o = strstr($m[4],"User")){
#			$usr = str_replace( " ","%20",substr( $o,6,strpos($o,"]") - 6 ) );
#			echo "<a href=$_SERVER[PHP_SELF]?ina=info&opa=regexp&sta=$usr&lim=500><img src=img/16/info.png hspace=8 border=0></a>";
#			if(strstr($m[4],"authenticated")){
#				$hint = "<img src=img/bulbg.png hspace=6>";
#			}elseif(strstr($m[4],"disconnected")){
#				$hint = "<img src=img/bulbr.png hspace=6>";
#			}
		}elseif(preg_match("/Config(ured from| changed)/",$m[4]) ){
			echo "<a href=Devices-Config.php?shc=$usrc><img src=img/16/cfg2.png hspace=8 border=0></a>";
		}elseif(strstr($m[4],"not discoverable!")){
			echo "<a href=Nodes-List.php?ina=ip&opa==&sta=$m[3]><img src=img/16/cubs.png hspace=8 border=0></a>";
		}elseif(strstr($m[4],"reappeared!")){
			echo "<a href=Nodes-Status.php?mac=$m[3]><img src=img/16/ngrn.png hspace=8 border=0></a>";
		}else{
			echo "<a href=Devices-Status.php?dev=$usrc&shp=on><img src=img/16/hwif.png hspace=8 border=0></a>";
		}
		echo "</th><td>$hint $m[4]</td></tr>\n";
		$nmsg++;
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}
echo "</table><table bgcolor=#666666 $tabtag >\n";
echo "<tr bgcolor=#$bg2><td>$nmsg messages with $query</td></tr></table>\n";

include_once ("inc/footer.php");
?>
