<?

/*
#============================================================================
# Program: Devices-Map.php
# Programmer: Remo Rickli
#
# DATE     COMMENT
# -------- ------------------------------------------------------------------
# 6/05/05	initial version.
# 10/03/06	new SQL query support
# 17/07/06	enhanced info and new network filter
# 21/02/07	refined layout and link weight computation, more hints and image map!
*/

error_reporting(E_ALL ^ E_NOTICE);

$bg1	= "5599BB";
$bg2	= "66AACC";
$btag	= "";
$nocache= 1;
$calendar= 0;
$refresh = 0;

$mapinfo   = array();
$mapframes = array();
$maplinks  = array();
$mapitems   = array();

$ndev      = array();
$bldlink   = array();
$ctylink   = array();
$devlink   = array();

include_once ("inc/header.php");
include_once ('inc/libdev.php');

$_GET = sanitize($_GET);
$lev = isset($_GET['lev']) ? $_GET['lev'] : "";
$dep = isset($_GET['dep']) ? $_GET['dep'] : 8;
$loi = isset($_GET['loi']) ? "checked" : "";
$bwi = isset($_GET['bwi']) ? "checked" : "";
$ifi = isset($_GET['ifi']) ? "checked" : "";
$ipi = isset($_GET['ipi']) ? "checked" : "";
$tit = isset($_GET['tit']) ? $_GET['tit'] : "NeDi Network Map";
$flt = isset($_GET['flt']) ? $_GET['flt'] : ".";
$ina = isset($_GET['ina']) ? $_GET['ina'] : "loc";
$xm  = isset($_GET['x']) ? $_GET['x'] : 800;
$ym  = isset($_GET['y']) ? $_GET['y'] : 600;
$xo  = isset($_GET['xo']) ? $_GET['xo'] : 0;
$yo  = isset($_GET['yo']) ? $_GET['yo'] : 0;
$res = isset($_GET['res']) ? $_GET['res'] : "";

if   ($res == "vga") {$xm = "640"; $ym = "480";}
elseif($res == "svga"){$xm = "800"; $ym = "600";}
elseif($res == "xga") {$xm = "1024";$ym = "768";}
elseif($res == "sxga"){$xm = "1280";$ym = "1024";}
elseif($res == "uxga") {$xm = "1600";$ym = "1200";}

$csi = isset($_GET['csi']) ? $_GET['csi'] : intval($xm /3);
$bsi = isset($_GET['bsi']) ? $_GET['bsi'] : intval($xm /4);
$fsi = isset($_GET['fsi']) ? $_GET['fsi'] : 70;
$cwt = isset($_GET['cwt']) ? $_GET['cwt'] : 5;
$bwt = isset($_GET['bwt']) ? $_GET['bwt'] : 5;
$cro = isset($_GET['cro']) ? $_GET['cro'] : 0;
$bro = isset($_GET['bro']) ? $_GET['bro'] : 0;
$lwt = isset($_GET['lwt']) ? $_GET['lwt'] : 5;

$maxcol = intval($fsi/10);

$cpos = strpos($locformat, "c");
$bpos = strpos($locformat, "b");
$fpos = strpos($locformat, "f");
$rpos = strpos($locformat, "r");
$kpos = strpos($locformat, "k");

$levopt = "";
if(!($cpos === false) ){
	$s = ($lev == "c")?"selected":"";
        $levopt .= "<OPTION VALUE=c $s>City\n";
}
if(!($bpos === false) ){
	$s = ($lev == "b")?"selected":"";
	$levopt .= "<OPTION VALUE=b $s>Building";
}
if(!($fpos === false) ){
	$s = ($lev == "f")?"selected":"";
        $levopt .= "<OPTION VALUE=f $s>Device\n";
}

$imgmap    = "";

$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
$query	= GenQuery('devices');

$res	= @DbQuery($query,$link);
if($res){
	while( ($dev = @DbFetchRow($res)) ){
		$locitems = explode($locsep, $dev[10]);
		if(!($cpos === false) ){$copt[$locitems[$cpos]]++;}
		if(!($bpos === false) ){$bopt[$locitems[$bpos]]++;}
	}
	@DbFreeResult($res);
}else{
	print @DbError($link);
}
?>
<h1>Device Map</h1>

<form method="get" name="map" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<? echo $bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>><img src=img/32/paint.png border=0 title="Draws image of your network"></a></th>
<th valign=top title="Size & depth of image">Image
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

<th valign=top title="Select detail levels">Layers
<table>
<tr><td>Title</td><td><input type="text" name="tit" value="<?=$tit?>" size=18></td></tr>
<tr><td>Level</td><td><select size=1 name="lev">
<?=$levopt ?>
</select>
W <input type="text" name="lwt" value="<?=$lwt?>" size=2 title="Label weight">
</td></tr>
<tr><td>Show</td><td>
<INPUT type="checkbox" name="loi" <?=$loi?>> L
<INPUT type="checkbox" name="bwi" <?=$bwi?>> BW
<INPUT type="checkbox" name="ifi" <?=$ifi?>> IF
<INPUT type="checkbox" name="ipi" <?=$ipi?>> IP
</td></tr>
</table>
</th>

<th valign=top title='Object placement properties'>Layout
<table>
<tr><td>City</td><td>
<input type="text" name="csi" value="<?=$csi?>" size=3 title="Length of city links">L
<input type="text" name="cwt" value="<?=$cwt?>" size=2 title="Weight of cities based on # of links">W
<input type="text" name="cro" value="<?=$cro?>" size=3 title="Rotation of city circle">@
</td></tr>
<tr><td>Bld</td><td>
<input type="text" name="bsi" value="<?=$bsi?>" size=3 title="Length of building links">L
<input type="text" name="bwt" value="<?=$bwt?>" size=2 title="Weight of buildings based on # of links">W
<input type="text" name="bro" value="<?=$bro?>" size=3 title="Rotation of building circle">@
</td></tr>
<tr><td>Floor</td><td>
<input type="text" name="fsi" value="<?=$fsi?>" size=3 title="Floor size and columns(divided by 10)">S
<input type="text" name="xo" value="<?=$xo?>" size=2 title="Map X offset"> X
<input type="text" name="yo" value="<?=$yo?>" size=2 title="Map Y offset"> Y
</td></tr>
</table>
</th>

<th valign=top title="location or vlan filter, with presets">Filter
<table >
<tr><td>
<select size=1 name="ina">
<option value="loc" <?=($ina == "loc")?"selected":""?>>Location
<option value="vlan" <?=($ina == "vlan")?"selected":""?>>Vlan
<option value="network" <?=($ina == "network")?"selected":""?>>Network
</select>
</td></tr>
<tr><td><input type="text" name="flt" value="<?=$flt?>" size=16></td></tr>
<tr><td><select size=1 name="cs" onchange="document.map.flt.value=document.map.cs.options[document.map.cs.selectedIndex].value">
<option value="">or select
<?
if($copt){
	echo '<option value="">------------';
	ksort($copt);
	while( list($cty,$nd)=each($copt) ){
		$ucty = str_replace(" ","%20",$cty);
		echo "<option value=$ucty>$cty ($nd)\n";
	}
}
if($bopt){
	echo '<option value="">------------';
	ksort($bopt);
	while( list($bld,$nd)=each($bopt) ){
		$ubld = str_replace(" ","%20",$bld);
		echo "<option value=$ubld>$bld ($nd)\n";
	}
}
?>
</select></td></tr>
</table>
</th>
<th width=80><input type=submit name="draw" value="draw"></th></tr>
</tr></table><p>
<?
if( isset($_GET['draw']) ){
	Read($ina,$flt,$ipi,$ifi);
	Map($lev);
	Writemap($_SESSION['user'],count($dev) );
}else{
	echo "<h2>Previous Map (not clickable)</h2>";
}
if (file_exists("log/map_$_SESSION[user].php")) {
?>
	<center><img usemap=#net src="log/map_<?=$_SESSION[user]?>.php"></center>
	<map name=net>
	<?=$imgmap?>
	</map>
<?
}

include_once ("inc/footer.php");

#===================================================================
# Generate the php script for the image.

function Writemap($usr,$nd) {

	global $xm,$ym,$dep,$tit,$ina,$flt,$mapitems,$mapinfo,$mapframes,$maplinks;

	$xf = $xm - 130;
	$yf = $ym - 10;
	$now = date ("G:i:s j.M y",time());


	if ($dep == "24"){
		$imgcreate = "\$image = imagecreatetruecolor($xm, $ym);\n";
		$imgcreate .= "Imagealphablending(\$image,true);\n";
		$imgcreate .= "\$gy1 = Imagecolorallocatealpha(\$image, 230, 230, 230, 40);\n";
		$imgcreate .= "\$gy2 = Imagecolorallocatealpha(\$image, 250, 250, 250, 40);\n";
	}else{
		$imgcreate = "\$image = imagecreate($xm, $ym);\n";
		$imgcreate .= "\$gy1 = ImageColorAllocate(\$image, 230, 230, 230);\n";
		$imgcreate .= "\$gy2 = ImageColorAllocate(\$image, 250, 250, 250);\n";
	}

       	$maphdr = array("<?PHP",
			"header(\"Content-type: image/png\");",
			$imgcreate,
			"\$red = ImageColorAllocate(\$image, 150, 0, 0);",
			"\$re2 = ImageColorAllocate(\$image, 220, 60, 60);",
			"\$grn = ImageColorAllocate(\$image, 0, 200, 0);",
			"\$gr2 = ImageColorAllocate(\$image, 0, 100, 0);",
			"\$bl1 = ImageColorAllocate(\$image, 0, 0, 200);",
			"\$bl2 = ImageColorAllocate(\$image, 0, 100, 200);",
			"\$bl3 = ImageColorAllocate(\$image, 100, 150, 220);",
			"\$org = ImageColorAllocate(\$image, 220, 220, 0);",
			"\$wte = ImageColorAllocate(\$image, 255, 255, 255);",
			"\$gry = ImageColorAllocate(\$image, 100, 100, 100);",
			"\$blk = ImageColorAllocate(\$image, 0, 0, 0);",
			"ImageFilledRectangle(\$image, 0, 0, $xm, $ym, \$wte);",
			"ImageString(\$image, 5, 8, 8, \"$tit\", \$blk);",
			"ImageString(\$image, 1, 8, 24, \"Filter: ($ina) $flt\", \$blk);",
			"ImageString(\$image, 1, 8, 33, \"Match: $nd Devices\", \$blk);",
			);

       	$mapftr = array("ImageString(\$image, 1, $xf, $yf, \"NeDi $now\", \$blk);",
			"Imagepng(\$image);",
			"Imagedestroy(\$image);",
			"?>"
			);
	

	$map = array_merge($maphdr,$mapinfo,$mapframes,$maplinks,$mapitems,$mapftr);

	$fd =  @fopen("log/map_$usr.php","w") or die ("can't create log/map_$usr.php");
	fwrite($fd,implode("\n",$map));
	fclose($fd);


}

#===================================================================
# Draws a link.

function Drawlink($x1,$y1,$x2,$y2,$bw,$rbw,$if=0,$nif=0) {

        global $maplinks,$bwi,$lwt,$lix,$liy;
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

	if($bw == 11000000 or $bw == 54000000){
		$maplinks[] = "Imageline(\$image,$x1,$y1,$x2,$y2,\$org);";
	}elseif($bw < 10000000){
		$maplinks[] = "Imageline(\$image,$x1,$y1,$x2,$y2,\$grn);";
	}elseif($bw < 100000000){
		$maplinks[] = "Imageline(\$image,$x1,$y1,$x2,$y2,\$bl2);";
	}elseif($bw < 1000000000){
		$maplinks[] = "Imageline(\$image,$x1,$y1,$x2,$y2,\$bl3);";
	}elseif($bw == 1000000000){
		$maplinks[] = "Imageline(\$image,$x1,$y1,$x2,$y2,\$red);";
	}else{
		$maplinks[] = "imagesetthickness(\$image,".($bw / 1000000000).");";
		$maplinks[] = "Imageline(\$image,$x1,$y1,$x2,$y2,\$re2);";
		$maplinks[] = "Imagesetthickness(\$image, 1);";
	}

	if($bwi){
		$xl = intval($x1  + $x2) / 2;
		$yl = intval($y1  + $y2) / 2;
		$label = ZFix($bw) . "/" . ZFix($rbw);
		$maplinks[] = "ImageString(\$image, 1,$xl,$yl,\"$label\", \$grn);";
	}
	$xi1 = intval($x1+($x2-$x1)/(1 + $lwt/10));
	$xi2 = intval($x2+($x1-$x2)/(1 + $lwt/10));
	$yi1 = intval($y1+($y2-$y1)/(1 + $lwt/10));
	$yi2 = intval($y2+($y1-$y2)/(1 + $lwt/10));
	if($if) {$maplinks[] = "ImageString(\$image, 1,$xi2,$yi2,\"$if\", \$gr2);";}
	if($nif){$maplinks[] = "ImageString(\$image, 1,$xi1,$yi1,\"$nif\", \$gr2);";}
}
#===================================================================
# Draws box.

function Drawbox($x1,$y1,$x2,$y2,$label) {

	global $mapframes,$imgmap;

	$xt = $x1 + 4;
	$yt = $y1 + 4;
	$xs = $x1 + 20;
	$ys = $y1 + 20;

	$mapframes[] = "Imagefilledrectangle(\$image, $x1,$y1,$x2,$y2, \$gy2);";
	$mapframes[] = "Imagefilledrectangle(\$image, $x1,$ys,$xs,$y2, \$gy1);";
	$mapframes[] = "Imagerectangle(\$image, $x1,$y1,$x2,$y2, \$blk);";
	$mapframes[] = "ImageString(\$image, 3, $xt,$yt,\"$label\", \$bl2);";
}

#===================================================================
# Draws a city, building or device.

function Drawitem($x,$y,$opt,$label,$lev) {

	global $dev,$loi,$ipi,$redbuild,$mapinfo,$mapitems;

	if($lev == "f"){
		$img = "dev/" . $dev[$label]['ic'];
		$lcol = "bl1";
		$font = "1";
	}elseif($lev == "b"){
		$img  = BldImg($opt,$label);
		$lcol = "bl2";
		$font = "2";
	}elseif($lev == "c"){
		$img = CtyImg($opt);
		$lcol = "bl1";
		$font = "5";
	}elseif($lev == "fl"){
		$img = "stair";
		$lcol = "blk";
		$font = "3";
	}elseif($lev == "ci"){
		$mapinfo[] = "\$icon = Imagecreatefrompng(\"../img/cityg.png\");";
		$mapinfo[] = "\$w = Imagesx(\$icon);";
		$mapinfo[] = "\$h = Imagesy(\$icon);";
		$mapinfo[] = "Imagecopy(\$image, \$icon,intval($x - \$w/2),intval($y - \$h/2),0,0,\$w,\$h);";
		$mapinfo[] = "ImageString(\$image,2, intval($x  - \$w/1.5), intval($y + \$h/1.5), \"$label\", \$bl3);";
		$mapinfo[] = "Imagedestroy(\$icon);";
		return;
	}
	$mapitems[] = "\$icon = Imagecreatefrompng(\"../img/$img.png\");";
	$mapitems[] = "\$w = Imagesx(\$icon);";
	$mapitems[] = "\$h = Imagesy(\$icon);";
	if ($lev == "f"){
		if ($loi){$mapitems[] = "ImageString(\$image, $font, intval($x  - \$w/1.5), intval($y - \$h/1.5 - 8), \"".$dev[$label]['rom']."\", \$bl3);";}
		if ($ipi){$mapitems[] = "ImageString(\$image, $font, intval($x  - \$w/1.5), intval($y + \$h/1.5 + 8), \"".$dev[$label]['ip']."\", \$gry);";}
	}
	$label = preg_replace('/\\$/','\\\$', $label);
	$mapitems[] = "Imagecopy(\$image, \$icon,intval($x - \$w/2),intval($y - \$h/2),0,0,\$w,\$h);";
	$mapitems[] = "ImageString(\$image, $font, intval($x  - \$w/1.5), intval($y + \$h/1.5), \"$label\", \$$lcol);";
	$mapitems[] = "Imagedestroy(\$icon);";
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
# Generate the map.
function Map($lev) {

	global $maxcol,$xm,$ym,$xo,$yo,$csi,$bsi,$fsi,$cro,$bro,$cwt,$loi,$bwt,$dev,$ndev,$bdev,$fdev;
	global $devlink,$ctylink,$bldlink,$rdevlink,$rctylink,$rbldlink,$nctylink,$nbldlink,$imgmap;

	$ncty = count($ndev);

	if($ncty == 1){
		$ctyscalx = 0;
		$ctyscaly = 0;
	}else{
		$ctyscalx = 1.3;
		$ctyscaly = 1;
	}
	$ctynum = 0;
	$bldnum = 0;
	foreach(Arrange($ndev,"c") as $cty){
		$phi = $cro * M_PI/180 + 2 * $ctynum * M_PI / $ncty;
		$ctynum++;
		$ncl = ($nctylink[$cty])?$nctylink[$cty]:1;
		$ctywght = pow($ncl,$cwt/10);
		$xct[$cty] = intval((intval($xm/2) + $xo) + $csi * cos($phi) * $ctyscalx / $ctywght);
		$yct[$cty] = intval((intval($ym/2) + $yo) + $csi * sin($phi) * $ctyscaly / $ctywght);
		$nbld = count($ndev[$cty]);

		if($lev == "c"){
			Drawitem($xct[$cty],$yct[$cty],$nbld,$cty,$lev);
			$area = ($xct[$cty]-20) .",". ($yct[$cty]-20) .",". ($xct[$cty]+20) .",". ($yct[$cty]+20);
			$imgmap .= "<area href=?flt=". urlencode($cty) ."&lev=b&loi=1&draw=1 coords=\"$area\" shape=rect title=\"Show $nbld buildings\">\n";
		}else{
			if($nbld != 1){
				$bldscalx = 1.3;
				$bldscaly = 1;
				if ($loi and $cty != "nureini"){
					Drawitem($xct[$cty],$yct[$cty],'0',$cty,'ci');
				}
			}
			foreach(Arrange($ndev[$cty],"b") as $bld){
				$eps = $bro * M_PI/180 + 2 * $bldnum * M_PI / $nbld;
				$bldnum++;
				$nbl = ($nbldlink[$bld])?$nbldlink[$bld]:1;
				$bldwght = pow($nbl,$bwt/10);
				$xbl[$bld] = intval($xct[$cty] + $bsi * cos($eps) * $bldscalx / $bldwght);
				$ybl[$bld] = intval($yct[$cty] + $bsi * sin($eps) * $bldscaly / $bldwght);

				if($lev == "b"){
					Drawitem($xbl[$bld],$ybl[$bld],$bdev[$cty][$bld],$bld,$lev);
					$area = ($xbl[$bld]-20) .",". ($ybl[$bld]-20) .",". ($xbl[$bld]+20) .",". ($ybl[$bld]+20);
					$imgmap .= "<area href=?flt=". urlencode($bld) ."&lev=f&loi=1&ipi=1&draw=1 coords=\"$area\" shape=rect title=\"Show". $bdev[$cty][$bld] ." devices\">\n";
				}else{
					$cury = $nflr = $mdfl = 0;
					$nflr = count($ndev[$cty][$bld]);
					$mdfl =  max(array_values($fdev[$cty][$bld]) );
					foreach(array_keys($fdev[$cty][$bld]) as $flr){
						if($fdev[$cty][$bld][$flr] > $maxcol){
							$afl  = intval($fdev[$cty][$bld][$flr] / $maxcol);
							$rem  = bcmod($fdev[$cty][$bld][$flr] , $maxcol);
							if($rem){
								$nflr = $nflr + $afl;
							}else{
								$nflr = $nflr + $afl - 1;
							}
							$mdfl = $maxcol;
						}
					}
					$xb1 = intval($xbl{$bld} - $fsi/2 * $mdfl - 50);
					$yb1 = intval($ybl[$bld] - $fsi/2 * $nflr + $fsi - 50);
					$xb2 = intval($xbl{$bld} + $fsi/2 * $mdfl - $fsi + 50);
					$yb2 = intval($ybl[$bld] + $fsi/2 * $nflr + 40);
					Drawbox($xb1,$yb1,$xb2,$yb2,$bld);
					uksort($ndev[$cty][$bld], "floorsort");
					foreach(array_keys($ndev[$cty][$bld]) as $flr){
						$cury++;
						$curx = 0;
						usort( $ndev[$cty][$bld][$flr],"Roomsort" );
						$xf = $xbl{$bld} -  intval($fsi * $mdfl/2 + 40);
						$yf = $ybl{$bld} +  intval($fsi * ($cury - $nflr/2));
						Drawitem($xf,$yf,0,$flr,"fl");
						foreach($ndev[$cty][$bld][$flr] as $dv){
							if($curx == $maxcol){
								$curx = 0;
								$cury++;
							} 
							$xd[$dv] = $xbl{$bld} +  intval($fsi * ($curx - $mdfl/2));
							$yd[$dv] = $ybl{$bld} +  intval($fsi * ($cury - $nflr/2));
							Drawitem($xd[$dv],$yd[$dv],'0',$dv,$lev);
							$area = ($xd[$dv]-20) .",". ($yd[$dv]-20) .",". ($xd[$dv]+20) .",". ($yd[$dv]+20);
							$imgmap .= "<area href=Devices-Status.php?dev=". urlencode($dv) ." coords=\"$area\" shape=rect title=\"Show $dv Status\">\n";
$curx++;
						}
					}	
				}
			}
		}
	}

	if($lev == "c"){
		foreach(array_keys($ctylink) as $ctyl){
			foreach(array_keys($ctylink[$ctyl]) as $ctyn){
				Drawlink($xct[$ctyl],$yct[$ctyl],$xct[$ctyn],$yct[$ctyn],$ctylink[$ctyl][$ctyn]['bw'],$rctylink[$ctyl][$ctyn]['bw'],$ctylink[$ctyl][$ctyn]['ip'],$rctylink[$ctyl][$ctyn]['ip']);
			}
		}
	}elseif($lev == "b"){
		foreach(array_keys($bldlink) as $bldl){
			foreach(array_keys($bldlink[$bldl]) as $bldn){
				Drawlink($xbl[$bldl],$ybl[$bldl],$xbl[$bldn],$ybl[$bldn],$bldlink[$bldl][$bldn]['bw'],$rbldlink[$bldl][$bldn]['bw'],$bldlink[$bldl][$bldn]['ip'],$rbldlink[$bldl][$bldn]['ip']);
			}
		}
	}elseif($lev == "f"){
		foreach(array_keys($devlink) as $devl){
			foreach(array_keys($devlink[$devl]) as $devn){
				Drawlink($xd[$devl]-8,$yd[$devl]-4,$xd[$devn]-8,$yd[$devn]-4,$devlink[$devl][$devn]['bw'],$rdevlink[$devl][$devn]['bw'],$devlink[$devl][$devn]['if'],$rdevlink[$devl][$devn]['if']);
			}
		}
	}
}

#===================================================================
# Arrange items according to their links.
function Arrange($array,$lev){

	global $actylink,$abldlink;

	$tmparray = array();
	$newtmparray = array();
	
	if($lev == "b"){
		$lnkarr = $abldlink;
	}elseif($lev == "c"){
		$lnkarr = $actylink;
	}
	foreach(array_keys($array) as $key){
		if($lnkarr[$key]){
			$nbr = array_keys($lnkarr[$key]);
			if (count($nbr) == 1 ){
//echo "$key $nbr[0] LEAF<br>";
				$tmparray[$key] = $nbr[0];
				$nnbr[$nbr[0]]++;
			}else{
				$tmparray[$key] = $key;
//echo "$key HUB<br>";
			}
		}else{
			$tmparray[$key] = $key;
//echo "$key Unlinked<br>";
		}
	}
	foreach ($tmparray as $key => $value){
		if($key == $value){
			$newtmparray[$key] = $value . "2";
		}else{
			$newarrcnt[$value]++;
			if($newarrcnt[$value] > $nnbr[$value] /2 ){
				$newtmparray[$key] = $value . "1";
			}else{
				$newtmparray[$key] = $value . "3";
			}
		}
	}
	asort($newtmparray);
	return array_keys($newtmparray);
}

#===================================================================
# Read devices and their neighbours and create the links.
function Read($ina,$filter,$ipi,$ifi){

	global $link,$locsep,$fpos,$bpos,$cpos,$rpos,$resmsg;
	global $dev,$ndev,$bdev,$fdev;
	global $devlink,$ctylink,$bldlink,$rdevlink,$rctylink,$rbldlink;
	global $nctylink,$nbldlink,$actylink,$abldlink;

	$net       = array();

	if($ina == "vlan"){
		$query	= GenQuery('vlans','s','*','','',array('vlanid'),array('regexp'),array($filter));
		$res	= @DbQuery($query,$link);
		if($res){
			while( ($vl = @DbFetchRow($res)) ){
				$devs[] = preg_replace('/([\^\$+])/','\\\\\\\\$1',$vl[0]);
			}
			@DbFreeResult($res);
		}else{
			print @DbError($link);
		}
		if (! is_array ($devs) ){echo $resmsg;die;}
		$query	= GenQuery('devices','s','*','','',array('name'),array('regexp'),array(implode("|",$devs)));
	}elseif($ina == "network"){
		$query	= GenQuery('networks','s','*','','',array('ip'),array('='),array($filter));
		$res	= @DbQuery($query,$link);
		if($res){
			while( ($vl = @DbFetchRow($res)) ){
				$devs[] = preg_replace('/([\^\$\*\+])/','\\\\\\\\$1',$vl[0]);
			}
			@DbFreeResult($res);
		}else{
			print @DbError($link);
		}
		if (! is_array ($devs) ){echo $resmsg;die;}
		$query	= GenQuery('devices','s','*','','',array('name'),array('regexp'),array(implode("|",$devs)));
	}else{
		$query	= GenQuery('devices','s','*','','',array('location'),array('regexp'),array($filter));
	}
	$res	= @DbQuery($query,$link);
	if($res){
		while( ($unit = @DbFetchRow($res)) ){
			$locitems = explode($locsep, $unit[10]);
			if($cpos === false){
				$cty = "nureini";
			}else{
				$cty = $locitems[$cpos];
			}
			if($bpos === false){
				$bld = "nureins";
			}else{
				$bld = $locitems[$bpos];
			}
			if($fpos === false){
				$flr = "nureine";
			}else{
				$flr = $locitems[$fpos];
			}
			if($rpos === false){
				$rom = "nureine";
			}else{
				$rom = $locitems[$rpos];
			}
			$dev[$unit[0]]['ip'] = long2ip($unit[1]); 
			$dev[$unit[0]]['ic'] = $unit[18]; 
			$dev[$unit[0]]['cty'] = $cty; 
			$dev[$unit[0]]['bld'] = $bld; 
			$dev[$unit[0]]['flr'] = $flr; 
                        $dev[$unit[0]]['rom'] = $rom;
			$ndev[$cty][$bld][$flr][] = $unit[0];
			$bdev[$cty][$bld]++;
			$fdev[$cty][$bld][$flr]++;
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
	}
	if($ipi){
		$query	= GenQuery('networks');
		$res	= @DbQuery($query,$link);
		if($res){
			while( ($n = @DbFetchRow($res)) ){
				$net[$n[0]][$n[1]] .= long2ip($n[2]) . " ";
			}
		}else{
			print @DbError($link);
		}
		@DbFreeResult($res);
	}
	$query	= GenQuery('links');
	$res	= @DbQuery($query,$link);
	if($res){
		while( ($l = @DbFetchRow($res)) ){
			if($dev[$l[1]]['ic'] and $dev[$l[3]]['ic']){							// both ends are ok, if an icon exists
				if($ifi){
					$inam = "$l[2] ";
				}else{
					$inam = "";
				}
				if(!isset($devlink[$l[3]][$l[1]]) ){							// opposite link doesn't exist?
					$devlink[$l[1]][$l[3]]['bw'] += $l[5];
					$devlink[$l[1]][$l[3]]['if'] .= $inam . $net[$l[1]][$l[2]];
				}else{
					$rdevlink[$l[3]][$l[1]]['bw'] += $l[5];
					$rdevlink[$l[3]][$l[1]]['if'] .= $inam . $net[$l[1]][$l[2]];
				}
				if($dev[$l[1]]['bld'] != $dev[$l[3]]['bld'])			{			// is it same bld?
					$nbldlink[$dev[$l[1]]['bld']] ++;
					$abldlink[$dev[$l[1]]['bld']][$dev[$l[3]]['bld']]++;				// needed for Arranging.
					if(!isset($bldlink[$dev[$l[3]]['bld']][$dev[$l[1]]['bld']]) ){			// link defined already?
						$bldlink[$dev[$l[1]]['bld']][$dev[$l[3]]['bld']]['bw'] += $l[5];
						$bldlink[$dev[$l[1]]['bld']][$dev[$l[3]]['bld']]['ip'] .= $net[$l[1]][$l[2]];
					}else{
						$rbldlink[$dev[$l[3]]['bld']][$dev[$l[1]]['bld']]['bw'] += $l[5];
						$rbldlink[$dev[$l[3]]['bld']][$dev[$l[1]]['bld']]['ip'] .= $net[$l[1]][$l[2]];
					}
				}
				if($dev[$l[1]]['cty'] != $dev[$l[3]]['cty']){						// is it same cty?
					$nctylink[$dev[$l[1]]['cty']]++;
					$actylink[$dev[$l[1]]['cty']][$dev[$l[3]]['cty']]++;     	               	// needed for Arranging.
					if(!isset($ctylink[$dev[$l[3]]['cty']][$dev[$l[1]]['cty']]) ){			// link defined already?
						$ctylink[$dev[$l[1]]['cty']][$dev[$l[3]]['cty']]['bw'] += $l[5];
						$ctylink[$dev[$l[1]]['cty']][$dev[$l[3]]['cty']]['ip'] .= $net[$l[1]][$l[2]];
					}else{
						$rctylink[$dev[$l[3]]['cty']][$dev[$l[1]]['cty']]['bw'] += $l[5];
						$rctylink[$dev[$l[3]]['cty']][$dev[$l[1]]['cty']]['ip'] .= $net[$l[1]][$l[2]];
					}
				}
			}
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
	}
}

?>
