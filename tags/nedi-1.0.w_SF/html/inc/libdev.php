<?PHP

//===============================
// Device related functions  (and variables)
//===============================

//===================================================================
// sort based on floor
function floorsort($a, $b) {

	if (is_numeric($a) and is_numeric($b) ){
		if ($a == $b) return 0;
		return ($a > $b) ? -1 : 1;
	}else{
		return strnatcmp ( $a,$b );
	}
}

//===================================================================
// Return Sys Services
function Syssrv($sv) {

	$srv = "";

	if ($sv &  1) {$srv = " Repeater"; }
	if ($sv &  2) {$srv = "$srv Bridge"; }
	if ($sv &  4) {$srv = "$srv Router"; }
	if ($sv &  8) {$srv = "$srv Gateway"; }
	if ($sv & 16) {$srv = "$srv Session"; }
	if ($sv & 32) {$srv = "$srv Terminal"; }
	if ($sv & 64) {$srv = "$srv Application"; }
	if (!$sv)     {$srv = "-"; }

	return $srv;
}

//===================================================================
// Return VTP mode
function VTPmod($vn) {

	$vmod = "";

	if 	($vn == 1) {return "client(1)"; }
	elseif	($vn == 2) {return "server(2)"; }
	elseif	($vn == 3) {return "transparent(3)"; }
	elseif	($vn == 4) {return "off(4)"; }
}

//===================================================================
// Return city image
function CtyImg($nd) {

	if($nd > 99){
		return "cityl";
	}elseif($nd > 9){
		return "citym";
	}elseif($nd){
		return "citys";
	}else{
		return "cityx";
	}
}

//===================================================================
// Return building image
function BldImg($nd,$na) {

	global $redbuild;
	
	if( preg_match("/$redbuild/",$na) ){
		$bc = "r";
	}else{
		$bc = "";
	}

	if($nd > 19){
		return "bldh$bc";
	}elseif($nd > 9){
		return "bldb$bc";
	}elseif($nd > 2){
		return "bldm$bc";
	}else{
		return "blds$bc";
	}	
}

//===================================================================
// Return Interface Type
function Iftype($it) {

	if ($it == "6"){$img = "p45";$tit="ethernetCsmacd";
	}elseif ($it == "7"){$img = "p45";$tit="iso88023Csmacd";
	}elseif ($it == "22"){$img = "ppp";$tit="propPointToPointSerial";
	}elseif ($it == "23"){$img = "ppp";$tit="ppp";
	}elseif ($it == "24"){$img = "tape";$tit="softwareLoopback";
	}elseif ($it == "28"){$img = "ppp";$tit="slip";
	}elseif ($it == "37"){$img = "ppp";$tit="atm";
	}elseif ($it == "44"){$img = "plug";$tit="frameRelayService";
	}elseif ($it == "56"){$img = "bsw";$tit="fibreChannel";
	}elseif ($it == "58"){$img = "gsw";$tit="frameRelayInterconnect";
	}elseif ($it == "53"){$img = "chip";$tit="propVirtual";
	}elseif ($it == "63"){$img = "tel";$tit="isdn";
	}elseif ($it == "71"){$img = "ant";$tit="radio spread spectrum";
	}elseif ($it == "75"){$img = "tel";$tit="isdns";
	}elseif ($it == "77"){$img = "plug";$tit="lapd";
	}elseif ($it == "81"){$img = "tel";$tit="ds0";
	}else{$img = "qg";$tit="Other-$it";}

	return array("$img.png",$tit);
}

//===================================================================
// Return Routing Protocol
function RteProto($p) {

	if	($p == "local")	{return "fogr";}
	elseif	($p == "netmgmt"){return "fobl";}
	elseif	($p == "icmp")	{return "fobl";}
	elseif	($p == "egp")	{return "fobl";}
	elseif	($p == "ggp")	{return "fobl";}
	elseif	($p == "hello")	{return "fobl";}
	elseif	($p == "rip")	{return "fovi";}
	elseif	($p == "is-is")	{return "fobl";}
	elseif	($p =="es-is")	{return "fobl";}
	elseif	($p =="ciscoIgrp"){return "fogy";}
	elseif	($p =="bbnSpfIgp"){return "fogy";}
	elseif	($p =="ospf")	{return "foor";}
	elseif	($p =="bgp")	{return "ford";}
	else{return "impt";}
}

?>
