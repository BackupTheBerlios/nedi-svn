<?

/*
#============================================================================
# Program: Monitoring-Setup.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 05/06/05	initial version.
# 01/03/06	bulk add with auto dependencies
# 17/03/06	new SQL query support
*/

error_reporting(E_ALL ^ E_NOTICE);

$bg1	= "AA9999";
$bg2	= "BBAAAA";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");
include_once ('inc/libdev.php');
include_once ('inc/libmon.php');

$_GET = sanitize($_GET);
$loc = isset($_GET['loc']) ? $_GET['loc'] : "";
$crm = isset($_GET['crm']) ? $_GET['crm'] : "";
$cad = isset($_GET['cad']) ? $_GET['cad'] : "";
$nrm = isset($_GET['nrm']) ? $_GET['nrm'] : "";
$nad = isset($_GET['nad']) ? $_GET['nad'] : "";
$dev = isset($_GET['dev']) ? $_GET['dev'] : "";
$dep = isset($_GET['dep']) ? $_GET['dep'] : "";
$dpo = isset($_GET['dpo']) ? $_GET['dpo'] : "";
$cal = isset($_GET['cal']) ? $_GET['cal'] : "";
$ars = isset($_GET['ars']) ? $_GET['ars'] : "";

$cpos = strpos($locformat, "c");
$bpos = strpos($locformat, "b");
$fpos = strpos($locformat, "f");
$rpos = strpos($locformat, "r");
$kpos = strpos($locformat, "k");

$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('devices');
$res	= @DbQuery($query,$link);
if($res){
	while( ($d = @DbFetchRow($res)) ){
		$devs[] = $d[0];
		$locitems = explode($locsep, $d[10]);
		if(!($cpos === false) ){$copt[$locitems[$cpos]]++;}
		if(!($bpos === false) ){$bopt[$locitems[$bpos]]++;}
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}

?>
<h1>Monitoring Setup</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>" name="lflt">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/sys.png border=0 title="Set devices to be monitored and their dependencies">
</a></th>
<th>
Location Filter
<input type="text" name="loc" value="<? echo $loc?>" size="20">
<select size=1 name="sl" onchange="document.lflt.loc.value=document.lflt.sl.options[document.lflt.sl.selectedIndex].value">
<option value="">or select
<?
if($copt){
	echo '<option value="">------------';
	ksort($copt);
	while( list($cty,$nd)=each($copt) ){
		$ucty = str_replace(" ","%20",$cty);
		echo "<option value=$ucty ";
		if($loc == $cty){echo "selected";}
		echo ">$cty ($nd)\n";
	}
}
if($bopt){
	echo '<option value="">------------';
	ksort($bopt);
	while( list($bld,$nd)=each($bopt) ){
		$ubld = str_replace(" ","%20",$bld);
		echo "<option value=$ubld ";
		if($loc == $bld){echo "selected";}
		echo ">$bld ($nd)\n";
	}
}
?>
</SELECT>
</th>
<th>
<input type="checkbox" name="ars"> auto resolve &nbsp;&nbsp;
<input type="submit" value="Monitor" name="cal" onclick="return confirm('Monitor matching devices?')" >
</th>
<th width=80>
<input type="submit" value="Show" name="show">
</th>
</tr></table></form><p>
<?
if($crm){
	$query	= GenQuery('monitoring','d','','','',array('device'),array('='),array($crm) );
	if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Monitoring $crm $delokmsg</h3>";}
}elseif($cad){
	$query	= GenQuery('monitoring','i','','','',array('device','status','depend','sms','mail','lastchk','uptime','lost','ok'),'',array($cad,'0','none','0','0','0','0','0','0') );
	if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3>Monitoring $cad $upokmsg</h3>";}
}elseif($nrm){
	$query	= GenQuery('monitoring','u','device',$dev,'',array($nrm),'',array('0') );
	if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3> $dev $nrm $delokmsg</h3>";}
}elseif($nad){
	$query	= GenQuery('monitoring','u','device',$dev,'',array($nad),'',array('1') );
	if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3> $dev $nad $upokmsg</h3>";}
}elseif($dep or $dpo){
	if(!$_GET['dep']){$dep = $dpo;}
	if (in_array($dep, $devs) or $dep == 'none') {
		$query	= GenQuery('monitoring','u','device',$dev,'',array('depend'),'',array($dep) );
		if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3> $dev dependency $upokmsg</h3>";}
	}else{
		echo $resmsg;
	}
}
if($loc){
?>
<table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bg2?> >
<th width=80><img src=img/32/dev.png><br>Device</th>
<th width=40%><img src=img/32/glob.png><br>Location</th>
<th width=40%><img src=img/32/find.png><br>Info</th>
<th><img src=img/32/clock.png><br>Check Uptime</th>
<th><img src=img/32/neti.png><br>Depends On</th>
<th><img src=img/32/say.png><br>Notification</th></tr>

<?
	$query	= GenQuery('devices','s','*','','',array('location'),array('regexp'),array($loc) );
	$res	= @DbQuery($query,$link);
	if($res){
		while( ($d = @DbFetchRow($res)) ){
			$dico[$d['0']] = $d['18'];
			$dsnm[$d['0']] = $d['14'];
			$dloc[$d['0']] = $d['10'];
			$mdev[$d['0']] = 0;
			$msta[$d['0']] = 0;
			$mdep[$d['0']] = 0;
			$msms[$d['0']] = 0;
			$mmal[$d['0']] = 0;
			$minfo[$d['0']] = 0;
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
	}
	$query	= GenQuery('monitoring');
	$res	= @DbQuery($query,$link);
	if($res){
		while( ($m = @DbFetchRow($res)) ){
			$mdev[$m['0']] = 1;
			$msta[$m['0']] = $m['1'];
			$mdep[$m['0']] = $m['2'];
			$msms[$m['0']] = $m['3'];
			$mmal[$m['0']] = $m['4'];
			if ($m['5']){$minfo[$m['0']] = "<b>$m[7]</b> lost of <b>$m[8]</b> Last check: <b>". date("D G:i",$m[5]) . "</b>";}
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
	}

	$hs="16";
	$uloc = str_replace(" ","%20",$loc);

	$ndev = 0;
	$row = 0;
	foreach ($dico as $na => $ico){
		if ($row == "1"){ $row = "0"; $bg = $bga;}
		else{ $row = "1"; $bg = $bgb;}
		$ud = rawurlencode($na);

		$query	= GenQuery('links','s','*','','',array('device'),array('='),array($na) );
		$res	= @DbQuery($query,$link);
		$neb	= array();
		if($res){
			while( ($l = @DbFetchRow($res)) ){
				$neb[$l[3]] = $l[4];
			}
			@DbFreeResult($res);
		}else{
			print @DbError($link);
		}
		if($cal and ! $mdev[$na] and $dsnm[$na]){
			$adep = 'none';
			if(count(array_keys($neb) ) == 1 and $ars){
				$adep = key($neb);
			}
			$mdep[$na] = $adep;
			$query	= GenQuery('monitoring','i','','','',array('device','status','depend','sms','mail','lastchk','uptime','lost','ok'),'',array($na,'0',$adep,'0','0','0','0','0','0') );
			if( !@DbQuery($query,$link) ){echo "<h4 align=center>".DbError($link)."</h4>";}else{echo "<h3>$na $upokmsg</h3>";$mdev[$na]=1;}
		}
		list($bgm,$stat) = GetStatus(1,$mdev[$na],$msta[$na]);
		
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bgm><a href=Devices-Status.php?dev=$ud><img src=img/dev/$ico.png title=\"$stat\" border=0></a><p>\n";
		echo "<a href=Nodes-List.php?ina=device&opa==&sta=$ud&ord=ifname><b>$na</b></a></th><td>$dloc[$na]</td><td>$minfo[$na]</td>";

		echo "<th>";
		if($mdev[$na]){
			echo "<a href=$_SERVER[PHP_SELF]?loc=$uloc&crm=$ud><img hspace=$hs src=img/16/bchk.png border=0 title=-Check></a>";
		}elseif($dsnm["$na"]){
			echo "<a href=$_SERVER[PHP_SELF]?loc=$uloc&cad=$ud><img hspace=$hs src=img/16/bcls.png border=0  title=+Check></a>";
		}else{
			echo "<img hspace=$hs src=img/16/bstp.png border=0  title=\"no SNMP available!\"></a>";
		}
		echo "</th>\n";
?>
<td nowrap>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" name="dev" value="<?=$na?>">
<input type="hidden" name="loc" value="<?=$loc?>">
<input type="text" name="dpo" size=12 value="<?=$mdep[$na]?>" onchange="this.form.submit();" title="type in device name to override link based dependency">
<SELECT size=1 name="dep" onchange="this.form.submit();" title="set link based dependency">
<OPTION VALUE="">Select
<OPTION VALUE="none">None
<?
		if($neb){
			foreach ($neb as $nen => $nif){
				echo "<OPTION VALUE=\"$nen\">$nen ($nif)\n";
			}
		}
		echo "</SELECT></form></td>\n";
		echo "<th>";
		if($msms[$na]){echo "<a href=$_SERVER[PHP_SELF]?loc=$uloc&dev=$ud&nrm=sms><img hspace=$hs src=img/16/mobil.png border=0 title=-SMS></a>";}
		else{echo "<a href=$_SERVER[PHP_SELF]?loc=$uloc&dev=$ud&nad=sms><img hspace=$hs src=img/16/bcls.png border=0  title=+SMS></a>";}
		if($mmal[$na]){echo "<a href=$_SERVER[PHP_SELF]?loc=$uloc&dev=$ud&nrm=mail><img hspace=$hs src=img/16/mail.png border=0 title=-Mail></a>";}
		else{echo "<a href=$_SERVER[PHP_SELF]?loc=$uloc&dev=$ud&nad=mail><img hspace=$hs src=img/16/bcls.png border=0  title=+Mail></a>";}
		echo "</th></tr>\n";
		$ndev++;
	}
	echo "</table><table bgcolor=#666666 $tabtag >\n";
	echo "<tr bgcolor=#$bg2><td>$ndev devices available for monitoring</td></tr></table>\n";
}
include_once ("inc/footer.php");
?>
