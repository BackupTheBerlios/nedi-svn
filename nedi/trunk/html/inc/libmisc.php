<?PHP
//===================================================================
// Miscellaneous functions
//===================================================================
// Some defaults 

$tabtag = "cellspacing=1 cellpadding=6 border=0 width=100%";
$lang	= 'eng';

$bga	= "D0D0D0";
$bgb	= "C0C0C0";
$bia	= "F0F0F0";
$bib	= "E6E6E6";

//===================================================================
// Read configuration
function ReadConf($group) {

	if (file_exists('/etc/nedi.conf')) {
		$conf = file('/etc/nedi.conf');
	}elseif (file_exists('/var/nedi/nedi.conf')) {
		$conf = file('/var/nedi/nedi.conf');
	}else{
		echo "Dude, where's nedi.conf?";
		die;
	}
	global $timeout,$ignoredvlans,$locsep,$locformat,$lang,$guiauth,$redbuild,$disc,$modgroup,$pause;
	global $mod,$backend,$dbpath,$dbhost,$dbname,$dbuser,$dbpass,$retire;
	global $rrdstep;

	foreach ($conf as $cl) {
		if ( !preg_match("/^#|^$/",$cl) ){
			$l = rtrim($cl);
			$v =  preg_split('/\t+/',$l);

			if ($v[0] == "module"){
				$v[4] = isset($v[4]) ? $v[4] : "usr";
				$modgroup["$v[1]-$v[2]"] = $v[4];
				if( strpos($group,$v[4]) !== false){
					$mod[$v[1]][$v[2]] = $v[3];
				}
			}
			elseif ($v[0] == "backend")	{$backend = $v[1];}
			elseif ($v[0] == "dbpath")	{$dbpath = $v[1];}
			elseif ($v[0] == "dbhost")	{$dbhost = $v[1];}
			elseif ($v[0] == "dbname")	{$dbname = $v[1];}
			elseif ($v[0] == "dbuser")	{$dbuser = $v[1];}
			elseif ($v[0] == "dbpass")	{$dbpass = $v[1];}

			elseif ($v[0] == "pause")	{$pause = $v[1];}
			elseif ($v[0] == "ignoredvlans"){$ignoredvlans = $v[1];}
			elseif ($v[0] == "retire")	{$retire = $v[1];}
			elseif ($v[0] == "timeout")	{$timeout = $v[1];}

			elseif ($v[0] == "rrdstep")	{$rrdstep = $v[1];}

			elseif ($v[0] == "locsep")	{$locsep = $v[1];}
			elseif ($v[0] == "locformat")	{$locformat = $v[1];}
			elseif ($v[0] == "guiauth")	{$guiauth = $v[1];}
			elseif ($v[0] == "redbuild")	{$redbuild = $v[1];}
			elseif ($v[0] == "disc")	{$disc = $v[1];}
		}
	}
}

//===================================================================
// Sanitize parameters
function sanitize( $arr ){
    if ( is_array( $arr ) ) {
        return array_map( 'sanitize', $arr );
    }
    return trim( preg_replace( "/\.\.|\"/","", $arr ) );
}

//===================================================================
// Return IP address from hex value
function hex2ip($hip) {
	return  hexdec(substr($hip, 0, 2)).".".hexdec(substr($hip, 2, 2)).".".hexdec(substr($hip, 4, 2)).".".hexdec(substr($hip, 6, 2));
}

//===================================================================
// Return from IP address as hex
function ip2hex($ip) {
	$i =  split('\.', str_replace( "*", "", $ip ) );
	return  sprintf("%02x%02x%02x%02x",$i[0],$i[1],$i[2],$i[3]);
}

//===================================================================
// Return IP address as bin
function ip2bin($ip) {
	$i	=  split('\.',$ip);
	return sprintf(".%08b.%08b.%08b.%08b",$i[0],$i[1],$i[2],$i[3]);
}

//===================================================================
// Invert IP address
function ipinv($ip) {
	$i	=  split('\.',$ip);
	return (255-$i[0]).".".(255-$i[1]).".".(255-$i[2]).".".(255-$i[3]);
}

//===================================================================
// convert netmask to various formats and check whether it's valid.
function Masker($nm) {

	if(preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/",$nm) ){
		$mask = $nm;
		list($n1,$n2,$n3,$n4) = split("\.", $nm);
		$bits = str_pad(decbin($n1) . decbin($n2) . decbin($n3) . decbin($n4),32,0);
		$nbit = count_chars($bits);
		$pfix = $nbit[49];										// the 49th char is "1"...
	}elseif(preg_match("/^[-]|\d{3,10}$/",$nm ) ){
		$nm   = sprintf("%u",$nm);
		$mask = long2ip($nm);
		$bits = base_convert($nm, 10, 2);
		$nbit = count_chars($bits);
		$pfix = $nbit[49];
	}elseif(preg_match("/^\d{1,2}$/",$nm) ){
		$bits = base_convert(sprintf("%u",0xffffffff << (32 - $nm) ),10,2);
		$mask = bindec(substr($bits, 0,8)).".".bindec(substr($bits, 8,8)).".".bindec(substr($bits, 16,8)).".".bindec(substr($bits, 24,8));
		$pfix = $nm;
	}
	$bin	= preg_replace( "/(\d{8})/", ".\$1", $bits );
	if(preg_match("/01/",$bits) ){
		return array($nm,'Illegal Mask',$bin);
	}else{
		return array($pfix,$mask,$bin);	
	}
}

//===================================================================
// Replace 0s with appropriate prefix
function ZFix($spd) {

	$spd	= preg_replace("/000000000$/","G",$spd);
	$spd	= preg_replace("/000000$/","M",$spd);
	$spd	= preg_replace("/000$/","K",$spd);

	return $spd;
}

//===================================================================
// Colorize html bg according to timestamps
function Agecol($fs, $ls,$row) {

        $o = 120 + 20 * $row;
		if(!$ls){$ls = $fs;}
        $now = time();
		global $retire;

        $tmpf = intval(100 - 100 * ($now - $fs) / ($retire * 86400));
        if ($tmpf < 0){$tmpf = 0;}

        $tmpl = intval(100 * ($now - $ls) / ($retire * 86400));
        if ($tmpl > 100){$tmpl = 100;}

        $tmpd = intval(100 * ($ls  - $fs) / ($retire * 86400));
        if ($tmpd > 100){$tmpd = 100;}

        $f = sprintf("%02x",$tmpf + $o);
        $l = sprintf("%02x",$tmpl + $o);
        $d = sprintf("%02x",$tmpd + $o);
        $g = sprintf("%02x",$o);

        return array ("$g$f$d","$l$g$d");
}

//===================================================================
// Generate column headers with sorting
function ColHead($n){

global $ord,$cols;

	if (!$ord){
		echo "<th>$cols[$n]<a href=?$_SERVER[QUERY_STRING]&ord=$n><img src=img/dwn.png border=0 title=\"Sort by $n\"></a></th>";
	}elseif($n == $ord){
		echo "<th class=blu>$cols[$n] <a href=?";
		echo preg_replace('/&ord=(.*)/',"&ord=$n+desc",$_SERVER['QUERY_STRING']);
		echo "><img src=img/up.png border=0 title=\"Reverse sort by $n\"></a></th>";
	}elseif("$n desc" == $ord){
		echo "<th class=blu>$cols[$n] <a href=?";
		echo preg_replace('/&ord=(.*)/',"&ord=$n",$_SERVER['QUERY_STRING']);
		echo "><img src=img/dwn.png border=0 title=\"Sort by $n\"></a></th>";
	}else{
		echo "<th>$cols[$n] <a href=?";
		echo preg_replace('/&ord=(.*)/',"&ord=$n",$_SERVER['QUERY_STRING']);
		echo "><img src=img/dwn.png border=0 title=\"Sort by $n\"></a></th>";
	}
}

//===================================================================
// Generate html select box
function selectbox($type,$sel="") {

	if($type == "oper"){
		$options = array("regexp"=>"regexp","not regexp"=>"not regexp",">"=>">","="=>"=","!="=>"!=",">="=>">=","<"=>"<");
	}elseif($type == "comop"){
		$options = array(""=>"A only","AND"=>"A and B","OR"=>"A or B",">"=>"colA > colB","="=>"colA = colB","<"=>"colA < colB");
	}elseif($type == "limit"){
		$options = array("10"=>"10","20"=>"20","50"=>"50","100"=>"100","500"=>"500","0"=>"none!");
	}
	foreach ($options as $key => $txt){
	       $selopt = ($sel == "$key")?"selected":"";
	       echo "<option value=\"$key\" $selopt >$txt\n";
	}
}

//===================================================================
// Generate coloured bar for html
function Bar($length=1,$tresh=0) {
	if($tresh > 0){
		if($length < $tresh){
			$img = "grn";
		}elseif($length < 2 * $tresh){
			$img = "org";
		}else{
			$img = "red";
		}
	}elseif($tresh < 0){
		if($length < -$tresh/2){
			$img = "red";
		}elseif($length < -$tresh){
			$img = "org";
		}else{
			$img = "grn";
		}
	}else{
		$img = "blu";
	}
	if($length > 100000){
		$length = intval($length / 10000 - 10);	
		return "<img src=img/$img.png width=400 height=20 hspace=2 border=1 title=\">100000\"><img src=img/$img.png width=$length height=20 hspace=2 border=1>";
	}elseif($length > 10000){
		$length = intval($length / 1000 - 10);	
		return "<img src=img/$img.png width=300 height=20 hspace=2 border=1 title=\">10000\"><img src=img/$img.png width=$length height=20 hspace=2 border=1>";
	}elseif($length > 1000){
		$length = intval($length / 100 - 10);	
		return "<img src=img/$img.png width=200 height=20 hspace=2 border=1 title=\">1000\"><img src=img/$img.png width=$length height=20 hspace=2 border=1>";
	}elseif($length > 100){
		$length = intval($length / 10 - 10);		
		return "<img src=img/$img.png width=100 height=20 hspace=2 border=1 title=\">100\" ><img src=img/$img.png width=$length height=20 hspace=2 border=1>";
	}else{
		$length = intval($length);
		return "<img src=img/$img.png width=$length height=20 hspace=2 border=1>";
	}
}

//===================================================================
// Return network type
function Nettype($nt) {

	if ($nt == "0.0.0.0"){$img = "bup";$tit="Default";
	}elseif (preg_match("/^127\.0\.0/",$nt)){$img = "brld";$tit="LocalLoopback";
	}elseif (preg_match("/^10\./",$nt)){$img = "bcls";$tit="Private-10/8";
	}elseif (preg_match("/^192\.168/",$nt)){$img = "err";$tit="Private-192.168/16";
	}elseif (preg_match("/^172\.[1][6-9]/",$nt)){$img = "bcls";$tit="Private-172.16/12";
	}elseif (preg_match("/^172\.[2][0-9]/",$nt)){$img = "bcls";$tit="Private-172.16/12";
	}elseif (preg_match("/^172\.[3][0-1]/",$nt)){$img = "bcls";$tit="Private-172.16/12";
	}else{$img = "brgt";$tit="Public";}
	
	return array("$img.png",$tit);
}

//===================================================================
// Return Smilie based on name
function Smilie($n) {
	
		$si = ord(substr(strtolower($n), 0, 1)) + ord(substr(strtolower($n), 1, 1)) + ord(substr(strtolower($n), 2, 1)) - 291;
		if($si < 1 or $si > 70){$si = "36";}
		return "img/smiles/$si.png";
}

//===================================================================
// Name: CreateArchive()
// 
// Description: Creates an archive out of one ore more existing files.
//              You can have either a .tar, .gz or a .bz2 archive.
//              If you want, you can have the creation time included
//              in the file name of the archive.
//
// Parameters:
//     $outfile	- Name of the archive to create (without file extension)
//     $type	- The type of compression. Accepts "gz", "bz2" or "tar" (for
//          	  a simple .tar archive).
//     $infiles	- If it's only one file, this can be a string. For more files,
//             	  you can use an array.
//     $timest	- If you wish to have a timestamp in your archive's file name,
//            	  you can set this parameter to the value 1.
//
// Return value:
//     The complete file name of the created archive (including its file extension)
//
function CreateArchive($outfile, $type, $infiles, $timest) {

	// This is used to create .tar archives
	// It is contained in the PEAR package Archive_Tar
	include_once("Archive/Tar.php");

	// Multiple files cannot be provided in plain format.
	// Therefore they are packed in a tar archive.
	if(is_array($infiles) && ($type == "plain")) {
		$type = "tar";
	}

	// There may already be an archive for the current user
	// saved in the ./html/log directory. This file is deleted
	// to ensure that there can only be one archive with the same
	// archive name.
	$glob = glob($outfile."*");
	if(count($glob) > 0) {
		foreach(glob($outfile."*") as $file) {
			unlink($file);
		}
	}

	$tarname = $outfile;

	// If the user wishes to have the creation time in the archive's file name.
	// it gets added here
	if($timest == 1) {
		$tarname .= "_".date("Ymd_Hi");
	}

	if($type != "plain") {
		$tarname .= ".tar";
	
		// Now a new Archive_Tar object is created
		// This object is used to create the .tar archive
		$tar = new Archive_Tar($tarname);
	
		// If $infile is only a string containing one single file name,
		// this string is put into an array. If there are more than one
		// input files, we already have an array and thus don't need to
		// create a new one.
		if(is_array($infiles)) {
			$tar->create($infiles); // This creates the .tar archive
		}
		else {
			$tar->create(array($infiles)); // This creates the .tar archive
		}
	}
	else {
		if(stristr($infiles, ".csv") != false) {
			$tarname .= ".csv";
		}
		elseif(stristr($infiles, ".sql") != false) {
			$tarname .= ".sql";
		}
		
		copy($infiles, $tarname);
	}
	
	// Depending on the parameter $type the archive gets compressed
	// If $type is empty or an invalid value, the .tar archive stays
	// unchanged
	switch($type) {
		case "gz":
			// The previously created .tar archive is opened for reading
			$archive = fopen($tarname, "r");
			
			// This is the new gzip archive that is going to be created
			$gzip = gzopen("$tarname.gz", "w");

			// The size of the .tar archive is counted and the number of
			// 2 MB blocks is counted
			$mb = ceil(filesize($tarname) / (1024*1024*2));
			
			// The .tar archive is split into $mb parts and these parts are
			// read and written to the gzip archive one after the other
			for($i=0; $i<$mb; $i++) {
				gzwrite($gzip, fread($archive, filesize($tarname)/$mb));
			}

			// Both archives, the .tar archive and the new gzip archive are closed
			gzclose($gzip);
			fclose($archive);

			// The .tar archive must be deleted manually
			unlink($tarname);
			
			// The name of the gzip file is returned, so the user does not have
			// to think about file extensions when calling this function
			return $tarname.".gz";
			break;
		case "bz2":
			// The previously created .tar archive is opened for reading
			$archive = fopen($tarname, "r");
			
			// This is the new bzip2 archive that is going to be created
			$bzip2 = bzopen("$tarname.bz2", "w");

			// The size of the .tar archive is counted and the number of
			// 2 MB blocks is counted
			$mb = ceil(filesize($tarname) / (1024*1024*5));
			
			// The .tar archive is split into $mb parts and these parts are
			// read and written to the bzip2 archive one after the other
			for($i=0; $i<$mb; $i++) {
				bzwrite($bzip2, fread($archive, filesize($tarname)/$mb));
			}

			// Both archives, the .tar archive and the new bzip2 archive are closed
			bzclose($bzip2);
			fclose($archive);

			// The .tar archive must be deleted manually
			unlink($tarname);
			
			// The name of the bzip2 file is returned, so the user does not have
			// to think about file extensions when calling this function
			return $tarname.".bz2";
			break;
		case "tar":
		case "plain":
		default:
			// In any other case the .tar file is left unchanged and its file name is returned
			return $tarname;
	}
}
?>
