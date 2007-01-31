<?php
/*
//===============================
# Program: drawrrd.php
# Set $rrdpath properly, if it doesn't work.
# use GET option d=1 to debug output, if you still encounter problems!
//===============================
*/
$rrdcmd  = "rrdtool";										# point to rrdtool
$rrdpath = "/var/nedi/rrd";									# point to rrds

# The above is the FASTEST way. People may argue to put this in nedi.conf. This enables 2 more ways, but I won't do it...
# LITTLE SLOWER and as dirty as above:
#if (file_exists('/etc/nedi.conf')) {
#        $conf = file('/etc/nedi.conf');
#}else{
#        echo "Dude, where's nedi.conf?";
#        die;
#}
#foreach ($conf as $cl) {
#        $l = rtrim($cl);
#        $v =  preg_split('/\s+/',$l);
#        if ($v[0] == "path")         {$path = $v[1];}
#}

# EVEN SLOWER but proper:
#include_once ('libmisc.php');
#ReadConf('usr');
# Now just imagine drawing 300 graphs....

session_start(); 
if( !$_SESSION['group'] ){
	echo $nokmsg;
	die;
}
$debug = isset( $_GET['d']) ? "Debugging" : "";
$_GET['dur'] = isset( $_GET['dur']) ? $_GET['dur'] : 7;
if(!preg_match('/[0-9]{1,3}/',$_GET['dur']) ){$_GET['dur'] = 7;}

$rrddev	= "$rrdpath/" . rawurlencode($_GET['dv']);
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
	$lbreak = $lb;
	$opts = "-w320 -h100 -s -$_GET[dur]d";
}elseif($_GET['s'] == 'l'){
	$lbreak = $lb;
	$title = "--title=\"$_GET[dv] $typ on ". date('d-m-Y') ." for the last $_GET[dur] days\" ";
	$opts = "-w800 -h200 -s -$_GET[dur]d";
}

if($debug){
	echo "<b>$debug</b>";
	echo "<pre>$rrdcmd graph  - -a PNG $title $opts\n\t$drawin\n\t$lbreak\n\t$drawout</pre>";
}else{
	header("Content-type: image/png");
	passthru("$rrdcmd graph  - -a PNG $title $opts $drawin $lbreak $drawout");
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
