<?

/*
#============================================================================
# Program: Topology-Map.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 6/05/05		initial version.
# 10/03/06		new SQL query support
# 17/07/06		enhanced info and new network filter
# 21/02/07		refined layout and link weight computation, more hints and image map!
# 20/03/07		changes to mapping and GUI for RRD graohs...
# 10/05/07		Geographic, iconless & error map, rewrite for SVG output!
# 26/07/07		new location scheme, hierarchial maps
*/

error_reporting(E_ALL ^ E_NOTICE);

$bg1	= "5599BB";
$bg2	= "66AACC";
$nocache= 1;
$refresh= 3600;

$ndev      = array();
$bldlink   = array();
$ctylink   = array();
$devlink   = array();

$imgmap    = "";

include_once ("inc/header.php");
include_once ('inc/libdev.php');
include_once ('inc/libgraph.php');

$_GET = sanitize($_GET);
$fmt = isset($_GET['draw']) ? $_GET['draw'] : "";
$lev = isset($_GET['lev']) ? $_GET['lev'] : "";
$dep = isset($_GET['dep']) ? $_GET['dep'] : 8;
$geo = isset($_GET['geo']) ? "checked" : "";
$loi = isset($_GET['loi']) ? "checked" : "";
$ifi = isset($_GET['ifi']) ? "checked" : "";
$ipi = isset($_GET['ipi']) ? "checked" : "";
$lis = isset($_GET['lis']) ? $_GET['lis'] : "";
$tit = isset($_GET['tit']) ? $_GET['tit'] : "NeDi Network Map";
$flt = isset($_GET['flt']) ? $_GET['flt'] : ".";
$ina = isset($_GET['ina']) ? $_GET['ina'] : "loc";
$xo  = isset($_GET['xo']) ? $_GET['xo'] : 0;
$yo  = isset($_GET['yo']) ? $_GET['yo'] : 0;
$res = isset($_GET['res']) ? $_GET['res'] : "";

if    ($res == "vga") {$xm = "640"; $ym = "480";$len = 150;$bsi = 100;$fsi = 60;}
elseif($res == "svga"){$xm = "800"; $ym = "600";$len = 200;$bsi = 150;$fsi = 70;}
elseif($res == "xga") {$xm = "1024";$ym = "768";$len = 250;$bsi = 200;$fsi = 80;}
elseif($res == "sxga"){$xm = "1280";$ym = "1024";$len = 300;$bsi = 250;$fsi = 100;}
elseif($res == "uxga"){$xm = "1600";$ym = "1200";$len = 400;$bsi = 300;$fsi = 120;}
else{
	$xm  = isset($_GET['x']) ? $_GET['x'] : 800;
	$ym  = isset($_GET['y']) ? $_GET['y'] : 600;
	$len = ($_GET['len']) ? $_GET['len'] : intval($xm /5);
	$bsi = ($_GET['bsi']) ? $_GET['bsi'] : intval($xm /4);
	$fsi = isset($_GET['fsi']) ? $_GET['fsi'] : 80;
}
$fco = isset($_GET['fco']) ? $_GET['fco'] : 6;
$cwt = isset($_GET['cwt']) ? $_GET['cwt'] : 3;
$bwt = isset($_GET['bwt']) ? $_GET['bwt'] : 3;
$cro = isset($_GET['cro']) ? $_GET['cro'] : 0;
$bro = isset($_GET['bro']) ? $_GET['bro'] : 0;
$lwt = isset($_GET['lwt']) ? $_GET['lwt'] : 3;
?>
<h1>Topology Map</h1>

<form method="get" name="map" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<? echo $bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>><img src=img/32/paint.png border=0 title="Draws image of your network"></a></th>
<th valign=top title="Size & depth of image"><h3>Image</h3>
<table>
<tr><td>Size</td><td>
<select size=1 name="res">
<option value="">preset
<option value="vga">640x480
<option value="svga">800x600
<option value="xga">1024x768
<option value="sxga">1280x1024
<option value="uxga">1600x1200
</select>
</td></tr>
<tr><td>or XY</td><td><input type="text" name="x" value="<?=$xm?>" size=4> <input type="text" name="y" value="<?=$ym?>" size=4>
</td></tr>
<tr><td>Depth</td><td>
<input type="radio" name="dep" value="8" <?=($dep == 8)?"checked":""?>>8bit
<input type="radio" name="dep" value="24"<?=($dep == 24)?"checked":""?>>24bit
</td></tr>
</table>
</th>

<th valign=top><h3>General</h3>
<table>
<tr><td>Title</td><td><input type="text" name="tit" value="<?=$tit?>" size=20></td></tr>
<tr><td>Level</td><td><select size=1 name="lev" title="Select detail level">
<OPTION VALUE="c" <?=($lev == "c")?"selected":""?>>city
<OPTION VALUE="C" <?=($lev == "C")?"selected":""?>>city dots
<option value="">--------
<OPTION VALUE="b" <?=($lev == "b")?"selected":""?>>building
<OPTION VALUE="B" <?=($lev == "B")?"selected":""?>>bld dots
<option value="">--------
<OPTION VALUE="f" <?=($lev == "f")?"selected":""?>>device
</select>
<INPUT type="checkbox" name="geo" <?=$geo?> title="Geographic Map"> Geo
</td></tr>
<tr><td>Offset</td><td>
<input type="text" name="xo" value="<?=$xo?>" size=3 title="Moves map horizontally"> X 
<input type="text" name="yo" value="<?=$yo?>" size=3 title="Moves map vertically"> Y
</td></tr>
</table>
</th>

<th valign=top><h3>Layers</h3>
<table><tr>
<td><INPUT type="checkbox" name="ifi" <?=$ifi?> title="Interface"> IF</td>
<td><INPUT type="checkbox" name="ipi" <?=$ipi?> title="IP addresses"> IP</td></tr>
<tr>
<td><INPUT type="checkbox" name="loi" <?=$loi?> title="Location info"> Loc</td>
<td><input type="text" name="lwt" value="<?=$lwt?>" size=2 title="Weight for IF labels">W</td>
</tr>
<tr>
<td colspan=2>
<select size=1 name="lis">
<option value="">plain links
<option value="">--------
<option value="r" <?=($lis == "r")?"selected":""?>>traffic
<option value="e" <?=($lis == "e")?"selected":""?>>errors
<option value="w" <?=($lis == "w")?"selected":""?>>bandwidth
<option value="">--------
<option value="t" <?=($lis == "t")?"selected":""?>>tiny graph
<option value="s" <?=($lis == "s")?"selected":""?>>small graph
<option value="m" <?=($lis == "m")?"selected":""?>>med. graph
</select>
</td>
</tr>
</table>
</th>

<th valign=top title='Object placement properties'><h3>Layout</h3>
<table>
<tr><td>City</td><td>
<input type="text" name="len" value="<?=$len?>" size=3 title="Length of city links">L
<input type="text" name="cwt" value="<?=$cwt?>" size=2 title="Weight of cities based on # of links">W
<input type="text" name="cro" value="<?=$cro?>" size=3 title="Rotation of city circle">@
</td></tr>
<tr><td>Build</td><td>
<input type="text" name="bsi" value="<?=$bsi?>" size=3 title="Length of building links">L
<input type="text" name="bwt" value="<?=$bwt?>" size=2 title="Weight of buildings based on # of links">W
<input type="text" name="bro" value="<?=$bro?>" size=3 title="Rotation of building circle">@
</td></tr>
<tr><td>Floor</td><td>
<input type="text" name="fsi" value="<?=$fsi?>" size=3 title="Floor size">S
<input type="text" name="fco" value="<?=$fco?>" size=2 title="Floor columns">C
</td></tr>
</table>
</th>

<th valign=top title="location or vlan filter, with presets"><h3>Filter</h3>
<table >
<tr><td>
<select size=1 name="ina">
<option value="loc" <?=($ina == "loc")?"selected":""?>>Location
<option value="type" <?=($ina == "type")?"selected":""?>>Device Type
<option value="vlan" <?=($ina == "vlan")?"selected":""?>>Vlan
<option value="network" <?=($ina == "network")?"selected":""?>>Network
</select>
</td></tr>
<tr><td><input type="text" name="flt" value="<?=$flt?>" size=16></td></tr>
<tr><td><select size=1 name="cs" onchange="document.map.flt.value=document.map.cs.options[document.map.cs.selectedIndex].value">
<option value="">or select
<?
$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('devices','s','name,location');
$res	= @DbQuery($query,$link);
if($res){
	while( ($d = @DbFetchRow($res)) ){
		$devs[] = $d[0];
		$l = explode($locsep, $d[1]);
		$lopt[$l[0]][$l[1]]++;
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}
ksort($lopt);
foreach(array_keys($lopt) as $r){
?><option value="<?=TopoLoc($r)?>" style="color:red"><?=$r?>

<?
	ksort($lopt[$r]);
	foreach(array_keys($lopt[$r]) as $c){
?><option value="<?=TopoLoc($r,$c)?>"><?=$c?> (<?=$lopt[$r][$c]?>)
<?
	}
}
?>
</select></td></tr>
</table>
</th>
<th width=80 valign=top title="png is a common bitmap- svg a vector oriented format."><h3>Draw</h3>
<input type=submit name="draw" value="png"><p>
<input type=submit name="draw" value="svg">
</th></tr>
</tr></table><p>
<?
if($fmt == 'svg'){
	echo "<h3>SVG Map</h3>";
	Layout();
	WriteSVG($_SESSION['user'],count($dev) );
?>
        <center>
	<a href="log/map_<?=$_SESSION[user]?>.svg">Download SVG</a><p>
	<embed width="<?=$xm?>" height="<?=$ym?>" src="log/map_<?=$_SESSION[user]?>.svg" name="SVG Map" type="image/svg+xml">
	</center>
<?
}else{
	if($fmt){
		echo "<h5>Live PNG Map (clickable)</h5>";
		Layout();
		WritePNG($_SESSION['user'],count($dev) );
	}else{
		echo "<h4>Previous Map (not clickable, without graphs!)</h4>";
	}
	if (file_exists("log/map_$_SESSION[user].php")) {
	?>
		<center><img usemap=#net src="log/map_<?=$_SESSION[user]?>.php"></center>
		<map name=net>
		<?=$imgmap?>
		</map>
	<?
	}
}

include_once ("inc/footer.php");

#===================================================================
# Generate the php script for the image.

function WritePNG($usr,$nd) {

	global $xm,$ym,$geo,$dep,$lis,$trfa,$trfw,$tit,$ina,$flt;
	global $mapbg,$mapinfo,$mapframes,$maplinks,$mapitems;

	$maphdr   = array();
	$mapftr   = array();

	$now = date ("G:i:s j.M y",time());

       	$map  = "<?PHP\n";
	$map .= "# PNG Map for $nd devices created on $now by NeDi (visit http://www.nedi.ch for more info)\n";
	$map .= "header(\"Content-type: image/png\");\n";
	$map .= "error_reporting(0);\n";
	if($geo){
		$map .= "\$image = Imagecreatefrompng(\"$mapbg\");\n";
		$map .= "Imagealphablending(\$image,true);\n";
		$map .= "\$gainsboro  = Imagecolorallocatealpha(\$image, 230, 230, 230, 40);\n";
		$map .= "\$whitesmoke = Imagecolorallocatealpha(\$image, 245, 245, 245, 40);\n";
	}elseif ($dep == "24"){
		$map .= "\$image = imagecreatetruecolor($xm, $ym);\n";
		$map .= "Imagealphablending(\$image,true);\n";
		$map .= "\$gainsboro  = Imagecolorallocatealpha(\$image, 230, 230, 230, 40);\n";
		$map .= "\$whitesmoke = Imagecolorallocatealpha(\$image, 245, 245, 245, 40);\n";
		$map .= "\$white      = ImageColorAllocate(\$image, 255, 255, 255);\n";
		$map .= "ImageFilledRectangle(\$image, 0, 0, $xm, $ym, \$white);\n";
	}else{
		$map .= "\$image = imagecreate($xm, $ym);\n";
		$map .= "\$gainsboro  = ImageColorAllocate(\$image, 230, 230, 230);\n";
		$map .= "\$whitesmoke = ImageColorAllocate(\$image, 245, 245, 245);\n";
		$map .= "\$white      = ImageColorAllocate(\$image, 255, 255, 255);\n";
		$map .= "ImageFilledRectangle(\$image, 0, 0, $xm, $ym, \$white);\n";
	}
	$map .= "\$red       = ImageColorAllocate(\$image, 200, 0, 0);\n";
	$map .= "\$purple    = ImageColorAllocate(\$image, 200, 0, 200);\n";
	$map .= "\$orange    = ImageColorAllocate(\$image, 240, 180, 0);\n";
	$map .= "\$green     = ImageColorAllocate(\$image, 0, 130, 0);\n";
	$map .= "\$limegreen = ImageColorAllocate(\$image, 50, 200, 50);\n";
	$map .= "\$navy      = ImageColorAllocate(\$image, 0, 0, 130);\n";
	$map .= "\$blue      = ImageColorAllocate(\$image, 0, 0, 250);\n";
	$map .= "\$cornflowerblue      = ImageColorAllocate(\$image, 100, 150, 220);\n";
	$map .= "\$gray      = ImageColorAllocate(\$image, 100, 100, 100);\n";
	$map .= "\$black     = ImageColorAllocate(\$image, 0, 0, 0);\n";
	$map .= "ImageString(\$image, 5, 8, 8, \"$tit\", \$black);\n";
	$map .= "ImageString(\$image, 1, 8, 26, \"$nd devices for $ina ~ /$flt/\", \$gray);\n";
	$map .= "ImageString(\$image, 1, ".($xm - 120).",".($ym - 10).", \"NeDi $now\", \$gray);\n";
	
	if($lis == 'r'){
		$map .= "ImageString(\$image, 1, 8, 40, \"Inbound Traffic:\", \$black);\n";
		$map .= "Imagefilledrectangle (\$image, 8,50,14,56, \$red);\n";
		$map .= "ImageString(\$image, 1, 20, 50, \" > ". intval($trfa + (100 - $trfa)/2) ." %\", \$black);\n";
		$map .= "Imagefilledrectangle (\$image, 8,60,14,66, \$orange);\n";
		$map .= "ImageString(\$image, 1, 20, 60, \" > $trfa %\", \$black);\n";
		$map .= "Imagefilledrectangle (\$image, 8,70,14,76, \$purple);\n";
		$map .= "ImageString(\$image, 1, 20, 70, \" > ". intval($trfw + $trfw/2) ." %\", \$black);\n";
		$map .= "Imagefilledrectangle (\$image, 8,80,14,86, \$blue);\n";
		$map .= "ImageString(\$image, 1, 20, 80, \" > $trfw %\", \$black);\n";
		$map .= "Imagefilledrectangle (\$image, 8,90,14,96, \$limegreen);\n";
		$map .= "ImageString(\$image, 1, 20, 90, \" <=$trfw %\", \$black);\n";
		$map .= "Imagefilledrectangle (\$image, 8,100,14,106, \$gray);\n";
		$map .= "ImageString(\$image, 1, 20, 100, \" BW?\", \$black);\n";
	}elseif($lis == 'e'){
		$map .= "ImageString(\$image, 1, 8, 40, \"Inbound Errors:\", \$black);\n";
		$map .= "Imagefilledrectangle (\$image, 8,50,14,56, \$red);\n";
		$map .= "ImageString(\$image, 1, 20, 50, \" > 1 per sec\", \$black);\n";
		$map .= "Imagefilledrectangle (\$image, 8,60,14,66, \$orange);\n";
		$map .= "ImageString(\$image, 1, 20, 60, \" > 1 per min\", \$black);\n";
		$map .= "Imagefilledrectangle (\$image, 8,70,14,76, \$blue);\n";
		$map .= "ImageString(\$image, 1, 20, 70, \" > 1 per discovery\", \$black);\n";
		$map .= "Imagefilledrectangle (\$image, 8,80,14,86, \$limegreen);\n";
		$map .= "ImageString(\$image, 1, 20, 80, \" 0 or 1 per discovery\", \$black);\n";
	}
	
	$map .= $mapinfo . $mapframes . $maplinks . $mapitems;

	$map .= "Imagepng(\$image);\n";
	$map .= "Imagedestroy(\$image);\n";
	$map .= "?>\n";
	
	$fd =  @fopen("log/map_$usr.php","w") or die ("can't create log/map_$usr.php");
	fwrite($fd,$map);
	fclose($fd);
}

#===================================================================
# Generate the php script for the image.

function WriteSVG($usr,$nd) {

	global $xm,$ym,$geo,$dep,$lis,$trfa,$trfw,$tit,$ina,$flt,$mapinfo,$mapframes,$maplinks,$mapitems;

	$now = date ("G:i:s j.M y",time());

       	$map  = "<?xml version=\"1.0\" encoding=\"iso-8859-1\" standalone=\"no\"?>\n";
	$map .= "<!DOCTYPE svg PUBLIC \"-//W3C//DTD SVG 1.0//EN\" \"http://www.w3.org/TR/SVG/DTD/svg10.dtd\">\n";
	$map .= "<svg viewBox=\"0 0 $xm $ym\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\">\n";
	$map .= "<g id=\"main\" font-size=\"9\">\n";
	$map .= "<rect id=\"canvas\" width=\"$xm\" height=\"$ym\" x=\"0\" y=\"0\" stroke=\"black\" fill=\"white\" />\n";
	$map .= "<g id=\"title\">\n";
	$map .= "	<text x=\"8\" y=\"20\" font-size=\"16\" font-weight=\"bold\">$tit</text>\n";
	$map .= "	<text x=\"8\" y=\"32\" style=\"fill:gray;\">$nd devices for $ina ~ /$flt/</text>\n";
	$map .= "	<text x=\"".($xm - 120)."\" y=\"".($ym - 5)."\" style=\"fill:gray;\">NeDi $now</text>\n";
	$map .= "</g>\n";

	$map .= "<g id=\"info\">\n";
	$map .= $mapinfo;
	$map .= "</g>\n";

	$map .= "<g id=\"frames\">\n";
	$map .= $mapframes;
	$map .= "</g>\n";

	$map .= "<g id=\"links\">\n";
	$map .= $maplinks;
	$map .= "</g>\n";

	$map .= "<g id=\"items\">\n";
	$map .= $mapitems;
	$map .= "</g>\n";

	$map .= "</g></svg>\n";

	$fd =  @fopen("log/map_$usr.svg","w") or die ("can't create log/map_$usr.svg");
	fwrite($fd,$map);
	fclose($fd);
}
#===================================================================
# Draws a link.

function Drawlink($x1,$y1,$x2,$y2,$prop) {

	$ida = 0;
	$oda = 0;

	$ltxt = "";
	$itxt = "";

	$slabel  = array();
	$elabel = array();
	
        global $fmt,$link,$lev,$lis,$ifi,$ipi,$lwt,$lix,$liy,$net;
	global $trfw,$trfa,$rrdstep,$rrdpath,$rrdcmd;

        if($x1 == $x2){
                $lix[$x1]+= 2;
                $x1 += $lix[$x1];
                $x2 = $x1;
        }
        if($y1 == $y2){
                $liy[$y1]+= 2;
                $y1 += $liy[$y1];
                $y2 = $y1;
        }

	foreach(array_keys($prop['bw']) as $dv){
		foreach(array_keys($prop['bw'][$dv]) as $if){
			if( preg_match("/[tsm]/",$lis) ){
				$rrd = "$rrdpath/" . rawurlencode($dv) . "/" . rawurlencode($if) . ".rrd";
				if (file_exists($rrd)){
					$rrdif["$dv-$if"] = $rrd;
				}else{
					echo "RRD:$rrd not found!\n";
				}
			}elseif($lis == 'r'){
				$iquery	= GenQuery('interfaces','s','dinoct,doutoct','','',array('device','ifname'),array('=','='),array($dv,$if),array('AND') );
				$ires	= @DbQuery($iquery,$link);
				$nres	= @DbNumRows($ires);
				if ($nres == 1) {
					$trf  = @DbFetchRow($ires);
					$ida += $trf[0];
					$oda += $trf[1];
				}
				@DbFreeResult($ires);
			}
			foreach(array_keys($prop['bw'][$dv][$if]) as $ndv){
				foreach(array_keys($prop['bw'][$dv][$if][$ndv]) as $nif){
					if($lis == 'e'){
						$iquery	= GenQuery('interfaces','s','dinerr,douterr','','',array('device','ifname'),array('=','='),array($dv,$if),array('AND') );
						$ires	= @DbQuery($iquery,$link);
						$nres	= @DbNumRows($ires);
						if ($nres == 1) {
							$err  = @DbFetchRow($ires);
							$ida += $err[0];
							$oda += $err[1];
						}
						$iquery	= GenQuery('interfaces','s','dinerr,douterr','','',array('device','ifname'),array('=','='),array($ndv,$nif),array('AND') );
						$ires	= @DbQuery($iquery,$link);
						$nres	= @DbNumRows($ires);
						if ($nres == 1) {
							$err  = @DbFetchRow($ires);
							$oda += $err[0];
							$ida += $err[1];
						}
						@DbFreeResult($ires);
					}
					if($ipi){
						if($net[$dv][$if])  {$ia = $net[$dv][$if];}
						if($net[$ndv][$nif]){$nia= $net[$ndv][$nif];}
					}
					if($ifi){
						if($lev == "f"){
							$in = $if;
							$nin= $nif;
						}else{
							$in = "$dv $if";
							$nin= "$ndv $nif";
						}
					}
					if ($ifi or $ipi){
						array_push($slabel,"$in $ia");
						array_push($elabel,"$nin $nia");
					}
					$bw  += $prop['bw'][$dv][$if][$ndv][$nif];
					$nbw += $prop['nbw'][$dv][$if][$ndv][$nif];
				}
			}
		}
	}
	$xl   = intval($x1  + $x2) / 2;
	$yl   = intval($y1  + $y2) / 2;
	$xi1  = intval($x1+($x2-$x1)/(1 + $lwt/10));
	$xi2  = intval($x2+($x1-$x2)/(1 + $lwt/10));
	$yi1  = intval($y1+($y2-$y1)/(1 + $lwt/10));
	$yi2  = intval($y2+($y1-$y2)/(1 + $lwt/10));
	$bwtxt= ZFix($bw) . "/" . ZFix($nbw);

	if($lis == 'r'){
		if($bw and $nbw){
			$ri = round($ida*800/$bw/$rrdstep);
			$ro = round($oda*800/$nbw/$rrdstep);
			if ($ri > intval($trfa + (100 - $trfa)/2) ){
				$lico = "red";
			}elseif ($ri > $trfa){
				$lico = "orange";
			}elseif ($ri > intval($trfw + $trfw/2) ){
				$lico = "purple";
			}elseif ($ri > $trfw){
				$lico = "blue";
			}else{
				$lico = "limegreen";
			}
			if ($ro > intval($trfa + (100 - $trfa)/2) ){
				$loco = "red";
			}elseif ($ro > $trfa){
				$loco = "orange";
			}elseif ($ro > intval($trfw + $trfw/2) ){
				$loco = "purple";
			}elseif ($ro > $trfw){
				$loco = "blue";
			}else{
				$loco = "limegreen";
			}
		}else{
			$lico = "gray";
			$loco = "gray";
			$ri   = "-";
			$ro   = "-";
		}
		if($fmt == "svg"){
			$ltxt .= "	<line x1=\"$x1\" y1=\"$y1\" x2=\"$xl\" y2=\"$yl\" stroke=\"$lico\" stroke-width=\"4\"/>\n";
			$ltxt .= "	<line x1=\"$xl\" y1=\"$yl\" x2=\"$x2\" y2=\"$y2\" stroke=\"$loco\" stroke-width=\"4\"/>\n";
			$ltxt .= "	<text x=\"$xi2\" y=\"".($yi2-8)."\">${ri}%</text>\n";
			$ltxt .= "	<text x=\"$xi2\" y=\"".($yi1-8)."\">${ro}%</text>\n";
		}else{
			$ltxt .= "Imagesetthickness(\$image,4);\n";
			$ltxt .= "Imageline(\$image,$x1,$y1,$xl,$yl,\$$lico);\n";
			$ltxt .= "Imageline(\$image,$xl,$yl,$x2,$y2,\$$loco);\n";
			$ltxt .= "Imagesetthickness(\$image, 1);\n";
			$itxt .= "ImageString(\$image, 1,$xi2,".($yi2-8).",\"${ri}%\", \$black);\n";
			$itxt .= "ImageString(\$image, 1,$xi1,".($yi1-8).",\"${ro}%\", \$black);\n";
		}
	}elseif($lis == 'e'){
		if ($ida > $rrdstep ){
			$lico = "red";
		}elseif ($ida > intval($rrdstep/60) ){
			$lico = "orange";
		}elseif ($ida > 1){
			$lico = "blue";
		}else{
			$lico = "limegreen";
		}
		if ($oda > $rrdstep ){
			$loco = "red";
		}elseif ($oda > intval($rrdstep/60) ){
			$loco = "orange";
		}elseif ($oda > 1){
			$loco = "blue";
		}else{
			$loco = "limegreen";
		}
		if($fmt == "svg"){
			$ltxt .= "	<line x1=\"$x1\" y1=\"$y1\" x2=\"$xl\" y2=\"$yl\" stroke=\"$lico\" stroke-width=\"4\"/>\n";
			$ltxt .= "	<line x1=\"$xl\" y1=\"$yl\" x2=\"$x2\" y2=\"$y2\" stroke=\"$loco\" stroke-width=\"4\"/>\n";
			$ltxt .= "	<text x=\"$xi2\" y=\"".($yi2-8)."\">$ida</text>\n";
			$ltxt .= "	<text x=\"$xi2\" y=\"".($yi1-8)."\">$oda</text>\n";
		}else{
			$ltxt .= "Imagesetthickness(\$image,4);\n";
			$ltxt .= "Imageline(\$image,$x1,$y1,$xl,$yl,\$$lico);\n";
			$ltxt .= "Imageline(\$image,$xl,$yl,$x2,$y2,\$$loco);\n";
			$ltxt .= "Imagesetthickness(\$image, 1);\n";
			$itxt .= "ImageString(\$image, 1,$xi2,".($yi2-8).",\"$ida\", \$black);\n";
			$itxt .= "ImageString(\$image, 1,$xi1,".($yi1-8).",\"$oda\", \$black);\n";
		}
	}elseif($bw == 11000000 or $bw == 54000000){
		if($fmt == "svg"){
			$ltxt .= "	<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke=\"cornflowerblue\" stroke-dasharray=\"3,3\"/>\n";
		}else{
			$ltxt .= "imagesetstyle(\$image,array(\$navy,\$blue,\$cornflowerblue,\$wte,\$wte) );\n";
			$ltxt .= "Imageline(\$image,$x1,$y1,$x2,$y2,IMG_COLOR_STYLED);\n";
		}
	}elseif($bw < 10000000){
		if($fmt == "svg"){
			$ltxt .= "	<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke=\"limegreen\" />\n";
		}else{
			$ltxt .= "Imageline(\$image,$x1,$y1,$x2,$y2,\$limegreen);\n";
		}
	}elseif($bw < 100000000){
		if($fmt == "svg"){
			$ltxt .= "	<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke=\"blue\" />\n";
		}else{
			$ltxt .= "Imageline(\$image,$x1,$y1,$x2,$y2,\$blue);\n";
		}
	}elseif($bw < 1000000000){
		if($fmt == "svg"){
			$ltxt .= "	<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke=\"purple\" />\n";
		}else{
			$ltxt .= "Imageline(\$image,$x1,$y1,$x2,$y2,\$purple);\n";
		}
	}elseif($bw == 1000000000){
		if($fmt == "svg"){
			$ltxt .= "	<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke=\"orange\" />\n";
		}else{
			$ltxt .= "Imageline(\$image,$x1,$y1,$x2,$y2,\$orange);\n";
		}
	}else{
		$lt = ($bw/1000000000 < 10)?($bw/1000000000):10;
		if($fmt == "svg"){
			$ltxt .= "	<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke=\"red\" stroke-width=\"$lt\"/>\n";
		}else{
			$ltxt .= "imagesetthickness(\$image,$lt);\n";
			$ltxt .= "Imageline(\$image,$x1,$y1,$x2,$y2,\$red);\n";
			$ltxt .= "Imagesetthickness(\$image, 1);\n";
		}
	}

	if($lis	and is_array($rrdif) ){
		$opts = GraphOpts($lis,0,'Link Traffic',$bw);
		list($drawin,$drawout,$tit) = GraphTraffic($rrdif,'trf');
		exec("$rrdcmd graph log/$xl$yl.png -a PNG $opts $drawin $drawout");
		if($lis == "t"){
			if($fmt == "svg"){
				$itxt .= "	<text x=\"".($xl-16)."\" y=\"".($yl-18)."\" fill=\"green\">$bwtxt</text>\n";
			}else{
				$itxt .= "ImageString(\$image, 1,".($xl-16).", ".($yl-18).", \"$bwtxt\", \$green);\n";
			}
		}
		if($fmt == "svg"){
				$itxt .= "	<text x=\"".($xl-16)."\" y=\"".($yl-18)."\" fill=\"green\">no RRDs in SVG!</text>\n";
		}else{
			$ltxt .= "\$icon = Imagecreatefrompng(\"$xl$yl.png\");\n";
			$ltxt .= "\$w = Imagesx(\$icon);\n";
			$ltxt .= "\$h = Imagesy(\$icon);\n";
			$ltxt .= "Imagecopy(\$image, \$icon,$xl-\$w/2,$yl-\$h/2,0,0,\$w,\$h);\n";
			$ltxt .= "Imagedestroy(\$icon);\n";
			$ltxt .= "unlink(\"$xl$yl.png\");\n";
		}
	}elseif( preg_match("/[rw]/",$lis) ){
		if($fmt == "svg"){
			$itxt .= "	<text x=\"".($xl-16)."\" y=\"$yl\" fill=\"green\">$bwtxt</text>\n";
		}else{
			$itxt .= "ImageString(\$image, 1, ".($xl-16).",$yl, \"$bwtxt\", \$green);\n";
		}
	}
	$yof = 0;
	foreach ($slabel as $i){
		$i = preg_replace('/[$&]/','.', $i);
		if($fmt == "svg"){
			$itxt .= "	<text x=\"$xi2\" y=\"".($yi2+$yof)."\" fill=\"green\">$i</text>\n";
		}else{
			$itxt .= "ImageString(\$image, 1, $xi2, ".($yi2+$yof).", \"$i\", \$green);\n";
		}
		$yof += 8;
	}
	$yof = 0;
	foreach ($elabel as $i){
		if($fmt == "svg"){
			$itxt .= "	<text x=\"$xi1\" y=\"".($yi1+$yof)."\" fill=\"green\">".Safelabel($i)."</text>\n";
		}else{
			$itxt .= "ImageString(\$image, 1, $xi1, ".($yi1+$yof).", \"".Safelabel($i)."\", \$green);\n";
		}
		$yof += 8;
	}
	return array($ltxt,$itxt);
}
#===================================================================
# Draws box.

function Drawbox($x1,$y1,$x2,$y2,$label) {

	global $fmt;

	$box  = "";

	if($fmt == "svg"){
		$box .= "	<rect fill=\"whitesmoke\" x=\"$x1\" y=\"$y1\" width=\"".($x2-$x1)."\" height=\"".($y2-$y1)."\" fill-opacity=\"0.6\" />\n";
		$box .= "	<rect fill=\"gainsboro\" x=\"$x1\" y=\"".($y1+20)."\" width=\"20\" height=\"".($y2-$y1-20)."\" fill-opacity=\"0.6\" />\n";
		$box .= "	<rect fill=\"none\" stroke=\"black\" x=\"$x1\" y=\"$y1\" width=\"".($x2-$x1)."\" height=\"".($y2-$y1)."\"/>\n";
		$box .= "	<text x=\"".($x1+4)."\" y=\"".($y1+12)."\" fill=\"blue\">$label</text>\n";

	}else{
		$box .= "Imagefilledrectangle(\$image, $x1, $y1, $x2, $y2, \$whitesmoke);\n";
		$box .= "Imagefilledrectangle(\$image, $x1, ".($y1+20).", ".($x1+20).", $y2, \$gainsboro);\n";
		$box .= "Imagerectangle(\$image, $x1, $y1, $x2, $y2, \$black);\n";
		$box .= "ImageString(\$image, 3, ".($x1+4).", ".($y1+4).",\"$label\", \$blue);\n";
	}
	return $box;
}

#===================================================================
# Draws a city, building or device.

function Drawitem($x,$y,$opt,$label,$typ) {

	global $fmt,$dev,$loi,$ipi,$redbuild;

	$itxt  = "";
	$pfont = "1";
	$vfont = "font-size=\"9\"";
	$vicon = "32";
	if($opt > 2){$r = 8;}else{$r = 4;}

	if($typ == "C"){
		if($fmt == "svg"){
			$itxt .= "	<circle fill=\"cornflowerblue\" stroke=\"black\" cx=\"$x\" cy=\"$y\" r=\"$r\"/>\n";
			$itxt .= "	<text x=\"".($x-20)."\" y=\"".($y+20)."\" fill=\"navy\">$label</text>\n";

		}else{
			$itxt .= "Imagefilledellipse(\$image, $x, $y, ".(2*$r).", ".(2*$r).", \"\$cornflowerblue\");\n";
			$itxt .= "Imageellipse(\$image, $x, $y, ".(2*$r).", ".(2*$r).", \"\$black\");\n";
			$itxt .= "ImageString(\$image, 1, ".($x-10).", ".($y+10).", \"$label\", \"\$navy\");\n";
		}
		return $itxt;
	}elseif($typ == "B"){
		if( preg_match("/$redbuild/",$label) ){$bc = "red";}else{$bc = "cornflowerblue";}
		if($fmt == "svg"){
			$itxt .= "	<rect fill=\"$bc\" stroke=\"black\" x=\"".($x-$r)."\" y=\"".($y-$r)."\" width=\"".(2*$r)."\" height=\"".(2*$r)."\" />\n";
			$itxt .= "	<text x=\"".($x-20)."\" y=\"".($y+20)."\" fill=\"blue\">$label</text>\n";
		}else{
			$itxt .= "Imagefilledrectangle(\$image, ".($x-$r).", ".($y-$r).", ".($x+$r).", ".($y+$r).", \$$bc);\n";
			$itxt .= "Imagerectangle(\$image, ".($x-$r).", ".($y-$r).", ".($x+$r).", ".($y+$r).", \"\$black\");\n";
			$itxt .= "ImageString(\$image, 1, ".($x-10).", ".($y+10).", \"$label\", \"\$blue\");\n";
		}
		return $itxt;
	}elseif($typ == "ci"){
		$img = "cityg";
		$lcol = "gray";
	}elseif($typ == "c"){
		$img = CtyImg($opt);
		$lcol = "navy";
		$pfont = "5";
		$vfont = "font-size=\"15\" font-weight=\"bold\"";
		$vicon = "50";
	}elseif($typ == "b"){
		$img  = BldImg($opt,$label);
		$lcol = "blue";
	}elseif($typ == "fl"){
		$img = "stair";
		$lcol = "navy";
		$pfont = "3";
		$vfont = "font-size=\"12\" font-weight=\"bold\"";
		$vicon = "10";
	}elseif($typ == "d"){
		$img = "dev/" . $dev[$label]['ic'];
		$lcol = "black";
	}
	if($fmt == "svg"){
		$itxt .= "	<image x=\"".($x-$vicon/2)."\" y=\"".($y-$vicon/2)."\" width=\"$vicon\" height=\"$vicon\" xlink:href=\"../img/$img.png\"/>\n";
		if ($typ == "d"){
			if ($loi){$itxt .= "	<text x=\"".($x-$vicon/2)."\" y=\"".($y-$vicon/2-4)."\" $vfont fill=\"$lcol\">".$dev[$label]['rom']."</text>\n";}
			if ($ipi){$itxt .= "<text x=\"".($x-$vicon/2)."\" y=\"".($y+$vicon/2+20)."\" fill=\"gray\">".$dev[$label]['ip']."</text>\n";}
		}
		$itxt .= "	<text x=\"".($x-$vicon/2)."\" y=\"".($y+$vicon/2+10)."\" $vfont fill=\"$lcol\">".Safelabel($label)."</text>\n";

	}else{
		$itxt .= "\$icon = Imagecreatefrompng(\"../img/$img.png\");\n";
		$itxt .= "\$w = Imagesx(\$icon);\n";
		$itxt .= "\$h = Imagesy(\$icon);\n";
		$itxt .= "Imagecopy(\$image, \$icon,intval($x - \$w/2),intval($y - \$h/2),0,0,\$w,\$h);\n";
		if ($typ == "d"){
			if ($loi){$itxt .= "ImageString(\$image, $pfont, intval($x  - \$w/1.8), intval($y - \$h/1.6 - 10), \"".$dev[$label]['rom']."\", \$cornflowerblue);\n";}
			if ($ipi){$itxt .= "ImageString(\$image, $pfont, intval($x  - \$w/1.8), intval($y + \$h/1.6 + 10), \"".$dev[$label]['ip']."\", \$gray );\n";}
		}
		$itxt .= "ImageString(\$image, $pfont, intval($x  - \$w/1.8), intval($y + \$h/1.6), \"".Safelabel($label)."\", \$$lcol);\n";
		$itxt .= "Imagedestroy(\$icon);\n";
	}
	return $itxt;
}

#===================================================================
# Generate the map.
function Layout() {

	global $link,$locsep,$resmsg;
	global $xm,$ym,$xo,$yo,$len,$geo,$ina,$flt,$lev,$ipi;
	global $mapbg,$rloc,$cloc,$bloc;

	$net       = array();

# Gather Device Info
	if($ina == "vlan"){
		$query	= GenQuery('vlans','g','device','device','',array('vlanid'),array('regexp'),array($flt));
		$res	= @DbQuery($query,$link);
		if($res){
			while( ($vl = @DbFetchRow($res)) ){
				$devs[] = preg_replace('/([\^\$+])/','\\\\\\\\$1',$vl[0]);		# \Q...\E Doesn't seem to work in MySQL?
			}
			@DbFreeResult($res);
		}else{
			echo @DbError($link);
		}
		if (! is_array ($devs) ){echo $resmsg;die;}
		$query	= GenQuery('devices','s','name,ip,location,icon','','',array('name'),array('regexp'),array(implode("|",$devs)));
	}elseif($ina == "network"){
		$query	= GenQuery('networks','g','device','device','',array('ip'),array('='),array($flt));
		$res	= @DbQuery($query,$link);
		if($res){
			while( ($net = @DbFetchRow($res)) ){
				$devs[] = preg_replace('/([\^\$\*\+])/','\\\\\\\\$1',$net[0]);
			}
			@DbFreeResult($res);
		}else{
			echo @DbError($link);
		}
		if (! is_array ($devs) ){echo $resmsg;die;}
		$query	= GenQuery('devices','s','name,ip,location,icon','','',array('name'),array('regexp'),array(implode("|",$devs)));
	}elseif($ina == "type"){
		$query	= GenQuery('devices','s','name,ip,location,icon','','',array('type'),array('regexp'),array($flt));
	}else{
		$query	= GenQuery('devices','s','name,ip,location,icon','','',array('location'),array('regexp'),array($flt));
	}
	$res	= @DbQuery($query,$link);
	if($res){
		while( ($d = @DbFetchRow($res)) ){
			$l = explode($locsep, $d[2]);
			if($lev == "c" or $lev == "C"){
				$locs[$l[0]][$l[1]]++;
			}elseif($lev == "b" or $lev == "B"){
				$locs[$l[0]][$l[1]][$l[2]]++;
			}else{
				$locs[$l[0]][$l[1]][$l[2]][$l[3]][] = $d[0];
			}
			$dev['ip'] [$d[0]] = long2ip($d[1]);
			$dev['ic'] [$d[0]] = $d[3];
			$dev['reg'][$d[0]] = $l[0];
			$dev['cty'][$d[0]] = $l[1];
			$dev['bld'][$d[0]] = $l[2];
			$dev['rom'][$d[0]] = $l[4];
		}
		@DbFreeResult($res);
	}else{
		echo @DbError($link);
	}
	if($ipi){
		$query	= GenQuery('networks');
		$res	= @DbQuery($query,$link);
		if($res){
			while( ($n = @DbFetchRow($res)) ){
				$net[$n[0]][$n[1]] .= " ". long2ip($n[2]);
			}
		}else{
			echo @DbError($link);
		}
		@DbFreeResult($res);
	}

# Gather Link Info
	$query	= GenQuery('links');
	$res	= @DbQuery($query,$link);
	if($res){
		while( ($l = @DbFetchRow($res)) ){
			if($dev[$l[1]]['ic'] and $dev[$l[3]]['ic']){					# both ends are ok, if an icon exists
				if($lev == "f"){
					if( isset($devlink[$l[3]][$l[1]]) ){				# opposite link doesn't exist?
						$devlink[$l[3]][$l[1]]['nbw'][$l[3]][$l[4]][$l[1]][$l[2]] = $l[5];
					}else{
						$devlink[$l[1]][$l[3]]['bw'][$l[1]][$l[2]][$l[3]][$l[4]] = $l[5];
					}
				}
				if($dev[$l[1]]['bld'] != $dev[$l[3]]['bld'])			{	# is it same bld?
					$nbldlink[$dev[$l[1]]['bld']] ++;
					$abldlink[$dev[$l[1]]['bld']][$dev[$l[3]]['bld']]++;		# needed for Arranging.
					if(isset($bldlink[$dev[$l[3]]['bld']][$dev[$l[1]]['bld']]) ){	# link defined already?
						$bldlink[$dev[$l[3]]['bld']][$dev[$l[1]]['bld']]['nbw'][$l[3]][$l[4]][$l[1]][$l[2]] = $l[5];
					}else{
						$bldlink[$dev[$l[1]]['bld']][$dev[$l[3]]['bld']]['bw'][$l[1]][$l[2]][$l[3]][$l[4]] = $l[5];
					}
				}
				if($dev[$l[1]]['cty'] != $dev[$l[3]]['cty']){				# is it same cty?
					$nctylink[$dev[$l[1]]['cty']]++;
					$actylink[$dev[$l[1]]['cty']][$dev[$l[3]]['cty']]++;		# needed for Arranging.
					if(isset($ctylink[$dev[$l[3]]['cty']][$dev[$l[1]]['cty']]) ){	# link defined already?
						$ctylink[$dev[$l[3]]['cty']][$dev[$l[1]]['cty']]['nbw'][$l[3]][$l[4]][$l[1]][$l[2]] = $l[5];
					}else{

						$ctylink[$dev[$l[1]]['cty']][$dev[$l[3]]['cty']]['bw'][$l[1]][$l[2]][$l[3]][$l[4]] = $l[5];
					}
				}
			}
		}
		@DbFreeResult($res);
	}else{
		echo @DbError($link);
	}

# Prepare geographic stuff
	$mapinfo   = "";
	$mapframes = "";
	$maplinks  = "";
	$mapitems  = "";

	if ($geo) {
		if(count($cdev) == 1){
			$rk   = array_keys($cdev);
			if(count($cdev[$rk[0]]) == 1){
				$ck = array_keys($cdev[$rk[0]]);
				$mapbg = TopoMap($rk[0],$ck[0]);
			}else{
				$mapbg = TopoMap($rk[0]);
			}
		}else{
			$mapbg = TopoMap();
		}
		$bg = Imagecreatefrompng("log/$mapbg");
		$xm = Imagesx($bg);
		$ym = Imagesy($bg);
		Imagedestroy($bg);

		$query	= GenQuery('locations');
		$res	= @DbQuery($query,$link);
		if($res){
			while( ($l = @DbFetchRow($res)) ){
				if($l[3]){
					if( $mapbg == TopoMap($l[1],$l[2]) ){
						$bloc[$l[3]] = $l[4];
						$bloc[$l[3]] = $l[5];
					}
				}elseif($l[2]){
					if($mapbg == TopoMap($l[1]) ){
						$cloc[$l[2]] = $l[4];
						$cloc[$l[2]] = $l[5];
					}
				}else{
					$rloc[$l[1]] = $l[4];
					$rloc[$l[1]] = $l[5];
				}
			}
			@DbFreeResult($res);
		}else{
			echo @DbError($link);
		}
		
	}
# Build Layout
	Drawlevel($locs,(intval($xm/2) + $xo), (intval($ym/2) + $yo),$len );
}

#===================================================================
# Recursevly draw a map level.
function Drawlevel($locs,$xs,$ys,$l) {

	global $link,$locsep,$resmsg;
	global $lev,$liwgt;
	global $mapbg,$mapinfo,$mapframes,$maplinks,$mapitems,$imgmap;

	$nloc   = count($locs);
	if($nloc == 1){
		$scalx = 0;
		$scaly = 0;
	}else{
		$scalx = 1.3;
		$scaly = 1;
	}
	$locnum = 0;
	foreach(Arrange($locs) as $loc){
		$locnum++;
		if( 1 ){
			$phi  = $cro * M_PI/180 + 2 * $locnum * M_PI / $nloc;
			$locw = pow($ncl,$lnw/10);
			$x = intval($xs + $l * cos($phi) * $scalx / $locw);
			$y = intval($ys + $l * sin($phi) * $scaly / $locw);
		}
		if( is_array($locs[$loc]) ){
			Drawlevel($locs[$loc],$x,$y,($l/2));
		}else{
			$mapitems .= Drawitem($x,$y,$locs[$loc],$loc,$lev);
			$area = ($x-20) .",". ($y-20) .",". ($x+20) .",". ($y+20);
			$imgmap .= "<area href=?flt=". rawurlencode("$loc$locsep") ."&lev=b&loi=1&draw=1 coords=\"$area\" shape=rect title=\"Show\">\n";
		}
	}
}

#===================================================================
# Generate the map.
function Map() {

	global $link,$lev,$fco,$xm,$ym,$xo,$yo,$csi,$bsi,$fsi,$cro,$bro,$cwt,$loi,$bwt,$dev,$ndev,$bdev,$cdev;
	global $devlink,$ctylink,$bldlink,$rdevlink,$rctylink,$rbldlink,$nctylink,$nbldlink,$imgmap;
	global $geo,$xbl,$ybl,$xct,$yct,$mapbg,$mapinfo,$mapframes,$maplinks,$mapitems,$locsep;

	$mapinfo   = "";
	$mapframes = "";
	$maplinks  = "";
	$mapitems  = "";

	$ctyscalx = 1.3;
	$ctyscaly = 1;

	$nreg = count($rdev);
	$ncty = count($cdev);
	if($nreg == 1){
		$rk = array_keys($rdev);
		if($ncty == 1){
			$ctyscalx = 0;
			$ctyscaly = 0;
			$ck = array_keys($rdev[$rk[0]]);
			$mapbg = TopoMap($rk[0],$ck[0]);
		}else{
			$mapbg = TopoMap($rk[0]);
		}
	}else{
		$mapbg = TopoMap();
	}


	if ($geo) {
		$bg = Imagecreatefrompng("log/$mapbg");
		$xm = Imagesx($bg);
		$ym = Imagesy($bg);
		Imagedestroy($bg);

		$query	= GenQuery('locations');
		$res	= @DbQuery($query,$link);
		if($res){
			while( ($l = @DbFetchRow($res)) ){
				if($l[3]){
					if( $mapbg == TopoMap($l[1],$l[2]) ){
						$xbl["$l[1]$locsep$l[2]"][$l[3]] = $l[4];
						$ybl["$l[1]$locsep$l[2]"][$l[3]] = $l[5];
					}
				}elseif($l[2]){
					if($mapbg == TopoMap($l[1]) ){
						$xct["$l[1]$locsep$l[2]"] = $l[4];
						$yct["$l[1]$locsep$l[2]"] = $l[5];
					}
				}else{
					$xrg[$l[1]] = $l[4];
					$yrg[$l[1]] = $l[5];
				}
			}
			@DbFreeResult($res);
		}else{
			echo @DbError($link);
		}
		
	}

	$ctynum = 0;
	ksort($cdev);
	foreach(Arrange($cdev,"c") as $cty){
		$ctynum++;
		$nbld = count($ndev[$cty]);
		$ncl  = ($nctylink[$cty])?$nctylink[$cty]:1;
		$l    = explode($locsep, $cty);
		if( !(isset($xct[$cty]) and isset($yct[$cty])) ){
			$phi = $cro * M_PI/180 + 2 * $ctynum * M_PI / $ncty;
			$ctywght = pow($ncl,$cwt/10);
			$xct[$cty] = intval((intval($xm/2) + $xo) + $csi * cos($phi) * $ctyscalx / $ctywght);
			$yct[$cty] = intval((intval($ym/2) + $yo) + $csi * sin($phi) * $ctyscaly / $ctywght);
		}
		if($lev == "c" or $lev == "C"){
			$mapitems .= Drawitem($xct[$cty],$yct[$cty],$cdev[$cty],$l[1],$lev);
			$area = ($xct[$cty]-20) .",". ($yct[$cty]-20) .",". ($xct[$cty]+20) .",". ($yct[$cty]+20);
			$imgmap .= "<area href=?flt=". rawurlencode($cty) ."&lev=b&loi=1&draw=1 coords=\"$area\" shape=rect title=\"Show $nbld buildings\">\n";
		}else{
			if($nbld == 1){
				$bldscalx = 0;
				$bldscaly = 0;
			}else{
				$bldscalx = 1.3;
				$bldscaly = 1;
				if ($loi){$mapinfo .= Drawitem($xct[$cty],$yct[$cty],'0',"$l[1] $l[0]",'ci');}
			}
			$bldnum = 0;
			foreach(Arrange($ndev[$cty],"b") as $bld){
				$bldnum++;
				$nbl = ($nbldlink[$bld])?$nbldlink[$bld]:1;
				if( !(isset($xbl[$bld]) and isset($ybl[$bld])) ){
					$eps = $bro * M_PI/180 + 2 * $bldnum * M_PI / $nbld;
					$bldwght = pow($nbl,$bwt/10);
					$xbl[$bld] = intval($xct[$cty] + $bsi * cos($eps) * $bldscalx / $bldwght);
					$ybl[$bld] = intval($yct[$cty] + $bsi * sin($eps) * $bldscaly / $bldwght);
				}
				if($lev == "b" or $lev == "B"){
					$mapitems .= Drawitem($xbl[$bld],$ybl[$bld],$bdev[$cty][$bld],$bld,$lev);
					$area = ($xbl[$bld]-20) .",". ($ybl[$bld]-20) .",". ($xbl[$bld]+20) .",". ($ybl[$bld]+20);
					$imgmap .= "<area href=?flt=". rawurlencode($bld) ."&lev=f&loi=1&ipi=1&draw=1 coords=\"$area\" shape=rect title=\"Show ". $bdev[$cty][$bld] ." devices\">\n";
				}else{
					$cury = $rows = 0;
					$cols = 1;
					foreach(array_keys($ndev[$cty][$bld]) as $flr){			# Determine building size
						$cols = max(count($ndev[$cty][$bld][$flr]),$cols);	# find max cols
						if($cols > $fco){					# Break row, if > Floor columns
							$rrow  = ceil($cols / $fco);			# How many rows result?
							$rows += $rrow;
							$cols = $fco;
						}else{
							$rows++;
						}
					}
					$woff = intval($fsi*($cols-1)/2);
					$hoff = intval($fsi*($rows-1)/2);

					$mapframes .= Drawbox(	$xbl[$bld] - $woff - intval($fsi/2) - 10,
								$ybl[$bld] - $hoff - intval($fsi/2),
								$xbl[$bld] + $woff + intval($fsi/2),
								$ybl[$bld] + $hoff + intval($fsi/2),
								$bld);
					uksort($ndev[$cty][$bld], "Floorsort");
					foreach(array_keys($ndev[$cty][$bld]) as $flr){
						$mapitems .= Drawitem(	$xbl[$bld] - $woff - intval($fsi/2),
									$ybl[$bld] - $hoff + $cury*$fsi,
									0,$flr,'fl');
						usort( $ndev[$cty][$bld][$flr],"Roomsort" );
						$curx = 0;
						foreach($ndev[$cty][$bld][$flr] as $dv){
							$xd[$dv] = $xbl[$bld] - $woff + $curx*$fsi;
							$yd[$dv] = $ybl[$bld] - $hoff + $cury*$fsi;
							$mapitems .= Drawitem($xd[$dv],$yd[$dv],'0',$dv,'d');
							$area = ($xd[$dv]-20) .",". ($yd[$dv]-20) .",". ($xd[$dv]+20) .",". ($yd[$dv]+20);
							$imgmap .= "<area href=Devices-Status.php?dev=". rawurlencode($dv) ." coords=\"$area\" shape=rect title=\"Show $dv Status\">\n";
							$curx++;
							if($curx == $fco){
								$curx = 0;
								$cury++;
							}
						}
						$cury++;
					}	
				}
			}
		}
	}

	if($lev == "c" or $lev == "C"){									# Draw links after we set all coordinates
		foreach(array_keys($ctylink) as $ctyl){
			foreach(array_keys($ctylink[$ctyl]) as $ctyn){
				$mylink = Drawlink($xct[$ctyl],$yct[$ctyl],$xct[$ctyn],$yct[$ctyn],$ctylink[$ctyl][$ctyn]);
				$maplinks .= $mylink[0];
				$mapitems .= $mylink[1];
			}
		}
	}elseif($lev == "b" or $lev == "B"){
		foreach(array_keys($bldlink) as $bldl){
			foreach(array_keys($bldlink[$bldl]) as $bldn){
				$mylink = Drawlink($xbl[$bldl],$ybl[$bldl],$xbl[$bldn],$ybl[$bldn],$bldlink[$bldl][$bldn]);
				$maplinks .= $mylink[0];
				$mapitems .= $mylink[1];
			}
		}
	}elseif($lev == "f"){
		foreach(array_keys($devlink) as $devl){
			foreach(array_keys($devlink[$devl]) as $devn){
				$mylink = Drawlink($xd[$devl]-8,$yd[$devl]-4,$xd[$devn]-8,$yd[$devn]-4,$devlink[$devl][$devn]);
				$maplinks .= $mylink[0];
				$mapitems .= $mylink[1];
			}
		}
	}
}

#===================================================================
# Sort by room and device name(on floors)
function Roomsort($a, $b){

	global $dev;

        if ($dev[$a]['rom'] == $dev[$b]['rom']){
		if ($a == $b){
			return 0;
		}
		return ($a < $b) ? -1 : 1;
	}
        return ($dev[$a]['rom'] < $dev[$b]['rom']) ? -1 : 1;
}

#===================================================================
# Arrange locationa according to their links.
function Arrange($array){
	return array_keys($array);
}

#===================================================================
# Read devices and their neighbours and create the links.
function Read($ina,$filter){

	global $link,$locsep,$resmsg;
	global $lev,$ipi,$net,$dev,$ndev,$bdev,$cdev,$rdev;
	global $devlink,$ctylink,$bldlink;
	global $nctylink,$nbldlink,$actylink,$abldlink;

	$net       = array();

	if($ina == "vlan"){
		$query	= GenQuery('vlans','g','device','device','',array('vlanid'),array('regexp'),array($filter));
		$res	= @DbQuery($query,$link);
		if($res){
			while( ($vl = @DbFetchRow($res)) ){
				$devs[] = preg_replace('/([\^\$+])/','\\\\\\\\$1',$vl[0]);		# \Q...\E Doesn't seem to work in MySQL?
			}
			@DbFreeResult($res);
		}else{
			echo @DbError($link);
		}
		if (! is_array ($devs) ){echo $resmsg;die;}
		$query	= GenQuery('devices','s','name,ip,location,icon','','',array('name'),array('regexp'),array(implode("|",$devs)));
	}elseif($ina == "network"){
		$query	= GenQuery('networks','g','device','device','',array('ip'),array('='),array($filter));
		$res	= @DbQuery($query,$link);
		if($res){
			while( ($net = @DbFetchRow($res)) ){
				$devs[] = preg_replace('/([\^\$\*\+])/','\\\\\\\\$1',$net[0]);
			}
			@DbFreeResult($res);
		}else{
			echo @DbError($link);
		}
		if (! is_array ($devs) ){echo $resmsg;die;}
		$query	= GenQuery('devices','s','name,ip,location,icon','','',array('name'),array('regexp'),array(implode("|",$devs)));
	}elseif($ina == "type"){
		$query	= GenQuery('devices','s','name,ip,location,icon','','',array('type'),array('regexp'),array($filter));
	}else{
		$query	= GenQuery('devices','s','name,ip,location,icon','','',array('location'),array('regexp'),array($filter));
	}
	$res	= @DbQuery($query,$link);
	if($res){
		while( ($unit = @DbFetchRow($res)) ){
			$l = explode($locsep, $unit[2]);
			$ndev["$l[0]$locsep$l[1]"][$l[2]][$l[3]][] = $unit[0];
			$bdev["$l[0]$locsep$l[1]"][$l[2]]++;
			$cdev["$l[0]$locsep$l[1]"]++;
			$rdev[$l[0]][$l[1]]++;
			$dev[$unit[0]]['ip'] = long2ip($unit[1]);
			$dev[$unit[0]]['ic'] = $unit[3];
			$dev[$unit[0]]['cty'] = "$l[0]$locsep$l[1]";
			$dev[$unit[0]]['bld'] = $l[2];
			$dev[$unit[0]]['rom'] = $l[4];
		}
		@DbFreeResult($res);
	}else{
		echo @DbError($link);
	}
	if($ipi){
		$query	= GenQuery('networks');
		$res	= @DbQuery($query,$link);
		if($res){
			while( ($n = @DbFetchRow($res)) ){
				$net[$n[0]][$n[1]] .= " ". long2ip($n[2]);
			}
		}else{
			echo @DbError($link);
		}
		@DbFreeResult($res);
	}
	$query	= GenQuery('links');
	$res	= @DbQuery($query,$link);
	if($res){
		while( ($l = @DbFetchRow($res)) ){
			if($dev[$l[1]]['ic'] and $dev[$l[3]]['ic']){					# both ends are ok, if an icon exists
				if($lev == "f"){
					if( isset($devlink[$l[3]][$l[1]]) ){				# opposite link doesn't exist?
						$devlink[$l[3]][$l[1]]['nbw'][$l[3]][$l[4]][$l[1]][$l[2]] = $l[5];
					}else{
						$devlink[$l[1]][$l[3]]['bw'][$l[1]][$l[2]][$l[3]][$l[4]] = $l[5];
					}
				}
				if($dev[$l[1]]['bld'] != $dev[$l[3]]['bld'])			{	# is it same bld?
					$nbldlink[$dev[$l[1]]['bld']] ++;
					$abldlink[$dev[$l[1]]['bld']][$dev[$l[3]]['bld']]++;		# needed for Arranging.
					if(isset($bldlink[$dev[$l[3]]['bld']][$dev[$l[1]]['bld']]) ){	# link defined already?
						$bldlink[$dev[$l[3]]['bld']][$dev[$l[1]]['bld']]['nbw'][$l[3]][$l[4]][$l[1]][$l[2]] = $l[5];
					}else{
						$bldlink[$dev[$l[1]]['bld']][$dev[$l[3]]['bld']]['bw'][$l[1]][$l[2]][$l[3]][$l[4]] = $l[5];
					}
				}
				if($dev[$l[1]]['cty'] != $dev[$l[3]]['cty']){				# is it same cty?
					$nctylink[$dev[$l[1]]['cty']]++;
					$actylink[$dev[$l[1]]['cty']][$dev[$l[3]]['cty']]++;		# needed for Arranging.
					if(isset($ctylink[$dev[$l[3]]['cty']][$dev[$l[1]]['cty']]) ){	# link defined already?
						$ctylink[$dev[$l[3]]['cty']][$dev[$l[1]]['cty']]['nbw'][$l[3]][$l[4]][$l[1]][$l[2]] = $l[5];
					}else{

						$ctylink[$dev[$l[1]]['cty']][$dev[$l[3]]['cty']]['bw'][$l[1]][$l[2]][$l[3]][$l[4]] = $l[5];
					}
				}
			}
		}
		@DbFreeResult($res);
	}else{
		echo @DbError($link);
	}
}

?>
