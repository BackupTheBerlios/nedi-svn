<?
/*
#============================================================================
# Program: Devices-List.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 22/02/05	initial version.
# 04/03/05	Revised backend
# 31/03/05	decimal IPs
# 10/03/06	new SQL query support
# 29/01/07	new Sorting approach
*/

$bg1	 = "88AADD";
$bg2	 = "99BBEE";
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
$col = isset($_GET['col']) ? $_GET['col'] : array('name','ip','location','contact','type');

$cols = array(	"name"=>"Name",
		"ip"=>"Main IP",
		"origip"=>"Original IP",
		"serial"=>"Serial #",
		"type"=>"Type",
		"services"=>"Services",
		"description"=>"Description",
		"os"=>"OS",
		"bootimage"=>"Bootimage",
		"location"=>"Location",
		"contact"=>"Contact",
		"vtpdomain"=>"VTP Domain",
		"vtpmode"=>"VTP Mode",
		"snmpversion"=>"SNMP Ver",
		"community"=>"Community",
		"cliport"=>"CLI port",
		"login"=>"Login",
		"firstseen"=>"First Seen",
		"lastseen"=>"Last Seen"
		);

?>
<h1>Device List</h1>
<form method="get" name="list" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/dev.png border=0 title="Conditions are regexp, IPs can have [/Prefix] to match subnets.">
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
if($rrdstep){ ?>
<OPTION VALUE="graphs" <?=(in_array("graphs",$col))?"selected":""?> >Graphs
<? } ?>
</SELECT>
</th>
<th width=80><input type="submit" value="Search"></th>
</tr></table></form><p>
<?
if ($ina){
	echo "<table bgcolor=#666666 $tabtag><tr bgcolor=#$bg2>\n";

	ColHead('name',80);
	if( in_array("ip",$col) ){ColHead('ip');}
	if( in_array("origip",$col) ){ColHead('origip');}
	if( in_array("serial",$col) ){ColHead('serial');}
	if( in_array("type",$col) ){ColHead('type');}
	if( in_array("services",$col) ){ColHead('services');}
	if( in_array("description",$col) ){ColHead('description');}
	if( in_array("os",$col) ){ColHead('os');}
	if( in_array("bootimage",$col) ){ColHead('bootimage');}
	if( in_array("location",$col) ){ColHead('location');}
	if( in_array("contact",$col) ){ColHead('contact');}
	if( in_array("vtpdomain",$col) ){ColHead('vtpdomain');}
	if( in_array("vtpmode",$col) ){ColHead('vtpmode');}
	if( in_array("snmpversion",$col) ){ColHead('snmpversion');}
	if( in_array("community",$col) ){ColHead('community');}
	if( in_array("login",$col) ){ColHead('login');}
	if( in_array("cliport",$col) ){ColHead('cliport');}
	if( in_array("firstseen",$col) ){ColHead('firstseen');}
	if( in_array("lastseen",$col) ){ColHead('lastseen');}
	if( in_array("graphs",$col) ){echo "<th>Graphs</th>";}
	echo "</tr>\n";

	$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
	$query	= GenQuery('devices','s','*',$ord,'',array($ina,$inb),array($opa,$opb),array($sta,$stb),array($cop) );
	$res	= @DbQuery($query,$link);
	if($res){
		$row = 0;
		while( ($dev = @DbFetchRow($res)) ){
			if ($row % 2){$bg = $bgb; $bi = $bib;}else{$bg = $bga; $bi = $bia;}
			$row++;
			$ip = long2ip($dev[1]);
			$oi = long2ip($dev[19]);
			$ud = urlencode($dev[0]);
			list($fc,$lc) = Agecol($dev[4],$dev[5],$row % 2);
			echo "<tr bgcolor=#$bg><th bgcolor=#$bi>\n";
			if(in_array("name",$col)){
				echo "<a href=Devices-Status.php?dev=$ud><img src=img/dev/$dev[18].png title=\"$dev[3]\" border=0 vspace=4></a><br>\n";
				}
			echo "<a href=Nodes-List.php?ina=device&opa==&sta=$ud&ord=device><b>$dev[0]</b></a>\n";
			if(in_array("ip",$col)){
				echo "<td><a href=telnet://$ip>$ip</a></td>";
			}
			if(in_array("origip",$col)){
				echo "<td><a href=telnet://$oi>$oi</a></td>";
			}
			if(in_array("serial",$col)){ echo "<td>$dev[2]</td>";}
			if(in_array("type",$col)){ 
				if( strstr($dev[3],"1.3.6.") ){
					echo "<td><a href=Other-Defgen.php?so=$dev[3]&ip=$ip&c=$dev[15]>$dev[3]</a></td>";
				}else{
					echo "<td>$dev[3]</td>";
				}
			}
			if(in_array("services",$col)){
				$sv = Syssrv($dev[6]);
				echo "<td>$sv ($dev[6])</td>";
			}
			if(in_array("description",$col)){echo "<td>$dev[7]</td>";}
			if(in_array("os",$col))		{echo "<td>$dev[8]</td>";}
			if(in_array("bootimage",$col))	{echo "<td>$dev[9]</td>";}
			if(in_array("location",$col))	{echo "<td>$dev[10]</td>";}
			if(in_array("contact",$col))	{echo "<td>$dev[11]</td>";}
			if(in_array("vtpdomain",$col))	{echo "<td>$dev[12]</td>";}
			if(in_array("vtpmode",$col))	{echo "<td>".VTPmod($dev[13])."</td>";}
			if(in_array("snmpversion",$col)){echo "<td>". ($dev[14] & 127) . (($dev[14] & 128)?"HC":"") ."</td>";}
			if(in_array("community",$col))	{echo "<td>$dev[15]</td>";}
			if(in_array("login",$col))	{echo "<td>$dev[17]</td>";}
			if(in_array("cliport",$col))	{echo "<td>$dev[16]</td>";}
			if( in_array("firstseen",$col) ){
				$fs       = date("j.M G:i:s",$dev[4]);
				echo "<td bgcolor=#$fc>$fs</td>";
			}
			if( in_array("lastseen",$col) ){
				$ls       = date("j.M G:i:s",$dev[5]);
				echo "<td bgcolor=#$lc>$ls</td>";
			}
			if(in_array("graphs",$col)){
				echo "<th><a href=Devices-Graph.php?dv=$ud&cpu=on><img src=inc/drawrrd.php?dv=$ud&t=cpu&s=s border=0 title=\"CPU load\">";
				echo "<a href=Devices-Graph.php?dv=$ud&mem=on><img src=inc/drawrrd.php?dv=$ud&t=mem&s=s border=0 title=\"Available Memory\">";
				echo "<a href=Devices-Graph.php?dv=$ud&tmp=on><img src=inc/drawrrd.php?dv=$ud&t=tmp&s=s border=0 title=\"Temperature\"></th>";
			}
			echo "</tr>\n";
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$row Devices ($query)</td></tr></table>\n";
}
include_once ("inc/footer.php");
?>
