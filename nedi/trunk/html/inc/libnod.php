<?
//===============================
// Node related functions.
//===============================

//===================================================================
// Assign an icon to a node.
function Nimg($m) {

	if     (stristr($m,"APPLE"))				{$i = "a27";}
	elseif (stristr($m,"000d93"))				{$i = "a93";}
	elseif (stristr($m,"000a95"))				{$i = "a95";}
	elseif (stristr($m,"ACCTON"))				{$i = "acc";}
	elseif (stristr($m,"ACER"))				{$i = "acr";}
	elseif (stristr($m,"ADVANTECH"))			{$i = "adv";}
	elseif (stristr($m,"ADAPTEC"))				{$i = "adt";}
	elseif (stristr($m,"ADVANCED TECHNOLOGY &"))		{$i = "adtx";}
	elseif (stristr($m,"AGILENT"))				{$i = "agi";}
	elseif (stristr($m,"AMBIT"))				{$i = "amb";}
	elseif (stristr($m,"ACTIONTEC"))			{$i = "atec";}
	elseif (stristr($m,"ALLEN BRAD"))			{$i = "ab";}
	elseif (stristr($m,"ASUS"))				{$i = "asu";}
	elseif (stristr($m,"AXIS"))				{$i = "axis";}
	elseif (stristr($m,"BECKHOFF"))				{$i = "bek";}
	elseif (stristr($m,"BROADCOM"))				{$i = "bcm";}
	elseif (stristr($m,"BROCADE"))				{$i = "brc";}
	elseif (stristr($m,"EMULEX"))				{$i = "emx";}
	elseif (stristr($m,"ENTRADA"))				{$i = "ent";}
	elseif (stristr($m,"EPSON"))				{$i = "eps";}
	elseif (stristr($m,"FIRST INTERNAT"))			{$i = "fic";}
	elseif (stristr($m,"INTERGRAPH"))			{$i = "igr";}
	elseif (stristr($m,"KINGSTON"))				{$i = "ktc";}
	elseif (stristr($m,"KYOCERA"))				{$i = "kyo";}
	elseif (stristr($m,"LEXMARK"))				{$i = "lex";}
	elseif (stristr($m,"CANON"))				{$i = "can";}
	elseif (stristr($m,"COMPAQ"))				{$i = "q";}
	elseif (stristr($m,"COMPAL"))				{$i = "cpl";}
	elseif (stristr($m,"DELL"))				{$i = "de";}
	elseif (stristr($m,"D-LINK"))				{$i = "dli";}
	elseif (stristr($m,"DIGITAL EQUIPMENT"))		{$i = "dec";}
	elseif (stristr($m,"FUJITSU"))				{$i = "fs";}
	elseif (stristr($m,"GIGA-BYTE"))			{$i = "gig";}
	elseif (stristr($m,"HEWLETT"))				{$i = "hp";}
	elseif (stristr($m,"IBM"))				{$i = "ibm";}
	elseif (stristr($m,"INTERFLEX"))			{$i = "intr";}
	elseif (stristr($m,"INTEL"))				{$i = "int";}
	elseif (stristr($m,"IWILL"))				{$i = "iwi";}
	elseif (stristr($m,"MINOLTA"))				{$i = "min";}
	elseif (stristr($m,"LINKSYS"))				{$i = "lsy";}
	elseif (stristr($m,"MICRO-STAR"))			{$i = "msi";}
	elseif (stristr($m,"LANTRONIX"))			{$i = "ltx";}
	elseif (stristr($m,"LANCOM"))				{$i = "lac";}
	elseif (stristr($m,"MOTOROLA"))				{$i = "mot";}
	elseif (stristr($m,"NATIONAL INSTRUMENTS"))		{$i = "ni";}
	elseif (stristr($m,"NETWORK COMP"))			{$i = "ncd";}
	elseif (stristr($m,"NETGEAR"))				{$i = "ngr";}
	elseif (stristr($m,"NEXT"))				{$i = "nxt";}
	elseif (stristr($m,"NOKIA"))				{$i = "nok";}
	elseif (stristr($m,"OVERLAND"))				{$i = "ovl";}
	elseif (stristr($m,"PLANET"))				{$i = "pla";}
	elseif (stristr($m,"PAUL SCHERRER"))			{$i = "psi";}
	elseif (stristr($m,"POLYCOM"))				{$i = "ply";}
	elseif (stristr($m,"QUANTA"))				{$i = "qnt";}
	elseif (stristr($m,"RARITAN"))				{$i = "rar";}
	elseif (stristr($m,"RAD DATA"))				{$i = "rad";}
	elseif (stristr($m,"REALTEK"))				{$i = "rtk";}
	elseif (stristr($m,"RICOH"))				{$i = "rco";}
	elseif (stristr($m,"RUBY TECH"))			{$i = "rub";}
	elseif (stristr($m,"SAMSUNG"))				{$i = "sam";}
	elseif (stristr($m,"SILICON GRAPHICS"))			{$i = "sgi";}
	elseif (stristr($m,"SHIVA"))				{$i = "sva";}
	elseif (stristr($m,"SIEMENS AG"))			{$i = "si";}
	elseif (stristr($m,"SNOM"))				{$i = "snom";}
	elseif (stristr($m,"SONY"))				{$i = "sony";}
	elseif (stristr($m,"STRATUS"))				{$i = "sts";}
	elseif (stristr($m,"SUN MICROSYSTEMS"))			{$i = "sun";}
	elseif (stristr($m,"SUPERMICRO"))			{$i = "sum";}
	elseif (stristr($m,"HUGHES"))				{$i = "wsw";}
	elseif (stristr($m,"FOUNDRY"))				{$i = "fdry";}
	elseif (stristr($m,"NUCLEAR"))				{$i = "atom";}
	elseif (stristr($m,"TOSHIBA"))				{$i = "tsa";}
	elseif (stristr($m,"TEKTRONIX"))			{$i = "tek";}
	elseif (stristr($m,"TYAN"))				{$i = "tya";}
	elseif (stristr($m,"VMWARE"))				{$i = "vm";}
	elseif (stristr($m,"WESTERN"))				{$i = "wdc";}
	elseif (stristr($m,"WISTRON"))				{$i = "wis";}
	elseif (stristr($m,"XYLAN"))				{$i = "xylan";}
	elseif (stristr($m,"XEROX"))				{$i = "xrx";}
	elseif (preg_match("/3\s*COM|MEGAHERTZ/i",$m))		{$i = "3com";}
	elseif (preg_match("/AIRONET|CISCO/i",$m))		{$i = "cis";}
	elseif (preg_match("/AVAYA|LANNET/i",$m))		{$i = "ava";}
	elseif (preg_match("/BAY|NORTEL|NETICS|XYLOGICS/i",$m))	{$i = "nort";}
	elseif (preg_match("/SMC Net|STANDARD MICROSYS/i",$m))	{$i = "smc";}
	else							{$i = "gen";}
	return "$i.png";
}

//===================================================================
// Emulate good old nbtstat on port 137
function NbtStat($ip) {

	$nbts	= pack('C50',129,98,00,00,00,01,00,00,00,00,00,00,32,67,75,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,65,00,00,33,00,01);
	$fp		= @fsockopen("udp://$ip", 137, $errno, $errstr);
	if (!$fp) {
		return "ERROR! $errno $errstr";
	}else {
		fwrite($fp, "$nbts");
		stream_set_timeout($fp, 0, 1000000 );
		$data =  fread($fp, 400);
		fclose($fp);

		if (preg_match("/AAAAAAAAAA/",$data) ){
			$nna = unpack('cnam',substr($data,56,1));  							# Get number of names
			$out = substr($data,57);                							# get rid of WINS header

			for ($i = 0; $i < $nna['nam'];$i++){
				$nam = preg_replace("/ +/","",substr($out,18*$i,15));
				$id = unpack('cid',substr($out,18*$i+15,1));
				$fl = unpack('cfl',substr($out,18*$i+16,1));
				$na = "";
				$gr = "";
				$co = "";
				if ($fl['fl'] > 0){
					if ($id['id'] == "3"){
						if ($na == ""){
							$na = $nam;
						}else{
							$co = $nam;
						}
					}
				}else{
					if ($na == ""){
						$gr = $nam;
					}
				}
			}
			return "<img src=img/16/bchk.png hspace=20> $na $gr $co";
		}else{
			return "<img src=img/16/bstp.png hspace=20> No response";
		}
	}
}

//===================================================================
// Check for open port and return server information, if possible.
function CheckTCP ($ip, $p,$d){

	if ($ip == "0.0.0.0") {
		return "<img src=img/16/bcls.png hspace=20> No IP!";
	}else{
		$fp = @fsockopen($ip, $p, $errno, $errstr, 1 );

		flush();
		if (!$fp) {
			return "<img src=img/16/bstp.png hspace=20> $errstr";
		} else {
			fwrite($fp,$d);
			stream_set_timeout($fp, 0, 100000 );
			$ans = fread($fp, 255);
			$ans .= fread($fp, 255);
			fclose($fp);
			if( preg_match("/<address>(.*)<\/address>/i",$ans,$mstr) ){
				return "<img src=img/16/bchk.png hspace=20> " . $mstr[1];
			}elseif( preg_match("/Server:(.*)/i",$ans,$mstr) ){
				return "<img src=img/16/bchk.png hspace=20> " . $mstr[1];
			}elseif( preg_match("/CONTENT=\"(.*)\">/i",$ans,$mstr) ){
				return "<img src=img/16/bchk.png hspace=20> " . $mstr[1];
			}else{
				$mstr = preg_replace("/[^\x20-\x7e]|<!|!>/",'',$ans);
				return "<img src=img/16/bchk.png hspace=20> $mstr";
			}
		}
	}
}

//===================================================================
// Create and send magic packet (copied from the PHP webiste)
function wake($ip, $mac, $port){
	$nic = fsockopen("udp://" . $ip, $port);
	if($nic){
		$packet = "";
		for($i = 0; $i < 6; $i++)
			$packet .= chr(0xFF);
		for($j = 0; $j < 16; $j++){
			for($k = 0; $k < 6; $k++){
				$str = substr($mac, $k * 2, 2);
				$dec = hexdec($str);
				$packet .= chr($dec);
			}
		}
		$ret = fwrite($nic, $packet);
		fclose($nic);
		if($ret)
			return true;
	}
	return false;
} 
?>