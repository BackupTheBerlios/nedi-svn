<?
/*
#============================================================================
# Program: Devices-Interfaces.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 27/06/07	initial version.
*/

$bg1	 = "90D0D0";
$bg2	 = "A0E0E0";
$btag	 = "";
$nocache = 0;
$calendar= 1;
$refresh = 0;

include_once ("inc/header.php");
include_once ('inc/libdev.php');

$_GET = sanitize($_GET);
$sta = isset($_GET['sta']) ? $_GET['sta'] : "";
$stb = isset($_GET['stb']) ? $_GET['stb'] : "";
$ina = isset($_GET['ina']) ? $_GET['ina'] : "";
$inb = isset($_GET['inb']) ? $_GET['inb'] : "";
$opa = isset($_GET['opa']) ? $_GET['opa'] : "";
$opb = isset($_GET['opb']) ? $_GET['opb'] : "";
$cop = isset($_GET['cop']) ? $_GET['cop'] : "";
$ord = isset($_GET['ord']) ? $_GET['ord'] : "";
$col = isset($_GET['col']) ? $_GET['col'] : array('device','ifname','description','alias','comment');

$cols = array(	"ifname"=>"IF Name",
		"device"=>"Device",
		"ifidx"=>"IF Index",
		"type"=>"Type",
		"mac"=>"MAC Address",
		"description"=>"Description",
		"alias"=>"Alias",
		"status"=>"Status",
		"speed"=>"Speed",
		"duplex"=>"Duplex",
		"vlid"=>"Vlan ID",
		"inoct"=>"Inbound Octets",
		"inerr"=>"Inbound Errors",
		"outoct"=>"Outbound Octets",
		"outerr"=>"Outbound Errors",
		"dinoct"=>"Last In Octets",
		"dinerr"=>"Last In Errors",
		"doutoct"=>"Last Out Octets",
		"douterr"=>"Last Out Errors",
		"comment"=>"Comment"
		);

?>
<h1>Interface List</h1>
<form method="get" name="list" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/dumy.png border=0 title="Search IF table.">
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
<th valign=top>Operation<p>
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
<th valign=top>Display<p>
<SELECT MULTIPLE name="col[]" size=4>
<?
foreach ($cols as $k => $v){
       $selopt = (in_array($k,$col))?"selected":"";
       echo "<option value=\"$k\" $selopt >$v\n";
}
?>
<OPTION VALUE="graphs" <?=(in_array("graphs",$col))?"selected":""?> >Graphs
</SELECT>
</th>
<th width=80><input type="submit" value="Show"></th>
</tr></table></form><p>
<?
if ($ina){
	echo "<table bgcolor=#666666 $tabtag><tr bgcolor=#$bg2>\n";
	if( in_array("ifname",$col) ){echo "<td width=30></td>";}
	ColHead('ifname',80);
	foreach($col as $h){
		if($h != 'graphs' and $h != 'ifname'){
			ColHead($h);
		}
	}
	if( in_array("graphs",$col) ){echo "<th>Graphs</th>";}
	echo "</tr>\n";

	$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
	$query	= GenQuery('interfaces','s','*',$ord,'',array($ina,$inb),array($opa,$opb),array($sta,$stb),array($cop) );
	$res	= @DbQuery($query,$link);
	if($res){
		$row = 0;
		while( ($if = @DbFetchRow($res)) ){
			if ($row % 2){$bg = $bgb; $bi = $bib;}else{$bg = $bga; $bi = $bia;}
			$row++;
			$ud = rawurlencode($if[0]);
			$ui = rawurlencode($if[1]);
			echo "<tr bgcolor=#$bg>";
			if(in_array("ifname",$col)){
				list($ifimg,$iftit)	= Iftype($if[4]);
				echo "<th bgcolor=#$bi><img src=img/$ifimg title=\"$iftit\"></th>";
			}
			echo "<td><a href=Nodes-List.php?ina=device&opa==&sta=$ud&cop=AND&inb=ifname&opb==&stb=$ui>$if[1]</a></td>\n";
			if(in_array("device",$col)){
				echo "<td><a href=Devices-Status.php?dev=$ud>$if[0]</a></td>\n";
			}
			if(in_array("ifidx",$col)){echo "<td align=right>$if[2]</td>";}
			if(in_array("type",$col)){echo "<td align=right>$if[4]</td>";}
			if(in_array("mac",$col)){echo "<td>$if[5]</td>";}
			if(in_array("description",$col)){echo "<td>$if[6]</td>";}
			if(in_array("alias",$col)){echo "<td>$if[7]</td>";}
			if(in_array("status",$col)){echo "<td align=right>$if[8]</td>";}
			if(in_array("speed",$col)){echo "<td align=right>".ZFix($if[9])."</td>";}
			if(in_array("duplex",$col)){echo "<td>$if[10]</td>";}
			if(in_array("vlid",$col)){echo "<td align=right>$if[11]</td>";}

			if(in_array("inoct",$col)){echo "<td align=right>$if[12]</td>";}
			if(in_array("inerr",$col)){echo "<td align=right>$if[13]</td>";}
			if(in_array("outoct",$col)){echo "<td align=right>$if[14]</td>";}
			if(in_array("outerr",$col)){echo "<td align=right>$if[15]</td>";}
			if(in_array("dinoct",$col)){echo "<td align=right>$if[16]</td>";}
			if(in_array("dinerr",$col)){echo "<td align=right>$if[17]</td>";}
			if(in_array("doutoct",$col)){echo "<td align=right>$if[18]</td>";}
			if(in_array("douterr",$col)){echo "<td align=right>$if[19]</td>";}

			if(in_array("comment",$col)){echo "<td>$if[20]</td>";}
			if(in_array("graphs",$col)){
				echo "<td nowrap align=center>\n";
				echo "<a href=Devices-Graph.php?dv=$ud&if%5B%5D=$ui><img src=inc/drawrrd.php?dv=$ud&if%5B%5D=$ui&s=s&t=trf border=0>\n";
				echo "<img src=inc/drawrrd.php?dv=$ud&if%5B%5D=$ui&s=s&t=err border=0></a>\n";
			}
			echo "</tr>\n";
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row Interfaces ($query)</td></tr></table>\n";
}
include_once ("inc/footer.php");
?>
