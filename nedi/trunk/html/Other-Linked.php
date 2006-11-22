<?
/*
#============================================================================
# Program: Other-Linked.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 02/11/06	initial version.
*/

$bg1	= "aa8866";
$bg2	= "bb9977";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");

$_GET = sanitize($_GET);
$dv = isset($_GET['dv']) ? $_GET['dv'] : "";
$if = isset($_GET['if']) ? $_GET['if'] : "";
$nb = isset($_GET['nb']) ? $_GET['nb'] : "";
$ni = isset($_GET['ni']) ? $_GET['ni'] : "";
$bwd = isset($_GET['bwd']) ? $_GET['bwd'] : "";
$bwn = isset($_GET['bwn']) ? $_GET['bwn'] : "";
$typ = isset($_GET['typ']) ? $_GET['typ'] : "";

$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
if ( isset($_GET['add']) ){
	$query	= GenQuery('links','i','','','',array('device','ifname','neighbour','nbrifname','bandwidth','type','power','nbrduplex','nbrvlanid'),'',array($dv,$if,$nb,$ni,$bwd,'S',0,'-',0) );
	if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Link $dv - $nb $upokmsg</h3>";}
	$query	= GenQuery('links','i','','','',array('device','ifname','neighbour','nbrifname','bandwidth','type','power','nbrduplex','nbrvlanid'),'',array($nb,$ni,$dv,$if,$bwn,'S',0,'-',0) );
	if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Link $nb - $dv $upokmsg</h3>";}
}elseif( isset($_GET['dli'])){
	$query	= GenQuery('links','d','','','',array('id'),array('='),array($_GET['dli']) );
	if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Link $_GET[dli] $delokmsg</h3>";}
}
?>
<h1>Link Editor</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>" name="li">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>><img src=img/32/wglb.png border=0 title="Edit links manually then use -L for discovery"></a></th>
<th>
Device
</th><th>
<select size=6 name="dv" onchange="this.form.submit();">
<?
$query	= GenQuery('devices');
$res	= @DbQuery($query,$link);
if($res){
	while( ($d = @DbFetchRow($res)) ){
		echo "<option value=\"$d[0]\" ";
		if($dv == $d[0]){echo "selected";}
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
<select size=6 name="if" onchange="this.form.submit();">
<?
		while( ($i = @DbFetchRow($res)) ){
			echo "<OPTION VALUE=\"$i[1]\" ";
			if($if == $i[1]){echo "selected";$bwd=$i[9];}
			echo " >$i[1] $i[7]\n";
		}
		@DbFreeResult($res);
		echo "</select>";
	}
}
?>
<th>
Neighbour
</th><th>
<select size=6 name="nb" onchange="this.form.submit();">
<?
$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('devices');
$res	= @DbQuery($query,$link);
if($res){
	while( ($d = @DbFetchRow($res)) ){
		echo "<option value=\"$d[0]\" ";
		if($nb == $d[0]){echo "selected";}
		echo " >$d[0]\n";
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}
?>
</select>
<?
if ($nb) {
	$query	= GenQuery('interfaces','s','*','name','',array('device'),array('='),array($nb) );
	$res	= @DbQuery($query,$link);
	if($res){
?>
<select size=6 name="ni" onchange="this.form.submit();">
<?
		while( ($i = @DbFetchRow($res)) ){
			echo "<OPTION VALUE=\"$i[1]\" ";
			if($ni == $i[1]){echo "selected";$bwn=$i[9];}
			echo " >$i[1]  $i[7]\n";
		}
		@DbFreeResult($res);
		echo "</select>";
	}
}
?>
<th>
Bandwidth
<p>
<input type="text" name="bwd" size=12 value="<?=$bwd?>">
<select size=1 name="bsd" onchange="document.li.bwd.value=document.li.bsd.options[document.li.bsd.selectedIndex].value">
<option value="">or select
<option value="1544000">T1
<option value="2048000">E1
<option value="10000000">10M
<option value="100000000">100M
<option value="1000000000">1G
<option value="10000000000">10G
</select><br>
<input type="text" name="bwn" size=12 value="<?=$bwn?>">
<select size=1 name="bsn" onchange="document.li.bwn.value=document.li.bsn.options[document.li.bsn.selectedIndex].value">
<option value="">or select
<option value="1544000">T1
<option value="2048000">E1
<option value="10000000">10M
<option value="100000000">100M
<option value="1000000000">1G
<option value="10000000000">10G
</select>
</th>

<th width=80>
<input type="submit" name="add" value="Add">
<p>or just<p>
<select size=1 name="typ" onchange="this.form.submit();">
<option value="">Show
<option value="S">Static
<option value="C">CDP
<option value="L">LLDP
<option value="O">OUI
<option value="M">MAC
</select>
</th>
</tr></table></form><p>
<?
if ($dv or $typ){
?>
<h2><?=$dv?> - Links</h2>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th colspan=2><img src=img/32/dev.png><br>Device</th>
<th colspan=2><img src=img/32/dev.png><br>Neighbour</th>
<th><img src=img/32/tap.png><br>Bandwidth</th>
<th><img src=img/32/powr.png title="PoE consumption in mW"><br>Power</th>
<th><img src=img/32/fiap.png title="C=CDP,M=Mac,O=Oui,V=VoIP,L=LLDP,S=static"><br>Type</th>
<th><img src=img/32/idea.png><br>Action</th></tr>
</tr>
<?
	if ($dv){
		$query	= GenQuery('links','s','*','ifname','',array('device'),array('='),array($dv));
	}else{
		$query	= GenQuery('links','s','*','ifname','',array('type'),array('='),array($typ));
	}
	$res	= @DbQuery($query,$link);
	if($res){
		$nli = 0;
		$row = 0;
		while( ($l = @DbFetchRow($res)) ){
			if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
			$row++;
			echo "<tr bgcolor=#$bg><th>$l[1]</th><td>$l[2]</td><th>$l[3]</th><td>$l[4] (Vlan$l[9] $l[8])</td>\n";
			echo "<td align=right>" . Zfix($l[5]) . "</td><td align=right>$l[7]</td>";
			echo "<td align=center>$l[6]</td>\n";
			echo "<th><a href=?dli=$l[0]&dv=$l[1]><img src=img/16/bcnl.png border=0 hspace=8 onclick=\"return confirm('Delete link?');\" title=\"Delete link\"></a></th></tr>\n";
			$nli++;
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$nli links with $query</td></tr></table>\n";
}
include_once ("inc/footer.php");
?>