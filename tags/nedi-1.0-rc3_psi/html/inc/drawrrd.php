<?php
/*
//===============================
# Program: drawrrd.php
# Set $rrdpath properly, if it doesn't work.
# use GET option d=1 to debug output, if you still encounter problems!
//===============================
*/

include_once ('libgraph.php');

session_start(); 
if( !$_SESSION['group'] ){
	echo $nokmsg;
	die;
}
$debug = isset( $_GET['d']) ? "Debugging" : "";
$_GET['dur'] = isset( $_GET['dur']) ? $_GET['dur'] : 7;
if(!preg_match('/[0-9]{1,3}/',$_GET['dur']) ){$_GET['dur'] = 7;}

$drawin	= "";
$drawout= "";

if($_GET['t'] == 'cpu'){
	$tit = 'CPU Load';
	$rrd = "$rrdpath/" . rawurlencode($_GET['dv']) ."/system.rrd";
	if (!file_exists("$rrd")){$debug = "RRD $rrd not found!";}
	$drawin .= "DEF:cpu=$rrd:cpu:AVERAGE AREA:cpu#cc8855 ";
	$drawin .= "CDEF:cpu2=cpu,1.2,/ AREA:cpu2#dd9966 ";
	$drawin .= "CDEF:cpu3=cpu,1.5,/ AREA:cpu3#eeaa77 ";
	$drawin .= "CDEF:cpu4=cpu,2,/ AREA:cpu4#ffbb88 ";
	$drawin .= "LINE2:cpu#995500:\"% CPU utilization\" ";
}elseif($_GET['t'] == 'mem'){
	$tit = 'Memory';
	$rrd = "$rrdpath/" . rawurlencode($_GET['dv']) ."/system.rrd";
	if (!file_exists("$rrd")){$debug = "RRD $rrd not found!";}
	$drawin .= "DEF:memcpu=$rrd:memcpu:AVERAGE AREA:memcpu#88bb77:\"Bytes free CPU Memory\" ";
	$drawin .= "CDEF:memcpu2=memcpu,1.1,/ AREA:memcpu2#99cc88 ";
	$drawin .= "CDEF:memcpu3=memcpu,1.2,/ AREA:memcpu3#aadd99 ";
	$drawin .= "CDEF:memcpu4=memcpu,1.3,/ AREA:memcpu4#bbeeaa ";
	$drawout .= "DEF:memio=$rrd:memio:AVERAGE LINE2:memio#008866:\"Bytes free I/O Memory\" ";
}elseif($_GET['t'] == 'tmp'){
	$tit = 'Temperature';
	$rrd = "$rrdpath/" . rawurlencode($_GET['dv']) ."/system.rrd";
	if (!file_exists("$rrd")){$debug = "RRD $rrd not found!";}
	$drawin .= "DEF:temp=$rrd:temp:AVERAGE AREA:temp#7788bb  ";
	$drawin .= "CDEF:temp2=temp,1.3,/ AREA:temp2#8899cc ";
	$drawin .= "CDEF:temp3=temp,1.8,/ AREA:temp3#99aadd ";
	$drawin .= "CDEF:temp4=temp,3,/ AREA:temp4#aabbee ";
	$drawin .= "LINE2:temp#224488:\"Degrees Celsius\" ";
	if ($fahrtmp){$drawin .= "CDEF:far=temp,1.8,*,32,+ LINE2:far#006699:\"Degrees Fahrenheit\" ";}
}else{
	foreach ($_GET['if'] as $i){
		$rrd[$i] = "$rrdpath/" . rawurlencode($_GET['dv']) . "/" . rawurlencode($i) . ".rrd";
		if (!file_exists($rrd[$i])){$debug .= "RRD $rrd[$i] not found!\n";}
	}
	list($drawin,$drawout,$tit) = GraphTraffic($rrd,$_GET['t']);
}
$opts = GraphOpts($_GET['s'],$_GET['dur'],$tit,0);

if($debug){
	echo "<b>$debug</b>";
	echo "<pre>$rrdcmd graph - -a PNG $opts\n\t$drawin\n\t$drawout</pre>";
}else{
	header("Content-type: image/png");
	passthru("$rrdcmd graph - -a PNG $opts $drawin $drawout");
}

?>
