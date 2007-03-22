<?
/*
#============================================================================
# Program: Nodes-List.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 25/02/05	initial version
# 04/03/05	Revised backend
# 31/03/05	decimal IPs
# 17/03/06	new SQL query support
# 29/01/07	new Sorting approach
*/

$bg1	= "AACCBB";
$bg2	= "BBDDCC";
$btag	= "";
$nocache= 0;
$calendar= 1;
$refresh = 0;

include_once ("inc/header.php");
include_once ('inc/libnod.php');

$_GET = sanitize($_GET);
$sta = isset($_GET['sta']) ? $_GET['sta'] : "";
$stb = isset($_GET['stb']) ? $_GET['stb'] : "";
$ina = isset($_GET['ina']) ? $_GET['ina'] : "";
$inb = isset($_GET['inb']) ? $_GET['inb'] : "";
$opa = isset($_GET['opa']) ? $_GET['opa'] : "";
$opb = isset($_GET['opb']) ? $_GET['opb'] : "";
$cop = isset($_GET['cop']) ? $_GET['cop'] : "";
$ord = isset($_GET['ord']) ? $_GET['ord'] : "";
$col = isset($_GET['col']) ? $_GET['col'] : array('name','ip','ifname','vlanid','firstseen','lastseen');

$cols = array(	"name"=>"Name",
		"ip"=>"IP Address",
		"mac"=>"MAC Address",
		"oui"=>"OUI Vendor",
		"firstseen"=>"Firstseen",
		"lastseen"=>"Lastseen",
		"device"=>"Device",
		"ifname"=>"Ifname",
		"vlanid"=>"Vlan",
		"ifmetric"=>"IF Metric",
		"ifupdate"=>"IF Update",
		"ifchanges"=>"IF Chg",
		"ipupdate"=>"IP Update",
		"ipchanges"=>"IP Chg",
		"iplost"=>"IP Lost",
		);

?>
<h1>Node List</h1>
<form method="get" name="list" action="<?=$_SERVER['PHP_SELF']?>" name="search">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/cubs.png border=0 title="List those nodes...">
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
<th valign=top>Display<p>
<SELECT MULTIPLE name="col[]" size=4>
<?
foreach ($cols as $k => $v){
       $selopt = (in_array($k,$col))?"selected":"";
       echo "<option value=\"$k\" $selopt >$v\n";
}
if($rrdstep){
	echo '<OPTION VALUE="graph" ';
	if(in_array("graph",$col)){echo "selected";}
	echo "> Graphs";
}
?>
<OPTION VALUE="ifdet" <?=(in_array("ifdet",$col))?"selected":""?> >IF Details
<OPTION VALUE="ssh" <?=(in_array("ssh",$col))?"selected":""?> >SSH Server
<OPTION VALUE="tel" <?=(in_array("tel",$col))?"selected":""?> >Telnet Server
<OPTION VALUE="www" <?=(in_array("www",$col))?"selected":""?> >Web Server
</SELECT>
</th>
<th width=80><input type="submit" value="Search"></th>
</tr></table></form><p>
<?

if ($ina){

	echo "<table bgcolor=#666666 $tabtag><tr bgcolor=#$bg2>\n";

	echo "<th width=80>&nbsp;</th>\n";
	if( in_array("name",$col) )	{ColHead('name');}
	if( in_array("ip",$col) )	{ColHead('ip');}
	if( in_array("ipupdate",$col) )	{ColHead('ipupdate');}
	if( in_array("ipchanges",$col) ){ColHead('ipchanges');}
	if( in_array("iplost",$col) )	{ColHead('iplost');}
	if( in_array("mac",$col) )	{ColHead('mac');}
	if( in_array("oui",$col) )	{ColHead('oui');}
	if( in_array("ifname",$col) )	{ColHead('ifname');}
	if( in_array("vlanid",$col) )	{ColHead('vlanid');}
	if( in_array("ifmetric",$col) )	{ColHead('ifmetric');}
	if( in_array("ifupdate",$col) )	{ColHead('ifupdate');}
	if( in_array("ifchanges",$col) ){ColHead('ifchanges');}
	if(in_array("ifdet",$col))	{echo "<th>IF Details</th>";}
	if(in_array("graph",$col))	{echo "<th>Traffic / Errors</th>";}
	if( in_array("firstseen",$col) ){ColHead('firstseen');}
	if( in_array("lastseen",$col) )	{ColHead('lastseen');}
	if( in_array("ssh",$col) )	{echo "<th>SSH server</th>";}
	if( in_array("tel",$col) )	{echo "<th>Telnet server</th>";}
	if( in_array("www",$col) )	{echo "<th>Web server</th>";}
	echo "</tr>\n";

	$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
	$query	= GenQuery('nodes','s','*',$ord,'',array($ina,$inb),array($opa,$opb),array($sta,$stb),array($cop));
	$res	= @DbQuery($query,$link);
	if($res){
		$row = 0;
		while( ($n = @DbFetchRow($res)) ){
			if ($row % 2){$bg = $bgb; $bi = $bib;}else{$bg = $bga; $bi = $bia;}
			$row++;
			$name		= preg_replace("/^(.*?)\.(.*)/","$1", $n[0]);
			$ip		= long2ip($n[1]);
			$img		= Nimg("$n[2];$n[3]");
			list($fc,$lc)	= Agecol($n[4],$n[5],$row % 2);
			$ud = urlencode($n[6]);
			$if = urlencode($n[7]);

			echo "<tr bgcolor=#$bg>\n";
			echo "<th bgcolor=#$bi><a href=Nodes-Status.php?mac=$n[2]><img src=img/oui/$img title=\"$n[3] ($n[2])\" border=0></a></th>\n";
			if(in_array("name",$col)){ echo "<td><b>$n[0]</b></td>";}
			if(in_array("ip",$col)){
				echo "<td><a href=?ina=ip&opa==&sta=$ip>$ip</a></td>";
			}
			if(in_array("ipupdate",$col)){
				$au      	= date("j.M G:i:s",$n[12]);
				list($a1c,$a2c) = Agecol($n[12],$n[12],$row % 2);
				echo "<td bgcolor=#$a1c>$au</td>";
			}
			if(in_array("ipchanges",$col))	{echo "<td align=right>$n[13]</td>";}
			if(in_array("iplost",$col))	{echo "<td align=right>$n[14]</td>";}
			if(in_array("mac",$col))	{echo "<td>$n[2]</td>";}
			if(in_array("oui",$col))	{echo "<td>$n[3]</td>";}
			if(in_array("ifname",$col)){
				echo "<td><a href=?ina=device&opa==&sta=$ud&ord=ifname>$n[6]</a>";
				echo " - <a href=?ina=device&opa==&inb=ifname&opb==&sta=$ud&cop=AND&stb=$if>$n[7]</a></td>";
			}
			if(in_array("vlanid",$col))	{echo "<td>$n[8]</td>";}
			if(in_array("ifupdate",$col)){
				$iu       = date("j.M G:i:s",$n[10]);
				list($i1c,$i2c) = Agecol($n[10],$n[10],$row % 2);
				echo "<td bgcolor=#$i1c>$iu</td>";
			}
			if(in_array("ifmetric",$col))	{echo "<td align=right>$n[9]</td>";}
			if(in_array("ifchanges",$col))	{echo "<td align=right>$n[13]</td>";}
			if($rrdstep and in_array("graph",$col)){
				echo "<td nowrap align=center>\n";
				echo "<a href=Devices-Graph.php?dv=$ud&if%5B%5D=$if><img src=inc/drawrrd.php?dv=$ud&if%5B%5D=$if&s=s&t=trf border=0>\n";
				echo "<img src=inc/drawrrd.php?dv=$ud&if%5B%5D=$if&s=s&t=err border=0></a>\n";
			}
			if(in_array("ifdet",$col)){
				$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
				$iquery	= GenQuery('interfaces','s','*','','',array('device','ifname'),array('=','='),array($n[6],$n[7]),array('AND') );
				$ires	= @DbQuery($iquery,$link);
				$nif	= @DbNumRows($ires);
				if ($nif == 1) {
					$if	= @DbFetchRow($ires);		
					if ($if[8] == "2"){
						$ifimg	= "<img src=img/bulbr.png title=\"Disabled!\">";
					}else{
						$ifimg = "<img src=img/bulbg.png title=\"Enabled\">";
					}
					echo "<td> $ifimg ".Zfix($if[9])."-$if[10] <i>$if[7] $if[20]</i></td>";
				}else{
					echo "<td>-</td>";
				}
				@DbFreeResult($ires);
			}
			if(in_array("firstseen",$col)){
				$fs       = date("j.M G:i:s",$n[4]);
				echo "<td bgcolor=#$fc>$fs</td>";
			}
			if(in_array("lastseen",$col)){
				$ls       = date("j.M G:i:s",$n[5]);
				echo "<td bgcolor=#$lc>$ls</td>";
			}
			if(in_array("ssh",$col)){
				echo "<td><a href=ssh://$ip><img src=img/16/lokc.png border=0></a>\n";
				echo "<td>". CheckTCP($ip,'22','') ."</td>";
			}
			if(in_array("tel",$col)){
				echo "<td><a href=telnet://$ip><img src=img/16/kons.png border=0></a>\n";
				echo CheckTCP($ip,'23','') ."</td>";
			}
			if(in_array("www",$col)){
				echo "<td><a href=http://$ip target=window><img src=img/16/glob.png border=0></a>\n";
				echo CheckTCP($ip,'80'," \r\n\r\n") ."</td>";
			}
			echo "</tr>\n";
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
	}
	echo "</table><table bgcolor=#666666 cellspacing=1 cellpadding=8 border=0 width=100%>\n";
	echo "<tr bgcolor=#$bg2><td>$row Nodes ($query)</td></tr></table>\n";
}
include_once ("inc/footer.php");
?>
