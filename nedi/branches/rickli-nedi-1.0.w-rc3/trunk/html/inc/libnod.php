<?
//===============================
// Node related functions.
//===============================

//===================================================================
// Assign an icon to a node.
function Nimg($txt) {

	if     (preg_match("/3\s*com|MEGAHERTZ/i",$txt))		{$icon = "3com";}
	elseif (preg_match("/ALLEN.BRAD/i",$txt))			{$icon = "ab";}
	elseif (preg_match("/ACCTON/i",$txt))				{$icon = "acc";}
	elseif (preg_match("/ACER/i",$txt))				{$icon = "acr";}
	elseif (preg_match("/Advantech/i",$txt))			{$icon = "adv";}
	elseif (preg_match("/ADAPTEC/i",$txt))				{$icon = "adt";}
	elseif (preg_match("/Agilent/i",$txt))				{$icon = "agi";}
	elseif (preg_match("/AMBIT/i",$txt))				{$icon = "amb";}
	elseif (preg_match("/000d93/i",$txt))				{$icon = "a93";}
	elseif (preg_match("/000a95/i",$txt))				{$icon = "a95";}
	elseif (preg_match("/apple/i",$txt))				{$icon = "a27";}
	elseif (preg_match("/ACTIONTEC /i",$txt))			{$icon = "atec";}
	elseif (preg_match("/ASUS/i",$txt))				{$icon = "asu";}
	elseif (preg_match("/AXIS/i",$txt))				{$icon = "axis";}
	elseif (preg_match("/AVAYA|LANNET/i",$txt))			{$icon = "ava";}
	elseif (preg_match("/BAY|Nortel|NetICs|XYLOGICS/i",$txt))	{$icon = "nort";}
	elseif (preg_match("/BROADCOM/i",$txt))				{$icon = "bcm";}
	elseif (preg_match("/BROCADE/i",$txt))				{$icon = "brc";}
	elseif (preg_match("/EMULEX/i",$txt))				{$icon = "emx";}
	elseif (preg_match("/ENTRADA/i",$txt))				{$icon = "ent";}
	elseif (preg_match("/FIRST INTERNAT/i",$txt))			{$icon = "fic";}
	elseif (preg_match("/INTERGRAPH/i",$txt))			{$icon = "igr";}
	elseif (preg_match("/KINGSTON/i",$txt))				{$icon = "ktc";}
	elseif (preg_match("/KYOCERA/i",$txt))				{$icon = "kyo";}
	elseif (preg_match("/LEXMARK/i",$txt))				{$icon = "lex";}
	elseif (preg_match("/Aironet|CISCO/i",$txt))			{$icon = "cis";}
	elseif (preg_match("/CANON/i",$txt))				{$icon = "can";}
	elseif (preg_match("/COMPAQ/i",$txt))				{$icon = "q";}
	elseif (preg_match("/COMPAL/i",$txt))				{$icon = "cpl";}
	elseif (preg_match("/DELL/i",$txt))				{$icon = "de";}
	elseif (preg_match("/D-LINK/i",$txt))				{$icon = "dli";}
	elseif (preg_match("/DIGITAL EQUIPMENT/i",$txt))		{$icon = "dec";}
	elseif (preg_match("/Fujitsu/i",$txt))				{$icon = "fs";}
	elseif (preg_match("/GIGA-BYTE/i",$txt))			{$icon = "gig";}
	elseif (preg_match("/HEWLETT/i",$txt))				{$icon = "hp";}
	elseif (preg_match("/IBM/i",$txt))				{$icon = "ibm";}
	elseif (preg_match("/IEEE/",$txt))				{$icon = "iee";}
	elseif (preg_match("/INTERFLEX/i",$txt))			{$icon = "intr";}
	elseif (preg_match("/INTEL/i",$txt))				{$icon = "int";}
	elseif (preg_match("/MINOLTA|IMAGING/i",$txt))			{$icon = "min";}
	elseif (preg_match("/LINKSYS/i",$txt))				{$icon = "lsy";}
	elseif (preg_match("/MICRO-STAR/i",$txt))			{$icon = "msi";}
	elseif (preg_match("/LANTRONIX/i",$txt))			{$icon = "ltx";}
	elseif (preg_match("/MOTOROLA/i",$txt))				{$icon = "mot";}
	elseif (preg_match("/NETWORK COMP/i",$txt))			{$icon = "ncd";}
	elseif (preg_match("/Netgear/",$txt))				{$icon = "ngr";}
	elseif (preg_match("/NEXT,/",$txt))				{$icon = "nxt";}
	elseif (preg_match("/Nokia,/",$txt))				{$icon = "nok";}
	elseif (preg_match("/OVERLAND/i",$txt))				{$icon = "ovl";}
	elseif (preg_match("/PLANET/i",$txt))				{$icon = "pla";}
	elseif (preg_match("/Paul Scherrer/i",$txt))			{$icon = "psi";}
	elseif (preg_match("/POLYCOM/i",$txt))				{$icon = "ply";}
	elseif (preg_match("/QUANTA/i",$txt))				{$icon = "qnt";}
	elseif (preg_match("/RAD DATA/i",$txt))				{$icon = "rad";}
	elseif (preg_match("/REALTEK/i",$txt))				{$icon = "rtk";}
	elseif (preg_match("/RICOH/i",$txt))				{$icon = "rco";}
	elseif (preg_match("/SILICON GRAPHICS/i",$txt))			{$icon = "sgi";}
	elseif (preg_match("/SHIVA/i",$txt))				{$icon = "sva";}
	elseif (preg_match("/Siemens AG/i",$txt))			{$icon = "si";}
	elseif (preg_match("/SNOM/",$txt))				{$icon = "snom";}
	elseif (preg_match("/SONY/i",$txt))				{$icon = "sony";}
	elseif (preg_match("/STRATUS/i",$txt))				{$icon = "sts";}
	elseif (preg_match("/SUN/i",$txt))				{$icon = "sun";}
	elseif (preg_match("/STANDARD MICROSYS/i",$txt))		{$icon = "smc";}
	elseif (preg_match("/HUGHES/i",$txt))				{$icon = "wsw";}
	elseif (preg_match("/FOUNDRY/i",$txt))				{$icon = "fdry";}
	elseif (preg_match("/NUCLEAR/i",$txt))				{$icon = "atom";}
	elseif (preg_match("/TOSHIBA/i",$txt))				{$icon = "tsa";}
	elseif (preg_match("/TEKTRONIX/i",$txt))			{$icon = "tek";}
	elseif (preg_match("/TYAN/i",$txt))				{$icon = "tya";}
	elseif (preg_match("/VMWARE/i",$txt))				{$icon = "vm";}
	elseif (preg_match("/WESTERN/i",$txt))				{$icon = "wdc";}
	elseif (preg_match("/XYLAN/i",$txt))				{$icon = "xylan";}
	elseif (preg_match("/XEROX/i",$txt))				{$icon = "xrx";}
	else								{$icon = "gen";}
	return "$icon.png";
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
				$mstr = preg_replace("/[^\x20-\x7e]/",'',$ans);
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