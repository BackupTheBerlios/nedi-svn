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
$col = isset($_GET['col']) ? $_GET['col'] : array('ip','location','contact','type');

?>
<h1>Device List</h1>
<form method="get" name="list" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/dev.png border=0 title="Conditions are regexp, IPs can have [/Prefix] to match subnets.">
</a></th>
<th valign=top>Condition A<p>
<SELECT size=1 name="ina">
<? selectbox("devices",$ina);?>
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
<? selectbox("devices",$inb);?>
</SELECT>
<SELECT size=1 name="opb">
<? selectbox("oper",$opb);?>
</SELECT>
<p><a href="javascript:show_calendar('list.stb');"><img src="img/cal.png" border=0 hspace=8></a>
<input type="text" name="stb" value="<?=$stb?>" size="25">
</th>
<th valign=top>Display<p>
<SELECT MULTIPLE name="col[]" size=4>
<OPTION VALUE="ip" <?=(in_array("ip",$col))?"selected":""?> >IP address
<OPTION VALUE="serial" <?=(in_array("serial",$col))?"selected":""?> >Serial#
<OPTION VALUE="type" <?=(in_array("type",$col))?"selected":""?> >Type
<OPTION VALUE="services" <?=(in_array("services",$col))?"selected":""?> >Services
<OPTION VALUE="description" <?=(in_array("description",$col))?"selected":""?> >Description
<OPTION VALUE="os" <?=(in_array("os",$col))?"selected":""?> >OS
<OPTION VALUE="bootimage" <?=(in_array("bootimage",$col))?"selected":""?> >Bootimage
<OPTION VALUE="location" <?=(in_array("location",$col))?"selected":""?> >Location
<OPTION VALUE="contact" <?=(in_array("contact",$col))?"selected":""?> >Contact
<OPTION VALUE="vtpdomain" <?=(in_array("vtpdomain",$col))?"selected":""?> >VTP domain
<OPTION VALUE="vtpmode" <?=(in_array("vtpmode",$col))?"selected":""?> >VTP mode
<OPTION VALUE="SNMP" <?=(in_array("SNMP",$col))?"selected":""?> >SNMP Access
<OPTION VALUE="login" <?=(in_array("login",$col))?"selected":""?> >CLI Access
<? if($rrdstep){ ?>
<OPTION VALUE="graphs" <?=(in_array("graphs",$col))?"selected":""?> >Graphs
<OPTION VALUE="seen" <?=(in_array("seen",$col))?"selected":""?> >Seen
<? } ?>
</SELECT>
</th>
<th valign=top>Order by<p>
<SELECT name="ord" size=4>
<OPTION VALUE="name" <?=($ord == "name")?"selected":""?> >Name
<OPTION VALUE="ip" <?=($ord == "ip")?"selected":""?> >IP address
<OPTION VALUE="serial" <?=($ord == "serial")?"selected":""?> >Serial #
<OPTION VALUE="type" <?=($ord == "type")?"selected":""?> >Type
<OPTION VALUE="bootimage" <?=($ord == "bootimage")?"selected":""?> >Bootimage
<OPTION VALUE="location" <?=($ord == "location")?"selected":""?> >Location
<OPTION VALUE="vtpdomain" <?=($ord == "vtpdomain")?"selected":""?> >VTP domain
<OPTION VALUE="firstseen" <?=($ord == "firstseen")?"selected":""?> >First seen
<OPTION VALUE="lastseen" <?=($ord == "lastseen")?"selected":""?> >Last seen
</SELECT>
</th>
<th width=80><input type="submit" value="Search"></th>
</tr></table></form><p>
<?
if ($ina){
	echo "<table bgcolor=#666666 $tabtag><tr bgcolor=#$bg2>\n";

	echo "<th width=80>Device</th>\n";
	if( in_array("ip",$col) ){echo "<th>IP Address</th>";}
	if( in_array("serial",$col) ){echo "<th>Serial #</th>";}
	if( in_array("type",$col) ){echo "<th>Type</th>";}
	if( in_array("services",$col) ){echo "<th>Services</th>";}
	if( in_array("description",$col) ){echo "<th>Description</th>";}
	if( in_array("os",$col) ){echo "<th>OS</th>";}
	if( in_array("bootimage",$col) ){echo "<th>Bootimage</th>";}
	if( in_array("location",$col) ){echo "<th>Location</th>";}
	if( in_array("contact",$col) ){echo "<th>Contact</th>";}
	if( in_array("vtpdomain",$col) ){echo "<th>VTPdomain</th>";}
	if( in_array("vtpmode",$col) ){echo "<th>VTPmode</th>";}
	if( in_array("SNMP",$col) ){echo "<th>SNMP Access</th>";}
	if( in_array("login",$col) ){echo "<th>Login</th>";}
	if( in_array("graphs",$col) ){echo "<th>Graphs</th>";}
	if( in_array("seen",$col) ){echo "<th>First seen</th><th>Last seen</th>";}
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
			$ud = urlencode($dev[0]);
			echo "<tr bgcolor=#$bg>\n";
			echo "<th bgcolor=#$bi><a href=Devices-Status.php?dev=$ud><img src=img/dev/$dev[18].png title=\"$dev[3]\" border=0 vspace=4></a><br>\n";
			echo "<a href=Nodes-List.php?ina=device&opa==&sta=$ud&ord=ifname><b>$dev[0]</b></a>\n";
			if(in_array("ip",$col)){
				echo "<td><a href=telnet://$ip>$ip</a></td>";
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
			if(in_array("description",$col)){ echo "<td>$dev[7]</td>";}
			if(in_array("os",$col)){ echo "<td>$dev[8]</td>";}
			if(in_array("bootimage",$col)){ echo "<td>$dev[9]</td>";}
			if(in_array("location",$col)){ echo "<td>$dev[10]</td>";}
			if(in_array("contact",$col)){ echo "<td>$dev[11]</td>";}
			if(in_array("vtpdomain",$col)){ echo "<td>$dev[12]</td>";}
			if(in_array("vtpmode",$col)){ echo "<td>".VTPmod($dev[13])."</td>";}
			if(in_array("SNMP",$col)){$ver = $dev[14] & 127; echo "<td>$dev[15] Ver:$ver</td>";}
			if(in_array("login",$col)){ echo "<td>$dev[17] (Port $dev[16])</td>";}
			if(in_array("graphs",$col)){
				echo "<th><a href=Devices-Graph.php?dv=$ud&cpu=on><img src=inc/drawrrd.php?dv=$ud&t=cpu&s=s border=0 title=\"CPU load\">";
				echo "<a href=Devices-Graph.php?dv=$ud&mem=on><img src=inc/drawrrd.php?dv=$ud&t=mem&s=s border=0 title=\"Available Memory\">";
				echo "<a href=Devices-Graph.php?dv=$ud&tmp=on><img src=inc/drawrrd.php?dv=$ud&t=tmp&s=s border=0 title=\"Temperature\"></th>";
			}

			if( in_array("seen",$col) ){
				list($fc,$lc) = Agecol($dev[4],$dev[5],$row % 2);
				$fs       = date("j.M G:i:s",$dev[4]);
				echo "<td bgcolor=#$fc>$fs</td>";
				$ls       = date("j.M G:i:s",$dev[5]);
				echo "<td bgcolor=#$lc>$ls</td>";
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
