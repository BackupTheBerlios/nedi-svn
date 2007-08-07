<?PHP

//===================================================================
// Device related functions
//===================================================================

//===================================================================
// sort based on floor
function Floorsort($a, $b){

	if (is_numeric($a) and is_numeric($b) ){
		if ($a == $b) return 0;
		return ($a > $b) ? -1 : 1;
	}else{
		return strnatcmp ( $a,$b );
	}
}

//===================================================================
// Return Sys Services
function Syssrv($sv){

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
function VTPmod($vn){

	$vmod = "";

	if 	($vn == 1) {return "client(1)"; }
	elseif	($vn == 2) {return "server(2)"; }
	elseif	($vn == 3) {return "transparent(3)"; }
	elseif	($vn == 4) {return "off(4)"; }
}

//===================================================================
// Return city image
function CtyImg($nd){

	if($nd > 19){
		return "cityx";
	}elseif($nd > 9){
		return "cityl";
	}elseif($nd > 2){
		return "citym";
	}else{
		return "citys";
	}
}

//===================================================================
// Return building image
function BldImg($nd,$na){

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
function Iftype($it){

	if ($it == "6"){$img = "p45";$tit="ethernetCsmacd";
	}elseif ($it == "7"){$img = "p45";$tit="iso88023Csmacd";
	}elseif ($it == "22"){$img = "ppp";$tit="propPointToPointSerial";
	}elseif ($it == "23"){$img = "ppp";$tit="ppp";
	}elseif ($it == "24"){$img = "tape";$tit="softwareLoopback";
	}elseif ($it == "28"){$img = "ppp";$tit="slip";
	}elseif ($it == "37"){$img = "ppp";$tit="atm";
	}elseif ($it == "39"){$img = "neto";$tit="sonet";
	}elseif ($it == "44"){$img = "plug";$tit="frameRelayService";
	}elseif ($it == "49"){$img = "netr";$tit="AAL5 over ATM";
	}elseif ($it == "56"){$img = "bsw";$tit="fibreChannel";
	}elseif ($it == "58"){$img = "gsw";$tit="frameRelayInterconnect";
	}elseif ($it == "53"){$img = "chip";$tit="propVirtual";
	}elseif ($it == "63"){$img = "tel";$tit="isdn";
	}elseif ($it == "71"){$img = "ant";$tit="radio spread spectrum";
	}elseif ($it == "75"){$img = "tel";$tit="isdns";
	}elseif ($it == "77"){$img = "plug";$tit="lapd";
	}elseif ($it == "81"){$img = "tel";$tit="ds0";
	}elseif ($it == "134"){$img = "netr";$tit="ATM Sub Interface";
	}elseif ($it == "135"){$img = "chip";$tit="Layer 2 Virtual LAN using 802.1Q";
	}elseif ($it == "209"){$img = "netg";$tit="Transparent bridge interface";
	}else{$img = "qg";$tit="Other-$it";}

	return array("$img.png",$tit);
}

//===================================================================
// Generate location string for DB query
function TopoLoc($reg="",$cty="",$bld=""){

	global $locsep;
	$l = "";
	if($reg or $cty or $bld){								# Any sub locations?
		$l .= "^$reg$locsep";								# Start at region level
		$l .= ($cty)?"$cty$locsep":"";							# Append city if set
		$l .= ($bld)?"$bld$locsep":"";							# Append building if set
	}
	return $l;
}

//===================================================================
// Find best map using a nice recursive function
function TopoMap($reg="",$cty=""){
	if($reg){
		if($cty){
			if (file_exists("log/map-$reg-$cty.png")) {
				return "map-$reg-$cty.png";
			}else{
				return TopoMap($reg);
			}
		}else{
			if (file_exists("log/map-$reg.png")) {
				return "map-$reg.png";
			}
		}
	}
	return "map-top.png";
}

?>
