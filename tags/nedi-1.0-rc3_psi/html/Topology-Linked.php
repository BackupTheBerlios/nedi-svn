<?
/*
#============================================================================
# Program: Topology-Linked.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 02/11/06	initial version.
# 09/01/07	Minor improvements.
# 25/05/07	More Minor improvements :-)
*/

$bg1	= "aa8866";
$bg2	= "bb9977";

include_once ("inc/header.php");

$_GET = sanitize($_GET);
$dv = isset($_GET['dv']) ? $_GET['dv'] : "";
$if = isset($_GET['if']) ? $_GET['if'] : "";
$nb = isset($_GET['nb']) ? $_GET['nb'] : "";
$ni = isset($_GET['ni']) ? $_GET['ni'] : "";
$ddu = isset($_GET['ddu']) ? $_GET['ddu'] : "";
$dvl = isset($_GET['dvl']) ? $_GET['dvl'] : "";
$ndu = isset($_GET['ndu']) ? $_GET['ndu'] : "";
$nvl = isset($_GET['nvl']) ? $_GET['nvl'] : "";
$dbw = isset($_GET['dbw']) ? $_GET['dbw'] : "";
$nbw = isset($_GET['nbw']) ? $_GET['nbw'] : "";
$typ = isset($_GET['typ']) ? $_GET['typ'] : "";

$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
if ( isset($_GET['add']) and $dv and $if and $nb and $ni){
	$query	= GenQuery('links','i','','','',array('device','ifname','neighbour','nbrifname','bandwidth','type','power','nbrduplex','nbrvlanid'),'',array($dv,$if,$nb,$ni,$dbw,'S',0,$ndu,$nvl) );
	if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Link $dv - $nb $upokmsg</h3>";}
	$query	= GenQuery('links','i','','','',array('device','ifname','neighbour','nbrifname','bandwidth','type','power','nbrduplex','nbrvlanid'),'',array($nb,$ni,$dv,$if,$nbw,'S',0,$ddu,$dvl) );
	if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Link $nb - $dv $upokmsg</h3>";}
}elseif($_GET['del']){
	$query	= GenQuery('links','d','','','',array('id'),array('='),array($_GET['del']) );
	if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Link $_GET[del] $delokmsg</h3>";}
}
?>
<h1>Link Editor</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>" name="li">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>><img src=img/32/wglb.png border=0 title="Edit links manually then use -L for discovery"></a></th>
<th><h3>Device</h3>
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
	$query	= GenQuery('interfaces','s','*','ifname','',array('device'),array('='),array($dv) );
	$res	= @DbQuery($query,$link);
	if($res){
?>
<select size=6 name="if" onchange="this.form.submit();">
<?
		while( ($i = @DbFetchRow($res)) ){
			echo "<OPTION VALUE=\"$i[1]\" ";
			if($if == $i[1]){
				echo "selected";
				$dbw=$i[9];
				$ddu=$i[10];
				$dvl=$i[11];
			}
			echo " >$i[1]  " . substr("$i[7] $i[20]\n",0,30);
		}
		@DbFreeResult($res);
		echo "</select>";
	}
}
if ($if) {
?>
<hr>
Duplex/Vlan
<input type="text" name="ddu" size=4 value="<?=$ddu?>">
<input type="text" name="dvl" size=4 value="<?=$dvl?>">
<select size=1 name="dbs" onchange="document.li.dbw.value=document.li.dbs.options[document.li.dbs.selectedIndex].value">
<option value="">Bandwidth
<option value="1544000">T1
<option value="2048000">E1
<option value="10000000">10M
<option value="100000000">100M
<option value="1000000000">1G
<option value="10000000000">10G
</select>
<input type="text" name="dbw" size=12 value="<?=$dbw?>">
</th>
<?
}
?>
<th><h3>Neighbour</h3>
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
	$query	= GenQuery('interfaces','s','*','ifname','',array('device'),array('='),array($nb) );
	$res	= @DbQuery($query,$link);
	if($res){
?>
<select size=6 name="ni" onchange="this.form.submit();">
<?
		while( ($i = @DbFetchRow($res)) ){
			echo "<OPTION VALUE=\"$i[1]\" ";
			if($ni == $i[1]){
				echo "selected";
				$nbw=$i[9];
				$ndu=$i[10];
				$nvl=$i[11];
			}
			echo " >$i[1]  " . substr("$i[7] $i[20]\n",0,30);
		}
		@DbFreeResult($res);
		echo "</select>";
	}
}
if ($ni) {
?>
<hr>
Duplex/Vlan
<input type="text" name="ndu" size=4 value="<?=$ndu?>">
<input type="text" name="nvl" size=4 value="<?=$nvl?>">
<select size=1 name="nbs" onchange="document.li.nbw.value=document.li.nbs.options[document.li.nbs.selectedIndex].value">
<option value="">Bandwidth
<option value="1544000">T1
<option value="2048000">E1
<option value="10000000">10M
<option value="100000000">100M
<option value="1000000000">1G
<option value="10000000000">10G
</select>
<input type="text" name="nbw" size=12 value="<?=$dbw?>">
<?
}
?>
</th>
<th width=80>
<select size=1 name="typ" onchange="this.form.submit();">
<option value="">Show
<option value="S">Static
<option value="C">CDP
<option value="L">LLDP
<option value="O">OUI
<option value="M">MAC
<option value="P">POP
</select>
<p>
<input type="submit" name="add" value="Add">
</th>
</tr></table></form><p>
<?
if ($dv or $typ){
?>
<h2><?=($typ)?$typ:$dv?> - Links</h2>
<table bgcolor=#666666 <?=$tabtag?> ><tr bgcolor=#<?=$bg2?>>
<th><img src=img/32/dev.png><br>Device</th>
<th><img src=img/32/dumy.png><br>Interface</th>
<th><img src=img/32/fiap.png title="C=CDP,M=Mac,O=Oui,V=VoIP,L=LLDP,S=static"><br>Type</th>
<th><img src=img/32/tap.png><br>Bandwidth</th>
<th><img src=img/32/powr.png title="PoE consumption in mW"><br>Power</th>
<th><img src=img/32/dev.png><br>Neighbour</th>
<th><img src=img/32/dumy.png><br>Interface</th>
<th width=80><img src=img/32/idea.png><br>Action</th></tr>
</tr>
<?
	if ($typ){
		$query	= GenQuery('links','s','*','ifname','',array('type'),array('='),array($typ));
	}else{
		$query	= GenQuery('links','s','*','ifname','',array('device'),array('='),array($dv));
	}
	$res	= @DbQuery($query,$link);
	if($res){
		$nli = 0;
		$row = 0;
		while( ($l = @DbFetchRow($res)) ){
			$ud = rawurlencode($l[1]);
			$un = rawurlencode($l[3]);
			if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
			$row++;
			echo "<tr bgcolor=#$bg><td><a href=Devices-Status.php?dev=$ud>$l[1]</a></td><td>$l[2]</td>\n";
			echo "<th>$l[6]</th>\n";
			echo "<td align=right>" . Zfix($l[5]) . "</td>\n";
			echo "<td align=right>$l[7]</td>";
			echo "<td><a href=Devices-Status.php?dev=$un>$l[3]</a></td><td>$l[4] (Vlan$l[9] $l[8])</td>\n";
			echo "<th><a href=?del=$l[0]&dv=$l[1]><img src=img/16/bcnl.png border=0 hspace=8 onclick=\"return confirm('Delete link?');\" title=\"Delete link\"></a></th></tr>\n";
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