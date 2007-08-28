<?

/*
#============================================================================
# Program: Devices-Config.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 25/02/05	initial version.
# 04/03/05	revised backend
# 13/05/05	added line#
# 10/03/06	new SQL query support
# 29/01/07	suppres motd char, sorting
# 07/05/07	New GUI, diff feature
# 25/07/07	Improved comparison/optimisation
*/

$bg1	= "99AACC";
$bg2	= "AABBDD";

include_once ("inc/header.php");

$_GET = sanitize($_GET);
$cm = isset($_GET['cm']) ? $_GET['cm'] : "";
$ld = isset($_GET['ld']) ? $_GET['ld'] : "";
$dd = isset($_GET['dd']) ? $_GET['dd'] : "";
$gen = isset($_GET['gen']) ? $_GET['gen'] : "";
$shl = isset($_GET['shl']) ? $_GET['shl'] : "";
$shc = isset($_GET['shc']) ? $_GET['shc'] : "";
$sln = isset($_GET['sln']) ? $_GET['sln'] : "";
$smo = isset($_GET['smo']) ? $_GET['smo'] : "";
$dch = isset($_GET['dch']) ? $_GET['dch'] : "";
$dco = isset($_GET['dco']) ? $_GET['dco'] : "";
$str = isset($_GET['str']) ? $_GET['str'] : "";
$ord = isset($_GET['ord']) ? $_GET['ord'] : "";

$cols = array(	"device"=>"Device",
		"config"=>"Configuration",
		"changes"=>"Changes",
		"time"=>"Update",
		);

$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('configs','s','device,time','device');
$res	= @DbQuery($query,$link);
if($res){
	while( ($c = @DbFetchRow($res)) ){
		$cfgup[$c[0]] = $c[1];
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}
?>
<h1>Devices Config</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>" name="cfg">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/cfg2.png border=0 title="Compare by selecting a device on the left, and what to compare to on the right side">
</a></th>

<th valign=top><h3>List</h3>
<select size=1 name="shl">
<option value="n">config regexp
<option value="i" <?=($shl == "i")?"selected":""?>>config not regexp
<option value="c" <?=($shl == "c")?"selected":""?>>changes regexp
<option value="d" <?=($shl == "d")?"selected":""?>>device is
</select>

<input type="text" name="str" value="<? echo $str?>" size="30">

<select size=1 name="ld" onchange="document.cfg.str.value=document.cfg.ld.options[document.cfg.ld.selectedIndex].value">
<option value="">or select ->
<?
foreach (array_keys($cfgup) as $d){
	echo "<option value=\"$d\"". (($ld == $d)?" selected":"").">$d\n";
}
?>
</select>
</th>
<th valign=top><h3>Compare</h3>
to <select size=1 name="dd">
<option value="">Same type
<?
foreach (array_keys($cfgup) as $d){
	echo "<option value=\"$d\"". (($dd == $d)?" selected":"").">$d\n";
}
?>
</select>
<select size=1 name="cm" title="Verbose show access vlans and random numbers too">
<option value="">Mode ->
<option value="v">Verbose
<option value="i" <?=($cm == "i")?"selected":""?>>IOS optimized
<option value="p" <?=($cm == "p")?"selected":""?>>Procurve optimized
<option value="f" <?=($cm == "f")?"selected":""?>>Ironware optimized
</select>
</th>
<th width=80>
<input type="submit" value="Show" name="gen">
</th>
</table></form><p>
<?

if ($dch){
	if(preg_match("/adm/",$_SESSION['group']) ){
		$query	= GenQuery('configs','u','device',$dch,'',array('changes'),'',array('') );
		if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3> $dch changes $delokmsg</h3>";}
	}else{
		echo $nokmsg;
	}
	$shc = $dch;
}
if ($dco){
	if(preg_match("/adm/",$_SESSION['group']) ){
		$query	= GenQuery('configs','d','','','',array('device'),array('='),array($dco) );
		if( !@DbQuery($query,$link) ){echo "<h4>".DbError($link)."</h4>";}else{echo "<h3> $dco config $delokmsg</h3>";}
?><script language="JavaScript"><!--
setTimeout("history.go(-2)",2000);
//--></script><?		

	}else{
		echo $nokmsg;
	}
}

if ($gen){
	$query	= GenQuery('devices','s','name,ip,type,os,icon');
	$res	= @DbQuery($query,$link);
	if($res){
		while( ($d = @DbFetchRow($res)) ){
			$devip[$d[0]] = long2ip($d[1]);
			$devty[$d[0]] = $d[2];
			$devos[$d[0]] = $d[3];
			$devic[$d[0]] = $d[4];
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
	}

	if(($dd or $cm)){
		$query	= GenQuery('configs','s','*','','',array('device'),array('='),array($ld));
		$res	= @DbQuery($query,$link);
		$cfgok	= @DbNumRows($res);
		if ($cfgok == 1) {
			$rdvc = @DbFetchRow($res);
			@DbFreeResult($res);
		}else{
			echo "<h4>$shc $n1rmsg ($cfgok)</h4>";
			die;
		}
		echo "<h2>Comparison</h2>\n";
		if($dd){
			$cmpdev = array($ld,$dd);
		}else{
			foreach (array_keys($cfgup) as $d){
				if($devty[$ld] == $devty[$d]){
					$cmpdev[] = $d;
				}
			}
		}
		sort ($cmpdev);
		foreach ($cmpdev as $ddv){
			$ud	= rawurlencode($ddv);
?>
<table bgcolor=#000000 <?=$tabtag?>><tr bgcolor=#<?=$bg2?>>
<th><a href=Devices-Status.php?dev=<?=$ud?>><img src=img/dev/<?=$devic[$ddv]?>.png border=0></a><br><?=$ddv?>
<tr><td align=left valign=top  bgcolor=#<?=$bia?>>
<a href=ssh://<?=$devip[$ddv]?>><img src=img/16/lokc.png border=0 align=right  title="SSH"></a>
<a href=telnet://<?=$devip[$ddv]?>><img src=img/16/loko.png border=0 align=right title="Telnet"></a><?=$oi?>
<a href=?shc=<?=$ud?>><img src=img/16/cfg2.png align=right border=0 title="Show Config"></a></a><pre>
<?
			if($ld == $ddv){
				Shoconf($rdvc[1],0,1);
				echo "</pre></td></tr></table><p>";
			}else{
				$query	= GenQuery('configs','s','*','','',array('device'),array('='),array($ddv));
				$res	= @DbQuery($query,$link);
				$cfgok	= @DbNumRows($res);
				if ($cfgok == 1) {
					$ddvc = @DbFetchRow($res);
					@DbFreeResult($res);
					echo PHPDiff( Opdiff($rdvc[1],$cm), Opdiff($ddvc[1],$cm) );		
					echo "</pre></td></tr></table><p>";
				}else{
					echo "<h4>$ddv $n1rmsg ($cfgok)</h4>";
				}
			}
		}
	}else{
		$target	='config';
		$opa	= 'regexp';
		if($shl == 'i'){
			$opa	= 'not regexp';
		}elseif($shl == 'c'){
			$target	='changes';
		}elseif($shl == 'd'){
			$opa	= '=';
			$target	='device';
		}
		echo "<h2>List</h2><table bgcolor=#666666 $tabtag><tr bgcolor=#$bg2>\n";

		ColHead('device',80);
		ColHead('config');
		ColHead('changes');
		?><th>OS</th><?
		ColHead('time');

		$query	= GenQuery('configs','s','*',$ord,'',array($target),array($opa),array($str));

		$res	= @DbQuery($query,$link);
		if($res){
			$row = 0;
			while( ($con = @DbFetchRow($res)) ){
				if ($row % 2){$bg = $bgb; $bi = $bib;}else{$bg = $bga; $bi = $bia;}
				$row++;
				$img	= $devic[$con[0]];
				$typ	= $devty[$con[0]];
				$cfg	= substr(implode("\n",preg_grep("/$str/i",split("\n",$con[1]) ) ),0,80 ) . "...";
				$chg	= substr(implode("\n",preg_grep("/$str/i",split("\n",$con[2]) ) ),0,80 );
				$ud	= rawurlencode($con[0]);
				echo "<tr bgcolor=#$bg>\n";
				echo "<th bgcolor=#$bi><a href=Devices-Status.php?dev=$ud><img src=img/dev/$img.png title=\"$typ\" border=0></a><br>$con[0]\n";
				echo "<td><a href=$_SERVER[PHP_SELF]?shc=$ud><pre>$cfg</pre></a></td>\n";
				$cu	= date("j.M (G:i)",$con[3]);
				list($u1c,$u2c) = Agecol($con[3],$con[3],$row % 2);
				echo "<td><pre>$chg</pre></td><td>".$devos[$con[0]]."</td><td bgcolor=#$u1c>$cu</td>\n";
				echo "</td></tr>\n";
			}
			@DbFreeResult($res);
		}else{
			print @DbError($link);
		}
		echo "</table><table bgcolor=#666666 $tabtag>\n";
		echo "<tr bgcolor=#$bg2><td>$row results using $query</td></tr></table>\n";
	}

}elseif($shc){

	echo "<h2>$shc</h2>\n";

	$query	= GenQuery('configs','s','*','','',array('device'),array('='),array($shc));
	$res	= @DbQuery($query,$link);
	$cfgok	= @DbNumRows($res);
	if ($cfgok == 1) {
		$cfg = @DbFetchRow($res);
		@DbFreeResult($res);
	}else{
		echo "<h4>$shc $n1rmsg ($cfgok)</h4>";
		die;
	}
	$ucfg	= rawurlencode($cfg[0]);

#	$fd =  @fopen("log/$cfg[0].conf","w");
#	fwrite($fd,$cfg[1]);
#	fclose($fd);
	$charr	= split("\n",$cfg[2]);
	$charr	= preg_replace("/^#(.*)$/","<font style='color: grey'>#$1</font>",$charr);
	$charr	= preg_replace("/(^\s*[0-9]{1,3}\-.*)$/","<font style='color: indianred'>$1</font>",$charr);
	$charr	= preg_replace("/(^\s*[0-9]{1,3}\+.*)$/","<font style='color: seagreen'>$1</font>",$charr);
	$changs	= implode("\n",$charr);
?>
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg2?> >
<th><img src=img/32/dtxt.png><br>Configuration (from <?=date("j. M Y",$cfg[3])?>)</th><th><img src=img/32/cfg.png><br>Changes</th></tr>
<tr><td align=left valign=top  bgcolor=#<?=$bia?> >
<?
if(preg_match("/adm/",$_SESSION['group']) )
	echo "<a href=$_SERVER[PHP_SELF]?dco=$ucfg><img src=img/16/bcnl.png align=right border=0 onclick=\"return confirm('Delete $cfg[0] config?')\" title=\"Delete config!\"></a>\n";
#if ( file_exists("log/$ucfg.cfg") )
#	echo "<a href=\"log/$ucfg.conf\"><img src=img/16/flop.png align=right border=0 title=\"Save link as...\"></a>\n";
echo "<a href=$_SERVER[PHP_SELF]?shc=$ucfg&sln=" . (!$sln) . "><img src=img/16/form.png align=right border=0 title=\"Toggle Line#\"></a>\n";
echo "<a href=$_SERVER[PHP_SELF]?shc=$ucfg&smo=" . (!$smo) . "><img src=img/16/say.png align=right border=0 title=\"Suppress motd character\"></a><ul><pre>\n";
Shoconf($cfg[1],$smo,$sln);
echo "</pre></ul></td><td align=left valign=top  bgcolor=#$bib >";
if(preg_match("/adm/",$_SESSION['group']) )
	echo "<a href=$_SERVER[PHP_SELF]?dch=$ucfg><img src=img/16/bcnl.png align=right border=0 onclick=\"return confirm('Clear changes for $cfg[0]?')\" title=\"Clear changes!\"></a>\n";
echo "<p><pre>$changs";
echo "</pre></td></tr></table>";
}

include_once ("inc/footer.php");

// Optimize configuration before comparison
function Opdiff($cfg,$mo){
	$config = "";
	foreach ( split("\n",$cfg) as $l ){
		if($mo == 'i' and preg_match("/secret 5|hostname|password 7|key 7|access vlan|clock-period|engineID|Current\s|change|updated/",$l) ){
			$config .= "\n";
		}elseif($mo == 'p' and preg_match("/untagged /",$l) ){
			$config .= "\n";
		}elseif($mo == 'f' and preg_match("/untagged /",$l) ){
			$config .= "\n";
		}else{
			$config .= "$l\n";
		}
	}
	return $config;
}

// Show a single configuration
function Shoconf($cfg,$smo,$sln){
	$lnr = 0;
	$config = "";
	foreach ( split("\n",$cfg) as $l ){
		$lnr++;
		if($smo)
			$l = preg_replace("/(\^)([\w])$/","$1",$l);
		if( preg_match("/^([!#])(.*)$/",$l) )
			$l = "<font style='color: grey'>$l</font>";
		elseif( preg_match("/^service|snmp|logging|system location|system contact|ntp|^clock/",$l) )
			$l = "<font style='color: blue'>$l</font>";
		elseif( preg_match("/^\s*no|shutdown|access-list|access-class| permit/",$l) )
			$l = "<font style='color: darkred'>$l</font>";
		elseif( preg_match("/^hostname|description|system name/",$l) )
			$l = "<font style='color: dimgrey'>$l</font>";
		elseif( preg_match("/^interface|^line|set port/",$l) )
			$l = "<font style='color: orange'>$l</font>";
		elseif( preg_match("/^ ip/",$l) )
			$l = "<font style='color: maroon'>$l</font>";
		elseif( preg_match("/^ standby.*|trunk|channel/",$l) )
			$l = "<font style='color: sienna'>$l</font>";
		elseif( preg_match("/username|password|^enable|set password|set enablepass/",$l) )
			$l = "<font style='color: red'>$l</font>";
		elseif( preg_match("/^aaa|.*radius|.*authentication/",$l) )
			$l = "<font style='color: steelblue'>$l</font>";
		elseif( preg_match("/ip route|^ passive-interface|default-gateway/",$l) )
			$l = "<font style='color: olive'>$l</font>";
		elseif( preg_match("/^router|^ network|vlan/",$l) )
			$l = "<font style='color: green'>$l</font>";
		if($sln)
			$config .= sprintf("<i>%3d</i>",$lnr) . " $l\n";
		else
			$config .= "$l\n";
	}
	echo $config;
}
    /**
        Diff implemented in pure php, written from scratch.
        Copyright (C) 2003  Daniel Unterberger <diff.phpnet@holomind.de>
        Copyright (C) 2005  Nils Knappmeier next version 
        
        This program is free software; you can redistribute it and/or
        modify it under the terms of the GNU General Public License
        as published by the Free Software Foundation; either version 2
        of the License, or (at your option) any later version.
        
        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.
        
        You should have received a copy of the GNU General Public License
        along with this program; if not, write to the Free Software
        Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
        
        http://www.gnu.org/licenses/gpl.html

        About:
        I searched a function to compare arrays and the array_diff()
        was not specific enough. It ignores the order of the array-values.
        So I reimplemented the diff-function which is found on unix-systems
        but this you can use directly in your code and adopt for your needs.
        Simply adopt the formatline-function. with the third-parameter of arr_diff()
        you can hide matching lines. Hope someone has use for this.

        Contact: d.u.diff@holomind.de <daniel unterberger>
    **/

    
## PHPDiff returns the differences between $old and $new, formatted
## in the standard diff(1) output format.
function PHPDiff($old,$new) 
{
   # split the source text into arrays of lines
   $t1 = explode("\n",$old);
   $x=array_pop($t1); 
   if ($x>'') $t1[]="$x\n\\ No newline at end of file";
   $t2 = explode("\n",$new);
   $x=array_pop($t2); 
   if ($x>'') $t2[]="$x\n\\ No newline at end of file";

   # build a reverse-index array using the line as key and line number as value
   # don't store blank lines, so they won't be targets of the shortest distance
   # search
   foreach($t1 as $i=>$x) if ($x>'') $r1[$x][]=$i;
   foreach($t2 as $i=>$x) if ($x>'') $r2[$x][]=$i;

   $a1=0; $a2=0;   # start at beginning of each list
   $actions=array();

   # walk this loop until we reach the end of one of the lists
   while ($a1<count($t1) && $a2<count($t2)) {
     # if we have a common element, save it and go to the next
     if ($t1[$a1]==$t2[$a2]) { $actions[]=4; $a1++; $a2++; continue; } 

     # otherwise, find the shortest move (Manhattan-distance) from the
     # current location
     $best1=count($t1); $best2=count($t2);
     $s1=$a1; $s2=$a2;
     while(($s1+$s2-$a1-$a2) < ($best1+$best2-$a1-$a2)) {
       $d=-1;
       foreach((array)@$r1[$t2[$s2]] as $n) 
         if ($n>=$s1) { $d=$n; break; }
       if ($d>=$s1 && ($d+$s2-$a1-$a2)<($best1+$best2-$a1-$a2))
         { $best1=$d; $best2=$s2; }
       $d=-1;
       foreach((array)@$r2[$t1[$s1]] as $n) 
         if ($n>=$s2) { $d=$n; break; }
       if ($d>=$s2 && ($s1+$d-$a1-$a2)<($best1+$best2-$a1-$a2))
         { $best1=$s1; $best2=$d; }
       $s1++; $s2++;
     }
     while ($a1<$best1) { $actions[]=1; $a1++; }  # deleted elements
     while ($a2<$best2) { $actions[]=2; $a2++; }  # added elements
  }

  # we've reached the end of one list, now walk to the end of the other
  while($a1<count($t1)) { $actions[]=1; $a1++; }  # deleted elements
  while($a2<count($t2)) { $actions[]=2; $a2++; }  # added elements

  # and this marks our ending point
  $actions[]=8;

  # now, let's follow the path we just took and report the added/deleted
  # elements into $out.
  $op = 0;
  $x0=$x1=0; $y0=$y1=0;
  $out = array();
  foreach($actions as $act) {
    if ($act==1) { $op|=$act; $x1++; continue; }
    if ($act==2) { $op|=$act; $y1++; continue; }
    if ($op>0) {
      $xstr = ($x1==($x0+1)) ? $x1 : ($x0+1).",$x1";
      $ystr = ($y1==($y0+1)) ? $y1 : ($y0+1).",$y1";
      if ($op==1) $out[] = "{$xstr}d{$y1}";
      elseif ($op==3) $out[] = "{$xstr}c{$ystr}";
      while ($x0<$x1) { $out[] = '<font style=\'color: seagreen\'>< '.$t1[$x0].'</font>'; $x0++; }   # deleted elems
      if ($op==2) $out[] = "{$x1}a{$ystr}";
      elseif ($op==3) $out[] = '<font style=\'color: grey\'>---</font>';
      while ($y0<$y1) { $out[] = '<font style=\'color: indianred\'>> '.$t2[$y0].'</font>'; $y0++; }   # added elems
    }
    $x1++; $x0=$x1;
    $y1++; $y0=$y1;
    $op=0;
  }
  $out[] = '';
  return join("\n",$out);
}