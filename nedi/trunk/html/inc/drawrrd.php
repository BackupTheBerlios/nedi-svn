<?php
/*
//===============================
# Program: drawrrd.php
# use GET option d=1 to debug output.
//===============================
*/
session_start(); 
if( !$_SESSION['group'] ){
	echo $nokmsg;
	die;
}
$debug = isset( $_GET['d']) ? "Debugging" : "";
$_GET['dur'] = isset( $_GET['dur']) ? $_GET['dur'] : 7;
if(!preg_match('/[0-9]{1,3}/',$_GET['dur']) ){$_GET['dur'] = 7;}

$udev	= rawurlencode($_GET['dv']);								#str_replace( "-","%2D",
$rrddev	= "rrd/$udev";										# change, if your system can't handle symlinks!
$rrdbin	= "rrdtool";										# point to rrdtol binary.
$title	= "";
$drawin	= "";
$drawout= "";
$lb	= "";
$lbreak	= "";

if($_GET['t'] == 'cpu'){
	$typ = 'CPU Load';
	$rrd = "$rrddev/system.rrd";
	$drawin .= "DEF:cpu=$rrd:cpu:AVERAGE AREA:cpu#cc8855 ";
	$drawin .= "CDEF:cpu2=cpu,1.2,/ AREA:cpu2#dd9966 ";
	$drawin .= "CDEF:cpu3=cpu,1.5,/ AREA:cpu3#eeaa77 ";
	$drawin .= "CDEF:cpu4=cpu,2,/ AREA:cpu4#ffbb88 ";
	$drawin .= "LINE2:cpu#995500:\"% CPU utilization\" ";
	if (!file_exists("$rrd")){$debug = "RRD $rrd not found!";}
}elseif($_GET['t'] == 'mem'){
	$typ = 'Memory';
	$rrd = "$rrddev/system.rrd";
	$drawin .= "DEF:memcpu=$rrd:memcpu:AVERAGE AREA:memcpu#88bb77:\"Bytes free CPU Memory\" ";
	$drawin .= "CDEF:memcpu2=memcpu,1.1,/ AREA:memcpu2#99cc88 ";
	$drawin .= "CDEF:memcpu3=memcpu,1.2,/ AREA:memcpu3#aadd99 ";
	$drawin .= "CDEF:memcpu4=memcpu,1.3,/ AREA:memcpu4#bbeeaa ";
	$drawout .= "DEF:memio=$rrd:memio:AVERAGE LINE2:memio#008866:\"Bytes free I/O Memory\" ";
	if (!file_exists("$rrd")){$debug = "RRD $rrd not found!";}
}elseif($_GET['t'] == 'tmp'){
	$typ = 'Temperature';
	$rrd = "$rrddev/system.rrd";
	$drawin .= "DEF:temp=$rrd:temp:AVERAGE AREA:temp#7788bb  ";
	$drawin .= "CDEF:temp2=temp,1.3,/ AREA:temp2#8899cc ";
	$drawin .= "CDEF:temp3=temp,1.8,/ AREA:temp3#99aadd ";
	$drawin .= "CDEF:temp4=temp,3,/ AREA:temp4#aabbee ";
	$drawin .= "LINE2:temp#224488:\"Degrees Celsius\" ";
	//$drawin .= "CDEF:far=temp,1.8,*,32,+ LINE2:far#006699:\"Degrees Fahrenheit\" ";
	if (!file_exists("$rrd")){$debug = "RRD $rrd not found!";}
}elseif($_GET['t'] == 'trf'){
	$typ = 'Traffic in Byte/s';
	$cols = array('0000aa','008800','0044bb','00bb44','0088ee','00ee88','00aaff','00ffaa','0044ff','00ff44','0088ff','00ff88');
	$lb = "COMMENT:\"\\n\" ";
	StackTraffic($rrddev,$_GET['if'],'inoct','outoct');
}elseif($_GET['t'] == 'err'){
	$typ = "Errors";
	$cols = array('880000','888800','aa0000','aa4400','ee0000','ee8800','ff0000','ffee00','ff0044','ffee44','ff0088','ffee88');
	$lb = "COMMENT:\"\\n\" ";
	StackTraffic($rrddev,$_GET['if'],'inerr','outerr');
}else{
	$typ   = "Invalid Type!!!";
	$debug = "Choose trf,err,cpu,mem or tmp!";
}
if($_GET['s'] == 's'){
	$opts = "-w70 -h50 -g -s -$_GET[dur]d -L5";
}elseif($_GET['s'] == 'm'){
	$opts = "-w320 -h100 -s -$_GET[dur]d";
}elseif($_GET['s'] == 'l'){
	$lbreak = $lb;
	$title = "--title=\"$_GET[dv] $typ on ". date('d-m-Y') ." for the last $_GET[dur] days\" ";
	$opts = "-w800 -h200 -s -$_GET[dur]d";
}

if($debug){
	echo "<b>$debug</b>";
	echo "<pre>$rrdbin graph  - -a PNG $title $opts\n\t$drawin\n\t$lbreak\n\t$drawout</pre>";
}else{
	header("Content-type: image/png");
	passthru("$rrdbin graph  - -a PNG $title $opts $drawin $lbreak $drawout");
}

function StackTraffic($rdv,$interfaces,$idef,$odef){

	global $cols,$debug,$drawin,$drawout;
	$c = 0;
	$inmod  = 'AREA';
	$outmod = 'LINE2';
	foreach ($interfaces as $i){
		if($c){$inmod = 'STACK';$outmod = 'STACK';}
		$rrd = "$rdv/" . rawurlencode($i) . ".rrd";
		if (!file_exists($rrd)){$debug = "RRD $rrd not found!";}
		$drawin .= "DEF:$idef$c=$rrd:$idef:AVERAGE $inmod:$idef$c#$cols[$c]:\"$i  in\" ";
		$c++;
		$drawout .= "DEF:$odef$c=$rrd:$odef:AVERAGE $outmod:$odef$c#$cols[$c]:\"$i out\" ";
		$c++;
	}
}
?>
