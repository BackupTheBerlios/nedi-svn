<?php
//===============================
// Graphical functions  (and variables)
//===============================

// RRDtool properties
$rrdcmd  = "rrdtool";										# point to rrdtool
$rrdpath = "/var/nedi/rrd";									# point to rrds
$fahrtmp = 0;											# show temperature in degrees F too

function GraphTraffic($rrd,$t){

	$c = 0;
	$drawin = "";
	$drawout= "";
	$inmod  = 'AREA';
	$outmod = 'LINE2';

	if($t == 'trf'){
		$idef = 'inoct';
		$odef = 'outoct';
		$tit = 'Traffic in Byte/s';
		$cols = array('0000aa','008800','0044bb','00bb44','0088ee','00ee88','00aaff','00ffaa','0044ff','00ff44','0088ff','00ff88','3388ff','33ff88','6688ff','66ff88');
	}else{
		$idef = 'inerr';
		$odef = 'outerr';
		$tit = "Errors";
		$cols = array('880000','886600','aa0000','aa8800','ee0000','eeaa00','ff0000','ffcc00','ff0066','ffcc66','ff0088','ffcc88','ff00aa','ffeeaa','ff00cc','ffccdd');
	}
	foreach (array_keys($rrd) as $i){
		if($c){$inmod = 'STACK';$outmod = 'STACK';}
		$drawin .= "DEF:$idef$c=$rrd[$i]:$idef:AVERAGE $inmod:$idef$c#$cols[$c]:\"$i  in\" ";
		$c++;
		$drawout .= "DEF:$odef$c=$rrd[$i]:$odef:AVERAGE $outmod:$odef$c#$cols[$c]:\"$i out\" ";
		$c++;
	}
	return array($drawin,$drawout,$tit);	
}

class Graph {
	
	function Graph($res){
		if   ($res ==  "svga"){$wd = "800"; $ht = "600";}
		elseif($res == "xga" ){$wd = "1024";$ht = "768";}
		elseif($res == "sxga"){$wd = "1280";$ht = "1024";}
		elseif($res == "uxga"){$wd = "1600";$ht = "1200";}
		else{$wd = "640";$ht = "480";}

		$this->img = imageCreate($wd, $ht);
		$this->wte = imageColorAllocate($this->img, 255, 255, 255);
		$this->blk = imageColorAllocate($this->img, 0, 0, 0);
		$this->gry = imageColorAllocate($this->img, 100, 100, 100);
		$this->red = imageColorAllocate($this->img, 150, 0, 0);
		$this->grn = imageColorAllocate($this->img, 0, 150, 0);
		$this->blu = imageColorAllocate($this->img, 0, 0, 150);

		imagestring($this->img, 2,5,5, $res, $this->blu);
	}
	
	function drawGrid() {
		$this->x0 = -$x1;
		$this->y0 = -$y1;
		$this->x1 = $x1;
		$this->y1 = $y1;
		$this->posX0 = $width/2;
		$this->posY0 = $height/2;
		$this->scale = (double)($width-20)/($this->x1-$this->x0);
		imageLine($this->img, $this->posX0 + $this->x0*$this->scale-2,
		$this->posY0,
		$this->posX0 + $this->x1*$this->scale+2,
		$this->posY0, $this->blk);
		imageLine($this->img, $this->posX0,
		$this->posY0 - $this->y0*$this->scale+2,
		$this->posX0,
		$this->posY0 - $this->y1*$this->scale-2, $this->blk);
		imagesetstyle($this->img, array($this->gry, $this->wte, $this->wte, $this->wte, $this->wte) );
		for ($x = 1; $x <= $this->x1; $x += 1) {
			imageline($this->img, $this->posX0+$x*$this->scale,0,$this->posX0+$x*$this->scale,$this->posY0 * 2, IMG_COLOR_STYLED);
			imageline($this->img, $this->posX0-$x*$this->scale,0,$this->posX0-$x*$this->scale,$this->posY0 * 2, IMG_COLOR_STYLED);
			
			imageLine($this->img, $this->posX0+$x*$this->scale,
			$this->posY0-3,
			$this->posX0+$x*$this->scale,
			$this->posY0+3, $this->blk);
			imageLine($this->img, $this->posX0-$x*$this->scale,
			$this->posY0-3,
			$this->posX0-$x*$this->scale,
			$this->posY0+3, $this->blk);
			imagestring($this->img, 2, $this->posX0+$x*$this->scale, $this->posY0+4, $x, $this->blu);
			imagestring($this->img, 2, $this->posX0-$x*$this->scale, $this->posY0+4, "-$x", $this->blu);
		}
		for ($y = 1; $y <= $this->y1; $y += 1) {
			imageline($this->img, 0, $this->posY0+$y*$this->scale,$this->posX0 * 2,$this->posY0+$y*$this->scale, IMG_COLOR_STYLED);
			imageline($this->img, 0, $this->posY0-$y*$this->scale,$this->posX0 * 2,$this->posY0-$y*$this->scale, IMG_COLOR_STYLED);
			
			imageLine($this->img, $this->posX0-3,
			$this->posY0-$y*$this->scale,
			$this->posX0+3,
			$this->posY0-$y*$this->scale, $this->blk);
			imageLine($this->img, $this->posX0-3,
			$this->posY0+$y*$this->scale,
			$this->posX0+3,
			$this->posY0+$y*$this->scale, $this->blk);
			imagestring($this->img, 2, $this->posX0+4, $this->posY0-$y*$this->scale, $y, $this->blu);
			imagestring($this->img, 2, $this->posX0+4, $this->posY0+$y*$this->scale, "-$y", $this->blu);
		}
	}

	function drawFunction($function, $dx = 0.1) {
		$xold = $x = $this->x0;
		eval("\$yold=".$function.";");
		for ($x += $dx; $x <= $this->x1; $x += $dx) {
			eval("\$y = ".$function.";");
			imageLine($this->img, $this->posX0+$xold*$this->scale,
			$this->posY0-$yold*$this->scale,
			$this->posX0+$x*$this->scale,
			$this->posY0-$y*$this->scale, $this->grn);
			$xold = $x;
			$yold = $y;
		}
	}
	
	function writePng() {
		imagePNG($this->img);
	}
	
	function destroyGraph() {
		imageDestroy($this->img);
	}

}
?>